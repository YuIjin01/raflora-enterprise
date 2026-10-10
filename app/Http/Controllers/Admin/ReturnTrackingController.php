<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AssetReturn;
use App\Models\Booking;
use App\Models\ClientNotification;
use App\Models\ReturnItem;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;

class ReturnTrackingController extends Controller
{
    protected function collectHardwareItemsForBooking(Booking $booking)
    {
        return InventoryTransaction::with('inventoryItem')
            ->where('booking_id', $booking->id)
            ->whereIn('transaction_type', ['dispatch', 'dispatch_correction'])
            ->get()
            ->filter(fn ($tx) => $tx->inventoryItem && !$tx->inventoryItem->is_perishable)
            ->groupBy('inventory_item_id')
            ->filter(fn ($txs) => abs((float) $txs->sum('quantity_change')) > 0)
            ->map(fn ($txs) => $txs->first()->inventoryItem)
            ->values();
    }

    protected function dispatchedQuantitiesForBooking(Booking $booking): array
    {
        return InventoryTransaction::with('inventoryItem')
            ->where('booking_id', $booking->id)
            ->whereIn('transaction_type', ['dispatch', 'dispatch_correction'])
            ->get()
            ->filter(fn ($tx) => $tx->inventoryItem && !$tx->inventoryItem->is_perishable)
            ->groupBy('inventory_item_id')
            ->map(fn ($txs) => abs((float) $txs->sum('quantity_change')))
            ->filter(fn ($qty) => $qty > 0)
            ->all();
    }

    protected function initializeReturnForBooking(Booking $booking): ?AssetReturn
    {
        $hardwareItems = $this->collectHardwareItemsForBooking($booking);

        if ($hardwareItems->isEmpty()) {
            return null;
        }

        return DB::transaction(function () use ($booking, $hardwareItems) {
            $return = AssetReturn::create([
                'booking_id' => $booking->id,
                'status' => 'Pending',
                'total_damage_charge' => 0,
            ]);

            foreach ($hardwareItems as $bookingItem) {
                ReturnItem::create([
                    'return_id' => $return->id,
                    'inventory_item_id' => $bookingItem->id,
                    'quantity_returned' => 0,
                    'quantity_good' => 0,
                    'quantity_damaged' => 0,
                    'quantity_lost' => 0,
                    'condition' => 'pending',
                    'final_amount' => 0,
                    'damage_charge' => 0,
                ]);
            }

            return $return;
        });
    }

    public function index()
    {
        // Auto-initialize returns for bookings requiring return audit (including cancelled bookings with active dispatches)
        $pendingReturnBookings = Booking::where(function ($q) {
                $q->whereIn('status', ['event_completed', 'completed', 'pending_return', 'pending_resolution'])
                  ->orWhere(function ($q2) {
                      $q2->where('status', 'cancelled')
                         ->whereHas('inventoryTransactions', function ($tx) {
                             $tx->whereIn('transaction_type', ['dispatch', 'dispatch_correction']);
                         });
                  });
            })
            ->doesntHave('returns')
            ->get();

        foreach ($pendingReturnBookings as $booking) {
            $this->initializeReturnForBooking($booking);
        }

        $statusOrder = "CASE status WHEN 'Pending' THEN 1 WHEN 'Partially Returned' THEN 2 WHEN 'Completed' THEN 3 ELSE 4 END";
        $returns = AssetReturn::with(['booking.client', 'inspectedByUser'])
            ->orderByRaw($statusOrder)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.return-tracking', compact('returns'));
    }

