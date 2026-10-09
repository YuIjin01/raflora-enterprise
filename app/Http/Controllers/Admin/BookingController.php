<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAlert;
use App\Models\Booking;
use App\Models\AssetReturn;
use App\Models\ClientNotification;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Payment;
use App\Models\ReturnItem;
use App\Models\BookingItem;
use App\Models\Presentation;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Mail\BookingStatusChanged;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $validStatuses = [
            'all',
            'approved',
            'pending',
            'quotation_sent',
            'admin_approved',
            'payment_pending',
            'payment_submitted',
            'fully_paid',
            'downpayment_received',
            'confirmed',
            'event_in_progress',
            'event_completed',
            'pending_return',
            'pending_resolution',
            'completed',
            'declined',
            'cancelled',
            'change_requested',
            'cancellation_requested',
        ];

        $rawStatus = (string) $request->query('status', 'all');
        $statusFilter = in_array($rawStatus, $validStatuses, true) ? $rawStatus : 'all';

        $searchTerm = trim((string) $request->query('search', ''));

        $eventTypeFilter = $request->query('event_type', 'all');
        $eventDateFrom = $request->query('event_date_from');
        $eventDateTo = $request->query('event_date_to');

        $availableEventTypes = Booking::select('event_type')
            ->whereNotNull('event_type')
            ->where('event_type', '!=', '')
            ->distinct()
            ->orderBy('event_type')
            ->pluck('event_type');

        $query = Booking::with(['client', 'payments', 'returns.returnItems']);

        // Status Filter
        if ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        // Event Type Filter
        if ($eventTypeFilter !== 'all' && !empty($eventTypeFilter)) {
            $query->where('event_type', $eventTypeFilter);
        }

        // Event Date Filter
        if (!empty($eventDateFrom)) {
            $query->whereDate('event_date', '>=', $eventDateFrom);
        }
        if (!empty($eventDateTo)) {
            $query->whereDate('event_date', '<=', $eventDateTo);
        }

        // Search Filter
        if ($searchTerm !== '') {
            $query->where(function ($q) use ($searchTerm) {
                if (is_numeric($searchTerm)) {
                    $q->orWhere('id', (int) $searchTerm);
                }
                $q->orWhere('event_type', 'like', "%{$searchTerm}%")
                  ->orWhere('venue', 'like', "%{$searchTerm}%")
                  ->orWhere('guest_name', 'like', "%{$searchTerm}%")
                  ->orWhere('guest_email', 'like', "%{$searchTerm}%")
                  ->orWhereHas('client', function ($cq) use ($searchTerm) {
                      $cq->where('full_name', 'like', "%{$searchTerm}%")
                         ->orWhere('email', 'like', "%{$searchTerm}%");
                  });
            });
        }

        $stageMap = [
            'request' => function($q) { /* Handled separately as it uses TemporaryGuestBooking */ },
            'review' => function($q) { $q->whereIn('status', ['pending', 'change_requested', 'cancellation_requested']); },
            'quotation' => function($q) { $q->whereIn('status', ['quotation_sent']); },
            'approval' => function($q) { $q->whereIn('status', ['approved', 'admin_approved']); },
            'payment' => function($q) { 
                $q->where(function ($query) {
                    $query->whereIn('status', ['payment_submitted', 'payment_pending'])
                          ->orWhereHas('payments', function ($pq) {
                              $pq->where('status', 'pending');
                          });
                });
            },
            'confirmed' => function($q) { $q->whereIn('status', ['downpayment_received', 'fully_paid', 'confirmed', 'event_in_progress', 'event_completed', 'pending_return', 'pending_resolution', 'completed']); },
            'cancelled' => function($q) { $q->whereIn('status', ['cancelled']); },
        ];

        $activeStage = $request->query('stage', 'all');

        $sort = $request->query('sort', 'newest');
        
        if ($activeStage === 'request') {
            $guestQuery = \App\Models\TemporaryGuestBooking::whereNull('claimed_at');
            
            if ($eventTypeFilter !== 'all' && !empty($eventTypeFilter)) {
                $guestQuery->where('event_type', $eventTypeFilter);
            }

            if (!empty($eventDateFrom)) {
                $guestQuery->whereDate('event_date', '>=', $eventDateFrom);
            }
            if (!empty($eventDateTo)) {
                $guestQuery->whereDate('event_date', '<=', $eventDateTo);
            }

            if (!empty($searchTerm)) {
                $guestQuery->where(function ($q) use ($searchTerm) {
                    $q->where('guest_name', 'like', "%{$searchTerm}%")
                      ->orWhere('guest_email', 'like', "%{$searchTerm}%")
                      ->orWhere('event_type', 'like', "%{$searchTerm}%")
                      ->orWhere('venue', 'like', "%{$searchTerm}%");
                });
            }

            switch ($sort) {
                case 'oldest':
                    $guestQuery->oldest();
                    break;
                case 'event_date_asc':
                    $guestQuery->orderBy('event_date', 'asc');
                    break;
                case 'event_date_desc':
                    $guestQuery->orderBy('event_date', 'desc');
                    break;
                case 'newest':
                default:
                    $guestQuery->latest();
                    break;
            }
            
            $bookings = $guestQuery->paginate(15)->withQueryString();
        } else {
            if ($activeStage !== 'all' && isset($stageMap[$activeStage])) {
                $stageMap[$activeStage]($query);
            }

            switch ($sort) {
                case 'oldest':
                    $query->oldest();
                    break;
                case 'event_date_asc':
                    $query->orderBy('event_date', 'asc');
                    break;
                case 'event_date_desc':
                    $query->orderBy('event_date', 'desc');
                    break;
                case 'newest':
                default:
                    $query->latest();
                    break;
            }

            $bookings = $query->paginate(15)->withQueryString();
        }

        $stageCounts = [
            'all' => Booking::count(),
            'request' => \App\Models\TemporaryGuestBooking::whereNull('claimed_at')->count(),
            'review' => Booking::whereIn('status', ['pending', 'change_requested', 'cancellation_requested'])->count(),
            'quotation' => Booking::whereIn('status', ['quotation_sent'])->count(),
            'approval' => Booking::whereIn('status', ['approved', 'admin_approved'])->count(),
            'payment' => Booking::where(function ($query) {
                $query->whereIn('status', ['payment_submitted', 'payment_pending'])
                      ->orWhereHas('payments', function ($q) {
                          $q->where('status', 'pending');
                      });
            })->count(),
            'confirmed' => Booking::whereIn('status', ['downpayment_received', 'fully_paid', 'confirmed', 'event_in_progress', 'event_completed', 'pending_return', 'pending_resolution', 'completed'])->count(),
            'cancelled' => Booking::whereIn('status', ['cancelled'])->count(),
        ];

        return view('admin.bookings', [
            'bookings' => $bookings,
            'statusFilter' => $statusFilter,
            'searchTerm' => $searchTerm,
            'sort' => $sort,
            'activeStage' => $activeStage,
            'stageCounts' => $stageCounts,
            'eventTypeFilter' => $eventTypeFilter,
            'eventDateFrom' => $eventDateFrom,
            'eventDateTo' => $eventDateTo,
            'availableEventTypes' => $availableEventTypes,
        ]);
    }

    protected function ensureReturnRecordForBooking(Booking $booking): void
    {
        if ($booking->returns()->exists()) {
            return;
        }
        // Build a list of booking items (including unmatched proposal lines)
        $bookingItems = $booking->bookingItems()->with('inventoryItem')->get();

        // Collect only hardware (non-perishable) items or items that appear to be hardware by keyword
        $hardwareItems = collect();
        foreach ($bookingItems as $bItem) {
            if ($bItem->inventoryItem) {
                if (!$bItem->inventoryItem->is_perishable) {
                    $hardwareItems->push($bItem->inventoryItem);
                }
                continue;
            }

            // No linked inventory item: attempt to infer from the item name
                $name = $bItem->item_name ?? '';
                $isPerishable = self::inferIsPerishableFromName($name);
            if (!$isPerishable) {
                // Create a lightweight inventory row so return_items can reference it
                $inv = InventoryItem::firstOrCreate([
                    'name' => $name,
                ], [
                    'category' => 'misc',
                    'is_perishable' => false,
                    'current_stock' => 0,
                    'unit_cost' => (float) ($bItem->quoted_unit_price ?? 0),
                    'min_stock' => 0,
                    'unit' => 'piece',
                ]);

                $hardwareItems->push($inv);
            }
        }

        if ($hardwareItems->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($booking, $hardwareItems) {
            $return = AssetReturn::create([
                'booking_id' => $booking->id,
                'status' => 'Pending',
                'total_damage_charge' => 0,
            ]);

            foreach ($hardwareItems as $item) {
                ReturnItem::create([
                    'return_id' => $return->id,
                    'inventory_item_id' => $item->id,
                    'quantity_returned' => 0,
                    'quantity_good' => 0,
                    'quantity_damaged' => 0,
                    'quantity_lost' => 0,
                    'condition' => 'pending',
                    'final_amount' => 0,
                    'damage_charge' => 0,
                ]);
            }
        });
    }

    public function show(Booking $booking): View
    {
        return $this->buildBookingReviewView($booking);
    }

    public function assignStaff(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'staff_id' => ['nullable', 'exists:users,id,role,staff'],
        ]);

        $oldStaffId = $booking->staff_id;
        $booking->staff_id = $validated['staff_id'] ?? null;
        $booking->save();

        if ((string) $oldStaffId !== (string) $booking->staff_id) {
            $this->logAuditEvent(
                'booking',
                'staff_assigned',
                $booking,
                $booking->staff_id ? 'Operational Staff assigned to booking' : 'Operational Staff assignment removed',
                Auth::id(),
                ['staff_id' => $oldStaffId],
                ['staff_id' => $booking->staff_id],
                'staff_assignment'
            );
        }

        return redirect()->back()->with('success', $booking->staff_id
            ? 'Staff member assigned to this event.'
            : 'Staff assignment removed from this event.');
    }

    public function reply(Request $request, Booking $booking, \App\Services\BookingAttachmentService $attachmentService): RedirectResponse
    {
        $rules = array_merge(
            [
                'message' => ['required', 'string', 'max:2000'],
                'action' => ['required', 'string', 'in:reply_only'],
                'submission_key' => ['nullable', 'string', 'max:255'],
                'visibility' => ['required', 'string', 'in:client_admin,admin_staff,shared'],
            ],
            \App\Services\BookingAttachmentService::getValidationRules(false)
        );

        $validated = $request->validate($rules);

        if (!empty($validated['submission_key'])) {
            $existing = \App\Models\BookingMessage::where('submission_key', $validated['submission_key'])->first();
            if ($existing) {
                return redirect()->back()->with('success', 'Reply sent successfully.');
            }
        }

        try {
            $messageData = [
                'booking_id' => $booking->id,
                'sender_type' => 'admin',
                'sender_id' => Auth::id(),
                'message' => $validated['message'],
                'visibility' => $validated['visibility'],
                'submission_key' => $validated['submission_key'] ?? null,
            ];

            $attachmentService->storeMessage(
                $booking, 
                $messageData, 
                $request->file('attachment'), 
                $validated['attachment_category'] ?? null
            );

            $this->createClientNotificationForBooking($booking, 'New message from Admin', 'Admin has replied to your request regarding booking #' . $booking->id . '.', 'booking_update');
            $this->logAuditEvent('booking', 'admin_reply', $booking, 'Admin replied to client request', Auth::id());

            return redirect()->back()->with('success', 'Reply sent successfully.');
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            return redirect()->back()->with('success', 'Reply sent successfully.');
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == 23000) {
                return redirect()->back()->with('success', 'Reply sent successfully.');
            }
            \Illuminate\Support\Facades\Log::error('Failed to send admin reply: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Unable to send reply. Please try again.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send admin reply: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Unable to send reply. Please try again.');
        }
    }

    public function handleCancellationRequest(Request $request, Booking $booking): RedirectResponse
    {
        if ($booking->status !== 'cancellation_requested') {
            return redirect()->back()->with('error', 'Booking is not awaiting cancellation review.');
        }

        $validated = $request->validate([
            'action' => ['required', 'string', 'in:approve,deny'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $priorStatus = $booking->pre_cancellation_status;
        if (empty($priorStatus) && $booking->quotations()->where('status', 'issued')->exists()) {
            $priorStatus = 'quotation_sent';
        }

        $eligibleStatuses = [
            'pending',
            'quotation_sent',
            'change_requested',
            'approved',
            'admin_approved',
            'payment_pending',
            'payment_submitted',
            'downpayment_received',
            'confirmed',
            'in_preparation',
            'event_in_progress',
        ];

        if ($validated['action'] === 'approve') {
            return DB::transaction(function () use ($booking, $validated, $priorStatus) {
                if (!empty($validated['admin_note'])) {
                    \App\Models\BookingMessage::create([
                        'booking_id' => $booking->id,
                        'sender_type' => 'admin',
                        'sender_id' => Auth::id(),
                        'message' => $validated['admin_note'],
                    ]);
                }

                $booking->status = 'cancelled';
                $booking->pre_cancellation_status = null;
                $booking->save();

                $reservationStatuses = ['downpayment_received', 'confirmed', 'in_preparation', 'event_in_progress', 'event_completed'];
                $hasLocks = InventoryTransaction::where('booking_id', $booking->id)
                    ->where('transaction_type', 'booking_lock')
                    ->exists();

                if (in_array($priorStatus, $reservationStatuses, true) || $hasLocks) {
                    $this->releaseInventoryForBooking($booking);
                }

                if ($booking->hasDispatchedReusableMaterials()) {
                    $this->ensureReturnRecordForBooking($booking);
                }

                $this->createClientNotificationForBooking(
                    $booking,
                    'Cancellation Approved',
                    'Your cancellation request for booking #' . $booking->id . ' has been approved.',
                    'booking_update'
                );
                $this->logAuditEvent('booking', 'cancellation_approved', $booking, 'Admin approved cancellation request', Auth::id());

                return redirect()->back()->with('success', 'Booking has been cancelled.');
            });
        }

        // Action === 'deny'
        if (empty($priorStatus) || !in_array($priorStatus, $eligibleStatuses, true)) {
            return redirect()->back()->with('error', 'Cannot process cancellation denial: prior booking status is missing or invalid.');
        }

        return DB::transaction(function () use ($booking, $validated, $priorStatus) {
            if (!empty($validated['admin_note'])) {
                \App\Models\BookingMessage::create([
                    'booking_id' => $booking->id,
                    'sender_type' => 'admin',
                    'sender_id' => Auth::id(),
                    'message' => $validated['admin_note'],
                ]);
            }

            if ($priorStatus === 'quotation_sent') {
                $booking->status = 'change_requested';
            } else {
                $booking->status = $priorStatus;
            }

            $booking->cancellation_reason = null;
            $booking->pre_cancellation_status = null;
            $booking->save();

            $this->createClientNotificationForBooking(
                $booking,
                'Cancellation Denied',
                'Your cancellation request for booking #' . $booking->id . ' has been denied. The Admin has sent a note.',
                'booking_update'
            );
            $this->logAuditEvent('booking', 'cancellation_denied', $booking, 'Admin denied cancellation request', Auth::id());

            $successMsg = $priorStatus === 'quotation_sent'
                ? 'Cancellation request denied. Booking reverted to discussion state.'
                : 'Cancellation request denied. Booking operational status restored to ' . $booking->status_display_label . '.';

            return redirect()->back()->with('success', $successMsg);
        });
    }

    public static function inferIsPerishableFromName(?string $name): bool
    {
        if (empty($name)) return true;
        $keywords = ['backdrop', 'frame', 'arch', 'vase', 'stand', 'lighting', 'light', 'structure', 'archway', 'tripod', 'standee'];
        $lower = strtolower($name);
        foreach ($keywords as $kw) {
            if (str_contains($lower, $kw)) {
                return false; // hardware
            }
        }

        // Default to perishable for floral-sounding names
        return true;
    }

    public function notifications(): View
    {
        $alerts = collect();
        $storedAlerts = AdminAlert::where('is_read', false)
            ->where('type', '!=', 'inventory_shortage')
            ->latest('created_at')
            ->get()
            ->each(function ($alert): void {
                $alert->is_dynamic = false;
            });

        foreach ($storedAlerts as $storedAlert) {
            $alerts->push($storedAlert);
        }
        $paymentPendingBookings = Booking::where(function ($query) {
            $query->whereIn('status', ['payment_submitted', 'payment_pending'])
                  ->orWhereHas('payments', function ($q) {
                      $q->where('status', 'pending');
                  });
        })
            ->latest('updated_at')
            ->get();

        foreach ($paymentPendingBookings as $booking) {
            $alerts->push((object) [
                'id' => 'booking-' . $booking->id,
                'type' => 'payment_pending',
                'title' => 'Payment Review Required',
                'message' => sprintf('Booking #%d is awaiting admin verification for the submitted payment reference.', $booking->id),
                'booking_id' => $booking->id,
                'inventory_item_id' => null,
                'created_at' => $booking->updated_at ?? $booking->created_at,
                'is_read' => false,
                'is_dynamic' => true,
            ]);
        }

        $bookingShortageAlerts = collect();
        $bookedInventoryItemIds = collect();
        $activeBookings = Booking::with(['bookingItems.inventoryItem'])
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhereNotIn('status', [
                        'cancelled',
                        'rejected',
                        'completed',
                        'declined',
                        'event_in_progress',
                        'event_completed',
                        'pending_return',
                        'pending_resolution',
                    ]);
            })
            ->latest('updated_at')
            ->get();

        foreach ($activeBookings as $booking) {
            $this->reconcileBookingInventoryLinks($booking);
            $booking->load('bookingItems.inventoryItem');
            $shortageItems = [];
            foreach ($booking->bookingItems as $bookingItem) {
                $required = (float) ($bookingItem->quantity ?? 0);
                if ($required <= 0) {
                    continue;
                }

                $item = $bookingItem->inventoryItem;
                if (!$item) {
                    $shortageItems[] = [
                        'inventory_item_id' => null,
                        'booking_item_id' => $bookingItem->id,
                        'name' => $bookingItem->item_name ?? 'Unmatched booking item',
                        'quoted_unit_price' => (float) ($bookingItem->quoted_unit_price ?? 0),
                        'required' => $required,
                        'current_stock' => 0,
                        'reserved_stock' => 0,
                        'net_available' => 0,
                        'shortfall' => $required,
                        'is_missing' => true,
                        'is_shortage' => true,
                    ];
                    continue;
                }

                $reservedByOtherBookings = max(0.0, (float) ($item->reserved_stock ?? 0) - $required);
                $available = max(0.0, (float) $item->current_stock - $reservedByOtherBookings);
                $isShortage = $available < $required;
                if ($isShortage) {
                    $bookedInventoryItemIds->push($item->id);
                }

                $shortageItems[] = [
                    'inventory_item_id' => $item->id,
                    'name' => $item->name,
                    'required' => $required,
                    'current_stock' => (float) $item->current_stock,
                    'reserved_stock' => $reservedByOtherBookings,
                    'net_available' => $available,
                    'shortfall' => max(0.0, $required - $available),
                    'is_missing' => false,
                    'is_shortage' => $isShortage,
                ];
            }

            if (!empty($shortageItems)) {
                usort($shortageItems, function (array $left, array $right): int {
                    return ((int) ($right['is_shortage'] ?? false)) <=> ((int) ($left['is_shortage'] ?? false));
                });
            }

            $shortageCount = count(array_filter($shortageItems, fn (array $item): bool => (bool) ($item['is_shortage'] ?? false)));
            if ($shortageCount === 0) {
                continue;
            }

            $itemNames = array_values(array_filter(array_map(fn ($item) => ($item['is_shortage'] ?? false) ? ($item['name'] ?? null) : null, $shortageItems)));
            $summaryNames = implode(', ', array_slice($itemNames, 0, 4));
            $summarySuffix = count($itemNames) > 4 ? ', ...' : '';

            $bookingShortageAlerts->push((object) [
                'id' => 'booking-shortage-' . $booking->id,
                'type' => 'booking_shortage',
                'title' => 'Stock Alert: Booking #' . $booking->id,
                'message' => $shortageCount . ' item' . ($shortageCount === 1 ? '' : 's') . ' need attention' . (!empty($summaryNames) ? ' (' . $summaryNames . $summarySuffix . ')' : '') . '. Available items are shown below in green.',
                'booking_id' => $booking->id,
                'inventory_item_id' => null,
                'created_at' => $booking->updated_at ?? $booking->created_at,
                'is_read' => false,
                'is_dynamic' => true,
                'items' => $shortageItems,
                'alert_type' => 'booking_shortage',
            ]);
        }

        foreach ($bookingShortageAlerts as $alert) {
            $alerts->push($alert);
        }

        $generalLowStockItems = InventoryItem::whereNotNull('min_stock')
            ->get()
            ->filter(function ($item) use ($bookedInventoryItemIds) {
                $netAvailable = max(0.0, (float) $item->current_stock - (float) $item->reserved_stock);
                $isBelowThreshold = ((float) $item->current_stock <= (float) $item->min_stock) || $netAvailable < 0;

                return $isBelowThreshold && !$bookedInventoryItemIds->contains($item->id);
            });

        if ($generalLowStockItems->isNotEmpty()) {
            $generalItems = $generalLowStockItems->map(function ($item) {
                $netAvailable = max(0.0, (float) $item->current_stock - (float) $item->reserved_stock);

                return [
                    'inventory_item_id' => $item->id,
                    'name' => $item->name,
                    'required' => (float) $item->min_stock,
                    'current_stock' => (float) $item->current_stock,
                    'reserved_stock' => (float) $item->reserved_stock,
                    'net_available' => $netAvailable,
                    'shortfall' => max(0.0, (float) $item->min_stock - $netAvailable),
                ];
            })->values()->all();

            $alerts->push((object) [
                'id' => 'general-low-stock',
                'type' => 'general_low_stock',
                'title' => 'Stock Alert: General Low Stock (' . count($generalItems) . ' Items)',
                'message' => count($generalItems) . ' item' . (count($generalItems) === 1 ? '' : 's') . ' below the minimum stock threshold',
                'booking_id' => null,
                'inventory_item_id' => null,
                'created_at' => now(),
                'is_read' => false,
                'is_dynamic' => true,
                'items' => $generalItems,
                'alert_type' => 'general_low_stock',
            ]);
        }

        return view('admin.notifications', [
            'alerts' => $alerts->sortByDesc(fn ($alert) => $alert->created_at)->values(),
        ]);
    }

    public function resolveBookingShortages(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'stock' => ['nullable', 'array'],
            'stock.*' => ['nullable', 'numeric', 'min:0'],
            'stock_updates' => ['nullable', 'array'],
            'stock_updates.*' => ['nullable', 'numeric', 'min:0'],
            'action' => ['nullable', 'string', 'in:restock_and_resolve,restock_and_edit'],
        ]);

        $stockUpdates = $request->input('stock', $request->input('stock_updates', []));
        $shouldResolveAlert = ($validated['action'] ?? 'restock_and_resolve') === 'restock_and_resolve';
        $restockedAnyItem = false;

        if ($shouldResolveAlert) {
            // Acknowledging an alert (read state) does not mean the physical shortage was resolved.
            // Duplicate procurement is now prevented by an operation-specific transaction check below.
        }

        DB::transaction(function () use ($stockUpdates, $booking, $shouldResolveAlert, &$restockedAnyItem): void {
            // Lock the booking parent record first to ensure consistent parent-first locking hierarchy
            $lockedBooking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            // Extract and sort inventory item IDs in ascending order for deterministic row locking
            $itemIds = collect($stockUpdates)
                ->filter(fn ($qty) => (float) $qty > 0)
                ->keys()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->sort()
                ->values()
                ->toArray();

            // Acquire inventory item row locks deterministically in ascending PK order
            $lockedItems = !empty($itemIds)
                ? InventoryItem::whereIn('id', $itemIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id')
                : collect();

            foreach ($itemIds as $inventoryItemId) {
                $updatedStock = (float) ($stockUpdates[$inventoryItemId] ?? 0);
                if ($updatedStock <= 0) {
                    continue;
                }

                $inventoryItem = $lockedItems->get($inventoryItemId);
                if (!$inventoryItem) {
                    continue;
                }
                
                // Idempotency / Duplicate Prevention:
                // Prevent duplicate submission of the EXACT SAME restocking operation within 10 seconds.
                $isDuplicate = InventoryTransaction::where('inventory_item_id', $inventoryItem->id)
                    ->where('booking_id', $lockedBooking->id)
                    ->where('transaction_type', 'procurement')
                    ->where('quantity_change', $updatedStock)
                    ->where('created_at', '>=', now()->subSeconds(10))
                    ->exists();

                if ($isDuplicate) {
                    continue;
                }

                $inventoryItem->increment('current_stock', $updatedStock);
                InventoryTransaction::create([
                    'inventory_item_id' => $inventoryItem->id,
                    'booking_id' => $lockedBooking->id,
                    'quantity_change' => $updatedStock,
                    'transaction_type' => 'procurement',
                    'reason' => 'Admin restock for booking #' . $lockedBooking->id,
                    'performed_by' => Auth::id(),
                ]);
                $restockedAnyItem = true;
            }

            if ($shouldResolveAlert && $restockedAnyItem) {
                AdminAlert::where('booking_id', $lockedBooking->id)
                    ->where('is_read', false)
                    ->update(['is_read' => true]);
            }
        });

        if (($validated['action'] ?? null) === 'restock_and_edit') {
            return redirect()->route('admin.bookings.edit', ['booking' => $booking->id])->with('success', 'Stock updates applied. Please finish the booking changes from the edit screen.');
        }

        if ($request->input('redirect_to') === 'booking' || ($request->headers->get('referer') && str_contains($request->headers->get('referer'), '/bookings/' . $booking->id))) {
            return redirect()->route('admin.bookings.show', ['booking' => $booking->id])->with('success', 'Booking shortages resolved successfully.');
        }

        return redirect()->route('admin.notifications')->with('success', 'Booking shortages resolved successfully.');
    }

    public function promoteBookingItemsToInventory(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'booking_item_ids' => ['required', 'array', 'min:1'],
            'booking_item_ids.*' => ['integer'],
        ]);

        $promoted = 0;
        foreach ($validated['booking_item_ids'] as $bookingItemId) {
            $bookingItem = $booking->bookingItems()
                ->whereKey($bookingItemId)
                ->whereNull('inventory_item_id')
                ->first();

            if (!$bookingItem || trim((string) $bookingItem->item_name) === '') {
                continue;
            }

            $inventoryItem = InventoryItem::firstOrCreate(
                ['name' => trim($bookingItem->item_name)],
                [
                    'category' => 'misc',
                    'is_perishable' => false,
                    'current_stock' => 0,
                    'unit_cost' => (float) ($bookingItem->quoted_unit_price ?? 0),
                    'min_stock' => 0,
                    'unit' => 'piece',
                ]
            );

            $bookingItem->forceFill(['inventory_item_id' => $inventoryItem->id])->save();
            $promoted++;
        }

        return redirect()->route('admin.notifications')->with(
            $promoted > 0 ? 'success' : 'error',
            $promoted > 0
                ? $promoted . ' AI item' . ($promoted === 1 ? '' : 's') . ' added to inventory with zero stock.'
                : 'No eligible missing AI items were found.'
        );
    }

    public function edit(Booking $booking): View
    {
        return $this->buildBookingReviewView($booking, true);
    }

    protected function normalizeBookingStatus(mixed $status): ?string
    {
        if ($status === null || $status === '') {
            return null;
        }

        $normalized = Str::slug(trim((string) $status), '_');

        return $normalized === '' ? null : $normalized;
    }

    protected function sanitizeAdminNoteMessage(?string $message): ?string
    {
        if ($message === null) {
            return null;
        }

        $message = preg_replace('/\r\n?/', "\n", $message);
        $lines = preg_split('/\n/', trim((string) $message));
        $filtered = [];
        $insideGeneratedSection = false;

        foreach ($lines as $line) {
            $trimmed = trim((string) $line);

            if (preg_match('/^\s*(Item adjustments|item adjustments)\s*:\s*$/i', $trimmed)) {
                $insideGeneratedSection = true;
                continue;
            }

            if ($insideGeneratedSection) {
                if ($trimmed === '') {
                    continue;
                }

                if (preg_match('/^[\-•\*] /', $trimmed) || preg_match('/^\d+\./', $trimmed)) {
                    continue;
                }

                $insideGeneratedSection = false;
            }

            $filtered[] = $line;
        }

        $message = implode("\n", $filtered);

        return trim((string) preg_replace('/\n{3,}/', "\n\n", $message));
    }

    protected function buildItemAdjustmentSummary(array $items): array
    {
        $activeItems = [];
        $removedItems = [];

        foreach ($items as $it) {
            $name = trim((string) ($it['item_name'] ?? 'Item'));
            $qty = array_key_exists('quantity', $it) ? (float) ($it['quantity'] ?? 0) : (array_key_exists('adjusted_quantity', $it) ? (float) ($it['adjusted_quantity'] ?? 0) : null);
            $isRemoved = !empty($it['remove']) || !empty($it['unavailable']);

            if ($isRemoved) {
                $removedItems[] = '• ' . $name;
                continue;
            }

            if ($qty === null) {
                continue;
            }

            $activeItems[] = '• ' . $name . '  Qty: ' . number_format($qty, 2, '.', '');
        }

        return [
            'items' => $activeItems,
            'removed' => $removedItems,
        ];
    }

    protected function buildPriceAdjustmentSummary(float $previousTotal, ?float $newTotal): array
    {
        if ($newTotal === null || !is_numeric($newTotal)) {
            return [];
        }

        if (abs($newTotal - $previousTotal) <= 0.01) {
            return [];
        }

        return [
            '• Quote updated: ₱' . number_format($previousTotal, 2) . ' → ₱' . number_format($newTotal, 2),
        ];
    }

    protected function appendGeneratedAdjustmentNote(string $adminNote, array $items, array $priceChanges = []): string
    {
        $summary = $this->buildItemAdjustmentSummary($items);
        $sections = [];

        if ($adminNote !== '') {
            $sections[] = trim($adminNote);
        }

        if (!empty($summary['items'])) {
            $sections[] = "Item adjustments:\n" . implode("\n", $summary['items']);
        }

        if (!empty($summary['removed'])) {
            $sections[] = "Removed items:\n" . implode("\n", $summary['removed']);
        }

        if (!empty($priceChanges)) {
            $sections[] = "Price changes:\n" . implode("\n", $priceChanges);
        }

        return trim(implode("\n\n", $sections));
    }

    protected function logAuditEvent(string $module, string $action, ?Booking $booking = null, ?string $details = null, ?int $userId = null, ?array $oldValues = null, ?array $newValues = null, ?string $eventType = null): void
    {
        AuditLog::create([
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'module' => $module,
            'event_type' => $eventType,
            'details' => $details,
            'old_values' => $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
            'new_values' => $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
            'ip_address' => request()->ip(),
            'entity_type' => $booking ? Booking::class : null,
            'entity_id' => $booking?->id,
        ]);
    }

    protected function createClientNotificationForBooking(Booking $booking, string $title, string $message, string $type = 'booking_update'): void
    {
        if (!$booking->client) {
            return;
        }

        $user = null;
        if (filter_var($booking->client->email, FILTER_VALIDATE_EMAIL)) {
            $user = \App\Models\User::where('email', $booking->client->email)->first();
        }
        if (!$user && !empty($booking->client->user_id)) {
            $user = \App\Models\User::find($booking->client->user_id);
        }
        if (!$user) {
            return;
        }

        // If message is structured payload, prefer its human-readable `text` key when present.
        if (is_array($message)) {
            $messageText = trim((string) ($message['text'] ?? '')) ?: trim(json_encode($message, JSON_UNESCAPED_UNICODE));
        } else {
            $messageText = trim((string) $message);
        }

        $existing = ClientNotification::where('user_id', $user->id)
            ->where('booking_id', $booking->id)
            ->where('message', $messageText)
            ->latest()
            ->first();

        if ($existing && $existing->created_at && $existing->created_at->diffInMinutes(now()) < 30) {
            return;
        }

        ClientNotification::create([
            'user_id' => $user->id,
            'booking_id' => $booking->id,
            'type' => $type,
            'title' => $title,
            'message' => $messageText,
            'is_read' => false,
        ]);
    }

    protected function buildBookingReviewView(Booking $booking, bool $isEditMode = false): View
    {
        $this->reconcileBookingInventoryLinks($booking);
        $booking->load(['client', 'aiAnalyses', 'payments', 'returns.returnItems', 'bookingItems.inventoryItem', 'quotations', 'staffChecklistItems.completedBy', 'assignedStaff']);
        $analysis = $booking->aiAnalyses()->latest('analyzed_at')->first();
        $payment = $booking->payments()->latest()->first();
        $activeQuotation = $booking->activeQuotation;

        // Load all inventory items with their substitutes to power the substitution engine
        $allInventoryItems = \App\Models\InventoryItem::with('substitutes')->get();
        $staffUsers = User::where('role', 'staff')->orderBy('name')->get(['id', 'name', 'email']);
        $bookingItems = $booking->bookingItems()->orderBy('created_at')->get();

        // Compute a strict read-only flag for the quote review view
        $quoteReadOnly = in_array($booking->status, ['downpayment_received', 'event_in_progress', 'event_completed', 'pending_return', 'pending_resolution', 'completed'], true);

        $bookingMessages = \App\Models\BookingMessage::where('booking_id', $booking->id)->orderBy('created_at')->get();

        $confirmedItems = $bookingItems->filter(fn ($bi) => $bi->confirmed_at !== null && (float) $bi->quantity > 0);
        $reusableItems = $confirmedItems->filter(fn ($bi) => $bi->inventoryItem && !$bi->inventoryItem->is_perishable);
        $freshFlowerItems = $confirmedItems->filter(fn ($bi) => $bi->inventoryItem && $bi->inventoryItem->is_perishable);
        $unconfirmedItems = $bookingItems->filter(fn ($bi) => $bi->confirmed_at === null && (float) $bi->quantity > 0);

        $bookingShortages = [];
        $reusableStatusList = [];
        $allReusableReserved = $reusableItems->isNotEmpty();

        foreach ($reusableItems as $bItem) {
            $inv = $bItem->inventoryItem;
            $required = (float) $bItem->quantity;

            $locked = (float) InventoryTransaction::query()
                ->where('booking_id', $booking->id)
                ->where('inventory_item_id', $inv->id)
                ->where('transaction_type', 'booking_lock')
                ->where('quantity_change', '<', 0)
                ->sum('quantity_change');
            $released = (float) InventoryTransaction::query()
                ->where('booking_id', $booking->id)
                ->where('inventory_item_id', $inv->id)
                ->where('transaction_type', 'booking_release')
                ->where('quantity_change', '>', 0)
                ->sum('quantity_change');

            $netLocked = abs($locked) - $released;
            $remaining = max(0.0, $required - $netLocked);
            $isReserved = $netLocked >= $required;

            if (!$isReserved) {
                $allReusableReserved = false;
            }

            $available = (float) $inv->net_available;
            $hasShortage = $available < $remaining;

            $netDispatched = abs((float) InventoryTransaction::query()
                ->where('booking_id', $booking->id)
                ->where('inventory_item_id', $inv->id)
                ->whereIn('transaction_type', ['dispatch', 'dispatch_correction'])
                ->sum('quantity_change'));

            $outstandingDispatch = max(0.0, $netLocked - $netDispatched);

            if ($hasShortage) {
                $bookingShortages[] = [
                    'inventory_item' => $inv,
                    'booking_item' => $bItem,
                    'required' => $required,
                    'locked' => $netLocked,
                    'remaining' => $remaining,
                    'available' => $available,
                    'shortage' => $remaining - $available,
                ];
            }

            $reusableStatusList[] = [
                'booking_item' => $bItem,
                'inventory_item' => $inv,
                'required' => $required,
                'locked' => $netLocked,
                'remaining' => $remaining,
                'available' => $available,
                'is_reserved' => $isReserved,
                'has_shortage' => $hasShortage,
                'net_dispatched' => $netDispatched,
                'outstanding_dispatch' => $outstandingDispatch,
            ];
        }

        if ($reusableItems->isEmpty()) {
            $allReusableReserved = true;
        }

        $totalObligation = (float) $booking->total_obligation;
        $paidAmount = (float) $booking->total_paid;
        $remainingBalance = (float) $booking->remaining_balance;
        $damageCharges = (float) $booking->damage_charges;
        $baseQuotedAmount = (float) ($booking->final_quoted_price ?? $booking->total_quoted ?? 0);
        $canLogFinalPayment = in_array($booking->status, ['event_in_progress', 'event_completed', 'pending_return', 'pending_resolution'], true) && ($remainingBalance > 0);

        return view('admin.booking-show', [
            'booking' => $booking,
            'analysis' => $analysis,
            'payment' => $payment,
            'allInventoryItems' => $allInventoryItems,
            'staffUsers' => $staffUsers,
            'bookingItems' => $bookingItems,
            'isEditMode' => $isEditMode,
            'isReadOnly' => $isEditMode && in_array($booking->status, ['event_in_progress', 'event_completed', 'pending_return', 'pending_resolution', 'completed'], true),
            'quoteReadOnly' => $quoteReadOnly,
            'activeQuotation' => $activeQuotation,
            'bookingMessages' => $bookingMessages,
            'reusableStatusList' => $reusableStatusList,
            'freshFlowerItems' => $freshFlowerItems,
            'unconfirmedItems' => $unconfirmedItems,
            'bookingShortages' => $bookingShortages,
            'allReusableReserved' => $allReusableReserved,
            'totalObligation' => $totalObligation,
            'paidAmount' => $paidAmount,
            'remainingBalance' => $remainingBalance,
            'damageCharges' => $damageCharges,
            'baseQuotedAmount' => $baseQuotedAmount,
            'canLogFinalPayment' => $canLogFinalPayment,
        ]);
    }

    protected function reconcileBookingInventoryLinks(Booking $booking): void
    {
        $inventoryByName = InventoryItem::query()
            ->get(['id', 'name'])
            ->filter(fn (InventoryItem $item): bool => trim((string) $item->name) !== '')
            ->keyBy(fn (InventoryItem $item): string => $this->normalizeInventoryName($item->name));

        if ($inventoryByName->isEmpty()) {
            return;
        }

        $booking->bookingItems()
            ->whereNull('inventory_item_id')
            ->get()
            ->each(function (BookingItem $bookingItem) use ($inventoryByName): void {
                $inventoryItem = $inventoryByName->get($this->normalizeInventoryName($bookingItem->item_name));
                if ($inventoryItem) {
                    $bookingItem->forceFill(['inventory_item_id' => $inventoryItem->id])->save();
                }
            });
    }

    protected function normalizeInventoryName(?string $name): string
    {
        return strtolower(trim(preg_replace('/[^a-z0-9]+/', ' ', (string) $name) ?? ''));
    }

    protected function adjustInventoryForBookingStatus(Booking $booking, ?string $oldStatus, ?string $newStatus): void
    {
        if ($oldStatus === $newStatus) {
            return;
        }

        $confirmedStatuses = ['downpayment_received', 'confirmed'];
        $cancelledStatuses = ['cancelled', 'declined', 'rejected'];

        if (in_array($newStatus, $confirmedStatuses, true)) {
            // Delay reusable inventory reservation until the defined preparation period
            if (is_null($booking->preparation_start_date) || $booking->preparation_start_date->isFuture()) {
                return;
            }

            $this->reserveReusableMaterialsForBooking($booking);
        }

        if (in_array($oldStatus, $confirmedStatuses, true) && in_array($newStatus, $cancelledStatuses, true)) {
            $this->releaseInventoryForBooking($booking);
        }
    }

    public function reserveReusableMaterialsForBooking(Booking $booking): array
    {
        $items = $this->confirmedInventoryItems($booking);
        $reusableItems = $items->filter(fn ($item) => !$item->is_perishable);

        if ($reusableItems->isEmpty()) {
            return ['reserved' => 0, 'shortages' => []];
        }

        $itemIds = $reusableItems->pluck('id')->sort()->values()->toArray();
        $lockedItems = InventoryItem::whereIn('id', $itemIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

        $shortages = [];
        $itemsToLock = [];

        foreach ($reusableItems as $inventoryItem) {
            $requiredQty = (float) ($inventoryItem->pivot->quantity ?? 0);
            if ($requiredQty <= 0) {
                continue;
            }

            $alreadyLocked = (float) InventoryTransaction::query()
                ->where('booking_id', $booking->id)
                ->where('inventory_item_id', $inventoryItem->id)
                ->where('transaction_type', 'booking_lock')
                ->where('quantity_change', '<', 0)
                ->sum('quantity_change');

            $alreadyReleased = (float) InventoryTransaction::query()
                ->where('booking_id', $booking->id)
                ->where('inventory_item_id', $inventoryItem->id)
                ->where('transaction_type', 'booking_release')
                ->where('quantity_change', '>', 0)
                ->sum('quantity_change');

            $netReserved = abs($alreadyLocked) - $alreadyReleased;
            $remainingToLock = $requiredQty - $netReserved;

            if ($remainingToLock <= 0) {
                continue;
            }

            $lockedItem = $lockedItems->get($inventoryItem->id);
            if ($lockedItem && $lockedItem->net_available < $remainingToLock) {
                $shortages[] = [
                    'name' => $lockedItem->name,
                    'required' => $remainingToLock,
                    'available' => $lockedItem->net_available,
                ];
            } else {
                $itemsToLock[] = [
                    'item' => $inventoryItem,
                    'quantity' => $remainingToLock,
                ];
            }
        }

        if (!empty($shortages)) {
            $names = collect($shortages)->pluck('name')->join(', ');
            throw new \RuntimeException("Insufficient available stock to reserve materials: {$names}. Please restock or resolve shortages first.");
        }

        $reservedCount = 0;
        foreach ($itemsToLock as $lockData) {
            InventoryTransaction::create([
                'inventory_item_id' => $lockData['item']->id,
                'booking_id' => $booking->id,
                'quantity_change' => -$lockData['quantity'],
                'transaction_type' => 'booking_lock',
                'reason' => 'Preparation-period reservation for booking #' . $booking->id,
                'performed_by' => Auth::id() ?? $booking->handled_by,
            ]);
            $reservedCount++;
        }

        if ($reservedCount > 0) {
            $this->logAuditEvent(
                'booking',
                'materials_reserved',
                $booking,
                "Reserved {$reservedCount} reusable inventory material(s) for booking #{$booking->id}",
                Auth::id()
            );
        }

        return ['reserved' => $reservedCount, 'shortages' => []];
    }

    public function reserveMaterials(Request $request, Booking $booking): RedirectResponse
    {
        if (Auth::user()?->role !== 'admin') {
            abort(403, 'Only an authorized admin can reserve booking materials.');
        }

        if (!in_array($booking->status, ['downpayment_received', 'confirmed', 'in_preparation', 'event_in_progress'], true)) {
            return redirect()->back()->with('error', 'Cannot reserve materials: booking must have verified payment / confirmed status.');
        }

        if (is_null($booking->preparation_start_date)) {
            return redirect()->back()->with('error', 'Cannot reserve materials: please set a Preparation Start Date first.');
        }

        if ($booking->preparation_start_date->isFuture()) {
            return redirect()->back()->with('error', 'Cannot reserve materials: Preparation Start Date has not been reached yet.');
        }

        try {
            $result = DB::transaction(function () use ($booking) {
                $res = $this->reserveReusableMaterialsForBooking($booking);
                $booking->preparation_status = 'in_preparation';
                $booking->save();
                return $res;
            });

            $freshFlowerCount = $booking->bookingItems()
                ->whereHas('inventoryItem', fn ($q) => $q->where('is_perishable', true))
                ->count();

            $msg = 'Reusable materials reserved successfully for preparation period.';
            if ($freshFlowerCount > 0) {
                $msg .= " Note: {$freshFlowerCount} fresh flower requirement(s) are tracked for procurement.";
            }

            return redirect()->back()->with('success', $msg);
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    protected function releaseInventoryForBooking(Booking $booking, ?string $reason = null): void
    {
        app(\App\Services\InventoryDispatchService::class)->releaseUnfulfilledReservations(
            $booking,
            Auth::id() ?? $booking->handled_by,
            $reason ?? ('Released confirmed booking inventory for booking #' . $booking->id)
        );
    }

    protected function collectInsufficientBookingItems(Booking $booking, array $items): array
    {
        $requestedQuantities = [];
        foreach ($items as $it) {
            $bookingItemId = $it['booking_item_id'] ?? null;
            $itemName = trim((string) ($it['item_name'] ?? ''));
            $requestedQty = isset($it['quantity']) ? (float) $it['quantity'] : null;

            if ($bookingItemId) {
                $bookingItem = BookingItem::find($bookingItemId);
                if ($bookingItem && $bookingItem->inventory_item_id) {
                    $requestedQuantities[$bookingItem->inventory_item_id] = $requestedQty ?? (float) $bookingItem->quantity;
                }
            }

            if ($itemName !== '' && $requestedQty !== null) {
                $inventoryItem = InventoryItem::query()
                    ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($itemName)])
                    ->first();

                if ($inventoryItem) {
                    $requestedQuantities[$inventoryItem->id] = $requestedQty;
                }
            }
        }

        if (empty($requestedQuantities)) {
            foreach ($this->confirmedInventoryItems($booking) as $inventoryItem) {
                $requestedQuantities[$inventoryItem->id] = (float) ($inventoryItem->pivot->quantity ?? 0);
            }
        }

        $insufficientItems = [];
        foreach ($requestedQuantities as $inventoryItemId => $requestedQty) {
            if ($requestedQty <= 0) {
                continue;
            }

            $inventoryItem = InventoryItem::find($inventoryItemId);
            if (!$inventoryItem) {
                continue;
            }

            if ((float) $inventoryItem->current_stock < $requestedQty) {
                $insufficientItems[] = $inventoryItem->name;
            }
        }

        return array_values(array_unique($insufficientItems));
    }

    protected function validateBookingInventoryAvailability(Booking $booking, ?string $status): bool
    {
        if (!in_array($status, ['downpayment_received', 'confirmed'], true)) {
            return true;
        }

        foreach ($this->confirmedInventoryItems($booking) as $inventoryItem) {
            // Fresh flowers are perishable procurement requirements, not date-overlap locks
            if ($inventoryItem->is_perishable) {
                continue;
            }

            $requiredQty = (float) ($inventoryItem->pivot->quantity ?? 0);
            if ($requiredQty <= 0) {
                continue;
            }

            $existingConfirmedDemand = Booking::query()
                ->whereDate('event_date', $booking->event_date->toDateString())
                ->whereIn('status', ['downpayment_received', 'confirmed', 'in_preparation', 'event_in_progress'])
                ->where('id', '!=', $booking->id)
                ->with('inventoryItems')
                ->get()
                ->sum(function ($existingBooking) use ($inventoryItem) {
                    $pivot = $existingBooking->inventoryItems->firstWhere('id', $inventoryItem->id);

                    return $pivot ? (float) $pivot->pivot->quantity : 0;
                });

            $availableAfterOverlap = (float) $inventoryItem->current_stock - $existingConfirmedDemand;
            if ($availableAfterOverlap < $requiredQty) {
                return false;
            }
        }

        return true;
    }

    public function storeProposal(Request $request, Booking $booking): RedirectResponse
    {
        $request->validate([
            'proposal_file' => ['required', 'file', 'mimes:pdf,doc,docx,ppt,pptx,jpg,jpeg,png', 'max:20480'],
        ]);

        $file = $request->file('proposal_file');
        $path = $file->store('bookings/proposals', 'public');

        $version = $booking->presentations()->count() > 0 ? 'v' . ((int) $booking->presentations()->count() + 1) : 'v1';

        $presentation = Presentation::create([
            'booking_id' => $booking->id,
            'version' => $version,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'sent_at' => now(),
            'sent_by' => Auth::id(),
            'status' => 'sent',
        ]);

        $this->logAuditEvent('booking', 'proposal_uploaded', $booking, 'Uploaded proposal ' . $presentation->version, Auth::id());

        // Notify client via in-app notification and attempt email
        $noteMessage = 'A new proposal (' . $presentation->version . ') has been uploaded for your booking. Please review the proposal versions and provide feedback.';
        $this->createClientNotificationForBooking($booking, 'New proposal uploaded', $noteMessage, 'proposal');

        try {
            if ($booking->client && filter_var($booking->client->email, FILTER_VALIDATE_EMAIL)) {
                Mail::to($booking->client->email)->send(new BookingStatusChanged($booking, null, 'proposal_uploaded'));
            }
        } catch (\Throwable $e) {
            logger()->warning('Proposal upload notification email failed: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Proposal uploaded successfully.');
    }

    public function finalApproveQuotation(Request $request, Booking $booking): RedirectResponse
    {
        if ($booking->status !== 'approved') {
            return redirect()->back()->with('error', 'This booking is not awaiting Admin final approval.');
        }

        $booking->setNormalizedStatus('admin_approved')->save();
        $this->logAuditEvent('booking', 'quotation_final_approved', $booking, 'Admin final-approved the accepted quotation', Auth::id());

        $this->createClientNotificationForBooking(
            $booking,
            'Quotation Approved — Payment Unlocked',
            'Your accepted quotation has been approved by Raflora. You may now submit your downpayment reference to secure your booking.',
            'booking_update'
        );

        return redirect()->back()->with('success', 'Quotation final-approved. Payment submission is now available.');
    }

    /**
     * Link an AI-suggested booking item to an existing inventory item.
     * This route validates only item-specific inputs.
     */
    public function linkAiItem(Request $request, Booking $booking, ?BookingItem $bookingItem = null): RedirectResponse
    {
        if (Auth::user()?->role !== 'admin') {
            abort(403, 'Only an authorized admin can link material suggestions.');
        }

        $request->validate([
            'link_inventory_item_id' => ['required', 'exists:inventory_items,id'],
        ]);

        // If booking item wasn't routed, allow resolving by ai_item_name
        $bookingItem = $this->resolveAiSuggestedBookingItem($request, $booking, $bookingItem);

        if (!$bookingItem) {
            return redirect()->back()->with('error', 'AI suggested item could not be located.');
        }

        $linkId = $request->input('link_inventory_item_id');
        $bookingItem->inventory_item_id = $linkId;
        $bookingItem->item_name = InventoryItem::find($linkId)?->name ?? $bookingItem->item_name;
        $bookingItem->save();

        $this->logAuditEvent('booking', 'ai_item_linked', $booking, 'Linked AI item to inventory id ' . $linkId, Auth::id());

        return redirect()->back()->with('success', 'AI suggestion linked to master inventory.');
    }

    /**
     * Promote an AI-suggested booking item into the master inventory.
     */
    public function promoteAiItem(Request $request, Booking $booking, ?BookingItem $bookingItem = null): RedirectResponse
    {
        if (Auth::user()?->role !== 'admin') {
            abort(403, 'Only an authorized admin can promote material suggestions.');
        }

        $request->validate([
            'catalog_name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:50'],
            'is_perishable' => ['nullable', 'boolean'],
        ]);

        $bookingItem = $this->resolveAiSuggestedBookingItem($request, $booking, $bookingItem);

        if (!$bookingItem) {
            Log::warning('promoteAiItem: could not locate AI suggested booking item', ['request' => $request->all(), 'booking_id' => $booking->id]);
            $latestAnalysis = $booking->aiAnalyses()->latest('analyzed_at')->first();
            Log::info('promoteAiItem: latest AI analysis for booking', ['analysis' => $latestAnalysis?->toArray()]);

            // Create master inventory item regardless of AI match
            $inventoryItem = InventoryItem::create([
                'name' => trim($request->input('catalog_name')),
                'category' => $request->input('category', 'misc'),
                'is_perishable' => (bool) $request->input('is_perishable', false),
                'current_stock' => 0,
                'unit_cost' => (float) ($request->input('unit_cost') ?? 0),
                'min_stock' => 0,
                'unit' => $request->input('unit', 'piece'),
            ]);

            // Create a booking item that references the new inventory item so procurement/pivot sync works
            $bookingItem = BookingItem::create([
                'booking_id' => $booking->id,
                'inventory_item_id' => $inventoryItem->id,
                'item_name' => $inventoryItem->name,
                'quantity' => max(1, (float) ($request->input('quantity') ?? 1)),
                'quoted_unit_price' => (float) ($request->input('unit_price') ?? 0),
                'is_ai_suggested' => 1,
            ]);

            $this->logAuditEvent('booking', 'ai_item_promoted', $booking, 'Promoted AI item to inventory id ' . $inventoryItem->id . ' (fallback)', Auth::id());

            return redirect()->back()->with('success', 'AI suggestion promoted into inventory catalog.');
        }

        // Normal path when booking item resolved - use submitted values when provided
        $inventoryItem = InventoryItem::create([
            'name' => trim($request->input('catalog_name')),
            'category' => $request->input('category', 'misc'),
            'is_perishable' => (bool) $request->input('is_perishable', false),
            'current_stock' => 0,
            'unit_cost' => (float) ($request->input('unit_cost') ?? $bookingItem->quoted_unit_price),
            'min_stock' => 0,
            'unit' => $request->input('unit', 'piece'),
        ]);

        $bookingItem->inventory_item_id = $inventoryItem->id;
        $bookingItem->item_name = $inventoryItem->name;
        $bookingItem->save();

        $this->logAuditEvent('booking', 'ai_item_promoted', $booking, 'Promoted AI item to inventory id ' . $inventoryItem->id, Auth::id());

        return redirect()->back()->with('success', 'AI suggestion promoted into inventory catalog.');
    }

    public function confirmMaterial(Request $request, Booking $booking, BookingItem $bookingItem): RedirectResponse
    {
        if (Auth::user()?->role !== 'admin') {
            abort(403, 'Only an authorized admin can confirm material requirements.');
        }

        $bookingItem = $booking->bookingItems()->whereKey($bookingItem->id)->firstOrFail();
        if ($bookingItem->confirmed_at) {
            return redirect()->back()->with('success', 'Material requirement is already confirmed.');
        }

        if (!$bookingItem->inventory_item_id) {
            return redirect()->back()->with('error', 'Link or promote this AI suggestion before confirming it.');
        }

        $bookingItem->confirmed_at = now();
        $bookingItem->save();

        $booking->inventoryItems()->syncWithoutDetaching([
            $bookingItem->inventory_item_id => [
                'quantity' => (float) $bookingItem->quantity,
                'quoted_unit_price' => (float) $bookingItem->quoted_unit_price,
                'is_ai_suggested' => (bool) $bookingItem->is_ai_suggested,
                'procurement_status' => $bookingItem->procurement_status ?? 'pending',
                'suggested_order_date' => $bookingItem->suggested_order_date,
                'suggested_delivery_date' => $bookingItem->suggested_delivery_date,
                'notes' => $bookingItem->notes,
            ],
        ]);

        $this->logAuditEvent(
            'booking',
            'material_confirmed',
            $booking,
            'Confirmed booking material item #' . $bookingItem->id,
            Auth::id(),
            ['booking_item_id' => $bookingItem->id, 'confirmed_at' => null],
            ['booking_item_id' => $bookingItem->id, 'confirmed_at' => $bookingItem->confirmed_at?->toIso8601String()],
            'material_requirement_confirmed'
        );

        return redirect()->back()->with('success', 'Material requirement confirmed for this booking.');
    }

    protected function resolveAiSuggestedBookingItem(Request $request, Booking $booking, ?BookingItem $bookingItem = null): ?BookingItem
    {
        if ($bookingItem) {
            return $bookingItem;
        }

        if ($request->filled('booking_item_id')) {
            $bookingItem = BookingItem::where('booking_id', $booking->id)
                ->where('id', $request->input('booking_item_id'))
                ->first();

            if ($bookingItem) {
                return $bookingItem;
            }
        }

        if ($request->filled('ai_item_name')) {
            $normalizedName = trim(strtolower($request->input('ai_item_name')));
            return BookingItem::where('booking_id', $booking->id)
                ->where('is_ai_suggested', 1)
                ->whereRaw('LOWER(TRIM(item_name)) = ?', [$normalizedName])
                ->first();
        }

        return null;
    }

    protected function confirmedInventoryItems(Booking $booking)
    {
        $bookingItems = $booking->bookingItems()->get();
        $unconfirmedInventoryIds = $bookingItems
            ->filter(fn ($bookingItem) => $bookingItem->confirmed_at === null)
            ->pluck('inventory_item_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique();

        return $booking->inventoryItems()->get()->filter(function ($inventoryItem) use ($unconfirmedInventoryIds): bool {
            return !$unconfirmedInventoryIds->contains((int) $inventoryItem->id);
        })->values();
    }

    public function update(Request $request, Booking $booking): RedirectResponse
    {
        if ($request->filled('resolve_ai_item') || $request->filled('ai_item_name')) {
            $bookingItem = null;

            if ($request->filled('resolve_ai_item')) {
                $bookingItem = $booking->bookingItems()->find($request->input('resolve_ai_item'));
            }

            if (!$bookingItem) {
                $bookingItem = $this->resolveAiSuggestedBookingItem($request, $booking, null);
            }

            if ($bookingItem) {
                $linkId = $request->input('link_inventory_item_id');
                if ($request->filled('link_inventory_item_id')) {
                    $bookingItem->inventory_item_id = $linkId;
                    $bookingItem->item_name = InventoryItem::find($linkId)?->name ?? $bookingItem->item_name;
                    $bookingItem->save();

                    return redirect()->back()->with('success', 'AI suggestion linked to the selected inventory item.');
                }

                if ($request->filled('promote_inventory_name')) {
                    $inventoryItem = InventoryItem::create([
                        'name' => trim($request->input('promote_inventory_name')),
                        'category' => $request->input('promote_inventory_category', 'misc'),
                        'is_perishable' => (bool) $request->input('promote_inventory_is_perishable', false),
                        'current_stock' => 0,
                        'unit_cost' => (float) $bookingItem->quoted_unit_price,
                        'min_stock' => 0,
                        'unit' => $request->input('promote_inventory_unit', 'piece'),
                    ]);

                    $bookingItem->inventory_item_id = $inventoryItem->id;
                    $bookingItem->item_name = $inventoryItem->name;
                    $bookingItem->save();

                    return redirect()->back()->with('success', 'AI suggestion promoted into the inventory catalog.');
                }
            }
        }
        $normalizedStatus = Booking::normalizeStatus($request->input('status'));
        if ($normalizedStatus !== null) {
            $request->merge(['status' => $normalizedStatus]);
        }

        $data = $request->validate([
            'event_type' => ['required', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'venue' => ['required', 'string', 'max:500'],
            'status' => ['required', 'string', 'in:pending,approved,rejected,event_in_progress,event_completed,pending_return,pending_resolution,completed,cancelled,quotation_sent,payment_submitted,payment_pending,confirmed,downpayment_received,declined,change_requested,cancellation_requested'],
            'special_requests' => ['nullable', 'string', 'max:2000'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
            'multiplier' => ['nullable', 'numeric', 'min:1'],
            'final_quoted_price' => ['nullable', 'numeric', 'min:0'],
            'preparation_start_date' => ['nullable', 'date', 'before_or_equal:event_date'],
            'preparation_status' => ['nullable', 'string', 'in:scheduled,in_preparation,ready,completed'],
        ]);

        $previousQuoteTotal = (float) ($booking->final_quoted_price ?? $booking->total_quoted ?? 0);

        $oldStatus = $booking->status;
        $action = $request->input('action', 'save');
        if ($action === 'send_quotation') {
            $this->reconcileBookingInventoryLinks($booking);
            $booking->load('bookingItems.inventoryItem');


            $booking->bookingItems()
                ->where('is_ai_suggested', true)
                ->whereNull('confirmed_at')
                ->whereNotNull('inventory_item_id')
                ->where('quantity', '>', 0)
                ->update(['confirmed_at' => now()]);

            $unconfirmedAiItems = $booking->bookingItems()
                ->where('is_ai_suggested', true)
                ->whereNull('confirmed_at')
                ->where('quantity', '>', 0)
                ->pluck('item_name')
                ->filter()
                ->unique()
                ->values();

            if ($unconfirmedAiItems->isNotEmpty()) {
                return redirect()->back()->with('error', 'Confirm every material requirement before sending the quotation.');
            }
        }
        $booking->event_type = $data['event_type'];
        $booking->event_date = $data['event_date'];
        $booking->venue = $data['venue'];
        $booking->setNormalizedStatus($data['status']);
        $booking->special_requests = $data['special_requests'] ?? $booking->special_requests;
        $booking->handled_by = Auth::id();

        if ($request->has('preparation_start_date')) {
            $booking->preparation_start_date = $request->input('preparation_start_date') ? Carbon::parse($request->input('preparation_start_date'))->toDateString() : null;
        }
        if ($request->filled('preparation_status')) {
            $booking->preparation_status = $request->input('preparation_status');
        }

        if ($action === 'send_quotation') {
            if (isset($data['multiplier'])) {
                $booking->multiplier = $data['multiplier'];
            }
            if (isset($data['final_quoted_price'])) {
                $booking->final_quoted_price = $data['final_quoted_price'];
                $booking->total_quoted = $data['final_quoted_price'];
            }
        }

        // Process admin note from the booking review form.
        $adminNote = $this->sanitizeAdminNoteMessage(($data['admin_notes'] ?? $data['admin_note'] ?? '')) ?? '';

        // Process item adjustments if provided without appending stale raw item lists to the custom client note.
        $items = $request->input('items', []);
        $newRawMaterialsSum = 0;
        $latestAnalysis = $booking->aiAnalyses()->latest('analyzed_at')->first();
        $analysisMaterials = $latestAnalysis?->suggested_materials ?? [];
        $analysisModified = false;
        $substitutionAuditEntries = [];

        if (!empty($items) && is_array($items)) {
            $itemsToKeep = [];

            foreach ($items as $it) {
                $name = trim((string) ($it['item_name'] ?? $it['name'] ?? 'Item'));
                $qty = isset($it['quantity']) ? (float) $it['quantity'] : (isset($it['adjusted_quantity']) ? (float) $it['adjusted_quantity'] : null);
                $qty = $qty !== null ? $qty : (isset($it['qty']) ? (float) $it['qty'] : null);
                $unitPrice = isset($it['unit_price']) ? (float) $it['unit_price'] : (isset($it['unit_cost']) ? (float) $it['unit_cost'] : null);
                $unavailable = !empty($it['unavailable']) || !empty($it['remove']);
                $inventoryItemId = !empty($it['inventory_item_id']) ? (int) $it['inventory_item_id'] : null;
                $isAiSuggested = !empty($it['is_ai_suggested']) || ($inventoryItemId === null && $name !== '');

                if (!empty($it['booking_item_id'])) {
                    $existingBookingItem = $booking->bookingItems()->find($it['booking_item_id']);
                    if (!$existingBookingItem) {
                        return redirect()->back()->with('error', 'The submitted booking material does not belong to this booking.');
                    }

                    $isAiSuggested = (bool) $existingBookingItem->is_ai_suggested;
                }

                if (!empty($it['booking_item_id'])) {
                    $bookingItem = $booking->bookingItems()->find($it['booking_item_id']);
                    if ($bookingItem) {
                        $updated = false;

                        if ($name !== '' && $bookingItem->item_name !== $name) {
                            $bookingItem->item_name = $name;
                            $updated = true;
                        }

                        if ($inventoryItemId !== null && $bookingItem->inventory_item_id !== $inventoryItemId) {
                            $originalInventoryItem = $bookingItem->inventoryItem;
                            $replacementInventoryItem = InventoryItem::find($inventoryItemId);

                            if ($originalInventoryItem) {
                                if (!$replacementInventoryItem || !$originalInventoryItem->substitutes()->whereKey($replacementInventoryItem->id)->exists()) {
                                    return redirect()->back()->with('error', 'The selected substitute is not designated for this material.');
                                }
                            } else {
                                if (!$replacementInventoryItem) {
                                    return redirect()->back()->with('error', 'The selected inventory item is invalid.');
                                }
                            }

                            if (Auth::user()?->role !== 'admin') {
                                abort(403, 'Only an authorized admin can substitute booking materials.');
                            }

                            $substitutionAuditEntries[] = [
                                'booking_item_id' => $bookingItem->id,
                                'original_inventory_item_id' => $originalInventoryItem?->id,
                                'replacement_inventory_item_id' => $replacementInventoryItem->id,
                                'was_confirmed' => $bookingItem->confirmed_at !== null,
                            ];

                            $bookingItem->inventory_item_id = $replacementInventoryItem->id;
                            $bookingItem->item_name = $replacementInventoryItem->name;
                            $name = $replacementInventoryItem->name;
                            $unitPrice = (float) $replacementInventoryItem->unit_cost;
                            $bookingItem->confirmed_at = null;
                            $updated = true;
                        }

                        if ($action === 'send_quotation') {
                            if ($unavailable) {
                                $bookingItem->procurement_status = 'unmatched';
                                $updated = true;
                            } elseif ($qty !== null && $qty <= 0) {
                                $bookingItem->procurement_status = 'out_of_stock';
                                $updated = true;
                            } elseif ($bookingItem->procurement_status === 'unmatched' || $bookingItem->procurement_status === 'out_of_stock') {
                                $bookingItem->procurement_status = 'pending';
                                $updated = true;
                            }
                        }

                        $isSubstitution = $inventoryItemId !== null && $bookingItem->inventory_item_id === $inventoryItemId && isset($replacementInventoryItem);
                        if (in_array($action, ['send_quotation', 'send_note']) || $isSubstitution) {
                            if ($qty !== null && (float) $bookingItem->quantity !== $qty) {
                                $bookingItem->quantity = $qty;
                                $updated = true;
                            }
                            if ($unitPrice !== null && (float) $bookingItem->quoted_unit_price !== $unitPrice) {
                                $bookingItem->quoted_unit_price = $unitPrice;
                                $updated = true;
                            }
                        }

                        if ($bookingItem->is_ai_suggested !== $isAiSuggested) {
                            $bookingItem->is_ai_suggested = $isAiSuggested;
                            $updated = true;
                        }

                        if ($updated) {
                            $bookingItem->save();

                            if (!empty($substitutionAuditEntries)) {
                                $substitution = end($substitutionAuditEntries);
                                if ($substitution['booking_item_id'] === $bookingItem->id) {
                                    $this->logAuditEvent(
                                        'booking',
                                        'material_substituted',
                                        $booking,
                                        'Substituted booking material item #' . $bookingItem->id,
                                        Auth::id(),
                                        $substitution,
                                        array_merge($substitution, [
                                            'confirmed_at' => null,
                                            'replacement_unit_cost' => $unitPrice,
                                        ]),
                                        'material_substituted'
                                    );
                                }
                            }
                        }

                        if ($bookingItem->inventory_item_id && $bookingItem->confirmed_at) {
                            $itemsToKeep[$bookingItem->inventory_item_id] = [
                                'quantity' => (float) $bookingItem->quantity,
                                'quoted_unit_price' => (float) $bookingItem->quoted_unit_price,
                            ];
                        }

                        continue;
                    }
                }

                if (!empty($analysisMaterials) && $name !== '') {
                    foreach ($analysisMaterials as $index => $analysisItem) {
                        if (!empty($analysisItem['item_name']) && strcasecmp(trim($analysisItem['item_name']), $name) === 0) {
                            if ($unavailable) {
                                unset($analysisMaterials[$index]);
                                $analysisModified = true;
                                break;
                            }

                            if ($qty !== null) {
                                $analysisMaterials[$index]['estimated_quantity'] = $qty;
                                $analysisModified = true;
                            }

                            if ($unitPrice !== null) {
                                $analysisMaterials[$index]['estimated_unit_cost_php'] = $unitPrice;
                                $analysisModified = true;
                            }

                            break;
                        }
                    }
                }

                if ($action === 'send_quotation') {
                    if ($unavailable) {
                        $bookingItem = BookingItem::create([
                            'booking_id' => $booking->id,
                            'inventory_item_id' => $inventoryItemId,
                            'item_name' => $name,
                            'quantity' => $qty !== null ? max(0, $qty) : 0,
                            'quoted_unit_price' => $unitPrice !== null ? $unitPrice : 0,
                            'is_ai_suggested' => $isAiSuggested,
                            'procurement_status' => 'unmatched',
                        ]);
                        continue;
                    }
                } else {
                    if ($unavailable) {
                        continue;
                    }
                }

                $inventoryItem = null;
                if ($inventoryItemId) {
                    $inventoryItem = InventoryItem::find($inventoryItemId);
                }

                if (!$inventoryItem && $name !== '' && !$isAiSuggested) {
                    $inventoryItem = InventoryItem::where('name', $name)->first();
                }

                if (!$inventoryItem && $name !== '') {
                    $inventoryItem = InventoryItem::create([
                        'name' => $name,
                        'category' => 'misc',
                        'is_perishable' => false,
                        'current_stock' => 0,
                        'unit_cost' => $unitPrice !== null ? $unitPrice : 0,
                        'min_stock' => 0,
                        'unit' => 'piece',
                    ]);
                }

                if ($action === 'send_quotation') {
                    $bookingItem = BookingItem::create([
                        'booking_id' => $booking->id,
                        'inventory_item_id' => $inventoryItem?->id,
                        'item_name' => $name,
                        'quantity' => $qty !== null ? max(0, $qty) : 0,
                        'quoted_unit_price' => $unitPrice !== null ? $unitPrice : 0,
                        'is_ai_suggested' => $isAiSuggested,
                        'procurement_status' => ($qty !== null && $qty <= 0) ? 'out_of_stock' : ($inventoryItem ? 'pending' : 'unmatched'),
                    ]);
                } else {
                    // Do not create new items if not sending quotation
                    $bookingItem = null;
                }

                if ($bookingItem && $inventoryItem && $bookingItem->confirmed_at) {
                    if ($qty !== null && $qty <= 0) {
                        continue;
                    }

                    $updateData = [];
                    if ($qty !== null) {
                        $updateData['quantity'] = $qty;
                    }
                    if ($unitPrice !== null) {
                        $updateData['quoted_unit_price'] = $unitPrice;
                    }
                    $itemsToKeep[$inventoryItem->id] = $updateData;
                }
            }

            if ($latestAnalysis && $analysisModified) {
                $latestAnalysis->suggested_materials = array_values($analysisMaterials);
                $latestAnalysis->save();
            }

            // Process batch promotions submitted inline in the main form
            if ($request->input('action') === 'promote_selected') {
                foreach ($items as $it) {
                    if (!empty($it['promote_catalog_name'])) {
                        $catName = trim((string) $it['promote_catalog_name']);
                        if ($catName === '') continue;

                        $inventoryItem = InventoryItem::create([
                            'name' => $catName,
                            'category' => $it['promote_category'] ?? 'misc',
                            'is_perishable' => !empty($it['promote_is_perishable']),
                            'current_stock' => 0,
                            'unit_cost' => (float) ($it['unit_price'] ?? 0),
                            'min_stock' => 0,
                            'unit' => $it['promote_unit'] ?? 'piece',
                        ]);

                        if (!empty($it['booking_item_id'])) {
                            $bItem = BookingItem::find($it['booking_item_id']);
                            if ($bItem) {
                                $bItem->inventory_item_id = $inventoryItem->id;
                                $bItem->item_name = $inventoryItem->name;
                                $bItem->save();
                            }
                        } else {
                            $bItem = BookingItem::create([
                                'booking_id' => $booking->id,
                                'inventory_item_id' => $inventoryItem->id,
                                'item_name' => $inventoryItem->name,
                                'quantity' => max(1, (float) ($it['quantity'] ?? 1)),
                                'quoted_unit_price' => (float) ($it['unit_price'] ?? 0),
                                'is_ai_suggested' => 1,
                            ]);
                        }

                        // Ensure pivot will contain this new inventory item
                        $itemsToKeep[$inventoryItem->id] = [
                            'quantity' => (float) ($bItem->quantity ?? ($it['quantity'] ?? 1)),
                            'quoted_unit_price' => (float) ($bItem->quoted_unit_price ?? ($it['unit_price'] ?? 0)),
                        ];
                    }
                }
            }

            $confirmedItemsToKeep = [];
            foreach ($booking->bookingItems()->get() as $confirmedBookingItem) {
                if ($confirmedBookingItem->inventory_item_id) {
                    $confirmedItemsToKeep[$confirmedBookingItem->inventory_item_id] = [
                        'quantity' => (float) $confirmedBookingItem->quantity,
                        'quoted_unit_price' => (float) $confirmedBookingItem->quoted_unit_price,
                    ];
                }
            }
            $booking->inventoryItems()->syncWithoutDetaching($confirmedItemsToKeep);

            $newRawMaterialsSum = 0;
            $updatedPivots = $this->confirmedInventoryItems($booking);
            foreach ($updatedPivots as $pivotItem) {
                $finalQty = $pivotItem->pivot->quantity;
                $finalUnitCost = $pivotItem->pivot->quoted_unit_price;
                $newRawMaterialsSum += ($finalQty * $finalUnitCost);
            }
            $booking->raw_materials_sum = $newRawMaterialsSum;

            if ($action === 'send_quotation') {
                if (!$request->filled('final_quoted_price')) {
                    app(\App\Services\QuotationPricingService::class)->calculateTotals($booking, false);
                }

                if (array_key_exists('final_quoted_price', $data)) {
                    $booking->final_quoted_price = $data['final_quoted_price'] !== null ? (float) $data['final_quoted_price'] : null;
                    if ($booking->final_quoted_price !== null) {
                        $booking->total_quoted = $booking->final_quoted_price;
                    }
                }
            }
        }

        $newPriceTotal = $booking->final_quoted_price;

        $generatedAdjustmentNote = $this->appendGeneratedAdjustmentNote(
            $adminNote,
            $items,
            $this->buildPriceAdjustmentSummary($previousQuoteTotal, $newPriceTotal)
        );

        if (array_key_exists('admin_notes', $data) || array_key_exists('admin_note', $data)) {
            $booking->admin_notes = $generatedAdjustmentNote !== '' ? $generatedAdjustmentNote : null;
        }

        if ($data['status'] === 'declined' && $booking->wasChanged('status')) {
            $booking->cancellation_reason = $adminNote ?: $booking->cancellation_reason;
        }

        // Handle action buttons
        $notificationStatus = null;

        if ($action === 'send_quotation') {
            $customValidUntil = $request->filled('valid_until') ? (string) $request->input('valid_until') : null;

            try {
                app(\App\Services\QuotationIssuanceService::class)->issue($booking, Auth::id(), $customValidUntil);
            } catch (\RuntimeException $e) {
                $booking->status = $oldStatus;
                $booking->save();
                return redirect()->back()->with('error', $e->getMessage());
            }

            $booking->setNormalizedStatus('quotation_sent');
            $notificationStatus = 'quotation_sent';
        } elseif ($action === 'mark_event_in_progress') {
            $hasFreshFlowers = $this->confirmedInventoryItems($booking)->contains(fn ($i) => $i->is_perishable)
                || $booking->bookingItems()->whereHas('inventoryItem', fn ($q) => $q->where('is_perishable', true))->exists();

            if ($hasFreshFlowers && !$booking->areFreshFlowersReady()) {
                $booking->status = $oldStatus;
                $booking->save();
                return redirect()->back()->with('error', 'Unable to start the event: fresh flower procurement readiness must be confirmed by Admin before execution.');
            }

            if (!$this->hasRequiredDispatchOccurred($booking)) {
                $booking->status = $oldStatus;
                $booking->save();
                return redirect()->back()->with('error', 'Cannot start the event because required reusable materials have not been dispatched.');
            }

            foreach ($this->confirmedInventoryItems($booking) as $inventoryItem) {
                if ($inventoryItem->is_perishable) {
                    continue;
                }

                $requiredQty = (float) ($inventoryItem->pivot->quantity ?? 0);
                if ($requiredQty <= 0) {
                    continue;
                }

                $existingReservation = InventoryTransaction::query()
                    ->where('booking_id', $booking->id)
                    ->where('inventory_item_id', $inventoryItem->id)
                    ->where('transaction_type', 'booking_lock')
                    ->where('quantity_change', '<', 0)
                    ->exists();

                if ($existingReservation) {
                    continue;
                }

                $booking->status = $oldStatus;
                $booking->save();

                return redirect()->back()->with('error', 'Unable to start the event because confirmed inventory has not been locked for: ' . $inventoryItem->name . '.');
            }

            $booking->setNormalizedStatus('event_in_progress');
        } elseif ($action === 'mark_event_completed') {
            if (!$this->hasRequiredDispatchOccurred($booking)) {
                $booking->status = $oldStatus;
                return redirect()->back()->with('error', 'Cannot mark event completed: no materials have been dispatched.');
            }

            $booking->setNormalizedStatus('event_completed');
            if ($booking->remaining_balance <= 0.0) {
                $booking->setNormalizedStatus('pending_return');
            }
        } elseif ($action === 'log_final_payment') {
            $remainingToPay = (float) $booking->remaining_balance;
            if ($remainingToPay > 0) {
                $payment = Payment::create([
                    'booking_id' => $booking->id,
                    'quotation_id' => $booking->acceptedQuotation?->id,
                    'amount' => $remainingToPay,
                    'payment_option' => 'full_payment',
                    'amount_paid' => $remainingToPay,
                    'remaining_balance' => 0.00,
                    'payment_type' => 'cash',
                    'reference_number' => 'admin-final-' . $booking->id . '-' . time() . '-' . rand(100, 999),
                    'status' => 'fully_paid',
                    'recorded_by' => Auth::id(),
                    'verified_by' => Auth::id(),
                    'verified_at' => now(),
                ]);

                $this->logAuditEvent('booking', 'payment_verified', $booking, 'Final payment logged by admin', Auth::id(), null, ['payment' => $payment->toArray()], 'final_payment_logged');
            }

            $hasPendingResolution = $booking->returns()
                ->whereHas('returnItems', function ($q) {
                    $q->where('charge_decision', 'pending')
                        ->where(function ($sub) {
                            $sub->whereIn('condition', ['damaged', 'lost', 'mixed'])
                                ->orWhere('quantity_damaged', '>', 0)
                                ->orWhere('quantity_lost', '>', 0);
                        });
                })->exists();

            $hasCompletedReturn = $booking->returns()->where('status', 'Completed')->exists();
            $hasIncompleteReturn = $booking->returns()->where('status', '!=', 'Completed')->exists();
            
            if ($hasPendingResolution) {
                $booking->setNormalizedStatus('pending_resolution');
            } elseif ($hasCompletedReturn && !$hasIncompleteReturn && $booking->fresh()->remaining_balance <= 0) {
                $booking->setNormalizedStatus('completed');
            } elseif ($hasCompletedReturn && !$hasIncompleteReturn) {
                $booking->setNormalizedStatus('event_completed');
            } else {
                $booking->setNormalizedStatus('pending_return');
            }

            $booking->save();
            $this->ensureReturnRecordForBooking($booking);
        } elseif ($action === 'reissue_quotation') {
            $customValidUntil = $request->filled('valid_until') ? (string) $request->input('valid_until') : null;

            try {
                app(\App\Services\QuotationIssuanceService::class)->issue($booking, Auth::id(), $customValidUntil);
            } catch (\RuntimeException $e) {
                $booking->status = $oldStatus;
                $booking->save();
                return redirect()->back()->with('error', $e->getMessage());
            }

            // Admin re-issues the quotation with refreshed pricing window
            $booking->setNormalizedStatus('quotation_sent');
            $notificationStatus = 'quotation_sent';
            // Dismiss any related expired quotation alerts
            \App\Models\AdminAlert::where('type', 'quotation_expired')
                ->where('booking_id', $booking->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);
        } elseif ($action === 'reconfirm_price_unchanged') {
            $tentativeQuote = $booking->tentativeQuotation();
            if (!$tentativeQuote) {
                return redirect()->back()->with('error', 'No tentative quotation pending reconfirmation found for this booking.');
            }

            $tentativeQuote->is_tentative = false;
            $tentativeQuote->reconfirmed_at = now();
            $tentativeQuote->save();

            \App\Models\AdminAlert::where('type', 'price_reconfirmation_due')
                ->where('booking_id', $booking->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            \App\Models\AuditLog::record(
                Auth::id(),
                'price_reconfirmed_unchanged',
                sprintf('Quotation v%d price reconfirmed unchanged at ₱%s by Admin.', $tentativeQuote->version, number_format((float) $tentativeQuote->final_quoted_price, 2)),
                'quotation_reconfirmation',
                [
                    'booking_id' => $booking->id,
                    'quotation_id' => $tentativeQuote->id,
                    'version' => $tentativeQuote->version,
                    'final_quoted_price' => (float) $tentativeQuote->final_quoted_price,
                ]
            );

            // Preserve existing booking status
            $booking->status = $oldStatus;
            $booking->save();

            return redirect()->back()->with('success', 'Price reconfirmed successfully. Current pricing remains in effect.');
        } elseif ($action === 'reconfirm_price_revised') {
            $customValidUntil = $request->filled('valid_until') ? (string) $request->input('valid_until') : null;

            try {
                $newQuote = app(\App\Services\QuotationIssuanceService::class)->reconfirmWithRevision($booking, Auth::id(), $customValidUntil);
            } catch (\RuntimeException $e) {
                $booking->status = $oldStatus;
                $booking->save();
                return redirect()->back()->with('error', $e->getMessage());
            }

            $booking->setNormalizedStatus('quotation_sent');
            $notificationStatus = 'quotation_sent';

            return redirect()->back()->with('success', 'Revised quotation v' . $newQuote->version . ' issued and sent to client for review and acceptance.');
        } elseif ($action === 'accept') {
            if ($booking->status !== 'approved') {
                return redirect()->back()->with('error', 'Only an accepted quotation can be final-approved.');
            }

            $booking->setNormalizedStatus('admin_approved');
            $notificationStatus = 'admin_approved';
        } elseif ($data['status'] === 'declined' && $booking->wasChanged('status')) {
            $notificationStatus = 'declined';
        }

        if ($booking->status !== $oldStatus
            && in_array($booking->status, ['cancelled', 'declined', 'rejected'], true)
            && in_array($oldStatus, ['downpayment_received', 'confirmed', 'in_preparation', 'event_in_progress', 'event_completed'], true)) {
            $this->releaseInventoryForBooking($booking);

            if ($booking->hasDispatchedReusableMaterials()) {
                $this->ensureReturnRecordForBooking($booking);
            }
        }

        if ($booking->status !== $oldStatus && !in_array($booking->status, ['pending', 'quotation_sent', 'approved', 'rejected', 'event_in_progress', 'completed', 'pending_return', 'pending_resolution', 'cancelled', 'declined'], true)) {
            if (!$this->validateBookingInventoryAvailability($booking, $booking->status)) {
                $booking->status = $oldStatus;

                return redirect()->back()->with('error', 'This booking would exceed the available stock for the selected event date.');
            }

            $this->adjustInventoryForBookingStatus($booking, $oldStatus, $booking->status);
        }

        if (in_array($booking->status, ['event_completed', 'pending_return', 'pending_resolution', 'completed'], true) 
            && !in_array($oldStatus, ['event_completed', 'pending_return', 'pending_resolution', 'completed'], true)) {
            if (!$this->hasRequiredDispatchOccurred($booking)) {
                $booking->status = $oldStatus;
                return redirect()->back()->with('error', 'Cannot transition to completion state: no materials have been dispatched.');
            }

            $this->releaseInventoryForBooking(
                $booking,
                'Released unfulfilled reservation upon event completion for booking #' . $booking->id
            );

            if ($booking->hasDispatchedReusableMaterials()) {
                $this->ensureReturnRecordForBooking($booking);
            }
        }

        if ($booking->status === 'completed' && $oldStatus !== 'completed') {
            if (!in_array($oldStatus, ['event_completed', 'pending_return', 'pending_resolution'], true)) {
                $booking->status = $oldStatus;
                return redirect()->back()->with('error', 'Cannot complete booking: the event has not concluded or reached post-event settlement.');
            }

            $remainingBal = (float) $booking->remaining_balance;
            if ($remainingBal > 0) {
                $booking->status = $oldStatus;
                return redirect()->back()->with('error', 'Cannot complete booking: an outstanding balance of ₱' . number_format($remainingBal, 2) . ' remains.');
            }

            if ($booking->hasDispatchedReusableMaterials() || $booking->returns()->exists()) {
                $hasCompletedReturn = $booking->returns()->where('status', 'Completed')->exists();
                $hasIncompleteReturn = $booking->returns()->where('status', '!=', 'Completed')->exists();
                if (!$hasCompletedReturn || $hasIncompleteReturn) {
                    $booking->status = $oldStatus;
                    return redirect()->back()->with('error', 'Cannot complete booking: material return audit has not been completed.');
                }
            }

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
                $booking->status = $oldStatus;
                return redirect()->back()->with('error', 'Cannot complete booking: unresolved damage charges remain pending adjudication.');
            }
        }

        if ($booking->status !== $oldStatus) {
            $this->logAuditEvent('booking', 'status_changed', $booking, 'Booking status changed from ' . $oldStatus . ' to ' . $booking->status, Auth::id());
        }

        if ($booking->wasChanged('preparation_start_date') || $booking->wasChanged('preparation_status')) {
            $this->logAuditEvent(
                'booking',
                'preparation_schedule_updated',
                $booking,
                'Preparation schedule updated for booking #' . $booking->id,
                Auth::id(),
                ['preparation_start_date' => $booking->getOriginal('preparation_start_date'), 'preparation_status' => $booking->getOriginal('preparation_status')],
                ['preparation_start_date' => $booking->preparation_start_date?->toDateString(), 'preparation_status' => $booking->preparation_status],
                'preparation_schedule_updated'
            );
        }

        if ($booking->wasChanged('final_quoted_price') || $booking->wasChanged('total_quoted')) {
            $this->logAuditEvent('booking', 'quote_updated', $booking, 'Quotation updated to ' . $booking->final_quoted_price ?: $booking->total_quoted, Auth::id());
        }

        if ($action === 'send_quotation') {
            // Ensure explicit final quoted override from the request is preserved
            if (array_key_exists('final_quoted_price', $data)) {
                $booking->final_quoted_price = $data['final_quoted_price'] !== null ? (float) $data['final_quoted_price'] : null;
                if ($booking->final_quoted_price !== null) {
                    $booking->total_quoted = $booking->final_quoted_price;
                }
            }

            // Directly persist manual override from the raw request to guarantee persistence
            if ($request->has('final_quoted_price')) {
                $booking->final_quoted_price = $request->input('final_quoted_price') !== null ? (float) $request->input('final_quoted_price') : null;
                if ($booking->final_quoted_price !== null) {
                    $booking->total_quoted = $booking->final_quoted_price;
                }
            }
        }

        $booking->save();

        if ($notificationStatus) {
            $title = match ($notificationStatus) {
                'quotation_sent' => 'Your quotation is ready',
                'payment_pending' => 'Booking accepted — payment is pending',
                'declined' => 'Your booking request was declined',
                default => 'Booking status updated',
            };

            $statusMessage = $adminNote ?: match ($notificationStatus) {
                'quotation_sent' => 'Your updated quotation is ready for review.',
                'payment_pending' => 'Your booking has been accepted and is now awaiting payment verification.',
                'declined' => 'The booking request was declined. Please review the details for next steps.',
                default => 'There is a new booking update from Raflora Enterprises.',
            };

            $appendedNote = trim((string) $generatedAdjustmentNote);
            if ($adminNote !== '' && str_starts_with($appendedNote, $adminNote)) {
                $appendedNote = trim((string) substr($appendedNote, strlen($adminNote)));
                $appendedNote = preg_replace('/^\n+/', '', (string) $appendedNote);
            }

            $message = $statusMessage;
            if (!empty($appendedNote)) {
                $message .= "\n\n" . $appendedNote;
            }

            $this->createClientNotificationForBooking($booking, $title, $message, 'booking_update');
        }

        if ($notificationStatus && $booking->client && filter_var($booking->client->email, FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::to($booking->client->email)->send(new BookingStatusChanged($booking, null, $notificationStatus));
            } catch (\Throwable $e) {
                // If mail fails, still keep the booking update.
                logger()->error('Booking status notification failed: ' . $e->getMessage());
            }
        }

        return redirect()->route('admin.bookings.show', ['booking' => $booking->id])
            ->with('success', 'Booking updated successfully.');
    }

    /**
     * Verify a payment submitted by a client.
     */
    public function verifyPayment(Request $request, Payment $payment): RedirectResponse
    {
        $validated = $request->validate([
            'amount_received' => [
                'nullable', 
                'numeric', 
                'gt:0',
                function ($attribute, $value, $fail) use ($payment) {
                    if (round((float) $value, 2) !== round((float) $payment->amount, 2)) {
                        $fail('The received amount must be exactly equal to the required payment amount (' . number_format((float) $payment->amount, 2) . ').');
                    }
                }
            ],
        ]);

        $insufficientItems = [];

        try {
            DB::transaction(function () use ($request, $payment, $validated, &$insufficientItems) {
                $booking = Booking::with('inventoryItems')->whereKey($payment->booking_id)->lockForUpdate()->first();
                
                if (!$booking) {
                    throw new \Exception('booking_not_found');
                }

                $isPostEvent = in_array($booking->status, ['event_completed', 'pending_return', 'pending_resolution'], true)
                    || $booking->returns()->exists()
                    || $booking->hasDispatchedReusableMaterials();

                if (!in_array($booking->status, ['payment_submitted', 'pending_resolution', 'event_completed', 'pending_return'], true)) {
                    throw new \Exception('invalid_status');
                }

                $payment = Payment::whereKey($payment->id)->lockForUpdate()->first();

                if ($payment->verified_at) {
                    throw new \Exception('already_verified');
                }

                // Lock inventory items ordered by ID to prevent deadlocks
                $itemIds = $booking->inventoryItems->pluck('id')->sort()->values()->toArray();
                if (!empty($itemIds)) {
                    $inventoryItems = InventoryItem::whereIn('id', $itemIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                } else {
                    $inventoryItems = collect();
                }

                // Only enforce immediate physical availability if preparation date is explicitly reached
                $shouldReserveNow = !$isPostEvent && !is_null($booking->preparation_start_date) && !$booking->preparation_start_date->isFuture();

                if ($shouldReserveNow) {
                    // Revalidate availability
                    foreach ($booking->inventoryItems as $item) {
                        $quantity = (float) ($item->pivot->quantity ?? 0);
                        if ($quantity <= 0) {
                            continue;
                        }

                        $inventoryItem = $inventoryItems->get($item->id);
                        if (!$inventoryItem) {
                            continue;
                        }

                        if ($inventoryItem->is_perishable) {
                            continue;
                        }

                        $existingReservation = (float) InventoryTransaction::query()
                            ->where('booking_id', $booking->id)
                            ->where('inventory_item_id', $inventoryItem->id)
                            ->where('transaction_type', 'booking_lock')
                            ->where('quantity_change', '<', 0)
                            ->sum('quantity_change');
                            
                        $existingRelease = (float) InventoryTransaction::query()
                            ->where('booking_id', $booking->id)
                            ->where('inventory_item_id', $inventoryItem->id)
                            ->where('transaction_type', 'booking_release')
                            ->where('quantity_change', '>', 0)
                            ->sum('quantity_change');
                            
                        $netDispatch = abs((float) InventoryTransaction::query()
                            ->where('booking_id', $booking->id)
                            ->where('inventory_item_id', $inventoryItem->id)
                            ->whereIn('transaction_type', ['dispatch', 'dispatch_correction'])
                            ->sum('quantity_change'));

                        $alreadyAllocatedToBooking = abs($existingReservation) - $existingRelease - $netDispatch;
                        
                        if (round($alreadyAllocatedToBooking, 4) < 0) {
                            throw new \LogicException(sprintf('Inconsistent ledger state: Booking allocation cannot be negative (%f).', $alreadyAllocatedToBooking));
                        }
                        
                        // Effective available = global net_available + what this booking already holds
                        $effectiveAvailableStock = (float) $inventoryItem->net_available + $alreadyAllocatedToBooking;

                        if ($effectiveAvailableStock < $quantity) {
                            $insufficientItems[] = $inventoryItem->name;
                        }
                    }

                    if (!empty($insufficientItems)) {
                        throw new \Exception('insufficient_stock');
                    }
                }

                $oldPayment = $payment->toArray();
                $oldBookingStatus = $booking->status;

                $amountReceived = isset($validated['amount_received']) ? (float) $validated['amount_received'] : (float) $payment->amount;

                $payment->status = $payment->payment_option === 'full_payment' ? 'fully_paid' : 'downpayment_received';
                $payment->verified_by = Auth::id();
                $payment->verified_at = now();
                $payment->amount_paid = $amountReceived;
                $payment->save();
                
                $payment->remaining_balance = $booking->fresh()->remaining_balance;
                $payment->save();
                
                $booking = $booking->fresh();
                
                $isPostEvent = in_array($oldBookingStatus, ['event_completed', 'pending_return', 'pending_resolution'], true)
                    || $booking->returns()->exists()
                    || $booking->hasDispatchedReusableMaterials();

                if ($isPostEvent) {
                    $hasPendingResolution = $booking->returns()
                        ->whereHas('returnItems', function ($q) {
                            $q->where('charge_decision', 'pending')
                                ->where(function ($sub) {
                                    $sub->whereIn('condition', ['damaged', 'lost', 'mixed'])
                                        ->orWhere('quantity_damaged', '>', 0)
                                        ->orWhere('quantity_lost', '>', 0);
                                });
                        })->exists();

                    $hasCompletedReturn = $booking->returns()->where('status', 'Completed')->exists();
                    $hasIncompleteReturn = $booking->returns()->where('status', '!=', 'Completed')->exists();

                    if ($booking->remaining_balance <= 0) {
                        if ($hasCompletedReturn && !$hasIncompleteReturn && !$hasPendingResolution) {
                            $booking->setNormalizedStatus('completed');
                        } elseif ($hasPendingResolution) {
                            $booking->setNormalizedStatus('pending_resolution');
                        } else {
                            $booking->setNormalizedStatus('event_completed');
                        }
                    } else {
                        if ($hasPendingResolution) {
                            $booking->setNormalizedStatus('pending_resolution');
                        } else {
                            $booking->setNormalizedStatus('event_completed');
                        }
                    }
                } else {
                    $booking->setNormalizedStatus($payment->payment_option === 'full_payment' ? 'confirmed' : 'downpayment_received');
                }
                
                $booking->save();
                
                // Reservation Integrity
                $this->adjustInventoryForBookingStatus($booking, $oldBookingStatus, $booking->status);

                $this->logAuditEvent(
                    'booking',
                    'payment_verified',
                    $booking,
                    'Payment #' . $payment->id . ' verified by admin: ' . Auth::id(),
                    Auth::id(),
                    ['payment' => $oldPayment, 'booking_status' => $oldBookingStatus],
                    ['payment' => $payment->toArray(), 'booking_status' => $booking->status],
                    'payment_verified'
                );

                if ($oldBookingStatus === 'pending_resolution') {
                    $this->createClientNotificationForBooking($booking, 'Resolution Payment Verified', 'Your payment for resolution/damages has been verified.', 'payment_update');
                } else {
                    $this->createClientNotificationForBooking($booking, 'Payment Verified', 'Your payment has been successfully verified. Your booking is now ' . $booking->status_display_label . '.', 'payment_update');
                }
            });
            
            return back()->with('success', 'Payment verified and booking updated.');

        } catch (\Exception $e) {
            if ($e->getMessage() === 'booking_not_found') {
                return back()->with('error', 'The associated booking could not be found.');
            }
            if ($e->getMessage() === 'invalid_status') {
                return back()->with('error', 'Payment verification requires a submitted payment.');
            }
            if ($e->getMessage() === 'already_verified') {
                return back()->with('warning', 'This payment has already been verified.');
            }
            if (str_starts_with($e->getMessage(), 'insufficient_stock')) {
                if (!empty($insufficientItems)) {
                    return back()->with('error', 'Unable to verify downpayment while stock is insufficient for: ' . implode(', ', array_unique($insufficientItems)) . '.');
                }
                $itemName = str_replace('insufficient_stock_', '', $e->getMessage());
                return back()->with('error', 'Unable to verify downpayment while stock is insufficient for: ' . $itemName . '.');
            }
            
            Log::error('Payment verification failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->with('error', 'An unexpected error occurred during verification. Please try again.');
        }
    }

    protected function hasRequiredDispatchOccurred(Booking $booking): bool
    {
        $confirmedHardware = $this->confirmedInventoryItems($booking)
            ->filter(fn ($item) => !$item->is_perishable);
        
        if ($confirmedHardware->isEmpty()) {
            return true; // No reusable materials required for physical dispatch
        }
        
        $totalNetDispatch = \App\Models\InventoryTransaction::where('booking_id', $booking->id)
            ->whereIn('transaction_type', ['dispatch', 'dispatch_correction'])
            ->whereHas('inventoryItem', fn ($q) => $q->where('is_perishable', false))
            ->sum('quantity_change');
            
        // Dispatch quantity_change is negative, so we use absolute value.
        // A value greater than 0 means at least partial dispatch of reusable materials has occurred.
        return abs((float) $totalNetDispatch) > 0.0;
    }

    /**
     * Reject a payment submitted by a client.
     */
    public function rejectPayment(Request $request, Payment $payment): RedirectResponse
    {
        $booking = $payment->booking()->first();

        if (!$booking) {
            return back()->with('error', 'The associated booking could not be found.');
        }

        if ($payment->status !== 'pending') {
            return back()->with('error', 'Only pending payments can be rejected.');
        }

        if ($payment->verified_at) {
            return back()->with('warning', 'This payment has already been verified and cannot be rejected.');
        }

        DB::transaction(function () use ($payment, $booking) {
            $lockedBooking = \App\Models\Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            
            $oldPayment = $payment->toArray();
            $oldBookingStatus = $lockedBooking->status;

            $payment->status = 'rejected';
            $payment->save();

            $hasReturns = $lockedBooking->returns()->exists();
            $hasDispatches = $lockedBooking->hasDispatchedReusableMaterials();
            $isPostEvent = $hasReturns || $hasDispatches || in_array($oldBookingStatus, ['event_completed', 'pending_return', 'pending_resolution'], true);

            if ($isPostEvent) {
                $hasPendingResolution = $lockedBooking->returns()
                    ->whereHas('returnItems', function ($q) {
                        $q->where('charge_decision', 'pending')
                            ->where(function ($sub) {
                                $sub->whereIn('condition', ['damaged', 'lost', 'mixed'])
                                    ->orWhere('quantity_damaged', '>', 0)
                                    ->orWhere('quantity_lost', '>', 0);
                            });
                    })->exists();

                $hasCompletedReturn = $lockedBooking->returns()->where('status', 'Completed')->exists();

                if ($hasPendingResolution) {
                    $revertStatus = 'pending_resolution';
                } elseif ($hasCompletedReturn) {
                    $revertStatus = 'event_completed';
                } else {
                    $revertStatus = 'pending_return';
                }
            } else {
                $hasPriorVerifiedPayments = $lockedBooking->payments()
                    ->whereIn('status', Booking::VERIFIED_PAYMENT_STATUSES)
                    ->where('id', '!=', $payment->id)
                    ->exists();

                if ($hasPriorVerifiedPayments) {
                    $revertStatus = 'downpayment_received';
                } else {
                    $revertStatus = 'admin_approved';
                }
            }

            $lockedBooking->setNormalizedStatus($revertStatus)->save();

            $this->logAuditEvent(
                'booking',
                'payment_rejected',
                $lockedBooking,
                'Payment #' . $payment->id . ' rejected by admin: ' . Auth::id(),
                Auth::id(),
                ['payment' => $oldPayment, 'booking_status' => $oldBookingStatus],
                ['payment' => $payment->toArray(), 'booking_status' => $lockedBooking->status],
                'payment_rejected'
            );
        });

        return back()->with('success', 'Payment has been rejected. The client can now submit a new payment reference.');
    }

    /**
     * Log final payment for a booking. Creates a Payment if none exists and verifies it in a single transaction.
     */
    public function finalPayment(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'amount_received' => ['required', 'numeric', 'min:0'],
            'payment_type' => ['required', 'string', 'in:gcash,bank_transfer,cash'],
        ]);

        $finalTotal = (float) $booking->total_obligation;
        $amountReceived = (float) $validated['amount_received'];

        DB::transaction(function () use ($booking, $amountReceived, $validated) {
            $lockedBooking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            $totalObligation = (float) $lockedBooking->total_obligation;
            $priorPaid = (float) $lockedBooking->total_paid;
            $remainingBefore = max(0.0, $totalObligation - $priorPaid);

            $newRemaining = max(0.0, $remainingBefore - $amountReceived);
            $isFullyPaid = ($newRemaining <= 0.0);

            $referenceNumber = 'admin-final-' . $lockedBooking->id . '-' . time() . '-' . rand(100, 999);

            $payment = Payment::create([
                'booking_id' => $lockedBooking->id,
                'quotation_id' => $lockedBooking->acceptedQuotation?->id,
                'amount' => $amountReceived,
                'payment_option' => $isFullyPaid ? 'full_payment' : 'downpayment',
                'amount_paid' => $amountReceived,
                'remaining_balance' => $newRemaining,
                'payment_type' => $validated['payment_type'],
                'reference_number' => $referenceNumber,
                'status' => $isFullyPaid ? 'fully_paid' : 'downpayment_received',
                'recorded_by' => Auth::id(),
                'verified_by' => Auth::id(),
                'verified_at' => now(),
            ]);

            $this->logAuditEvent('booking', 'payment_verified', $lockedBooking, 'Final payment recorded and verified by admin', Auth::id(), null, ['payment' => $payment->toArray()], 'final_payment_logged');

            $hasPendingResolution = $lockedBooking->returns()
                ->whereHas('returnItems', function ($q) {
                    $q->where('charge_decision', 'pending')
                        ->where(function ($sub) {
                            $sub->whereIn('condition', ['damaged', 'lost', 'mixed'])
                                ->orWhere('quantity_damaged', '>', 0)
                                ->orWhere('quantity_lost', '>', 0);
                        });
                })->exists();

            $hasCompletedReturn = $lockedBooking->returns()->where('status', 'Completed')->exists();
            $hasIncompleteReturn = $lockedBooking->returns()->where('status', '!=', 'Completed')->exists();

            $oldStatus = $lockedBooking->status;

            if ($newRemaining <= 0.0) {
                if ($hasPendingResolution) {
                    $lockedBooking->setNormalizedStatus('pending_resolution')->save();
                    $this->logAuditEvent('booking', 'status_changed', $lockedBooking, 'Booking set to pending resolution after payment because return items await adjudication', Auth::id(), ['booking_status' => $oldStatus], ['booking_status' => $lockedBooking->status], 'pending_resolution');
                } elseif ($hasCompletedReturn && !$hasIncompleteReturn) {
                    $lockedBooking->setNormalizedStatus('completed')->save();

                    $this->logAuditEvent('booking', 'status_changed', $lockedBooking, 'Booking completed after final payment because return audit was already completed', Auth::id(), ['booking_status' => $oldStatus], ['booking_status' => $lockedBooking->status], 'booking_completed');
                } elseif (in_array($lockedBooking->status, ['event_completed', 'pending_return'], true)) {
                    $lockedBooking->setNormalizedStatus('pending_return')->save();

                    $this->logAuditEvent('booking', 'status_changed', $lockedBooking, 'Booking moved to pending return audit after final payment', Auth::id(), ['booking_status' => $oldStatus], ['booking_status' => $lockedBooking->status], 'pending_return');
                    $this->ensureReturnRecordForBooking($lockedBooking);
                } else {
                    $lockedBooking->save();
                }
            } else {
                if ($hasPendingResolution) {
                    $lockedBooking->setNormalizedStatus('pending_resolution')->save();
                } elseif ($hasCompletedReturn && !$hasIncompleteReturn && $lockedBooking->status !== 'event_completed') {
                    $lockedBooking->setNormalizedStatus('event_completed')->save();

                    $this->logAuditEvent('booking', 'status_changed', $lockedBooking, 'Booking kept as event completed with balance pending after return audit completed', Auth::id(), ['booking_status' => $oldStatus], ['booking_status' => $lockedBooking->status], 'status_updated');
                }
            }
        });

        return redirect()->back()->with('success', 'Final payment processed successfully.');
    }

    /**
     * Decline a booking request.
     */
    public function decline(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        if (in_array($booking->status, ['downpayment_received', 'confirmed'], true)) {
            $this->releaseInventoryForBooking($booking);
        }

        $booking->setNormalizedStatus('declined');
        if (!empty($data['admin_note'])) {
            $booking->cancellation_reason = $data['admin_note'];
        }
        $booking->handled_by = Auth::id();
        $booking->save();

        $declineMessage = trim((string) ($data['admin_note'] ?? 'The booking request was declined. Please review the details for next steps.'));

        $user = \App\Models\User::where('email', $booking->client?->email)->first();
        if ($user) {
            ClientNotification::create([
                'user_id' => $user->id,
                'booking_id' => $booking->id,
                'type' => 'booking_update',
                'title' => 'Your booking request was declined',
                'message' => $declineMessage,
                'is_read' => false,
            ]);
        }

        // notify client
        try {
            if ($booking->client && filter_var($booking->client->email, FILTER_VALIDATE_EMAIL)) {
                Mail::to($booking->client->email)
                    ->send(new BookingStatusChanged($booking, null, 'declined'));
            }
        } catch (\Throwable $e) {
            // logger()->error('Mail send failed: '.$e->getMessage());
        }

        return redirect()->route('admin.bookings')->with('success', 'Booking declined.');
    }

    public function dispatchItems(Request $request, Booking $booking, \App\Services\InventoryDispatchService $dispatchService)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.inventory_item_id' => 'required|integer|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0',
            'reason' => 'required|string|max:255',
        ]);

        try {
            $dispatchService->dispatchItems($booking, $validated['items'], Auth::id(), $validated['reason']);
            return back()->with('success', 'Items dispatched successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Dispatch failed: ' . $e->getMessage());
        }
    }

    public function correctDispatch(Request $request, InventoryTransaction $transaction, \App\Services\InventoryDispatchService $dispatchService)
    {
        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
        ]);

        try {
            $dispatchService->correctDispatch($transaction, $validated['quantity'], Auth::id(), $validated['reason']);
            return back()->with('success', 'Dispatch correction applied successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Correction failed: ' . $e->getMessage());
        }
    }

    /**
     * Admin explicitly confirms fresh flower readiness for an event.
     */
    public function confirmFreshFlowers(Booking $booking): RedirectResponse
    {
        if (Auth::user()?->role !== 'admin') {
            abort(403, 'Unauthorized. Admin authorization required.');
        }

        $perishableItems = $booking->bookingItems()
            ->whereHas('inventoryItem', fn ($q) => $q->where('is_perishable', true))
            ->get();

        if ($perishableItems->isEmpty()) {
            return back()->with('info', 'This booking has no perishable fresh flower items requiring procurement confirmation.');
        }

        DB::transaction(function () use ($booking, $perishableItems) {
            foreach ($perishableItems as $item) {
                $item->procurement_status = 'confirmed';
                $item->save();
            }

            $this->logAuditEvent(
                'booking',
                'fresh_flowers_confirmed',
                $booking,
                'Fresh flower procurement readiness explicitly confirmed by Admin: ' . Auth::id(),
                Auth::id()
            );
        });

        return back()->with('success', 'Fresh flower readiness has been explicitly confirmed for this event.');
    }

    /**
     * Admin approves a physical stock adjustment submitted by Staff.
     */
    public function approveStockAdjustment(Request $request, AdminAlert $alert): RedirectResponse
    {
        if (Auth::user()?->role !== 'admin') {
            abort(403, 'Unauthorized. Admin authorization required.');
        }

        if ($alert->type !== 'physical_count_variance' || !$alert->inventory_item_id) {
            return back()->with('error', 'Invalid adjustment alert.');
        }

        if ($alert->is_read) {
            return back()->with('warning', 'This adjustment has already been reviewed.');
        }

        $validated = $request->validate([
            'admin_reason' => 'nullable|string|max:255',
            'approved_stock' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $alert, $validated) {
            $inventoryItem = InventoryItem::whereKey($alert->inventory_item_id)->lockForUpdate()->firstOrFail();
            
            // Extract the observed stock from alert message if not passed in request
            preg_match('/observed physical count of ([\d\.]+)/', $alert->message, $matches);
            $targetStock = $request->filled('approved_stock')
                ? (float) $validated['approved_stock']
                : (isset($matches[1]) ? (float) $matches[1] : (float) $inventoryItem->current_stock);

            $oldStock = (float) $inventoryItem->current_stock;
            $stockDelta = $targetStock - $oldStock;

            if (abs($stockDelta) >= 0.0001) {
                $inventoryItem->current_stock = max(0.0, $targetStock);
                $inventoryItem->save();

                InventoryTransaction::create([
                    'inventory_item_id' => $inventoryItem->id,
                    'booking_id' => $alert->booking_id,
                    'quantity_change' => $stockDelta,
                    'transaction_type' => 'adjustment',
                    'reason' => $validated['admin_reason'] ?? ('Admin approved physical stock adjustment from alert #' . $alert->id),
                    'performed_by' => Auth::id(),
                ]);
            }

            $alert->update(['is_read' => true]);

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'stock_adjustment_approved',
                'module' => 'inventory',
                'details' => "Admin approved stock adjustment for item #{$inventoryItem->id} ({$inventoryItem->name}) to {$targetStock} (variance: {$stockDelta})",
                'entity_type' => InventoryItem::class,
                'entity_id' => $inventoryItem->id,
            ]);
        });

        return back()->with('success', 'Physical stock adjustment approved and applied.');
    }

    /**
     * Admin rejects a physical stock adjustment submitted by Staff.
     */
    public function rejectStockAdjustment(Request $request, AdminAlert $alert): RedirectResponse
    {
        if (Auth::user()?->role !== 'admin') {
            abort(403, 'Unauthorized. Admin authorization required.');
        }

        if ($alert->type !== 'physical_count_variance') {
            return back()->with('error', 'Invalid adjustment alert.');
        }

        $alert->update(['is_read' => true]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'stock_adjustment_rejected',
            'module' => 'inventory',
            'details' => "Admin rejected physical stock adjustment for alert #{$alert->id}",
            'entity_type' => AdminAlert::class,
            'entity_id' => $alert->id,
        ]);

        return back()->with('success', 'Physical stock adjustment rejected.');
    }
}
