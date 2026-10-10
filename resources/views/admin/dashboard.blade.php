<x-admin-layout title="Admin Dashboard">
    @push('styles')
    @endpush

    <!-- CDN for Chart.js (reusing standard Chart.js implementation) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Top Greeting Banner & Quick Action Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <p class="text-xs sm:text-sm font-semibold uppercase tracking-wider text-pink-600 mb-0.5">Operational Overview</p>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight flex items-center gap-2">
                <span>Welcome back, {{ auth()->user()->name ?? 'Administrator' }}</span>
                <span class="text-2xl select-none" aria-hidden="true">👋</span>
            </h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-1">Here's what's happening with your floral event business today.</p>
        </div>
        <div class="flex items-center gap-3 shrink-0 self-start sm:self-center">
            <span class="hidden md:inline-flex items-center gap-2 text-xs font-semibold text-gray-600 bg-white border border-gray-200/90 rounded-xl px-3.5 py-2.5 shadow-2xs">
                <i class="fa-regular fa-calendar text-pink-600 text-xs"></i>
                <span>{{ now()->format('l, M j, Y') }}</span>
            </span>
            <a
                href="{{ route('bookings.create') }}"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-[#be185d] hover:bg-[#9d174d] text-white font-semibold text-sm rounded-xl shadow-xs hover:shadow transition focus:outline-none focus:ring-2 focus:ring-pink-500 cursor-pointer"
            >
                <i class="fa-solid fa-plus text-xs"></i>
                <span>New Booking</span>
            </a>
        </div>
    </div>

    <!-- KPI ROW (4 Primary Metric Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- KPI 1: Bookings This Month -->
        <div class="bg-white rounded-2xl border border-gray-100/90 p-5 shadow-xs hover:shadow-md transition">
            <div class="flex items-center justify-between gap-3 mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Total Bookings</span>
                <div class="w-10 h-10 rounded-xl bg-pink-50 border border-pink-100 flex items-center justify-center text-pink-600 shrink-0">
                    <i class="fa-regular fa-calendar-days text-base"></i>
                </div>
            </div>
            <div class="flex items-baseline justify-between gap-2">
                <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">{{ number_format($bookingsThisMonth) }}</p>
                @if($bookingsGrowth >= 0)
                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded-full">
                        <i class="fa-solid fa-arrow-up text-[10px]"></i> +{{ $bookingsGrowth }}%
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-rose-700 bg-rose-50 border border-rose-100 px-2 py-0.5 rounded-full">
                        <i class="fa-solid fa-arrow-down text-[10px]"></i> {{ $bookingsGrowth }}%
                    </span>
                @endif
            </div>
            <p class="text-xs text-gray-500 mt-2 flex items-center justify-between">
                <span>Bookings this month</span>
                <span class="text-gray-400">vs last month</span>
            </p>
        </div>

        <!-- KPI 2: Collected Revenue -->
        <div class="bg-white rounded-2xl border border-gray-100/90 p-5 shadow-xs hover:shadow-md transition">
            <div class="flex items-center justify-between gap-3 mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Collected Revenue</span>
                <div class="w-10 h-10 rounded-xl bg-purple-50 border border-purple-100 flex items-center justify-center text-purple-600 shrink-0">
                    <i class="fa-solid fa-peso-sign text-base"></i>
                </div>
            </div>
            <div class="flex items-baseline justify-between gap-2">
                <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">₱{{ number_format($revenueThisMonth, 2) }}</p>
                @if($revenueGrowth >= 0)
                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded-full">
                        <i class="fa-solid fa-arrow-up text-[10px]"></i> +{{ $revenueGrowth }}%
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-rose-700 bg-rose-50 border border-rose-100 px-2 py-0.5 rounded-full">
                        <i class="fa-solid fa-arrow-down text-[10px]"></i> {{ $revenueGrowth }}%
                    </span>
                @endif
            </div>
            <p class="text-xs text-gray-500 mt-2 flex items-center justify-between">
                <span>Verified payments this month</span>
                <span class="text-gray-400">vs last month</span>
            </p>
        </div>

        <!-- KPI 3: Active Packages -->
        <div class="bg-white rounded-2xl border border-gray-100/90 p-5 shadow-xs hover:shadow-md transition">
            <div class="flex items-center justify-between gap-3 mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Active Packages</span>
                <div class="w-10 h-10 rounded-xl bg-rose-50 border border-rose-100 flex items-center justify-center text-rose-600 shrink-0">
                    <i class="fa-solid fa-boxes-packing text-base"></i>
                </div>
            </div>
            <div class="flex items-baseline justify-between gap-2">
                <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">{{ number_format($activePackagesCount) }}</p>
                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-purple-700 bg-purple-50 border border-purple-100 px-2 py-0.5 rounded-full">
                    Catalogue
                </span>
            </div>
            <p class="text-xs text-gray-500 mt-2 flex items-center justify-between">
                <span>Published for public booking</span>
                <a href="{{ route('admin.packages.index') }}" class="text-pink-600 hover:text-pink-700 font-semibold">View &rarr;</a>
            </p>
        </div>

        <!-- KPI 4: Total Clients (Using Client Model Count) -->
        <div class="bg-white rounded-2xl border border-gray-100/90 p-5 shadow-xs hover:shadow-md transition">
            <div class="flex items-center justify-between gap-3 mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Total Clients</span>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 shrink-0">
                    <i class="fa-solid fa-users text-base"></i>
                </div>
            </div>
            <div class="flex items-baseline justify-between gap-2">
                <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">{{ number_format($totalClientsCount) }}</p>
                @if($clientsGrowth >= 0)
                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded-full">
                        <i class="fa-solid fa-arrow-up text-[10px]"></i> +{{ $clientsGrowth }}%
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-rose-700 bg-rose-50 border border-rose-100 px-2 py-0.5 rounded-full">
                        <i class="fa-solid fa-arrow-down text-[10px]"></i> {{ $clientsGrowth }}%
                    </span>
                @endif
            </div>
            <p class="text-xs text-gray-500 mt-2 flex items-center justify-between">
                <span>Registered client profiles</span>
                <a href="{{ route('admin.client-records') }}" class="text-indigo-600 hover:text-indigo-700 font-semibold">View &rarr;</a>
            </p>
        </div>
    </div>

    <!-- Active Alerts Panel (Preserved AdminAlert System) -->
    @if($activeAlerts->count() > 0)
    <section class="mb-6 overflow-hidden rounded-2xl border border-amber-200 bg-white shadow-xs" aria-labelledby="active-alerts-heading">
        <div class="p-4 sm:p-5 border-b border-amber-100 flex items-center justify-between bg-amber-50/40">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-amber-100 border border-amber-200 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-bell text-amber-600 text-sm"></i>
                </div>
                <div>
                    <h2 id="active-alerts-heading" class="text-sm sm:text-base font-bold text-gray-900">Active Alerts</h2>
                    <p class="text-xs text-gray-500">{{ $activeAlerts->count() }} unread alert{{ $activeAlerts->count() !== 1 ? 's' : '' }} requiring operational attention</p>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.alerts.read-all') }}">
                @csrf
                <button type="submit" class="text-xs text-gray-600 hover:text-gray-900 font-semibold border border-gray-200 bg-white rounded-xl px-3.5 py-1.5 hover:bg-gray-50 transition shadow-2xs cursor-pointer">
                    Dismiss All
                </button>
            </form>
        </div>
        <div class="divide-y divide-gray-100 max-h-72 overflow-y-auto">
            @foreach($activeAlerts as $alert)
            <div class="flex items-start gap-4 p-4 hover:bg-amber-50/20 transition group">
                <div class="mt-0.5 shrink-0 w-8 h-8 rounded-xl flex items-center justify-center {{ $alert->type === 'inventory_shortage' ? 'bg-rose-100 text-rose-600' : 'bg-amber-100 text-amber-600' }}">
                    <i class="fa-solid {{ $alert->type === 'inventory_shortage' ? 'fa-boxes-stacked' : 'fa-clock' }} text-xs"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-0.5">
                        <span class="text-[11px] font-bold px-2 py-0.5 rounded-full {{ $alert->type === 'inventory_shortage' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700' }}">
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
                    <div class="flex items-center gap-3 mt-2">
                        @if($alert->booking_id)
                            <a href="{{ route('admin.bookings.show', $alert->booking_id) }}" class="text-xs font-semibold text-purple-600 hover:text-purple-800 transition">
                                View Booking #{{ $alert->booking_id }} &rarr;
                            </a>
                        @endif
                        @if($alert->type === 'inventory_shortage')
                            <a href="{{ route('admin.inventory.index') }}" class="text-xs font-semibold text-pink-600 hover:text-pink-800 transition">
                                Manage Inventory &rarr;
                            </a>
                        @endif
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.alerts.read', $alert) }}" class="shrink-0">
                    @csrf
                    <button type="submit" title="Dismiss" class="opacity-0 group-hover:opacity-100 transition w-7 h-7 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 flex items-center justify-center cursor-pointer">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </form>
            </div>
            @endforeach
        </div>
    </section>
    @endif

    <!-- ROW 1: REVENUE OVERVIEW + EVENT TYPES + UPCOMING EVENTS -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
        <!-- 1. Revenue Overview Chart (5 cols) -->
        <div class="lg:col-span-5 bg-white rounded-2xl border border-gray-100/90 p-5 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between gap-3 mb-2">
                    <h2 class="text-base font-bold text-gray-900">Revenue Overview</h2>
                    <span class="text-xs font-semibold text-gray-500 bg-gray-50 border border-gray-200/80 rounded-lg px-2.5 py-1">
                        This Year ({{ now()->year }})
                    </span>
                </div>
                <div class="flex items-baseline gap-2 mb-4">
                    <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">₱{{ number_format($revenueThisYear, 2) }}</p>
                    <span class="text-xs font-semibold text-emerald-600 flex items-center gap-1">
                        <i class="fa-solid fa-arrow-trend-up text-xs"></i> Total collected
                    </span>
                </div>
                <p class="text-xs text-gray-400 mb-4">Monthly verified payment collections for {{ now()->year }}</p>
            </div>
            <div class="relative h-60 w-full">
                <canvas id="revenueOverviewChart" aria-label="Monthly Revenue Trend"></canvas>
            </div>
        </div>

        <!-- 2. Bookings by Event Type (3.5 cols -> 4 cols) -->
        <div class="lg:col-span-4 bg-white rounded-2xl border border-gray-100/90 p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between gap-3 mb-3">
                <h2 class="text-base font-bold text-gray-900">Bookings by Event Type</h2>
                <span class="text-xs font-semibold text-gray-500 bg-gray-50 border border-gray-200/80 rounded-lg px-2.5 py-1">
                    Active
                </span>
            </div>

            @if(count($eventTypes) > 0)
                <div class="relative h-44 my-2 flex items-center justify-center">
                    <canvas id="eventTypesChart" aria-label="Bookings by Event Type Donut Chart"></canvas>
                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none text-center">
                        <span class="text-2xl font-black text-gray-900 tracking-tight">{{ $totalEventTypeBookings }}</span>
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Bookings</span>
                    </div>
                </div>
                <div class="space-y-2 mt-3 pt-3 border-t border-gray-100 max-h-40 overflow-y-auto">
                    @foreach($eventTypes as $et)
                        <div class="flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $et['color'] }};"></span>
                                <span class="font-medium text-gray-700 truncate">{{ $et['name'] }}</span>
                            </div>
                            <span class="font-bold text-gray-900 ml-2 shrink-0">{{ $et['count'] }} <span class="text-gray-400 font-normal">({{ $et['percentage'] }}%)</span></span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="h-56 flex flex-col items-center justify-center text-center p-4">
                    <i class="fa-solid fa-calendar-xmark text-gray-300 text-3xl mb-2"></i>
                    <p class="text-sm font-semibold text-gray-700">No active bookings recorded</p>
                    <p class="text-xs text-gray-400 mt-1">Bookings by event type will display once inquiries are created.</p>
                </div>
            @endif
        </div>

        <!-- 3. Upcoming Events (3.5 cols -> 3 cols) -->
        <div class="lg:col-span-3 bg-white rounded-2xl border border-gray-100/90 p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between gap-3 mb-3">
                <h2 class="text-base font-bold text-gray-900">Upcoming Events</h2>
                <a href="{{ route('admin.bookings') }}" class="text-xs font-semibold text-pink-600 hover:text-pink-700">View All</a>
            </div>

            <div class="space-y-3 divide-y divide-gray-50 flex-1">
                @forelse($upcomingEvents as $event)
                    <a
                        href="{{ route('admin.bookings.show', $event->id) }}"
                        class="block pt-2.5 first:pt-0 hover:bg-gray-50/60 p-2 rounded-xl transition group"
                    >
                        <div class="flex items-start gap-3">
                            <!-- Date Badge -->
                            <div class="w-11 h-11 rounded-xl bg-pink-50 border border-pink-100/80 flex flex-col items-center justify-center shrink-0 text-center">
                                <span class="text-[9px] font-extrabold uppercase text-pink-600 leading-none">
                                    {{ optional($event->event_date)->format('M') }}
                                </span>
                                <span class="text-sm font-black text-gray-900 leading-none mt-0.5">
                                    {{ optional($event->event_date)->format('j') }}
                                </span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-bold text-gray-900 truncate group-hover:text-pink-600 transition">
                                    {{ $event->client?->full_name ?? $event->guest_name ?? 'Client' }} — {{ ucfirst($event->event_type) }}
                                </p>
                                <p class="text-[11px] text-gray-500 mt-0.5 truncate">
                                    <i class="fa-regular fa-clock text-[10px] text-gray-400 mr-1"></i>{{ $event->event_time ?? 'TBD' }}
                                    @if($event->venue)
                                        • {{ Str::limit($event->venue, 20) }}
                                    @endif
                                </p>
                                <div class="mt-1">
                                    <span class="inline-block text-[10px] font-bold px-2 py-0.5 rounded-full {{ in_array($event->status, ['confirmed', 'downpayment_received', 'completed']) ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-amber-50 text-amber-700 border border-amber-100' }}">
                                        {{ $event->status_display_label }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="py-12 text-center text-gray-400">
                        <i class="fa-regular fa-calendar-check text-2xl text-gray-300 mb-2"></i>
                        <p class="text-xs">No upcoming events scheduled.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- ROW 2: PAYMENT SUMMARY + TOP PACKAGES + INVENTORY STATUS -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
        <!-- 1. Payment Summary (4 cols) -->
        <div class="lg:col-span-4 bg-white rounded-2xl border border-gray-100/90 p-5 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between gap-3 mb-2">
                    <h2 class="text-base font-bold text-gray-900">Payment Summary</h2>
                    <span class="text-xs font-semibold text-gray-500 bg-gray-50 border border-gray-200/80 rounded-lg px-2.5 py-1">
                        Operational Cash Flow
                    </span>
                </div>
                <p class="text-xs text-gray-400 mb-4">Authoritative payment collection vs outstanding receivables</p>
            </div>

            <div class="space-y-4 my-2">
                <!-- Paid -->
                <div class="p-3 bg-emerald-50/50 rounded-xl border border-emerald-100/60">
                    <div class="flex items-center justify-between text-xs font-bold text-gray-700 mb-1.5">
                        <span class="flex items-center gap-1.5 text-emerald-700">
                            <i class="fa-solid fa-circle-check text-xs"></i> Paid (Verified)
                        </span>
                        <span class="text-gray-900 font-extrabold">₱{{ number_format($paymentSummary['paid'], 2) }}</span>
                    </div>
                    <div class="w-full bg-emerald-200/50 h-2 rounded-full overflow-hidden">
                        <div class="bg-emerald-600 h-2 rounded-full" style="width: {{ $paymentSummary['paid_pct'] }}%;"></div>
                    </div>
                    <p class="text-[10px] text-gray-400 mt-1 text-right">{{ $paymentSummary['paid_pct'] }}% of total obligation</p>
                </div>

                <!-- Payment Pending -->
                <div class="p-3 bg-amber-50/50 rounded-xl border border-amber-100/60">
                    <div class="flex items-center justify-between text-xs font-bold text-gray-700 mb-1.5">
                        <span class="flex items-center gap-1.5 text-amber-700">
                            <i class="fa-solid fa-hourglass-half text-xs"></i> Payment Pending
                        </span>
                        <span class="text-gray-900 font-extrabold">₱{{ number_format($paymentSummary['pending'], 2) }}</span>
                    </div>
                    <div class="w-full bg-amber-200/50 h-2 rounded-full overflow-hidden">
                        <div class="bg-amber-500 h-2 rounded-full" style="width: {{ $paymentSummary['pending_pct'] }}%;"></div>
                    </div>
                    <p class="text-[10px] text-gray-400 mt-1 text-right">{{ $paymentSummary['pending_pct'] }}% submitted / unverified</p>
                </div>

                <!-- Balance Due -->
                <div class="p-3 bg-rose-50/50 rounded-xl border border-rose-100/60">
                    <div class="flex items-center justify-between text-xs font-bold text-gray-700 mb-1.5">
                        <span class="flex items-center gap-1.5 text-rose-700">
                            <i class="fa-solid fa-circle-exclamation text-xs"></i> Balance Due
                        </span>
                        <span class="text-gray-900 font-extrabold">₱{{ number_format($paymentSummary['balance_due'], 2) }}</span>
                    </div>
                    <div class="w-full bg-rose-200/50 h-2 rounded-full overflow-hidden">
                        <div class="bg-rose-500 h-2 rounded-full" style="width: {{ $paymentSummary['balance_due_pct'] }}%;"></div>
                    </div>
                    <p class="text-[10px] text-gray-400 mt-1 text-right">{{ $paymentSummary['balance_due_pct'] }}% remaining balance</p>
                </div>
            </div>
            <div class="pt-2"></div>
        </div>

        <!-- 2. Top Packages (4 cols) -->
        <div class="lg:col-span-4 bg-white rounded-2xl border border-gray-100/90 p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between gap-3 mb-3">
                <h2 class="text-base font-bold text-gray-900">Top Packages</h2>
                <a href="{{ route('admin.packages.index') }}" class="text-xs font-semibold text-pink-600 hover:text-pink-700">View All</a>
            </div>

            <div class="space-y-3 flex-1 divide-y divide-gray-50">
                @forelse($topPackages as $index => $pkg)
                    <div class="flex items-center justify-between gap-3 pt-2.5 first:pt-0">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="w-6 h-6 rounded-lg bg-gray-100 text-gray-600 font-black text-xs flex items-center justify-center shrink-0">
                                {{ $index + 1 }}
                            </span>
                            @if(!empty($pkg->image_path))
                                <img src="{{ asset('storage/' . $pkg->image_path) }}" alt="{{ $pkg->title }}" class="w-10 h-10 rounded-xl object-cover border border-gray-100 shrink-0">
                            @else
                                <div class="w-10 h-10 rounded-xl bg-pink-50 text-pink-600 flex items-center justify-center text-sm border border-pink-100 shrink-0">
                                    <i class="fa-solid fa-gift"></i>
                                </div>
                            @endif
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-gray-900 truncate">{{ $pkg->title }}</p>
                                <span class="inline-block text-[10px] font-semibold text-pink-600 bg-pink-50 px-2 py-0.5 rounded-full mt-0.5">
                                    {{ $pkg->category ?? 'Floral Bundle' }}
                                </span>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-xs font-bold text-gray-900">{{ $pkg->bookings_count }}</span>
                            <p class="text-[10px] text-gray-400">bookings</p>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center text-gray-400">
                        <i class="fa-solid fa-boxes-packing text-2xl text-gray-300 mb-2"></i>
                        <p class="text-xs">No package bookings recorded yet.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- 3. Inventory Status (4 cols) -->
        <div class="lg:col-span-4 bg-white rounded-2xl border border-gray-100/90 p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between gap-3 mb-3">
                <h2 class="text-base font-bold text-gray-900">Inventory Status</h2>
                <a href="{{ route('admin.inventory.index') }}" class="text-xs font-semibold text-pink-600 hover:text-pink-700">View All</a>
            </div>

            @if($inventoryStatus['total'] > 0)
                <div class="relative h-44 my-2 flex items-center justify-center">
                    <canvas id="inventoryStatusChart" aria-label="Inventory Status Donut Chart"></canvas>
                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none text-center">
                        <span class="text-2xl font-black text-gray-900 tracking-tight">{{ $inventoryStatus['total'] }}</span>
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Items</span>
                    </div>
                </div>

                <div class="space-y-2 mt-3 pt-3 border-t border-gray-100">
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            <span class="font-medium text-gray-700">In Stock</span>
                        </div>
                        <span class="font-bold text-gray-900">{{ $inventoryStatus['in_stock'] }} <span class="text-gray-400 font-normal">({{ $inventoryStatus['in_stock_pct'] }}%)</span></span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                            <span class="font-medium text-gray-700">Low Stock</span>
                        </div>
                        <span class="font-bold text-gray-900">{{ $inventoryStatus['low_stock'] }} <span class="text-gray-400 font-normal">({{ $inventoryStatus['low_stock_pct'] }}%)</span></span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                            <span class="font-medium text-gray-700">Out of Stock</span>
                        </div>
                        <span class="font-bold text-gray-900">{{ $inventoryStatus['out_of_stock'] }} <span class="text-gray-400 font-normal">({{ $inventoryStatus['out_of_stock_pct'] }}%)</span></span>
                    </div>
                </div>
            @else
                <div class="h-56 flex flex-col items-center justify-center text-center p-4">
                    <i class="fa-solid fa-boxes-stacked text-gray-300 text-3xl mb-2"></i>
                    <p class="text-sm font-semibold text-gray-700">No inventory materials recorded</p>
                    <p class="text-xs text-gray-400 mt-1">Items added to inventory will be tracked here.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- ROW 3: RECENT BOOKINGS + LOW STOCK + RECENT ACTIVITIES -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
        <!-- 1. Recent Bookings Table (5 cols) -->
        <div class="lg:col-span-5 bg-white rounded-2xl border border-gray-100/90 shadow-xs overflow-hidden flex flex-col justify-between">
            <div class="p-4 sm:p-5 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-base font-bold text-gray-900">Recent Bookings</h2>
                <a href="{{ route('admin.bookings') }}" class="text-xs font-semibold text-pink-600 hover:text-pink-700">View All</a>
            </div>
            <div class="overflow-x-auto w-full flex-1">
                <table class="w-full text-left text-xs">
                    <thead class="bg-gray-50/70 border-b border-gray-100 text-gray-500 font-bold uppercase tracking-wider text-[10px]">
                        <tr>
                            <th class="px-4 py-3">Client</th>
                            <th class="px-3 py-3">Event Type</th>
                            <th class="px-3 py-3">Event Date</th>
                            <th class="px-3 py-3">Amount</th>
                            <th class="px-3 py-3">Status</th>
                            <th class="px-3 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        @forelse($recentBookings as $booking)
                            <tr class="hover:bg-pink-50/20 transition">
                                <td class="px-4 py-3 font-semibold text-gray-900">
                                    {{ $booking->client?->full_name ?? $booking->guest_name ?? 'Guest Client' }}
                                </td>
                                <td class="px-3 py-3">
                                    {{ ucfirst($booking->event_type) }}
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-gray-500">
                                    {{ optional($booking->event_date)->format('M j, Y') ?? 'N/A' }}
                                </td>
                                <td class="px-3 py-3 font-bold text-gray-900 whitespace-nowrap">
                                    @php
                                        $amt = (float) ($booking->final_quoted_price > 0 ? $booking->final_quoted_price : ($booking->total_quoted ?? 0));
                                    @endphp
                                    ₱{{ number_format($amt, 2) }}
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <span class="inline-block text-[10px] font-bold px-2 py-0.5 rounded-full {{ in_array($booking->status, ['confirmed', 'downpayment_received', 'completed']) ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-gray-100 text-gray-700' }}">
                                        {{ $booking->status_display_label }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('admin.bookings.show', $booking->id) }}" class="text-pink-600 hover:text-pink-800 font-bold hover:underline">
                                        Review
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-400">No recent bookings recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 2. Low Stock Items (3.5 cols -> 4 cols) -->
        <div class="lg:col-span-4 bg-white rounded-2xl border border-gray-100/90 shadow-xs overflow-hidden flex flex-col justify-between">
            <div class="p-4 sm:p-5 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-base font-bold text-gray-900">Low Stock Items</h2>
                <a href="{{ route('admin.inventory.index') }}" class="text-xs font-semibold text-pink-600 hover:text-pink-700">View All</a>
            </div>
            <div class="overflow-x-auto w-full flex-1">
                <table class="w-full text-left text-xs">
                    <thead class="bg-gray-50/70 border-b border-gray-100 text-gray-500 font-bold uppercase tracking-wider text-[10px]">
                        <tr>
                            <th class="px-4 py-3">Item</th>
                            <th class="px-3 py-3">Current Stock</th>
                            <th class="px-3 py-3">Status</th>
                            <th class="px-3 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        @forelse($lowStockItems as $item)
                            <tr class="hover:bg-amber-50/20 transition">
                                <td class="px-4 py-3 font-semibold text-gray-900 truncate max-w-[120px]">
                                    {{ $item->name }}
                                </td>
                                <td class="px-3 py-3 font-bold text-gray-800 whitespace-nowrap">
                                    {{ $item->current_stock }} {{ $item->unit ?? 'units' }}
                                    <span class="block text-[10px] text-gray-400 font-normal">Min: {{ $item->min_stock }}</span>
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    @if($item->current_stock <= 0)
                                        <span class="inline-block text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-50 text-rose-700 border border-rose-100">
                                            Out of Stock
                                        </span>
                                    @else
                                        <span class="inline-block text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-100">
                                            Low Stock
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('admin.inventory.edit', $item->id) }}" class="text-pink-600 hover:text-pink-800 font-bold hover:underline">
                                        Update
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-400">All inventory items are well-stocked!</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 3. Recent Activities (3.5 cols -> 3 cols) -->
        <div class="lg:col-span-3 bg-white rounded-2xl border border-gray-100/90 p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between gap-3 mb-3">
                <h2 class="text-base font-bold text-gray-900">Recent Activities</h2>
                <a href="{{ route('admin.settings') }}" class="text-xs font-semibold text-pink-600 hover:text-pink-700">Audit Trail</a>
            </div>

            <div class="space-y-3 divide-y divide-gray-50 flex-1">
                @forelse($recentActivities as $activity)
                    <div class="flex items-start gap-3 pt-2.5 first:pt-0">
                        <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 border border-purple-100/80 flex items-center justify-center shrink-0 mt-0.5">
                            <i class="fa-solid fa-clock-rotate-left text-xs"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-bold text-gray-900 truncate">
                                {{ $activity->user?->name ?? 'System' }}
                            </p>
                            <p class="text-[11px] text-gray-600 line-clamp-2">
                                @if(is_array($activity->details) && isset($activity->details['message']))
                                    {{ $activity->details['message'] }}
                                @elseif(is_string($activity->details))
                                    {{ $activity->details }}
                                @else
                                    {{ Str::title(str_replace('_', ' ', $activity->action ?? 'Event logged')) }}
                                @endif
                            </p>
                            <span class="text-[10px] text-gray-400 block mt-0.5">{{ $activity->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center text-gray-400">
                        <i class="fa-solid fa-history text-2xl text-gray-300 mb-2"></i>
                        <p class="text-xs">No recent activity logged.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- QUICK ACTIONS ROW: DIRECT ADMIN SHORTCUTS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Action 1: Review Bookings -->
        <a href="{{ route('admin.bookings') }}" class="bg-white rounded-2xl border border-gray-100/90 p-5 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition group block">
            <div class="flex items-center justify-between gap-3 mb-2">
                <span class="text-[10px] font-bold uppercase tracking-wider text-pink-600">Quick action</span>
                <i class="fa-solid fa-arrow-right text-gray-400 group-hover:text-pink-600 transition text-xs"></i>
            </div>
            <h3 class="text-base font-bold text-gray-900 mb-1">Review Bookings</h3>
            <p class="text-xs text-gray-500 leading-relaxed">Open the booking queue and verify payment references.</p>
        </a>

        <!-- Action 2: Return Tracking -->
        <a href="{{ route('admin.return-tracking') }}" class="bg-white rounded-2xl border border-gray-100/90 p-5 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition group block">
            <div class="flex items-center justify-between gap-3 mb-2">
                <span class="text-[10px] font-bold uppercase tracking-wider text-purple-600">Quick action</span>
                <i class="fa-solid fa-boxes-packing text-gray-400 group-hover:text-purple-600 transition text-xs"></i>
            </div>
            <h3 class="text-base font-bold text-gray-900 mb-1">Return Tracking</h3>
            <p class="text-xs text-gray-500 leading-relaxed">Review post-event asset returns, assess damages, and reconcile warehouse stock.</p>
        </a>

        <!-- Action 3: Manage Inventory -->
        <a href="{{ route('admin.inventory.index') }}" class="bg-white rounded-2xl border border-gray-100/90 p-5 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition group block">
            <div class="flex items-center justify-between gap-3 mb-2">
                <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600">Quick action</span>
                <i class="fa-solid fa-warehouse text-gray-400 group-hover:text-indigo-600 transition text-xs"></i>
            </div>
            <h3 class="text-base font-bold text-gray-900 mb-1">Manage Inventory</h3>
            <p class="text-xs text-gray-500 leading-relaxed">Track physical floral assets, set reorder points, and replenish stock.</p>
        </a>

        <!-- Action 4: AI Material Plan -->
        <a href="{{ route('admin.ai-analysis') }}" class="bg-white rounded-2xl border border-gray-100/90 p-5 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition group block">
            <div class="flex items-center justify-between gap-3 mb-2">
                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600">Quick action</span>
                <i class="fa-solid fa-brain text-gray-400 group-hover:text-emerald-600 transition text-xs"></i>
            </div>
            <h3 class="text-base font-bold text-gray-900 mb-1">AI Material Plan</h3>
            <p class="text-xs text-gray-500 leading-relaxed">See latest AI-suggested materials and update procurement plans.</p>
        </a>
    </div>

    <!-- Chart.js Scripts Initialization -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const chartFont = { family: 'Montserrat', size: 11 };

            // 1. Revenue Overview Trend Chart
            const revCanvas = document.getElementById('revenueOverviewChart');
            if (revCanvas) {
                const ctxRev = revCanvas.getContext('2d');
                const gradient = ctxRev.createLinearGradient(0, 0, 0, 240);
                gradient.addColorStop(0, 'rgba(190, 24, 93, 0.25)');
                gradient.addColorStop(1, 'rgba(190, 24, 93, 0.0)');

                new Chart(ctxRev, {
                    type: 'line',
                    data: {
                        labels: @json($monthlyRevenueLabels),
                        datasets: [{
                            label: 'Collected Revenue',
                            data: @json($monthlyRevenueData),
                            borderColor: '#be185d',
                            borderWidth: 3,
                            backgroundColor: gradient,
                            fill: true,
                            tension: 0.35,
                            pointBackgroundColor: '#be185d',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return '₱' + Number(context.raw || 0).toLocaleString(undefined, {minimumFractionDigits: 2});
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: '#f1f5f9' },
                                ticks: {
                                    font: chartFont,
                                    callback: function(value) {
                                        if (value >= 1000) {
                                            return '₱' + (value / 1000) + 'k';
                                        }
                                        return '₱' + value;
                                    }
                                }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { font: chartFont }
                            }
                        }
                    }
                });
            }

            // 2. Bookings by Event Type Donut Chart
            const eventCanvas = document.getElementById('eventTypesChart');
            if (eventCanvas && @json(count($eventTypesCounts)) > 0) {
                new Chart(eventCanvas, {
                    type: 'doughnut',
                    data: {
                        labels: @json($eventTypesLabels),
                        datasets: [{
                            data: @json($eventTypesCounts),
                            backgroundColor: ['#be185d', '#ec4899', '#8b5cf6', '#3b82f6', '#10b981', '#f59e0b', '#6b7280'],
                            borderWidth: 2,
                            borderColor: '#ffffff',
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '72%',
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
            }

            // 3. Inventory Status Donut Chart
            const invCanvas = document.getElementById('inventoryStatusChart');
            if (invCanvas && {{ $inventoryStatus['total'] }} > 0) {
                new Chart(invCanvas, {
                    type: 'doughnut',
                    data: {
                        labels: ['In Stock', 'Low Stock', 'Out of Stock'],
                        datasets: [{
                            data: [{{ $inventoryStatus['in_stock'] }}, {{ $inventoryStatus['low_stock'] }}, {{ $inventoryStatus['out_of_stock'] }}],
                            backgroundColor: ['#10b981', '#f59e0b', '#f43f5e'],
                            borderWidth: 2,
                            borderColor: '#ffffff',
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '72%',
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
            }
        });
    </script>
</x-admin-layout>
