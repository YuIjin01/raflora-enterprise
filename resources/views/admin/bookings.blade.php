<x-admin-layout title="Bookings Management">
    <!-- Primary Booking Workflow Navigation -->
    <div class="mb-6 w-full overflow-x-auto pb-2 scrollbar-hide">
        <div class="flex items-center gap-1 md:gap-2 min-w-max px-1">
            
            {{-- Workflow Stage: All Bookings --}}
            <a href="{{ route('admin.bookings', ['stage' => 'all']) }}" 
               class="flex flex-col p-3 rounded-xl border-2 {{ $activeStage === 'all' ? 'border-purple-300 bg-purple-50/50 shadow-sm' : 'border-slate-100 bg-white hover:border-purple-200 hover:shadow-sm' }} min-w-[130px] md:min-w-[140px] transition-all group">
                <div class="flex justify-between items-start w-full mb-1">
                    <div class="w-7 h-7 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center text-xs font-bold">
                        <i class="fa-solid fa-list-ul"></i>
                    </div>
                </div>
                <div class="text-center mt-1 mb-2">
                    <div class="text-sm font-bold text-slate-800 leading-tight">All Bookings</div>
                    <div class="text-[10px] text-slate-500 font-medium">All bookings</div>
                </div>
                <div class="w-full py-0.5 rounded-lg {{ $activeStage === 'all' ? 'bg-purple-100 text-purple-800' : 'bg-purple-50 text-purple-600 group-hover:bg-purple-100 group-hover:text-purple-700' }} text-base font-bold text-center transition-colors">
                    {{ $stageCounts['all'] }}
                </div>
            </a>
            
            <div class="text-slate-300 px-0.5"><i class="fa-solid fa-arrow-right text-[10px]"></i></div>

            {{-- Workflow Stage: Booking Request --}}
            <a href="{{ route('admin.bookings', ['stage' => 'request']) }}" 
               class="flex flex-col p-3 rounded-xl border-2 {{ $activeStage === 'request' ? 'border-blue-300 bg-blue-50/50 shadow-sm' : 'border-slate-100 bg-white hover:border-blue-200 hover:shadow-sm' }} min-w-[130px] md:min-w-[140px] transition-all group">
                <div class="flex justify-between items-center w-full mb-1">
                    <div class="w-7 h-7 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-bold">
                        1
                    </div>
                    <i class="fa-regular fa-file-lines text-blue-400 text-lg"></i>
                </div>
                <div class="text-center mt-1 mb-2">
                    <div class="text-sm font-bold text-slate-800 leading-tight">Booking Request</div>
                    <div class="text-[10px] text-slate-500 font-medium">Guest booking requests</div>
                </div>
                <div class="w-full py-0.5 rounded-lg {{ $activeStage === 'request' ? 'bg-blue-100 text-blue-800' : 'bg-blue-50 text-blue-600 group-hover:bg-blue-100 group-hover:text-blue-700' }} text-base font-bold text-center transition-colors">
                    {{ $stageCounts['request'] }}
                </div>
            </a>
            
            <div class="text-slate-300 px-0.5"><i class="fa-solid fa-arrow-right text-[10px]"></i></div>

            {{-- Workflow Stage: Review --}}
            <a href="{{ route('admin.bookings', ['stage' => 'review']) }}" 
               class="flex flex-col p-3 rounded-xl border-2 {{ $activeStage === 'review' ? 'border-sky-300 bg-sky-50/50 shadow-sm' : 'border-slate-100 bg-white hover:border-sky-200 hover:shadow-sm' }} min-w-[130px] md:min-w-[140px] transition-all group">
                <div class="flex justify-between items-center w-full mb-1">
                    <div class="w-7 h-7 rounded-full bg-sky-100 text-sky-700 flex items-center justify-center text-xs font-bold">
                        2
                    </div>
                    <i class="fa-solid fa-magnifying-glass text-sky-400 text-lg"></i>
                </div>
                <div class="text-center mt-1 mb-2">
                    <div class="text-sm font-bold text-slate-800 leading-tight">Review</div>
                    <div class="text-[10px] text-slate-500 font-medium">For review</div>
                </div>
                <div class="w-full py-0.5 rounded-lg {{ $activeStage === 'review' ? 'bg-sky-100 text-sky-800' : 'bg-sky-50 text-sky-600 group-hover:bg-sky-100 group-hover:text-sky-700' }} text-base font-bold text-center transition-colors">
                    {{ $stageCounts['review'] }}
                </div>
            </a>
            
            <div class="text-slate-300 px-0.5"><i class="fa-solid fa-arrow-right text-[10px]"></i></div>

            {{-- Workflow Stage: Quotation --}}
            <a href="{{ route('admin.bookings', ['stage' => 'quotation']) }}" 
               class="flex flex-col p-3 rounded-xl border-2 {{ $activeStage === 'quotation' ? 'border-amber-300 bg-amber-50/50 shadow-sm' : 'border-slate-100 bg-white hover:border-amber-200 hover:shadow-sm' }} min-w-[130px] md:min-w-[140px] transition-all group">
                <div class="flex justify-between items-center w-full mb-1">
                    <div class="w-7 h-7 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center text-xs font-bold">
                        3
                    </div>
                    <i class="fa-solid fa-file-invoice-dollar text-amber-400 text-lg"></i>
                </div>
                <div class="text-center mt-1 mb-2">
                    <div class="text-sm font-bold text-slate-800 leading-tight">Quotation</div>
                    <div class="text-[10px] text-slate-500 font-medium">Quote preparation</div>
                </div>
                <div class="w-full py-0.5 rounded-lg {{ $activeStage === 'quotation' ? 'bg-amber-100 text-amber-800' : 'bg-amber-50 text-amber-600 group-hover:bg-amber-100 group-hover:text-amber-700' }} text-base font-bold text-center transition-colors">
                    {{ $stageCounts['quotation'] }}
                </div>
            </a>
            
            <div class="text-slate-300 px-0.5"><i class="fa-solid fa-arrow-right text-[10px]"></i></div>

            {{-- Workflow Stage: Approval --}}
            <a href="{{ route('admin.bookings', ['stage' => 'approval']) }}" 
               class="flex flex-col p-3 rounded-xl border-2 {{ $activeStage === 'approval' ? 'border-rose-300 bg-rose-50/50 shadow-sm' : 'border-slate-100 bg-white hover:border-rose-200 hover:shadow-sm' }} min-w-[130px] md:min-w-[140px] transition-all group">
                <div class="flex justify-between items-center w-full mb-1">
                    <div class="w-7 h-7 rounded-full bg-rose-100 text-rose-700 flex items-center justify-center text-xs font-bold">
                        4
                    </div>
                    <i class="fa-solid fa-user-check text-rose-400 text-lg"></i>
                </div>
                <div class="text-center mt-1 mb-2">
                    <div class="text-sm font-bold text-slate-800 leading-tight">Approval</div>
                    <div class="text-[10px] text-slate-500 font-medium">Awaiting approval</div>
                </div>
                <div class="w-full py-0.5 rounded-lg {{ $activeStage === 'approval' ? 'bg-rose-100 text-rose-800' : 'bg-rose-50 text-rose-600 group-hover:bg-rose-100 group-hover:text-rose-700' }} text-base font-bold text-center transition-colors">
                    {{ $stageCounts['approval'] }}
                </div>
            </a>
            
            <div class="text-slate-300 px-0.5"><i class="fa-solid fa-arrow-right text-[10px]"></i></div>

            {{-- Workflow Stage: Payment --}}
            <a href="{{ route('admin.bookings', ['stage' => 'payment']) }}" 
               class="flex flex-col p-3 rounded-xl border-2 {{ $activeStage === 'payment' ? 'border-indigo-300 bg-indigo-50/50 shadow-sm' : 'border-slate-100 bg-white hover:border-indigo-200 hover:shadow-sm' }} min-w-[130px] md:min-w-[140px] transition-all group">
                <div class="flex justify-between items-center w-full mb-1">
                    <div class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold">
                        5
                    </div>
                    <i class="fa-regular fa-credit-card text-indigo-400 text-lg"></i>
                </div>
                <div class="text-center mt-1 mb-2">
                    <div class="text-sm font-bold text-slate-800 leading-tight">Payment</div>
                    <div class="text-[10px] text-slate-500 font-medium">Payment verification</div>
                </div>
                <div class="w-full py-0.5 rounded-lg {{ $activeStage === 'payment' ? 'bg-indigo-100 text-indigo-800' : 'bg-indigo-50 text-indigo-600 group-hover:bg-indigo-100 group-hover:text-indigo-700' }} text-base font-bold text-center transition-colors">
                    {{ $stageCounts['payment'] }}
                </div>
            </a>
            
            <div class="text-slate-300 px-0.5"><i class="fa-solid fa-arrow-right text-[10px]"></i></div>

            {{-- Workflow Stage: Confirmed --}}
            <a href="{{ route('admin.bookings', ['stage' => 'confirmed']) }}" 
               class="flex flex-col p-3 rounded-xl border-2 {{ $activeStage === 'confirmed' ? 'border-emerald-300 bg-emerald-50/50 shadow-sm' : 'border-slate-100 bg-white hover:border-emerald-200 hover:shadow-sm' }} min-w-[130px] md:min-w-[140px] transition-all group">
                <div class="flex justify-between items-center w-full mb-1">
                    <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold">
                        6
                    </div>
                    <i class="fa-regular fa-circle-check text-emerald-400 text-lg"></i>
                </div>
                <div class="text-center mt-1 mb-2">
                    <div class="text-sm font-bold text-slate-800 leading-tight">Confirmed</div>
                    <div class="text-[10px] text-slate-500 font-medium">Confirmed bookings</div>
                </div>
                <div class="w-full py-0.5 rounded-lg {{ $activeStage === 'confirmed' ? 'bg-emerald-100 text-emerald-800' : 'bg-emerald-50 text-emerald-600 group-hover:bg-emerald-100 group-hover:text-emerald-700' }} text-base font-bold text-center transition-colors">
                    {{ $stageCounts['confirmed'] }}
                </div>
            </a>
            
            <div class="text-slate-300 px-0.5"><i class="fa-solid fa-arrow-right text-[10px]"></i></div>

            {{-- Workflow Stage: Cancelled --}}
            <a href="{{ route('admin.bookings', ['stage' => 'cancelled']) }}" 
               class="flex flex-col p-3 rounded-xl border-2 {{ $activeStage === 'cancelled' ? 'border-red-300 bg-red-50/50 shadow-sm' : 'border-slate-100 bg-white hover:border-red-200 hover:shadow-sm' }} min-w-[130px] md:min-w-[140px] transition-all group">
                <div class="flex justify-between items-center w-full mb-1">
                    <div class="w-7 h-7 rounded-full bg-red-100 text-red-700 flex items-center justify-center text-xs font-bold">
                        7
                    </div>
                    <i class="fa-solid fa-ban text-red-400 text-lg"></i>
                </div>
                <div class="text-center mt-1 mb-2">
                    <div class="text-sm font-bold text-slate-800 leading-tight">Cancelled</div>
                    <div class="text-[10px] text-slate-500 font-medium">Cancelled bookings</div>
                </div>
                <div class="w-full py-0.5 rounded-lg {{ $activeStage === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-red-50 text-red-600 group-hover:bg-red-100 group-hover:text-red-700' }} text-base font-bold text-center transition-colors">
                    {{ $stageCounts['cancelled'] }}
                </div>
            </a>

        </div>
    </div>

    <!-- Filter Controls: Status filter dropdown, Sort & Search -->
    <form id="filterForm" method="GET" action="{{ route('admin.bookings') }}" class="mb-4 bg-white px-4 py-3 rounded-2xl shadow-sm border border-slate-200">
        <input type="hidden" name="stage" value="{{ $activeStage }}">
        <div class="flex flex-wrap items-center gap-2 md:gap-3">
            {{-- Search (Full width on mobile, auto on desktop) --}}
            <div class="relative w-full md:min-w-[240px] md:flex-1 lg:max-w-md">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input type="text" id="bookingSearchInput" name="search" value="{{ $searchTerm }}"
                    placeholder="Search client, event, ID..."
                    autocomplete="off"
                    class="block w-full pl-9 pr-3 py-2 border border-slate-200 rounded-xl text-sm bg-slate-50 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-1 focus:ring-purple-500 focus:border-purple-500 transition">
            </div>

            {{-- Status Filter (Half width on mobile) --}}
            <div class="w-[calc(50%-4px)] md:w-auto md:min-w-[200px]">
                <label for="status" class="sr-only">Status</label>
                <select id="status" name="status" onchange="document.getElementById('filterForm').submit()"
                    class="block w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:ring-1 focus:ring-purple-500 focus:border-purple-500 transition">
                    <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All Statuses</option>
                    <option value="approved" {{ $statusFilter === 'approved' ? 'selected' : '' }}>Awaiting Admin Approval</option>
                    <option value="pending" {{ $statusFilter === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="quotation_sent" {{ $statusFilter === 'quotation_sent' ? 'selected' : '' }}>Quotation Sent</option>
                    <option value="admin_approved" {{ $statusFilter === 'admin_approved' ? 'selected' : '' }}>Admin Approved (Payment Pending)</option>
                    <option value="payment_pending" {{ $statusFilter === 'payment_pending' ? 'selected' : '' }}>Payment Pending</option>
                    <option value="payment_submitted" {{ $statusFilter === 'payment_submitted' ? 'selected' : '' }}>Payment Submitted</option>
                    <option value="fully_paid" {{ $statusFilter === 'fully_paid' ? 'selected' : '' }}>Fully Paid</option>
                    <option value="downpayment_received" {{ $statusFilter === 'downpayment_received' ? 'selected' : '' }}>Downpayment Received</option>
                    <option value="confirmed" {{ $statusFilter === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                    <option value="event_in_progress" {{ $statusFilter === 'event_in_progress' ? 'selected' : '' }}>Event In Progress</option>
                    <option value="event_completed" {{ $statusFilter === 'event_completed' ? 'selected' : '' }}>Event Completed</option>
                    <option value="pending_return" {{ $statusFilter === 'pending_return' ? 'selected' : '' }}>Pending Return</option>
                    <option value="pending_resolution" {{ $statusFilter === 'pending_resolution' ? 'selected' : '' }}>Pending Resolution</option>
                    <option value="completed" {{ $statusFilter === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="declined" {{ $statusFilter === 'declined' ? 'selected' : '' }}>Declined</option>
                    <option value="cancelled" {{ $statusFilter === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>

            {{-- Event Type Filter (Half width on mobile) --}}
            <div class="w-[calc(50%-4px)] md:w-auto md:min-w-[160px]">
                <label for="event_type" class="sr-only">Event Type</label>
                <select id="event_type" name="event_type" onchange="document.getElementById('filterForm').submit()"
                    class="block w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:ring-1 focus:ring-purple-500 focus:border-purple-500 transition">
                    <option value="all" {{ ($eventTypeFilter ?? 'all') === 'all' ? 'selected' : '' }}>All Event Types</option>
                    @foreach($availableEventTypes as $type)
                        <option value="{{ $type }}" {{ ($eventTypeFilter ?? '') === $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Event Date Range Filter --}}
            <div class="w-full md:w-auto flex items-center gap-2">
                <label for="event_date_range" class="sr-only">Event Date</label>
                <div class="relative w-full">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fa-regular fa-calendar text-slate-400"></i>
                    </div>
                    <input type="text" id="event_date_range" placeholder="Select Date Range..."
                        class="block w-full md:w-64 pl-9 pr-8 py-2 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:ring-1 focus:ring-purple-500 focus:border-purple-500 transition cursor-pointer">
                    
                    @if($eventDateFrom || $eventDateTo)
                        <button type="button" onclick="clearDateRange()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 transition">
                            <i class="fa-solid fa-xmark text-sm"></i>
                        </button>
                    @endif
                </div>
                <input type="hidden" id="event_date_from" name="event_date_from" value="{{ $eventDateFrom ?? '' }}">
                <input type="hidden" id="event_date_to" name="event_date_to" value="{{ $eventDateTo ?? '' }}">
            </div>

            {{-- Sort (Half width on mobile) --}}
            <div class="flex-1 md:flex-none md:w-auto md:min-w-[160px]">
                <label for="sort" class="sr-only">Sort by</label>
                <select id="sort" name="sort" onchange="document.getElementById('filterForm').submit()"
                    class="block w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:ring-1 focus:ring-purple-500 focus:border-purple-500 transition">
                    <option value="newest" {{ ($sort ?? 'newest') === 'newest' ? 'selected' : '' }}>Newest First</option>
                    <option value="oldest" {{ ($sort ?? 'newest') === 'oldest' ? 'selected' : '' }}>Oldest First</option>
                    <option value="event_date_asc" {{ ($sort ?? 'newest') === 'event_date_asc' ? 'selected' : '' }}>Event Date (Nearest)</option>
                    <option value="event_date_desc" {{ ($sort ?? 'newest') === 'event_date_desc' ? 'selected' : '' }}>Event Date (Furthest)</option>
                </select>
            </div>

            {{-- Clear (Full width on mobile, auto on desktop) --}}
            <div class="w-full sm:w-auto mt-2 sm:mt-0 flex gap-2">
                <a href="{{ route('admin.bookings', ['stage' => $activeStage]) }}"
                    class="inline-flex w-full items-center justify-center px-4 py-2 border border-slate-200 text-sm font-medium rounded-xl text-slate-600 bg-white hover:bg-slate-50 transition whitespace-nowrap">
                    Clear
                </a>
            </div>
        </div>
    </form>

    <div class="mb-4 rounded-lg border border-purple-100 bg-purple-50 px-4 py-3 text-sm text-purple-800">
        Manage booking approvals, payment verification, and quotation follow-ups from this unified screen.
    </div>

    <!-- Bookings Table: Lists all bookings for admin review -->
    <section class="rf-panel overflow-hidden" aria-labelledby="bookings-queue-heading">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-slate-200 p-5">
            <div>
                <h1 id="bookings-queue-heading" class="page-title text-3xl">Bookings queue</h1>
                <p class="section-subtitle mt-2">Review client requests, quotations, payment states, and operational follow-up from one queue.</p>
            </div>
            <div class="mt-2 sm:mt-0 text-xs font-medium text-slate-500">
                Showing {{ $bookings->total() }} {{ \Illuminate\Support\Str::plural('booking', $bookings->total()) }}
            </div>
        </div>
        <div class="overflow-x-auto">
            <div class="w-full min-w-[1120px]"><table class="rf-table">
                <thead class="bg-purple-50">
                    <tr>
                        <th scope="col" class="min-w-[180px]">CLIENT &amp; EVENT</th>
                        <th scope="col" class="min-w-[160px]">EVENT DETAILS</th>
                        <th scope="col" class="min-w-[140px]">FINANCIALS</th>
                        <th scope="col" class="min-w-[180px]">STATUS (WORKFLOW)</th>
                        <th scope="col" class="min-w-[140px]">NEXT ACTION</th>
                        <th scope="col" class="min-w-[140px]">ACTIONS</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-purple-100">
                    @forelse($bookings as $booking)
                        @if($booking instanceof \App\Models\TemporaryGuestBooking)
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-800 text-sm">{{ $booking->guest_name ?? 'Guest' }}</div>
                                    <div class="text-xs text-slate-500 mt-0.5">ID: #{{ $booking->id ?? 'Req' }} • {{ ucfirst($booking->event_type) }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-slate-700"><i class="fa-regular fa-calendar mr-1.5 text-slate-400"></i>{{ optional($booking->event_date)->format('M j, Y') ?? 'TBA' }}</div>
                                    <div class="text-xs text-slate-500 mt-0.5 truncate max-w-[200px]" title="{{ $booking->venue }}"><i class="fa-solid fa-location-dot mr-1.5 text-slate-400"></i>{{ $booking->venue ?? 'TBA' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-xs text-slate-500 italic">No quote yet</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md bg-blue-50 text-blue-700 border border-blue-100 mb-1">
                                        <div class="w-4 h-4 rounded-full bg-blue-100 flex items-center justify-center text-[9px] font-bold">1</div>
                                        <span class="text-xs font-semibold">Booking Request</span>
                                    </div>
                                    <div class="text-[11px] font-medium text-slate-500">Awaiting Claim</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1.5 text-xs text-blue-600 font-semibold">
                                        <i class="fa-solid fa-hand-pointer"></i>
                                        <span>Review &amp; Claim</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="px-3 py-1.5 bg-slate-50 border border-slate-200 text-slate-500 rounded-md text-xs text-center cursor-not-allowed">
                                        Client Must Claim First
                                    </div>
                                </td>
                            </tr>
                        @else
                            @php
                                $latestPayment = $booking->payments->sortByDesc('created_at')->first();
                                $quoteReviewLabel = in_array($booking->status, ['event_in_progress', 'completed'], true)
                                    ? 'View Quote Details'
                                    : 'Review / Edit Quote';
                                $totalObligation = (float) $booking->total_obligation;
                                $paidAmount = (float) $booking->total_paid;
                                $remaining = (float) $booking->remaining_balance;
                                $hasDamageCharges = $totalObligation > (float) ($booking->final_quoted_price ?? $booking->total_quoted ?? 0);
                            @endphp
                            <tr>
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-800 text-sm">{{ $booking->client?->full_name ?? $booking->guest_name ?? 'Guest' }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">ID: #{{ $booking->id ?? 'Req' }} • {{ ucfirst($booking->event_type) }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-slate-700"><i class="fa-regular fa-calendar mr-1.5 text-slate-400"></i>{{ optional($booking->event_date)->format('M j, Y') ?? 'TBA' }}</div>
                                <div class="text-xs text-slate-500 mt-0.5 truncate max-w-[200px]" title="{{ $booking->venue }}"><i class="fa-solid fa-location-dot mr-1.5 text-slate-400"></i>{{ $booking->venue ?? 'TBA' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-xs space-y-1 w-36">
                                    <div class="flex justify-between gap-2">
                                        <span class="text-slate-500">{{ $hasDamageCharges ? 'Obligation:' : 'Quote:' }}</span>
                                        <span class="font-semibold text-slate-800">₱{{ number_format($totalObligation, 2) }}</span>
                                    </div>
                                    <div class="flex justify-between gap-2">
                                        <span class="text-slate-500">Paid:</span>
                                        <span class="font-medium text-emerald-600">₱{{ number_format($paidAmount, 2) }}</span>
                                    </div>
                                    @if($remaining > 0)
                                    <div class="flex justify-between gap-2 border-t border-slate-100 pt-1 mt-1">
                                        <span class="text-slate-500">Bal:</span>
                                        <span class="font-bold text-rose-600">₱{{ number_format($remaining, 2) }}</span>
                                    </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $hasReturnAudit = $booking->returns->isNotEmpty();
                                    
                                    $stageNum = 2; $stageName = 'Review'; $stageColor = 'sky';
                                    if (in_array($booking->status, ['quotation_sent'])) { $stageNum = 3; $stageName = 'Quotation'; $stageColor = 'amber'; }
                                    elseif (in_array($booking->status, ['approved', 'admin_approved'])) { $stageNum = 4; $stageName = 'Approval'; $stageColor = 'rose'; }
                                    elseif (in_array($booking->status, ['payment_submitted', 'payment_pending'])) { $stageNum = 5; $stageName = 'Payment'; $stageColor = 'indigo'; }
                                    elseif (in_array($booking->status, ['downpayment_received', 'fully_paid', 'confirmed', 'event_in_progress', 'event_completed', 'pending_return', 'pending_resolution', 'completed'])) { $stageNum = 6; $stageName = 'Confirmed'; $stageColor = 'emerald'; }
                                    elseif ($booking->status === 'cancelled') { $stageNum = 7; $stageName = 'Cancelled'; $stageColor = 'red'; }
                                    elseif ($booking->status === 'declined') { $stageNum = 0; $stageName = 'Declined'; $stageColor = 'slate'; }
                                    
                                    $detailLabel = match($booking->status) {
                                        'approved' => 'Awaiting Admin Approval',
                                        'admin_approved' => 'Admin Approved',
                                        'event_completed' => $hasReturnAudit ? 'Items Returned' : 'Event Completed',
                                        default => $booking->status_display_label,
                                    };

                                    $stageClasses = [
                                        2 => ['bg' => 'bg-sky-50', 'text' => 'text-sky-700', 'border' => 'border-sky-100', 'circleBg' => 'bg-sky-100'],
                                        3 => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'border' => 'border-amber-100', 'circleBg' => 'bg-amber-100'],
                                        4 => ['bg' => 'bg-rose-50', 'text' => 'text-rose-700', 'border' => 'border-rose-100', 'circleBg' => 'bg-rose-100'],
                                        5 => ['bg' => 'bg-indigo-50', 'text' => 'text-indigo-700', 'border' => 'border-indigo-100', 'circleBg' => 'bg-indigo-100'],
                                        6 => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-100', 'circleBg' => 'bg-emerald-100'],
                                        7 => ['bg' => 'bg-red-50', 'text' => 'text-red-700', 'border' => 'border-red-100', 'circleBg' => 'bg-red-100'],
                                        0 => ['bg' => 'bg-slate-50', 'text' => 'text-slate-700', 'border' => 'border-slate-200', 'circleBg' => 'bg-slate-200'],
                                    ];
                                    $sClass = $stageClasses[$stageNum] ?? $stageClasses[2];
                                @endphp
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md {{ $sClass['bg'] }} {{ $sClass['text'] }} {{ $sClass['border'] }} mb-1 border">
                                    @if($stageNum > 0)
                                    <div class="w-4 h-4 rounded-full {{ $sClass['circleBg'] }} flex items-center justify-center text-[9px] font-bold">{{$stageNum}}</div>
                                    @endif
                                    <span class="text-xs font-semibold whitespace-nowrap">{{$stageName}}</span>
                                </div>
                                <div class="text-[11px] font-medium text-slate-600">
                                    {{ $detailLabel }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $showMarkInProgress = in_array($booking->status, ['downpayment_received', 'confirmed'], true);
                                    $showMarkEventCompleted = $booking->status === 'event_in_progress';
                                    $hasCompletedReturn = $booking->returns->where('status', 'Completed')->isNotEmpty();
                                    $hasCancellationRecovery = ($booking->status === 'cancelled') && ($booking->returns->isNotEmpty() || $booking->hasDispatchedReusableMaterials());
                                    
                                    $nextActionText = '-';
                                    $nextActionClass = 'text-slate-400';
                                    $nextActionIcon = 'fa-minus';

                                    if ($booking->status === 'approved') {
                                        $nextActionText = 'Final Approve';
                                        $nextActionClass = 'text-rose-600 font-semibold';
                                        $nextActionIcon = 'fa-check-double';
                                    } elseif (in_array($booking->status, ['payment_submitted', 'pending_resolution'], true) && $latestPayment && $latestPayment->status === 'pending') {
                                        $nextActionText = 'Verify Payment';
                                        $nextActionClass = 'text-indigo-600 font-semibold';
                                        $nextActionIcon = 'fa-receipt';
                                    } elseif ($showMarkInProgress) {
                                        $nextActionText = 'Mark In Progress';
                                        $nextActionClass = 'text-emerald-600 font-semibold';
                                        $nextActionIcon = 'fa-play';
                                    } elseif ($showMarkEventCompleted) {
                                        $nextActionText = 'Mark Completed';
                                        $nextActionClass = 'text-emerald-600 font-semibold';
                                        $nextActionIcon = 'fa-flag-checkered';
                                    } elseif ($booking->status === 'event_completed' || in_array($booking->status, ['pending_return', 'pending_resolution'], true)) {
                                        if (!$hasCompletedReturn) {
                                            $nextActionText = 'Manage Return';
                                            $nextActionClass = 'text-purple-600 font-semibold';
                                            $nextActionIcon = 'fa-box-open';
                                        } elseif ($remaining > 0) {
                                            $nextActionText = 'Log Final Payment';
                                            $nextActionClass = 'text-indigo-600 font-semibold';
                                            $nextActionIcon = 'fa-money-bill';
                                        } else {
                                            $nextActionText = 'None';
                                        }
                                    } elseif ($hasCancellationRecovery && !$hasCompletedReturn) {
                                        $nextActionText = 'Manage Return';
                                        $nextActionClass = 'text-purple-600 font-semibold';
                                        $nextActionIcon = 'fa-box-open';
                                    } elseif (in_array($booking->status, ['pending', 'change_requested'])) {
                                        $nextActionText = 'Review / Edit Quote';
                                        $nextActionClass = 'text-sky-600 font-semibold';
                                        $nextActionIcon = 'fa-file-invoice';
                                    } elseif ($booking->status === 'cancellation_requested') {
                                        $nextActionText = 'Review Cancel';
                                        $nextActionClass = 'text-red-600 font-semibold';
                                        $nextActionIcon = 'fa-ban';
                                    } elseif ($booking->status === 'quotation_sent') {
                                        $nextActionText = 'Wait for Client';
                                        $nextActionClass = 'text-amber-600 font-medium';
                                        $nextActionIcon = 'fa-hourglass-half';
                                    } elseif (in_array($booking->status, ['admin_approved', 'payment_pending'])) {
                                        $nextActionText = 'Wait for Payment';
                                        $nextActionClass = 'text-indigo-600 font-medium';
                                        $nextActionIcon = 'fa-hourglass-half';
                                    } elseif ($booking->status === 'completed' || $booking->status === 'cancelled') {
                                        $nextActionText = 'None';
                                    }
                                @endphp
                                <div class="flex items-center gap-1.5 text-[11px] {{ $nextActionClass }}">
                                    <i class="fa-solid {{ $nextActionIcon }} w-3 text-center"></i>
                                    <span>{{ $nextActionText }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-3">
                                    <a href="{{ route('admin.bookings.edit', ['booking' => $booking->id]) }}" class="rf-btn rf-btn-outline w-full text-center text-sm">
                                        {{ $quoteReviewLabel }}
                                    </a>

                                    @if($booking->status === 'approved')
                                        <form method="POST" action="{{ route('admin.bookings.final-approve', ['booking' => $booking->id]) }}">
                                            @csrf
                                            <button type="submit" class="btn-primary w-full text-sm">
                                                Final Approve &amp; Enable Payment
                                            </button>
                                        </form>
                                    @endif

                                    @if(in_array($booking->status, ['payment_submitted', 'pending_resolution'], true) && $latestPayment && $latestPayment->status === 'pending')
                                        <button type="button" aria-label="Verify payment for booking {{ $booking->id }}" onclick="openBookingModal('booking-payment-modal-{{ $booking->id }}')" class="rf-btn rf-btn-outline w-full text-sm">
                                            Verify Payment
                                        </button>
                                    @endif

                                    @php
                                        $showMarkInProgress = in_array($booking->status, ['downpayment_received', 'confirmed'], true);
                                        $showMarkEventCompleted = $booking->status === 'event_in_progress';
                                        $showCompletedBadge = $booking->status === 'completed';
                                        $hasCompletedReturn = $booking->returns->where('status', 'Completed')->isNotEmpty();
                                        $hasCancellationRecovery = ($booking->status === 'cancelled') && ($booking->returns->isNotEmpty() || $booking->hasDispatchedReusableMaterials());
                                        $showEventCompletedBadge = ($booking->status === 'event_completed') && ($remaining > 0);
                                        $showEventCompletedFullyPaidBadge = ($booking->status === 'event_completed') && ($remaining <= 0);
                                    @endphp

                                    @if($showMarkInProgress)
                                        <form method="POST" action="{{ route('admin.bookings.update', ['booking' => $booking->id]) }}" class="mt-2">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="event_type" value="{{ $booking->event_type }}">
                                            <input type="hidden" name="event_date" value="{{ optional($booking->event_date)->format('Y-m-d') }}">
                                            <input type="hidden" name="venue" value="{{ $booking->venue }}">
                                            <input type="hidden" name="status" value="{{ $booking->status }}">
                                            <button type="submit" name="action" value="mark_event_in_progress" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg shadow-sm text-xs inline-block text-center cursor-pointer mt-2 w-full">
                                                Mark Event In Progress
                                            </button>
                                        </form>
                                    @endif

                                    @if($showMarkEventCompleted)
                                        <form method="POST" action="{{ route('admin.bookings.update', ['booking' => $booking->id]) }}" class="mt-2">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="event_type" value="{{ $booking->event_type }}">
                                            <input type="hidden" name="event_date" value="{{ optional($booking->event_date)->format('Y-m-d') }}">
                                            <input type="hidden" name="venue" value="{{ $booking->venue }}">
                                            <input type="hidden" name="status" value="{{ $booking->status }}">
                                            <button type="submit" name="action" value="mark_event_completed" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg shadow-sm text-xs inline-block text-center cursor-pointer mt-2 w-full">
                                                Mark Event Completed
                                            </button>
                                        </form>
                                    @endif

                                    @if($showEventCompletedBadge)
                                        <span class="inline-flex items-center justify-center rounded-full bg-amber-100 px-3 py-2 text-xs font-semibold uppercase text-amber-800">
                                            Event Completed (Balance Pending)
                                        </span>
                                    @endif

                                    @if($showEventCompletedFullyPaidBadge)
                                        <span class="inline-flex items-center justify-center rounded-full bg-emerald-100 px-3 py-2 text-xs font-semibold uppercase text-emerald-800">
                                            Completed &amp; Fully Paid
                                        </span>
                                    @endif

                                    @if($booking->status === 'event_completed')
                                        @if(!$hasCompletedReturn)
                                            <a href="{{ route('admin.return-tracking.manage', ['booking' => $booking->id]) }}" class="px-3 py-1.5 bg-purple-50 text-purple-700 font-medium rounded-md border border-purple-200 hover:bg-purple-100 inline-block text-center text-xs">
                                                Manage Return Audit
                                            </a>
                                        @else
                                            <a href="{{ route('admin.return-tracking.manage', ['booking' => $booking->id]) }}" class="px-3 py-1.5 bg-slate-50 text-slate-700 font-medium rounded-md border border-slate-200 hover:bg-slate-100 inline-block text-center text-xs">
                                                View Return Audit
                                            </a>
                                            <span class="inline-flex items-center justify-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase text-slate-700">
                                                Return Audit Completed
                                            </span>
                                        @endif
                                    @endif

                                    @if($showCompletedBadge)
                                        <span class="inline-flex items-center justify-center rounded-full bg-emerald-100 px-3 py-2 text-xs font-semibold uppercase text-emerald-800">
                                            Completed &amp; Fully Paid
                                        </span>
                                        <a href="{{ route('admin.return-tracking.manage', ['booking' => $booking->id]) }}" class="px-3 py-1.5 bg-slate-50 text-slate-700 font-medium rounded-md border border-slate-200 hover:bg-slate-100 inline-block text-center text-xs">
                                            View Return Audit
                                        </a>
                                    @endif

                                    @if(in_array($booking->status, ['pending_return', 'pending_resolution'], true))
                                        @if(!$hasCompletedReturn)
                                            <a href="{{ route('admin.return-tracking.manage', ['booking' => $booking->id]) }}" class="px-3 py-1.5 bg-purple-50 text-purple-700 font-medium rounded-md border border-purple-200 hover:bg-purple-100 inline-block text-center text-xs">
                                                Manage Return Audit
                                            </a>
                                        @else
                                            <a href="{{ route('admin.return-tracking.manage', ['booking' => $booking->id]) }}" class="px-3 py-1.5 bg-slate-50 text-slate-700 font-medium rounded-md border border-slate-200 hover:bg-slate-100 inline-block text-center text-xs">
                                                View Return Audit
                                            </a>
                                            <span class="inline-flex items-center justify-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase text-slate-700">
                                                Return Audit Completed
                                            </span>
                                        @endif
                                    @endif

                                    @if($hasCancellationRecovery)
                                        @if(!$hasCompletedReturn)
                                            <a href="{{ route('admin.return-tracking.manage', ['booking' => $booking->id]) }}" class="px-3 py-1.5 bg-purple-50 text-purple-700 font-medium rounded-md border border-purple-200 hover:bg-purple-100 inline-block text-center text-xs">
                                                Manage Return Audit
                                            </a>
                                        @else
                                            <a href="{{ route('admin.return-tracking.manage', ['booking' => $booking->id]) }}" class="px-3 py-1.5 bg-slate-50 text-slate-700 font-medium rounded-md border border-slate-200 hover:bg-slate-100 inline-block text-center text-xs">
                                                View Return Audit
                                            </a>
                                            <span class="inline-flex items-center justify-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase text-slate-700">
                                                Return Audit Completed
                                            </span>
                                        @endif
                                    @endif

                                    @php
                                        $canLogFinalPayment = in_array($booking->status, ['event_in_progress', 'event_completed', 'pending_return', 'pending_resolution'], true) && ($remaining > 0);
                                    @endphp
                                    @if($canLogFinalPayment)
                                        <button type="button" onclick="openBookingModal('booking-final-payment-modal-{{ $booking->id }}')" class="px-4 py-2 bg-purple-700 hover:bg-purple-800 text-white font-semibold rounded-lg shadow-sm text-xs inline-block text-center cursor-pointer mt-2 w-full">
                                            Log Final Payment
                                        </button>
                                        <div id="booking-final-payment-modal-{{ $booking->id }}" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/70 p-4">
                                            <div class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl" onclick="event.stopPropagation()">
                                                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                                                    <div>
                                                        <h3 class="text-lg font-semibold text-slate-900">Log Final Payment</h3>
                                                        <p class="text-sm text-slate-500">#{{ $booking->id }} • {{ ucfirst($booking->event_type) }}</p>
                                                    </div>
                                                    <button type="button" onclick="closeBookingModal('booking-final-payment-modal-{{ $booking->id }}')" class="rounded-full p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700">
                                                        <i class="fa-solid fa-xmark"></i>
                                                    </button>
                                                </div>

                                                <div class="space-y-6 p-6">
                                                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 grid grid-cols-1 gap-3">
                                                        <div class="flex justify-between"><span class="text-sm text-slate-600">Total Obligation</span><span class="font-semibold">₱{{ number_format($totalObligation, 2) }}</span></div>
                                                        <div class="flex justify-between"><span class="text-sm text-slate-600">Already Paid</span><span class="font-semibold">₱{{ number_format($paidAmount, 2) }}</span></div>
                                                        <div class="flex justify-between"><span class="text-sm text-slate-600">Remaining Balance</span><span class="font-semibold">₱{{ number_format($remaining, 2) }}</span></div>
                                                    </div>

                                                    <form method="POST" action="{{ route('admin.bookings.final_payment', ['booking' => $booking->id]) }}" class="space-y-4">
                                                        @csrf
                                                        <div>
                                                            <label class="block text-sm font-semibold text-slate-700">Final Payment Amount Received</label>
                                                            <input type="number" step="0.01" name="amount_received" value="{{ number_format($remaining, 2, '.', '') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                                        </div>

                                                        <div>
                                                            <label class="block text-sm font-semibold text-slate-700">Payment Method</label>
                                                            <select name="payment_type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                                                <option value="gcash">GCash</option>
                                                                <option value="bank_transfer">Bank Transfer</option>
                                                                <option value="cash">Cash</option>
                                                            </select>
                                                        </div>

                                                        <div>
                                                            <button type="submit" class="w-full rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 transition">
                                                                Confirm Final Payment
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>

                                                <div class="flex justify-end border-t border-slate-200 px-6 py-4">
                                                    <button type="button" onclick="closeBookingModal('booking-final-payment-modal-{{ $booking->id }}')" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        @if(in_array($booking->status, ['payment_submitted', 'pending_resolution'], true) && $latestPayment && $latestPayment->status === 'pending')
                            <tr>
                                <td colspan="6" class="p-0">
                            <div id="booking-payment-modal-{{ $booking->id }}" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/70 p-4">
                                <div class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl" onclick="event.stopPropagation()">
                                    <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                                        <div>
                                            <h3 class="text-lg font-semibold text-slate-900">Payment Verification</h3>
                                            <p class="text-sm text-slate-500">#{{ $booking->id }} • {{ ucfirst($booking->event_type) }}</p>
                                        </div>
                                        <button type="button" onclick="closeBookingModal('booking-payment-modal-{{ $booking->id }}')" class="rounded-full p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>

                                    <div class="space-y-6 p-6">
                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Client Name</p>
                                                <p class="mt-2 text-sm font-semibold text-slate-800">{{ $booking->client?->full_name ?? $booking->guest_name ?? ($booking->user?->name ?? 'Guest') }}</p>
                                            </div>
                                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Client Mobile</p>
                                                <p class="mt-2 text-sm font-semibold text-slate-800">{{ $booking->client?->phone ?? $booking->guest_phone ?? 'Not provided' }}</p>
                                            </div>
                                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Client Email</p>
                                                <p class="mt-2 text-sm font-semibold text-slate-800">{{ $booking->client?->email ?? $booking->guest_email ?? 'Not provided' }}</p>
                                            </div>
                                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Event Type</p>
                                                <p class="mt-2 text-sm font-semibold text-slate-800">{{ ucfirst($booking->event_type) }}</p>
                                            </div>
                                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Date &amp; Time</p>
                                                <p class="mt-2 text-sm font-semibold text-slate-800">{{ optional($booking->event_date)->format('F j, Y') }} • {{ $booking->event_time ?? 'Time not set' }}</p>
                                            </div>
                                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Venue</p>
                                                <p class="mt-2 text-sm font-semibold text-slate-800">{{ $booking->venue }}</p>
                                            </div>
                                        </div>

                                        <div class="rounded-xl border border-purple-200 bg-purple-50 p-4">
                                            <div class="flex items-center justify-between gap-4">
                                                <div>
                                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-purple-500">{{ $hasDamageCharges ? 'Total Obligation' : 'Total Quote' }}</p>
                                                    <p class="mt-2 text-2xl font-bold text-purple-700">₱{{ number_format($totalObligation, 2) }}</p>
                                                </div>
                                                <div class="rounded-full bg-white px-3 py-1 text-sm font-semibold text-purple-700 shadow-sm">
                                                    {{ str_replace('_', ' ', ucfirst($booking->status)) }}
                                                </div>
                                            </div>
                                        </div>

                                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                                            <div class="flex items-center gap-2">
                                                <i class="fa-solid fa-circle-info text-amber-600"></i>
                                                <h4 class="text-sm font-semibold text-amber-900">Payment Details</h4>
                                            </div>
                                            <div class="mt-4 space-y-2 text-sm text-amber-900">
                                                <p><span class="font-semibold">Method:</span> {{ strtoupper(str_replace('_', ' ', $latestPayment->payment_method ?? $latestPayment->payment_type ?? 'N/A')) }}</p>
                                                <p><span class="font-semibold">Reference:</span> {{ $latestPayment->reference_number }}</p>
                                                @if(!empty($latestPayment->receipt_image))
                                                    <a href="{{ asset('storage/' . $latestPayment->receipt_image) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center text-sm font-semibold text-purple-700 hover:text-purple-900">
                                                        <i class="fa-solid fa-image mr-1"></i>View Receipt
                                                    </a>
                                                @endif
                                            </div>
                                            <form method="POST" action="{{ route('admin.payments.verify', ['payment' => $latestPayment->id]) }}" class="mt-4">
                                                @csrf
                                                <div class="grid grid-cols-1 gap-2">
                                                    <div>
                                                        <label class="block text-sm font-semibold text-slate-700">Amount Received (PHP)</label>
                                                        @php
                                                            $defaultAmount = (float) $latestPayment->amount;
                                                        @endphp
                                                        <input type="number" step="0.01" name="amount_received" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" value="{{ number_format($defaultAmount, 2, '.', '') }}">
                                                    </div>
                                                    <div class="flex gap-2">
                                                        <button type="submit" class="flex-1 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 transition">
                                                            Verify &amp; Lock Booking
                                                        </button>
                                                        <button type="button" onclick="document.getElementById('reject-payment-form-{{ $latestPayment->id }}').submit();" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 transition">
                                                            Reject
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                            <form id="reject-payment-form-{{ $latestPayment->id }}" method="POST" action="{{ route('admin.payments.reject', ['payment' => $latestPayment->id]) }}" class="hidden">
                                                @csrf
                                            </form>
                                        </div>
                                    </div>

                                    <div class="flex justify-end border-t border-slate-200 px-6 py-4">
                                        <button type="button" onclick="closeBookingModal('booking-payment-modal-{{ $booking->id }}')" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                                            Close
                                        </button>
                                    </div>
                                </div>
                            </div>
                                </td>
                            </tr>
                        @endif
                        @endif
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                @if($statusFilter === 'approved')
                                    No bookings are currently awaiting Admin final approval.
                                @elseif($statusFilter !== 'all' || !empty($searchTerm))
                                    No bookings found matching your search and filter criteria.
                                    <div class="mt-2">
                                        <a href="{{ route('admin.bookings') }}" class="text-sm font-semibold text-purple-600 hover:text-purple-800 underline">Clear filters</a>
                                    </div>
                                @else
                                    No bookings available.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table></div>
        </div>

        @if($bookings->hasPages())
            <div class="p-5 border-t border-slate-200">
                {{ $bookings->links() }}
            </div>
        @endif
    </section>

    <script>
        function openBookingModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) {
                return;
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeBookingModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) {
                return;
            }

            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }

        document.addEventListener('DOMContentLoaded', function() {
            let debounceTimer;
            const searchInput = document.getElementById('bookingSearchInput');
            const filterForm = document.getElementById('filterForm');

            if (searchInput && filterForm) {
                // Focus at end of input to maintain typing flow after reload
                if (searchInput.value) {
                    searchInput.focus();
                    const len = searchInput.value.length;
                    searchInput.setSelectionRange(len, len);
                }

                searchInput.addEventListener('input', function() {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(function() {
                        filterForm.submit();
                    }, 600);
                });
                
                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        clearTimeout(debounceTimer);
                        filterForm.submit();
                    }
                });
            }
        });

        function clearDateRange() {
            document.getElementById('event_date_from').value = '';
            document.getElementById('event_date_to').value = '';
            document.getElementById('filterForm').submit();
        }
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dateFromInput = document.getElementById('event_date_from');
            const dateToInput = document.getElementById('event_date_to');
            const rangeInput = document.getElementById('event_date_range');
            
            let initialDates = [];
            if (dateFromInput && dateFromInput.value) initialDates.push(dateFromInput.value);
            if (dateToInput && dateToInput.value) initialDates.push(dateToInput.value);

            if (rangeInput) {
                flatpickr(rangeInput, {
                    mode: "range",
                    dateFormat: "Y-m-d",
                    defaultDate: initialDates,
                    showMonths: window.innerWidth > 768 ? 2 : 1,
                    onChange: function(selectedDates, dateStr, instance) {
                        if (selectedDates.length === 2) {
                            if (dateFromInput) dateFromInput.value = instance.formatDate(selectedDates[0], "Y-m-d");
                            if (dateToInput) dateToInput.value = instance.formatDate(selectedDates[1], "Y-m-d");
                            document.getElementById('filterForm').submit();
                        } else if (selectedDates.length === 0) {
                            if (dateFromInput) dateFromInput.value = "";
                            if (dateToInput) dateToInput.value = "";
                            document.getElementById('filterForm').submit();
                        }
                    }
                });
            }
        });
    </script>
</x-admin-layout>