    public function manage(Booking $booking)
    {
        $booking = Booking::with(['client', 'returns.returnItems.inventoryItem'])->findOrFail($booking->id);

        $hasDispatches = $booking->inventoryTransactions()
            ->whereIn('transaction_type', ['dispatch', 'dispatch_correction'])
            ->exists();
        $hasExistingReturn = $booking->returns()->exists();

        $isEligibleStatus = in_array($booking->status, ['event_completed', 'pending_return', 'pending_resolution', 'completed'], true);
        $isEligibleCancelled = ($booking->status === 'cancelled') && ($hasDispatches || $hasExistingReturn);

        if (!$isEligibleStatus && !$isEligibleCancelled) {
            return redirect()->route('admin.bookings')
                ->with('error', "Return audit cannot be opened for booking #{$booking->id} because it has not completed event execution or reached an eligible return stage.");
        }

        $return = $booking->returns()->with(['returnItems.inventoryItem'])->latest()->first();

        if (!$return) {
            $return = $this->initializeReturnForBooking($booking);

            if (!$return) {
                // Booking has no dispatched hardware; create an empty return record so zero-hardware audit can be closed
                $return = AssetReturn::create([
                    'booking_id' => $booking->id,
                    'status' => 'Pending',
                    'total_damage_charge' => 0,
                ]);
            } else {
                $return->load(['returnItems.inventoryItem']);
            }
        }

        $dispatchedQuantities = $this->dispatchedQuantitiesForBooking($booking);

        return view('admin.return-tracking-manage', compact('booking', 'return', 'dispatchedQuantities'));
    }

    public function show(AssetReturn $return)
    {
        $return->load(['booking.client', 'returnItems.inventoryItem', 'inspectedByUser']);
        $booking = $return->booking;
        
        $dispatchedQuantities = $this->dispatchedQuantitiesForBooking($booking);

        return view('admin.return-tracking-manage', compact('booking', 'return', 'dispatchedQuantities'));
    }

