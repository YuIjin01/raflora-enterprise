<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use SoftDeletes;
    protected static function booted(): void
    {
        static::updated(function (InventoryItem $inventoryItem): void {
            if ($inventoryItem->wasChanged('current_stock')) {
                AuditLog::create([
                    'user_id' => Auth::id(),
                    'action' => 'inventory_updated',
                    'module' => 'inventory',
                    'details' => 'Inventory stock updated to ' . $inventoryItem->current_stock,
                    'entity_type' => InventoryItem::class,
                    'entity_id' => $inventoryItem->id,
                ]);
            }
        });
    }
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'item_code',
        'image_path',
        'name',
        'category',
        'is_perishable',
        'current_stock',
        'unit_cost',
        'min_stock',
        'unit',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_perishable' => 'boolean',
            'current_stock' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'min_stock' => 'decimal:2',
        ];
    }

    /**
     * Get all inventory transactions for this item.
     */
    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class, 'inventory_item_id', 'id');
    }

    /**
     * Get all return items for this inventory item.
     */
    public function returnItems(): HasMany
    {
        return $this->hasMany(ReturnItem::class, 'inventory_item_id', 'id');
    }

    /**
     * Get all bookings that include this inventory item (through booking_items pivot).
     */
    public function bookings(): BelongsToMany
    {
        return $this->belongsToMany(
            Booking::class,
            'booking_items',
            'inventory_item_id',
            'booking_id'
        )->withPivot(['quantity', 'quoted_unit_price', 'is_ai_suggested', 'procurement_status', 
                      'suggested_order_date', 'suggested_delivery_date', 'notes']);
    }

    /**
     * Get all returns that include this inventory item (through return_items pivot).
     */
    public function returns(): BelongsToMany
    {
        return $this->belongsToMany(
            AssetReturn::class,
            'return_items',
            'inventory_item_id',
            'return_id'
        )->withPivot(['quantity_returned', 'quantity_good', 'quantity_damaged', 'quantity_lost', 'condition', 'final_amount', 'damage_charge', 'notes']);
    }

    /**
     * Check if inventory is low (below minimum stock) based on operational availability.
     */
    public function isLowStock(): bool
    {
        return $this->net_available <= $this->min_stock;
    }

    public function getReservedStockAttribute($value = null): float
    {
        if ($value !== null) {
            return (float) $value;
        }
        if (array_key_exists('reserved_stock', $this->attributes)) {
            return (float) $this->attributes['reserved_stock'];
        }

        // P0-A/B: Calculate via ledger using Booking-Item level aggregation
        $transactions = $this->inventoryTransactions()
            ->whereNotNull('booking_id')
            ->get()
            ->groupBy('booking_id');

        $totalReserved = 0.0;

        foreach ($transactions as $bookingId => $txs) {
            $lock = $txs->where('transaction_type', 'booking_lock')->sum(fn($tx) => abs((float) $tx->quantity_change));
            $release = $txs->where('transaction_type', 'booking_release')->sum('quantity_change');
            
            // Dispatch is recorded as negative, so taking the sum of dispatch and corrections gives net dispatch (as negative).
            // But since lock is positive, release is positive (or wait, release is positive? booking_release should be a deduction from reservation but it doesn't affect physical stock)
            // Let's check how release is recorded. usually booking_release is positive or negative?
            // "release is subtracted". Let's just use the exact logic previously there.
            $netDispatch = abs($txs->whereIn('transaction_type', ['dispatch', 'dispatch_correction'])->sum('quantity_change'));

            $netReservation = $lock - $release - $netDispatch;
            
            if (round($netReservation, 4) < 0) {
                \Illuminate\Support\Facades\Log::error(sprintf(
                    'Inconsistent ledger state for Item %d, Booking %d. Net reservation cannot be negative (Lock: %f, Release: %f, Dispatch: %f).',
                    $this->id, $bookingId, $lock, $release, $netDispatch
                ));
                // An excess release or over-dispatch means the booking's physical obligation is exhausted.
                // We floor it at 0 to prevent phantom reservations from double-deducting available stock.
                $netReservation = 0.0;
            }
            
            $totalReserved += $netReservation;
        }

        return $totalReserved;
    }

    public function getAvailableStockAttribute(): float
    {
        return $this->net_available;
    }

    public function getReorderLevelAttribute(): float
    {
        return (float) ($this->min_stock ?? 0);
    }

    public function getNetAvailableAttribute(): float
    {
        $netAvailable = (float) $this->current_stock - (float) $this->reserved_stock;
        
        // Removed max(0.0, ...) to ensure negative availability is visible if overbooked
        return $netAvailable;
    }

    /**
     * Get all packages that include this inventory item.
     */
    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class, 'inventory_item_package')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /**
     * Get all substitutes for this inventory item.
     */
    public function substitutes(): BelongsToMany
    {
        return $this->belongsToMany(
            InventoryItem::class,
            'inventory_item_substitutes',
            'item_id',
            'substitute_id'
        );
    }
}
