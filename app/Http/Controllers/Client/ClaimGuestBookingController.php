<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\AiAnalysisResult;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\TemporaryGuestBooking;
use App\Services\GeminiVisionService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ClaimGuestBookingController extends Controller
{
    /**
     * Show the claim confirmation page for a temporary guest booking.
     */
    public function show(Request $request, string $token): View|RedirectResponse
    {
        $booking = TemporaryGuestBooking::where('claim_token_hash', hash('sha256', $token))->first();

        if (!$booking) {
            abort(404, 'Booking request not found or token invalid.');
        }

        if ($booking->isClaimed()) {
            return redirect()->route('client.dashboard')->with('error', 'This booking request has already been claimed.');
        }

        if ($booking->isExpired()) {
            abort(403, 'This booking request has expired (24 hours). Please start a new request.');
        }

        // Email match check
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        if (strtolower($user->email) !== strtolower($booking->guest_email)) {
            abort(403, 'Your account email does not match the email used for this request. Please log in with the correct account.');
        }
        
        if (!$user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice')->with('error', 'You must verify your email address before claiming this request.');
        }

        return view('client.claim-booking', [
            'booking' => $booking,
            'token' => $token,
            'emailMatches' => true,
        ]);
    }

    /**
     * Convert the temporary guest booking into a permanent Booking.
     */
    public function claim(Request $request, string $token): RedirectResponse
    {
        $user = Auth::user();

        // Lock the row to prevent concurrent claims
        $tempBooking = TemporaryGuestBooking::where('claim_token_hash', hash('sha256', $token))->lockForUpdate()->first();

        if (!$tempBooking) {
            abort(404, 'Booking request not found or token invalid.');
        }

        if ($tempBooking->isClaimed()) {
            return redirect()->route('client.dashboard')->with('error', 'This booking request has already been claimed.');
        }

        if ($tempBooking->isExpired()) {
            abort(403, 'This booking request has expired (24 hours). Please start a new request.');
        }

        if (strtolower($user->email) !== strtolower($tempBooking->guest_email)) {
            abort(403, 'Your account email does not match the email used for this request. Please log in with the correct account.');
        }
        
        if (!$user->hasVerifiedEmail()) {
            abort(403, 'You must verify your email address before claiming this request.');
        }

        try {
            DB::transaction(function () use ($tempBooking, $user, $token) {
                // Re-evaluate logic from GuestBookingController store to convert TemporaryGuestBooking into Booking

                $priceValidUntil = Carbon::now()->addDays(7)->toDateString();
                $suggestedProcurement = Carbon::parse($tempBooking->event_date)->subDays(7)->toDateString();

                $client = \App\Models\Client::firstOrCreate(
                    ['email' => $user->email],
                    [
                        'full_name' => $tempBooking->guest_name,
                        'phone' => $tempBooking->guest_phone,
                        'address' => $tempBooking->guest_address,
                    ]
                );

                $booking = Booking::create([
                    'client_id' => $client->id,
                    'guest_name' => $tempBooking->guest_name,
                    'guest_email' => $tempBooking->guest_email,
                    'guest_phone' => $tempBooking->guest_phone,
                    'guest_address' => $tempBooking->guest_address,
                    'handled_by' => null,
                    'event_type' => $tempBooking->event_type,
                    'event_date' => $tempBooking->event_date,
                    'event_time' => $tempBooking->event_time,
                    'event_size' => $tempBooking->guest_count,
                    'table_count' => $tempBooking->table_count,
                    'venue' => $tempBooking->venue,
                    'special_requests' => $tempBooking->special_requests,
                    'inspiration_image' => $tempBooking->inspiration_image_path,
                    'status' => 'pending',
                    'total_quoted' => 0,
                    'raw_materials_sum' => 0,
                    'multiplier' => 1.0,
                    'final_quoted_price' => 0,
                    'price_valid_until' => $priceValidUntil,
                    'suggested_procurement_date' => $suggestedProcurement,
                    'ai_analysis_data' => $tempBooking->analysis_data,
                    'package_id' => $tempBooking->package_id,
                ]);
                
                // Also set the token on the new booking to allow tracking.
                $booking->guest_access_token = $token;
                $booking->save();

                if ($tempBooking->booking_type === 'preset' && $tempBooking->package_id) {
                    $selectedPackage = \App\Models\Package::find($tempBooking->package_id);
                    if ($selectedPackage) {
                        $booking->total_quoted = $selectedPackage->price;
                        $booking->raw_materials_sum = $selectedPackage->price;
                        $booking->final_quoted_price = $selectedPackage->price;
                        if (!$booking->inspiration_image) {
                            $booking->inspiration_image = $selectedPackage->image_path;
                        }
                        $booking->save();

                        // Attach items
                        $packageItems = $selectedPackage->inventoryItems;
                        if ($packageItems->count() > 0) {
                            foreach ($packageItems as $item) {
                                BookingItem::create([
                                    'booking_id' => $booking->id,
                                    'inventory_item_id' => $item->id,
                                    'item_name' => $item->name,
                                    'quantity' => $item->pivot->quantity,
                                    'quoted_unit_price' => $item->unit_cost,
                                    'is_ai_suggested' => false,
                                    'confirmed_at' => now(),
                                    'procurement_status' => 'pending',
                                    'suggested_order_date' => $booking->suggested_procurement_date,
                                    'notes' => 'From Package: ' . $selectedPackage->title,
                                ]);
                            }
                        } else {
                            BookingItem::create([
                                'booking_id' => $booking->id,
                                'inventory_item_id' => null,
                                'item_name' => $selectedPackage->title,
                                'quantity' => 1,
                                'quoted_unit_price' => $selectedPackage->price,
                                'is_ai_suggested' => false,
                                'confirmed_at' => now(),
                                'procurement_status' => 'pending',
                                'suggested_order_date' => $booking->suggested_procurement_date,
                                'notes' => 'From Package (fallback): ' . $selectedPackage->title,
                            ]);
                        }
                    }
                } else {
                    // Custom AI booking
                    if ($tempBooking->analysis_data && isset($tempBooking->analysis_data['suggested_materials'])) {
                        $materials = $tempBooking->analysis_data['suggested_materials'];
                        
                        // We use the helper logic to persist AI materials, which we need to reproduce here or put in a service.
                        $totalCost = 0.0;
                        $persistedMaterials = [];
                        
                        foreach ($materials as $material) {
                            $itemName = trim((string) ($material['item_name'] ?? ''));
                            if ($itemName === '') continue;

                            $quantity = (float) ($material['quantity'] ?? $material['estimated_quantity'] ?? 1);
                            $unitCost = (float) ($material['unit_cost_php'] ?? $material['estimated_unit_cost_php'] ?? $material['estimated_unit_cost'] ?? 0);
                            
                            if ($quantity <= 0 || $unitCost <= 0) continue;

                            $inventoryItem = \App\Models\InventoryItem::whereRaw('LOWER(TRIM(name)) = ?', [strtolower($itemName)])->first();
                            $actualUnitPrice = $inventoryItem ? (float) $inventoryItem->unit_cost : 0;
                            
                            $existingItem = null;
                            if ($inventoryItem) {
                                $existingItem = BookingItem::where('booking_id', $booking->id)
                                    ->where('inventory_item_id', $inventoryItem->id)
                                    ->first();
                            }

                            if ($existingItem) {
                                $existingItem->quantity += $quantity;
                                $existingItem->save();
                            } else {
                                $existingItem = BookingItem::create([
                                    'booking_id' => $booking->id,
                                    'inventory_item_id' => $inventoryItem ? $inventoryItem->id : null,
                                    'item_name' => $itemName,
                                    'quantity' => $quantity,
                                    'quoted_unit_price' => $actualUnitPrice,
                                    'ai_recommended_price' => $unitCost,
                                    'is_ai_suggested' => true,
                                    'procurement_status' => 'pending',
                                    'suggested_order_date' => $booking->suggested_procurement_date,
                                    'notes' => 'AI suggested',
                                ]);
                            }
                            
                            $material['inventory_item_id'] = $inventoryItem ? $inventoryItem->id : null;
                            $persistedMaterials[] = $material;
                        }

                        AiAnalysisResult::create([
                            'booking_id' => $booking->id,
                            'raw_gemini_response' => json_encode($tempBooking->analysis_data),
                            'suggested_materials' => $persistedMaterials,
                            'analyzed_at' => Carbon::now(),
                        ]);

                        app(\App\Services\QuotationPricingService::class)->calculateTotals($booking, false);
                    }
                }

                $tempBooking->claimed_at = Carbon::now();
                $tempBooking->client_id = $client->id;
                $tempBooking->save();
            });

            return redirect()->route('bookings')
                ->with('success', 'Booking request claimed successfully! It is now in your bookings list.');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed to claim guest booking: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->route('client.dashboard')->with('error', 'There was an error claiming your request. Please try again.');
        }
    }
}