    public function update(Request $request, AssetReturn $return)
    {
        $booking = $return->booking ?? Booking::find($return->booking_id);
        if ($booking && ($booking->status === 'completed' || ($booking->status === 'cancelled' && $return->status === 'Completed'))) {
            return back()->with('error', 'This return audit is completed and archived. Completed return records cannot be modified.');
        }

        $validated = $request->validate([
            'items' => 'nullable|array',
            'items.*.quantity_returned' => 'nullable|numeric|min:0',
            'items.*.quantity_good' => 'nullable|numeric|min:0',
            'items.*.quantity_damaged' => 'nullable|numeric|min:0',
            'items.*.quantity_lost' => 'nullable|numeric|min:0',
            'items.*.condition' => 'nullable|string|in:good,damaged,lost,pending,mixed',
            'items.*.charge_decision' => 'nullable|string|in:charge,no_charge,pending,charge_client',
            'items.*.damage_charge' => 'nullable|numeric|min:0',
            'items.*.charge_reason' => 'nullable|string',
            'items.*.notes' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $items = $request->input('items', []);
        $validItemUpdates = [];

        foreach ($items as $itemId => $data) {
            if (!is_array($data)) {
                continue;
            }

            $hasCondition = array_key_exists('condition', $data);
            $hasQuantity = array_key_exists('quantity_returned', $data)
                || array_key_exists('quantity_good', $data)
                || array_key_exists('quantity_damaged', $data)
                || array_key_exists('quantity_lost', $data);
            $hasCharge = array_key_exists('damage_charge', $data) || array_key_exists('charge_decision', $data);

            if ($hasCondition || $hasQuantity || $hasCharge) {
                $validItemUpdates[$itemId] = $data;
            }
        }

        if (empty($validItemUpdates)) {
            // Check if this is an authorized zero-hardware return audit
            if ($return->returnItems->isEmpty() && empty($this->dispatchedQuantitiesForBooking($return->booking))) {
                DB::transaction(function () use ($request, $return) {
                    $return->update([
                        'status' => 'Completed',
                        'notes' => $request->notes,
                        'inspected_by' => auth()->id(),
                        'return_date' => now(),
                    ]);

                    $booking = Booking::with('payments')->find($return->booking_id);
                    if ($booking) {
                        if ($booking->status === 'cancelled') {
                            AuditLog::create([
                                'user_id' => auth()->id(),
                                'action' => 'return_completed',
                                'module' => 'return_tracking',
                                'details' => 'Zero-hardware return audit completed for cancelled booking #' . $booking->id . '. Cancellation status preserved.',
                                'entity_type' => Booking::class,
                                'entity_id' => $booking->id,
                            ]);
                        } else {
                            $targetStatus = ($booking->fresh()->remaining_balance <= 0) ? 'completed' : 'event_completed';
                            if ($booking->status !== $targetStatus) {
                                $booking->setNormalizedStatus($targetStatus)->save();

                                AuditLog::create([
                                    'user_id' => auth()->id(),
                                    'action' => 'return_audit_status_applied',
                                    'module' => 'booking',
                                    'details' => 'Booking status changed to ' . $booking->status_display_label . ' after zero-hardware return audit',
                                    'entity_type' => Booking::class,
                                    'entity_id' => $booking->id,
                                ]);
                            }
                        }
                    }
                });

                return redirect()->route('admin.return-tracking')->with('success', 'Zero-hardware return audit completed successfully.');
            }

            return back()->with('error', 'No return item updates were provided. Please adjust returned quantities, conditions, or damage charges before saving.');
        }

        $dispatchedQuantities = $this->dispatchedQuantitiesForBooking($return->booking);
        foreach ($validItemUpdates as $itemId => $data) {
            $returnItem = ReturnItem::where('return_id', $return->id)->whereKey($itemId)->first();
            if (!$returnItem) {
                return back()->with('error', 'The selected return item does not belong to this return record.');
            }

            $dispatchedQty = (float) ($dispatchedQuantities[$returnItem->inventory_item_id] ?? 0);

            if (isset($data['quantity_good']) || isset($data['quantity_damaged']) || isset($data['quantity_lost'])) {
                $qGood = (float) ($data['quantity_good'] ?? 0);
                $qDamaged = (float) ($data['quantity_damaged'] ?? 0);
                $qLost = (float) ($data['quantity_lost'] ?? 0);
                if ($qGood < 0 || $qDamaged < 0 || $qLost < 0) {
                    return back()->with('error', 'Return quantities cannot be negative.');
                }
                if (($qGood + $qDamaged + $qLost) > $dispatchedQty) {
                    return back()->with('error', 'Returned quantity cannot exceed the confirmed dispatched quantity for ' . ($returnItem->inventoryItem?->name ?? 'this item') . '.');
                }
            } else {
                $quantityReturned = (float) ($data['quantity_returned'] ?? 0);
                if ($quantityReturned < 0) {
                    return back()->with('error', 'Return quantities cannot be negative.');
                }
                if ($quantityReturned > $dispatchedQty) {
                    return back()->with('error', 'Returned quantity cannot exceed the confirmed dispatched quantity for ' . ($returnItem->inventoryItem?->name ?? 'this item') . '.');
                }
            }
        }

        $newStatus = 'Partially Returned';

        try {
            DB::transaction(function () use ($items, $validItemUpdates, $request, $return, $dispatchedQuantities, &$newStatus) {
                // Lock parent booking (if present) and return record first for consistent parent-first locking hierarchy
                $booking = $return->booking_id
                    ? Booking::with('payments')->whereKey($return->booking_id)->lockForUpdate()->first()
                    : null;
                $lockedReturn = AssetReturn::whereKey($return->id)->lockForUpdate()->firstOrFail();

                $totalDamageCharge = 0;
                $dispatchedTotal = 0;
                $accountedTotal = 0;
                $hasOutstandingItems = false;

                $allReturnItems = ReturnItem::where('return_id', $return->id)->get();

                // Normalize and pre-lock all inventory items involved in this return in deterministic ascending PK order
                $inventoryItemIds = $allReturnItems->pluck('inventory_item_id')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->sort()
                    ->values()
                    ->toArray();

                $lockedInventoryItems = !empty($inventoryItemIds)
                    ? InventoryItem::whereIn('id', $inventoryItemIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id')
                    : collect();

                foreach ($allReturnItems as $returnItem) {
                    $dispatchedQty = (float) ($dispatchedQuantities[$returnItem->inventory_item_id] ?? 0);
                    $dispatchedTotal += $dispatchedQty;

                    if (isset($validItemUpdates[$returnItem->id])) {
                        $data = $validItemUpdates[$returnItem->id];

                        if (isset($data['quantity_good']) || isset($data['quantity_damaged']) || isset($data['quantity_lost'])) {
                            $qGood = min($dispatchedQty, max(0.0, (float) ($data['quantity_good'] ?? 0)));
                            $qDamaged = min($dispatchedQty, max(0.0, (float) ($data['quantity_damaged'] ?? 0)));
                            $qLost = min($dispatchedQty, max(0.0, (float) ($data['quantity_lost'] ?? 0)));
                            // RET-AUD-001: quantity_returned strictly represents physically returned items
                            $quantityReturned = $qGood + $qDamaged;

                            $conditionsCount = ($qGood > 0 ? 1 : 0) + ($qDamaged > 0 ? 1 : 0) + ($qLost > 0 ? 1 : 0);
                            if ($conditionsCount > 1) {
                                $condition = 'mixed';
                            } elseif ($qDamaged > 0) {
                                $condition = 'damaged';
                            } elseif ($qLost > 0) {
                                $condition = 'lost';
                            } elseif ($qGood > 0) {
                                $condition = 'good';
                            } else {
                                $condition = strtolower((string) ($data['condition'] ?? 'pending'));
                            }
                        } else {
                            // Legacy single condition and quantity_returned
                            $condition = strtolower((string) ($data['condition'] ?? 'good'));
                            $rawQty = min($dispatchedQty, max(0.0, (float) ($data['quantity_returned'] ?? 0)));
                            if ($condition === 'good') {
                                $qGood = $rawQty;
                                $qDamaged = 0.0;
                                $qLost = 0.0;
                                $quantityReturned = $rawQty;
                            } elseif ($condition === 'damaged') {
                                $qGood = 0.0;
                                $qDamaged = $rawQty;
                                $qLost = 0.0;
                                $quantityReturned = $rawQty;
                            } elseif ($condition === 'lost') {
                                $qGood = 0.0;
                                $qDamaged = 0.0;
                                $qLost = $rawQty;
                                $quantityReturned = 0.0; // Lost items are not physically returned
                            } else {
                                $qGood = 0.0;
                                $qDamaged = 0.0;
                                $qLost = 0.0;
                                $quantityReturned = 0.0;
                            }
                        }

                        $chargeDecision = $data['charge_decision'] ?? $returnItem->charge_decision ?? 'pending';
                        if ($chargeDecision === 'charge_client') {
                            $chargeDecision = 'charge';
                        }

                        $damageCharge = 0.0;
                        $chargeReason = null;

                        if ($qDamaged == 0 && $qLost == 0 && !in_array($condition, ['damaged', 'lost', 'mixed'], true)) {
                            // All good or none damaged/lost: no charge applies
                            $chargeDecision = 'no_charge';
                            $damageCharge = 0.0;
                            $chargeReason = null;
                        } else {
                            if ($chargeDecision === 'charge') {
                                $damageCharge = max(0.0, (float) ($data['damage_charge'] ?? $returnItem->damage_charge ?? 0));
                                $chargeReason = $data['charge_reason'] ?? $returnItem->charge_reason ?? null;
                            } elseif ($chargeDecision === 'no_charge') {
                                $damageCharge = 0.0;
                                $chargeReason = $data['charge_reason'] ?? $returnItem->charge_reason ?? null;
                            } else {
                                $damageCharge = 0.0;
                                $chargeReason = null;
                            }
                        }

                        // RET-AUD-002: good stock already credited back for this booking and item comes from
                        // the inventory ledger (return-tracking movements), not from the recorded counts.
                        // Staff record returned counts for Admin review without crediting inventory, so the
                        // counts alone cannot tell whether stock was restored.
                        $creditLedger = InventoryTransaction::where('booking_id', $return->booking_id)
                            ->where('inventory_item_id', $returnItem->inventory_item_id)
                            ->whereIn('transaction_type', ['return', 'damage']);

                        $isPreSplitLegacyRow = (float) $returnItem->quantity_good == 0.0
                            && (float) $returnItem->quantity_damaged == 0.0
                            && (float) $returnItem->quantity_lost == 0.0
                            && (float) $returnItem->quantity_returned > 0;

                        if ((clone $creditLedger)->exists()) {
                            $oldGood = (float) $creditLedger->sum('quantity_change');
                        } elseif ($isPreSplitLegacyRow) {
                            // Historical single-field record credited before ledger entries were recorded.
                            $oldGood = ($returnItem->condition === 'good') ? (float) $returnItem->quantity_returned : 0.0;
                        } else {
                            // Nothing credited yet (pending item, or counts recorded by Staff awaiting Admin review).
                            $oldGood = 0.0;
                        }
                        $newGood = $qGood;

                        $returnItem->update([
                            'quantity_returned' => $quantityReturned,
                            'quantity_good' => $qGood,
                            'quantity_damaged' => $qDamaged,
                            'quantity_lost' => $qLost,
                            'condition' => $condition,
                            'damage_charge' => $damageCharge,
                            'charge_decision' => $chargeDecision,
                            'charge_reason' => $chargeReason,
                            'charge_decision_by' => in_array($chargeDecision, ['charge', 'no_charge'], true) ? auth()->id() : null,
                            'charge_decision_at' => in_array($chargeDecision, ['charge', 'no_charge'], true) ? now() : null,
                            'notes' => $data['notes'] ?? $returnItem->notes,
                        ]);

                        if ($qDamaged > 0 || $qLost > 0 || in_array($condition, ['damaged', 'lost', 'mixed'], true)) {
                            AuditLog::create([
                                'user_id' => auth()->id(),
                                'action' => 'return_damage_assessed',
                                'module' => 'return_tracking',
                                'details' => "Item '{$returnItem->inventoryItem?->name}' condition assessed as '{$condition}' (good: {$qGood}, damaged: {$qDamaged}, lost: {$qLost}, decision: {$chargeDecision}, charge: ₱" . number_format($damageCharge, 2) . ") for booking #{$return->booking_id}",
                                'entity_type' => ReturnItem::class,
                                'entity_id' => $returnItem->id,
                            ]);
                        }

                        $totalDamageCharge += $damageCharge;

                        $itemAccounted = $qGood + $qDamaged + $qLost;
                        $accountedTotal += min($dispatchedQty, $itemAccounted);

                        if ($itemAccounted < $dispatchedQty) {
                            $hasOutstandingItems = true;
                        }

                        if (($qDamaged > 0 || $qLost > 0 || in_array($condition, ['lost', 'damaged', 'mixed'], true)) && $chargeDecision === 'pending') {
                            $hasOutstandingItems = true; // Still pending admin review
                        }

                        $stockDiff = $newGood - $oldGood;

                        if ($stockDiff != 0) {
                            $inventoryItem = $lockedInventoryItems->get($returnItem->inventory_item_id);
                            if ($inventoryItem) {
                                // RET-AUD-003: Prevent downward corrections from producing negative current_stock
                                if ($stockDiff < 0) {
                                    $availableStock = (float) $inventoryItem->current_stock;
                                    $requiredDecrement = abs($stockDiff);

                                    if ($availableStock < $requiredDecrement) {
                                        $unit = $inventoryItem->unit ?? 'piece';
                                        throw new \DomainException("Insufficient available inventory stock ({$availableStock} {$unit}) for '{$inventoryItem->name}' to process a downward return correction of {$requiredDecrement} {$unit}. Current stock cannot become negative.");
                                    }
                                }

                                $inventoryItem->current_stock = (float) $inventoryItem->current_stock + $stockDiff;
                                $inventoryItem->save();

                                InventoryTransaction::create([
                                    'inventory_item_id' => $inventoryItem->id,
                                    'booking_id' => $return->booking_id,
                                    'quantity_change' => $stockDiff,
                                    'transaction_type' => $stockDiff > 0 ? 'return' : 'damage',
                                    'reason' => 'Return tracking update: ' . $condition,
                                    'performed_by' => auth()->id() ?? 1,
                                ]);
                            }
                        }
                    } else {
                        // Item not in this update request
                        $existingAccounted = (float) $returnItem->accounted_quantity;
                        if ($existingAccounted == 0 && in_array($returnItem->condition, ['good', 'damaged', 'lost'], true)) {
                            $existingAccounted = (float) $returnItem->quantity_returned;
                        }
                        $accountedTotal += min($dispatchedQty, $existingAccounted);
                        $totalDamageCharge += (float) $returnItem->damage_charge;

                        if ($existingAccounted < $dispatchedQty) {
                            $hasOutstandingItems = true;
                        }

                        if (in_array($returnItem->condition, ['lost', 'damaged', 'mixed'], true) && $returnItem->charge_decision === 'pending') {
                            $hasOutstandingItems = true;
                        }
                    }
                }

                $newStatus = ($dispatchedTotal > 0 && $accountedTotal >= $dispatchedTotal && !$hasOutstandingItems)
                    ? 'Completed'
                    : 'Partially Returned';

                $return->update([
                    'status' => $newStatus,
                    'total_damage_charge' => $totalDamageCharge,
                    'notes' => $request->notes,
                    'inspected_by' => auth()->id(),
                    'return_date' => now(),
                ]);

                if ($booking) {
                    if ($booking->status === 'cancelled') {
                        // Decision C: Keep cancelled status for recovered cancelled booking
                        AuditLog::create([
                            'user_id' => auth()->id(),
                            'action' => 'cancelled_booking_return_reconciled',
                            'module' => 'return_tracking',
                            'details' => 'Return audit and inventory reconciliation updated for cancelled booking #' . $booking->id . '. Cancellation status preserved.',
                            'entity_type' => Booking::class,
                            'entity_id' => $booking->id,
                        ]);
                    } else {
                        $remainingBalance = $booking->fresh()->remaining_balance;

                        $hasPendingResolution = $booking->returns()
                            ->whereHas('returnItems', function ($q) {
                                $q->where('charge_decision', 'pending')
                                    ->where(function ($sub) {
                                        $sub->whereIn('condition', ['damaged', 'lost', 'mixed'])
                                            ->orWhere('quantity_damaged', '>', 0)
                                            ->orWhere('quantity_lost', '>', 0);
                                    });
                            })->exists();

                        if ($hasPendingResolution) {
                            $targetStatus = 'pending_resolution';
                        } elseif ($newStatus === 'Completed') {
                            $targetStatus = ($remainingBalance <= 0) ? 'completed' : 'event_completed';
                        } else {
                            $targetStatus = 'pending_return';
                        }

                        if ($booking->status !== $targetStatus) {
                            $booking->setNormalizedStatus($targetStatus)->save();

                            AuditLog::create([
                                'user_id' => auth()->id(),
                                'action' => 'return_audit_status_applied',
                                'module' => 'booking',
                                'details' => 'Booking status changed to ' . $booking->status_display_label . ' after return audit',
                                'entity_type' => Booking::class,
                                'entity_id' => $booking->id,
                            ]);

                            if ($targetStatus === 'completed') {
                                app(\App\Services\InventoryDispatchService::class)->releaseUnfulfilledReservations(
                                    $booking,
                                    auth()->id(),
                                    'Released unfulfilled reservation upon return completion for booking #' . $booking->id
                                );

                                // client_id references clients.id; notifications belong to the client's user account.
                                $clientUser = $booking->client && filter_var($booking->client->email, FILTER_VALIDATE_EMAIL)
                                    ? \App\Models\User::where('email', $booking->client->email)->first()
                                    : null;

                                if ($clientUser) {
                                    ClientNotification::create([
                                        'user_id' => $clientUser->id,
                                        'booking_id' => $booking->id,
                                        'type' => 'booking_update',
                                        'title' => 'Booking completed',
                                        'message' => 'Your return audit is complete and your booking has been marked as completed.',
                                        'is_read' => false,
                                    ]);
                                }
                            }
                        }
                    }
                }
            });
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('admin.return-tracking')->with('success', 'Return record updated successfully.');
    } 
}