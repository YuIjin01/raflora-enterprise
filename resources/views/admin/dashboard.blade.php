<x-admin-layout title="Admin Dashboard">
    <!-- Statistics Cards: Summary metrics for admin monitoring -->
    <div class="mb-6 grid grid-cols-3 gap-2 sm:gap-4">
        <div class="rf-panel min-w-0 p-3 sm:p-6">
            <div class="flex flex-col items-start gap-2 sm:flex-row sm:items-center sm:gap-4">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-purple-100 sm:h-12 sm:w-12">
                    <i class="fa-solid fa-calendar-days text-purple-700 text-xl"></i>
                </div>
                <div>
                    <p class="text-[11px] font-medium leading-tight text-gray-500 sm:text-sm">Total Bookings</p>
                    <p class="text-xl font-bold text-gray-800 sm:text-2xl">{{ $totalBookings }}</p>
                </div>
            </div>
        </div>
        <div class="rf-panel min-w-0 p-3 sm:p-6">
            <div class="flex flex-col items-start gap-2 sm:flex-row sm:items-center sm:gap-4">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 sm:h-12 sm:w-12">
                    <i class="fa-solid fa-clock text-amber-700 text-xl"></i>
                </div>
                <div>
                    <p class="text-[11px] font-medium leading-tight text-gray-500 sm:text-sm">Pending Bookings</p>
                    <p class="text-xl font-bold text-gray-800 sm:text-2xl">{{ $pendingBookings }}</p>
                </div>
            </div>
        </div>
        <div class="rf-panel min-w-0 p-3 sm:p-6">
            <div class="flex flex-col items-start gap-2 sm:flex-row sm:items-center sm:gap-4">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-100 sm:h-12 sm:w-12">
                    <i class="fa-solid fa-users text-emerald-700 text-xl"></i>
                </div>
                <div>
                    <p class="text-[11px] font-medium leading-tight text-gray-500 sm:text-sm">Total Users</p>
                    <p class="text-xl font-bold text-gray-800 sm:text-2xl">{{ $totalUsers }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Alerts Panel -->
    @if($activeAlerts->count() > 0)
    <section class="mb-6 overflow-hidden rounded-2xl border border-amber-200 bg-white shadow-sm" aria-labelledby="active-alerts-heading">
        <div class="p-5 border-b border-amber-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-amber-100 flex items-center justify-center">
                    <i class="fa-solid fa-bell text-amber-600 text-sm"></i>
                </div>
                <div>
                    <h2 id="active-alerts-heading" class="text-base font-bold text-gray-800">Active Alerts</h2>
                    <p class="text-xs text-gray-500">{{ $activeAlerts->count() }} unread alert{{ $activeAlerts->count() !== 1 ? 's' : '' }} requiring attention</p>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.alerts.read-all') }}">
                @csrf
                <button type="submit" class="text-xs text-gray-500 hover:text-gray-700 font-medium border border-gray-200 rounded-lg px-3 py-1.5 hover:bg-gray-50 transition">
                    Dismiss All
                </button>
            </form>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach($activeAlerts as $alert)
            <div class="flex items-start gap-4 p-4 hover:bg-amber-50/30 transition group">
                <div class="mt-0.5 shrink-0 w-8 h-8 rounded-full flex items-center justify-center {{ $alert->type === 'inventory_shortage' ? 'bg-red-100' : 'bg-amber-100' }}">
                    <i class="fa-solid {{ $alert->type === 'inventory_shortage' ? 'fa-cubes-stacked text-red-600' : 'fa-clock text-amber-600' }} text-xs"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-0.5">
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $alert->type === 'inventory_shortage' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }}">
                            {{ match ($alert->type) {
                                'inventory_shortage' => 'Stock Shortage',
                                'quotation_expired' => 'Quotation Expired',
                                'meeting_requested' => 'Meeting Request',
                                'meeting_cancelled' => 'Meeting Cancelled',
                                default => \Illuminate\Support\Str::headline((string) $alert->type),
                            } }}
                        </span>
                        <span class="text-xs text-gray-400">{{ $alert->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="text-sm font-semibold text-gray-800 mb-0.5">{{ $alert->title }}</p>
                    <p class="text-xs text-gray-500 leading-relaxed">{{ $alert->message }}</p>
                    <div class="flex items-center gap-3 mt-1.5">
                        @if($alert->booking_id)
                            <a href="{{ route('admin.bookings.show', $alert->booking_id) }}" class="text-xs font-semibold text-purple-600 hover:text-purple-800 transition">
                                View Booking #{{ $alert->booking_id }} &rarr;
                            </a>
                        @endif
                        @if($alert->type === 'inventory_shortage')
                            <a href="{{ route('admin.inventory.index') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition">
                                Manage Inventory &rarr;
                            </a>
                        @endif
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.alerts.read', $alert) }}" class="shrink-0">
                    @csrf
                    <button type="submit" title="Dismiss" class="opacity-0 group-hover:opacity-100 transition w-7 h-7 rounded-full text-gray-400 hover:text-gray-600 hover:bg-gray-100 flex items-center justify-center">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </form>
            </div>
            @endforeach
        </div>
    </section>
    @endif

    <div class="mb-6 rounded-2xl border border-purple-100 bg-purple-50/70 p-4 text-sm text-purple-800" role="note">
        <div class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
            <div>
                <p class="font-semibold">Calendar view</p>
                <p class="text-purple-700">Upcoming bookings are tagged as Delivery / Home Assembly or On-Site Setup to support multi-booking on the same date.</p>
            </div>
            <a href="{{ route('admin.bookings') }}" class="text-sm font-semibold text-purple-700 hover:text-purple-900">Open bookings</a>
        </div>
    </div>

    <!-- Quick Actions: Direct admin shortcuts -->
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
        <a href="{{ route('admin.bookings') }}" class="rf-panel group block p-5 transition hover:-translate-y-0.5 hover:shadow-md sm:p-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-purple-500 mb-2 font-semibold">Quick action</p>
                    <h3 class="text-lg font-bold text-gray-800">Review Bookings</h3>
                </div>
                <i class="fa-solid fa-arrow-right text-gray-400 group-hover:text-purple-600 transition"></i>
            </div>
            <p class="mt-3 text-sm text-gray-500">Open the booking queue and verify payment references.</p>
        </a>
        <a href="{{ route('admin.return-tracking') }}" class="rf-panel group block p-5 transition hover:-translate-y-0.5 hover:shadow-md sm:p-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-purple-500 mb-2 font-semibold">Quick action</p>
                    <h3 class="text-lg font-bold text-gray-800">Return Tracking</h3>
                </div>
                <i class="fa-solid fa-boxes-packing text-gray-400 group-hover:text-purple-600 transition"></i>
            </div>
            <p class="mt-3 text-sm text-gray-500">Review post-event asset returns, assess damages, and reconcile warehouse stock.</p>
        </a>
        <a href="{{ route('admin.ai-analysis') }}" class="rf-panel group block p-5 transition hover:-translate-y-0.5 hover:shadow-md sm:p-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-purple-500 mb-2 font-semibold">Quick action</p>
                    <h3 class="text-lg font-bold text-gray-800">AI Material Plan</h3>
                </div>
                <i class="fa-solid fa-brain text-gray-400 group-hover:text-purple-600 transition"></i>
            </div>
            <p class="mt-3 text-sm text-gray-500">See latest AI-suggested materials and update procurement plans.</p>
        </a>
    </div>

    <!-- Recent Bookings Table: Lists latest booking requests -->
    <section class="rf-panel overflow-hidden" aria-labelledby="recent-bookings-heading">
        <div class="p-5 border-b border-gray-100">
            <h2 id="recent-bookings-heading" class="text-lg font-bold text-gray-800">Recent Bookings</h2>
        </div>
        <div class="overflow-x-auto w-full">
            <table class="w-full text-left rf-table--stack">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-xs uppercase tracking-wider text-gray-500 font-semibold">Client</th>
                        <th scope="col" class="px-6 py-3 text-xs uppercase tracking-wider text-gray-500 font-semibold">Event Type</th>
                        <th scope="col" class="px-6 py-3 text-xs uppercase tracking-wider text-gray-500 font-semibold">Date</th>
                        <th scope="col" class="px-6 py-3 text-xs uppercase tracking-wider text-gray-500 font-semibold">Status</th>
                        <th scope="col" class="px-6 py-3 text-xs uppercase tracking-wider text-gray-500 font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($recentBookings as $booking)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 text-sm font-medium text-gray-800">{{ $booking->client?->full_name ?? 'Guest' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ ucfirst($booking->event_type) }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ optional($booking->event_date)->format('M j, Y') }}</td>
                            <td class="px-6 py-4">
                                @if($booking->status === 'pending')
                                    <span class="bg-amber-100 text-amber-800 text-xs px-2.5 py-1 rounded-full font-medium">Pending</span>
                                @elseif($booking->status === 'quotation_sent')
                                    <span class="bg-indigo-100 text-indigo-800 text-xs px-2.5 py-1 rounded-full font-medium">Quotation Sent</span>
                                @elseif($booking->status === 'completed' || $booking->status === 'confirmed' || $booking->status === 'downpayment_received')
                                    <span class="bg-emerald-100 text-emerald-800 text-xs px-2.5 py-1 rounded-full font-medium">{{ $booking->status_display_label }}</span>
                                @else
                                    <span class="bg-gray-100 text-gray-800 text-xs px-2.5 py-1 rounded-full font-medium">{{ $booking->status_display_label }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.bookings.show', $booking->id) }}" class="text-purple-600 hover:text-purple-900 font-semibold text-sm hover:underline transition">Review &rarr;</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">No recent bookings available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-admin-layout>
