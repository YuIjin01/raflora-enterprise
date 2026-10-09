<?php

namespace App\Services;

use App\Models\Booking;

class QuotationPricingService
{
    /**
     * Calculate and update the pricing totals for a booking.
     * 
     * This service centralizes the logic for calculating:
     * - raw_materials_sum (based on quoted_unit_price of items)
     * - multiplier
     * - labor
     * - final_quoted_price
     * 
     * It does not issue quotations or handle payments.
     */
    public function calculateTotals(Booking $booking, bool $save = true): void
    {
        if (!empty($booking->package_id)) {
            // Package bookings have fixed pricing, do not recalculate based on materials
            return;
        }

        $rawMaterialsSum = 0.0;
        foreach ($booking->bookingItems as $item) {
            $rawMaterialsSum += ($item->quantity * $item->quoted_unit_price);
        }
        $booking->raw_materials_sum = round($rawMaterialsSum, 2);
        
        $multiplier = $booking->multiplier ?? 3.0;
        $booking->multiplier = $multiplier;

        $laborAmount = 0.0;
        if ($booking->labor_method === 'percentage') {
            $laborAmount = $booking->raw_materials_sum * ((float) $booking->labor_rate / 100);
        } elseif ($booking->labor_method === 'fixed') {
            $laborAmount = (float) $booking->labor_rate;
        }

        $finalPrice = ($booking->raw_materials_sum * $multiplier) + $laborAmount;
        $booking->final_quoted_price = round($finalPrice, 2);
        
        $booking->total_quoted = $booking->final_quoted_price;

        if ($save) {
            $booking->save();
        }
    }
}
