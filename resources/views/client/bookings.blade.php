<x-app-layout title="My Bookings">
    <x-client-layout active="bookings">
        <section class="rf-panel mx-auto max-w-6xl p-5 sm:p-8">
            <div class="mb-8 flex flex-col items-start justify-between gap-5 md:flex-row md:items-center">
                <div class="flex items-center">
                    <h1 class="rf-page-title text-2xl sm:text-3xl font-bold text-slate-900">My Bookings</h1>
                    <x-info-popover title="My Bookings">
                        Manage your event requests, view quotations, and track booking status from one place.
                    </x-info-popover>
                </div>
                <a href="{{ route('booking.start') }}" class="rf-btn rf-btn-primary shrink-0">
                    <i class="fa-solid fa-plus mr-1.5" aria-hidden="true"></i>
                    New Booking
                </a>
            </div>

            @if($bookings->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/50 p-12 text-center text-slate-500 shadow-sm mt-8">
                    <p class="text-base font-medium">No bookings found. Start by creating a new booking.</p>
                </div>
            @else
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 xl:grid-cols-3">
                    @foreach($bookings as $booking)
                        @php
                            $statusClass = match ($booking->status) {
                                'quotation_sent', 'payment_pending', 'admin_approved' => 'rf-badge--warning',
                                'downpayment_received', 'completed', 'confirmed', 'fully_paid' => 'rf-badge--success',
                                'cancelled', 'declined', 'rejected' => 'rf-badge--danger',
                                default => 'rf-badge--primary',
                            };
                            $actionHint = match ($booking->status) {
                                'pending' => 'Under review by Raflora',
                                'quotation_sent' => 'Quotation ready for your review',
                                'change_requested' => 'Quotation revision in progress',
                                'approved' => 'Awaiting admin final approval',
                                'admin_approved' => 'Payment required to secure booking',
                                'payment_pending', 'payment_submitted' => 'Payment under verification',
                                'downpayment_received', 'confirmed' => 'Booking confirmed • Preparation underway',
                                'in_preparation' => 'Event materials in preparation',
                                'event_in_progress' => 'Event in progress',
                                'event_completed' => ((float) $booking->remaining_balance > 0) ? 'Event concluded • Final payment due' : 'Event concluded',
                                'pending_return' => 'Material return & inspection in progress',
                                'pending_resolution' => 'Return assessment pending review',
                                'completed', 'fully_paid' => 'Event successfully completed',
                                'cancelled' => 'Booking cancelled',
                                'declined', 'rejected' => 'Booking declined',
                                default => null,
                            };
                        @endphp
                        <div class="flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between border-b border-slate-100 bg-slate-50/50 p-5">
                                <h3 class="text-lg font-bold text-slate-900">{{ ucfirst($booking->event_type ?? 'Event') }}</h3>
                                <div class="shrink-0">
                                    <span class="rf-badge {{ $statusClass }}">
                                        <span aria-hidden="true">•</span>
                                        {{ $booking->client_status_label }}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="flex flex-1 flex-col p-5">
                                <dl class="mb-6 space-y-4 text-sm">
                                    @if($booking->package)
                                    <div>
                                        <dt class="font-semibold text-slate-500">Package</dt>
                                        <dd class="mt-1 font-medium text-slate-900">{{ $booking->package->title }}</dd>
                                    </div>
                                    @endif
                                    <div>
                                        <dt class="font-semibold text-slate-500">Date &amp; Time</dt>
                                        <dd class="mt-1 font-medium text-slate-900">{{ optional($booking->event_date)->format('F j, Y') ?? 'TBD' }} at {{ $booking->event_time ?? 'TBD' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-semibold text-slate-500">Venue</dt>
                                        <dd class="mt-1 font-medium text-slate-900">{{ $booking->venue ?? 'TBD' }}</dd>
                                    </div>
                                    @if($booking->event_size)
                                    <div>
                                        <dt class="font-semibold text-slate-500">Event Size</dt>
                                        <dd class="mt-1 font-medium text-slate-900">{{ number_format($booking->event_size) }} guests</dd>
                                    </div>
                                    @endif
                                </dl>

                                @if($actionHint)
                                    <div class="mb-4 rounded-lg bg-slate-50 border border-slate-200/80 px-3 py-2 text-xs text-slate-600 font-medium flex items-center gap-2">
                                        <i class="fa-solid fa-circle-info text-slate-400 text-[11px]" aria-hidden="true"></i>
                                        <span>{{ $actionHint }}</span>
                                    </div>
                                @endif
                                
                                <div class="mt-auto pt-4 border-t border-slate-100">
                                    <a href="{{ route('bookings.analysis', ['booking' => $booking->id]) }}" class="rf-btn rf-btn-outline w-full justify-center">View Booking</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

    </x-client-layout>
</x-app-layout>
