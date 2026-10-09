<x-app-layout title="Booking History">
    <x-client-layout active="booking-history">
        <section class="rf-panel mx-auto max-w-6xl p-5 sm:p-8">
            <div class="mb-8 flex items-center">
                <h1 class="rf-page-title text-2xl sm:text-3xl font-bold text-slate-900">Booking History</h1>
                <x-info-popover title="Booking History">
                    Track your previous bookings and review the status of each event.
                </x-info-popover>
            </div>

            @if($bookings->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center text-slate-500">
                    <p class="text-lg font-medium">You have no past bookings yet.</p>
                </div>
            @else
                <div class="overflow-x-auto rounded-2xl border border-slate-200">
                    <table class="rf-table min-w-[760px]">
                        <thead>
                            <tr class="border-b border-white/10">
                                <th scope="col">Booking #</th>
                                <th scope="col">Event</th>
                                <th scope="col">Date</th>
                                <th scope="col">Status</th>
                                <th scope="col">Quoted total</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bookings as $booking)
                                <tr>
                                    <td class="font-medium text-slate-900">#{{ $booking->id }}</td>
                                    <td class="capitalize">{{ $booking->event_type }}</td>
                                    <td>{{ optional($booking->event_date)->format('F j, Y') }}</td>
                                    <td>
                                        @php
                                            $statusClass = match ($booking->status) {
                                                'quotation_sent', 'payment_pending' => 'rf-badge--warning',
                                                'downpayment_received', 'confirmed', 'fully_paid', 'completed' => 'rf-badge--success',
                                                'cancelled', 'declined', 'rejected' => 'rf-badge--danger',
                                                default => 'rf-badge--primary',
                                            };
                                        @endphp
                                        <span class="rf-badge {{ $statusClass }}"><span aria-hidden="true">•</span>
                                            {{ $booking->client_status_label ?? $booking->status_display_label }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="space-y-1">
                                            <div>Quote: ₱{{ number_format((float) ($booking->total_obligation ?? 0), 2) }}</div>
                                            <div>Balance: ₱{{ number_format((float) ($booking->remaining_balance ?? 0), 2) }}</div>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="{{ route('bookings.analysis', ['booking' => $booking->id]) }}" class="rf-btn rf-btn-outline text-sm">View details</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </x-client-layout>
</x-app-layout>
