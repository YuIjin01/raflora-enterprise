<x-admin-layout title="Booking Review">
    <div class="mb-6 flex flex-col items-start justify-between gap-4 lg:flex-row lg:items-center">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.24em] text-purple-700">Operational booking review</p>
            <h1 class="mt-2 text-2xl font-bold text-slate-900 sm:text-3xl">Booking #{{ $booking->id }} · {{ ucfirst($booking->event_type) }}</h1>
            <p class="mt-1 text-sm text-slate-500">Client: {{ $booking->client?->full_name ?? 'Guest' }}</p>
            <p class="mt-2">
                @if($booking->status === 'pending')
                    <span class="rf-badge rf-badge--warning">
                        Pending Admin Review
                    </span>
                @elseif($booking->status === 'downpayment_received')
                    <span class="rf-badge rf-badge--success">
                        {{ $booking->status_display_label }}
                    </span>
                @else
                    <span class="rf-badge rf-badge--primary">
                        {{ $booking->status_display_label }}
                    </span>
                @endif
            </p>
        </div>
        <a href="{{ route('admin.bookings') }}" class="rf-btn rf-btn-outline">
            &larr; Back to Bookings
        </a>
    </div>

    @if(session('success'))
        <div class="rf-alert rf-alert--success mb-6" role="status"><i class="fa-solid fa-circle-check" aria-hidden="true"></i>{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="rf-alert rf-alert--danger mb-6" role="alert"><i class="fa-solid fa-circle-xmark" aria-hidden="true"></i>{{ session('error') }}</div>
    @endif

    @if(!empty($quoteReadOnly) && $quoteReadOnly)
        <div class="rf-alert rf-alert--warning mb-6" role="status">
            This quotation is locked because the booking is currently {{ strtoupper($booking->status) }}.
        </div>
    @endif

    @php
        $quoteReadOnly = $quoteReadOnly ?? false;
        $bookingLocked = $quoteReadOnly;
    @endphp

        <!-- Booking Navigation Tabs -->
    <div class="mb-6 border-b border-slate-200">
        <nav class="-mb-px flex space-x-1 sm:space-x-6 overflow-x-auto" aria-label="Tabs">
            <button type="button" onclick="switchTab('tab-overview')" class="tab-button active border-purple-500 text-purple-600 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition" data-target="tab-overview">Overview</button>
            <button type="button" onclick="switchTab('tab-details')" class="tab-button border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition" data-target="tab-details">Event Details & AI</button>
            <button type="button" onclick="switchTab('tab-quotation')" class="tab-button border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition" data-target="tab-quotation">Quotation</button>
            <button type="button" onclick="switchTab('tab-materials')" class="tab-button border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition" data-target="tab-materials">Materials & Inventory</button>
            <button type="button" onclick="switchTab('tab-preparation')" class="tab-button border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition" data-target="tab-preparation">Preparation</button>
            <button type="button" onclick="switchTab('tab-staff')" class="tab-button border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition" data-target="tab-staff">Staff Assignment</button>
            <button type="button" onclick="switchTab('tab-communication')" class="tab-button border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition" data-target="tab-communication">Communication</button>
        </nav>
    </div>

    <div id="tab-overview" class="tab-content block space-y-6">
{{-- Split forms: items adjustments and admin note/quote actions are separate to avoid validation collisions --}}

    @php
        $totalObligation = (float) $booking->total_obligation;
        $paidAmount = (float) $booking->total_paid;
        $remaining = max(0, $totalObligation - $paidAmount);
        
        $canLogFinalPayment = in_array($booking->status, ['event_in_progress', 'event_completed', 'pending_return', 'pending_resolution'], true) && ($remaining > 0);
        $showMarkInProgress = in_array($booking->status, ['downpayment_received', 'confirmed'], true);
        $showMarkEventCompleted = $booking->status === 'event_in_progress';
        $hasCompletedReturn = $booking->returns->where('status', 'Completed')->isNotEmpty();
        $hasCancellationRecovery = ($booking->status === 'cancelled') && ($booking->returns->isNotEmpty() || $booking->hasDispatchedReusableMaterials());
        
        $showOperationalActions = $canLogFinalPayment || $showMarkInProgress || $showMarkEventCompleted || ($booking->status === 'event_completed') || in_array($booking->status, ['pending_return', 'pending_resolution'], true) || $hasCancellationRecovery || $booking->status === 'completed';
        
        $damageLoss = $booking->returns ? $booking->returns->sum('total_damage_charge') : 0;
        $baseQuote = $booking->final_quoted_price ?: $booking->total_quoted;
    @endphp

    <section class="rf-panel overflow-hidden p-5 sm:p-6 mb-6" aria-labelledby="financial-summary-heading">
        <h2 id="financial-summary-heading" class="mb-4 border-b border-purple-50 pb-2 text-lg font-bold text-gray-800">Financial Summary &amp; Settlement</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-6 text-sm">
            <div>
                <p class="text-slate-500 uppercase font-semibold text-[10px]">Base Quote</p>
                <p class="font-bold text-slate-800">₱{{ number_format($baseQuote, 2) }}</p>
            </div>
            <div>
                <p class="text-rose-500 uppercase font-semibold text-[10px]">Damage / Loss</p>
                <p class="font-bold text-rose-700">₱{{ number_format($damageLoss, 2) }}</p>
            </div>
            <div>
                <p class="text-purple-600 uppercase font-semibold text-[10px]">Total Obligation</p>
                <p class="font-bold text-purple-900">₱{{ number_format($totalObligation, 2) }}</p>
            </div>
            <div>
                <p class="text-emerald-600 uppercase font-semibold text-[10px]">Verified Paid</p>
                <p class="font-bold text-emerald-900">₱{{ number_format($paidAmount, 2) }}</p>
            </div>
            <div>
                <p class="text-indigo-600 uppercase font-semibold text-[10px]">Remaining Balance</p>
                <p class="font-bold text-indigo-900">₱{{ number_format($remaining, 2) }}</p>
                @if($remaining > 0)
                    <span class="inline-flex items-center justify-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold uppercase text-amber-800 mt-1">Balance Pending</span>
                @else
                    <span class="inline-flex items-center justify-center rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase text-emerald-800 mt-1">Fully Paid</span>
                @endif
            </div>
        </div>
    </section>

    @if($showOperationalActions)
    <section class="rf-panel overflow-hidden p-5 sm:p-6 mb-6" aria-labelledby="operational-actions-heading">
        <h2 id="operational-actions-heading" class="mb-4 border-b border-purple-50 pb-2 text-lg font-bold text-gray-800">Operational Actions</h2>
        <div class="flex flex-wrap items-center gap-3">
            @if($showMarkInProgress)
                <form method="POST" action="{{ route('admin.bookings.update', ['booking' => $booking->id]) }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="event_type" value="{{ $booking->event_type }}">
                    <input type="hidden" name="event_date" value="{{ optional($booking->event_date)->format('Y-m-d') }}">
                    <input type="hidden" name="venue" value="{{ $booking->venue }}">
                    <input type="hidden" name="status" value="{{ $booking->status }}">
                    <button type="submit" name="action" value="mark_event_in_progress" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-4 py-2 rounded-lg text-sm transition shadow-sm">
                        <i class="fa-solid fa-play mr-2"></i>Mark Event In Progress
                    </button>
                </form>
            @endif

            @if($showMarkEventCompleted)
                <form method="POST" action="{{ route('admin.bookings.update', ['booking' => $booking->id]) }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="event_type" value="{{ $booking->event_type }}">
                    <input type="hidden" name="event_date" value="{{ optional($booking->event_date)->format('Y-m-d') }}">
                    <input type="hidden" name="venue" value="{{ $booking->venue }}">
                    <input type="hidden" name="status" value="{{ $booking->status }}">
                    <button type="submit" name="action" value="mark_event_completed" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-4 py-2 rounded-lg text-sm transition shadow-sm">
                        <i class="fa-solid fa-flag-checkered mr-2"></i>Mark Event Completed
                    </button>
                </form>
            @endif

            @if($canLogFinalPayment)
                <button type="button" onclick="openBookingModal('booking-show-final-payment-modal')" class="bg-purple-700 hover:bg-purple-800 text-white font-semibold px-4 py-2 rounded-lg text-sm transition shadow-sm">
                    <i class="fa-solid fa-money-bill mr-2"></i>Log Final Payment
                </button>
            @endif

            @if($booking->status === 'event_completed' || in_array($booking->status, ['pending_return', 'pending_resolution'], true) || $hasCancellationRecovery || $booking->status === 'completed')
                @if(!$hasCompletedReturn)
                    <a href="{{ route('admin.return-tracking.manage', ['booking' => $booking->id]) }}" class="bg-purple-50 text-purple-700 font-semibold px-4 py-2 rounded-lg border border-purple-200 hover:bg-purple-100 text-sm transition shadow-sm">
                        <i class="fa-solid fa-box-open mr-2"></i>Manage Return Audit
                    </a>
                @else
                    <a href="{{ route('admin.return-tracking.manage', ['booking' => $booking->id]) }}" class="bg-slate-50 text-slate-700 font-semibold px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-100 text-sm transition shadow-sm">
                        <i class="fa-solid fa-box-open mr-2"></i>View Return Audit
                    </a>
                @endif
            @endif
        </div>
        
        @if($canLogFinalPayment)
            <div id="booking-show-final-payment-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/70 p-4">
                <div class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl" onclick="event.stopPropagation()">
                    <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Log Final Payment</h3>
                            <p class="text-sm text-slate-500">#{{ $booking->id }} • {{ ucfirst($booking->event_type) }}</p>
                        </div>
                        <button type="button" onclick="closeBookingModal('booking-show-final-payment-modal')" class="rounded-full p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700">
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
                        <button type="button" onclick="closeBookingModal('booking-show-final-payment-modal')" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">Close</button>
                    </div>
                </div>
            </div>
        @endif
    </section>
    @endif

    @if($booking->payments->count() > 0)
    <section class="rf-panel overflow-hidden p-5 sm:p-6 mb-6" aria-labelledby="payments-heading">
        <h2 id="payments-heading" class="mb-4 border-b border-purple-50 pb-2 text-lg font-bold text-gray-800">Submitted Payments</h2>
        <div class="space-y-4">
            @foreach($booking->payments as $payment)
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between p-4 bg-slate-50 border border-slate-200 rounded-lg">
                    <div>
                        <p class="font-bold text-slate-800">Amount: ₱{{ number_format($payment->amount, 2) }} <span class="text-xs text-slate-500 ml-2 uppercase">{{ $payment->payment_option }}</span></p>
                        <p class="text-sm text-slate-600 mt-1">Ref: <span class="font-mono">{{ $payment->reference_number }}</span> ({{ strtoupper($payment->payment_type) }})</p>
                        <p class="text-xs text-slate-500 mt-1">Status: <span class="font-semibold text-slate-700">{{ ucfirst($payment->status) }}</span></p>
                    </div>
                    @if($payment->status === 'pending')
                    <div class="flex gap-2">
                        <form method="POST" action="{{ route('admin.payments.verify', $payment->id) }}">
                            @csrf
                            <button type="submit" onclick="return confirm('Verify this payment?')" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-sm">Verify</button>
                        </form>
                        <form method="POST" action="{{ route('admin.payments.reject', $payment->id) }}">
                            @csrf
                            <button type="submit" onclick="return confirm('Reject this payment?')" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-sm">Reject</button>
                        </form>
                    </div>
                    @else
                        @if($payment->verified_by)
                            <div class="text-xs text-slate-500">Verified at {{ optional($payment->verified_at)->format('M d, Y h:i A') }}</div>
                        @endif
                    @endif
                </div>
            @endforeach
        </div>
    </section>
    @endif

    </div>
    <div id="tab-details" class="tab-content hidden space-y-6">
        <!-- SECTION 1: CLIENT REQUEST & INSPIRATION -->
        <section class="rf-panel overflow-hidden p-5 sm:p-6" aria-labelledby="event-details-heading">
            <h2 id="event-details-heading" class="mb-4 border-b border-purple-50 pb-2 text-lg font-bold text-gray-800">Event Details &amp; Inspiration</h2>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                <!-- Inspiration Image -->
                <div class="col-span-1 rounded-lg overflow-hidden bg-slate-50 border border-slate-200">
                    @if($booking->inspiration_image)
                        <div class="aspect-[4/5] relative">
                            <img src="{{ asset('storage/' . $booking->inspiration_image) }}" alt="Inspiration Image" class="absolute inset-0 w-full h-full object-cover">
                        </div>
                    @else
                        <div class="aspect-[4/5] flex items-center justify-center">
                            <span class="text-slate-400 text-sm">No Image Provided</span>
                        </div>
                    @endif
                </div>
                
                <!-- Booking Details -->
                <div class="col-span-2 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-slate-500 uppercase font-semibold">Event Date</p>
                            <p class="text-slate-800 font-medium">{{ optional($booking->event_date)->format('F j, Y') ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 uppercase font-semibold">Venue</p>
                            <p class="text-slate-800 font-medium">{{ $booking->venue ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 uppercase font-semibold">Client Mobile</p>
                            <p class="text-slate-800 font-medium">{{ $booking->client?->phone ?? $booking->guest_phone ?? 'Not provided' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 uppercase font-semibold">Client Email</p>
                            <p class="text-slate-800 font-medium">{{ $booking->client?->email ?? $booking->guest_email ?? 'Not provided' }}</p>
                        </div>
                    <div>
                        <p class="text-xs text-slate-500 uppercase font-semibold mb-1">Special Request Notes from Client</p>
                        <div class="bg-slate-50 p-4 rounded-lg text-sm text-slate-700 border border-slate-100 min-h-[5rem]">
                            {{ $booking->special_requests ?: 'No special requests provided.' }}
                        </div>
                    </div>
                    
                </div>
            </div>
        </section>

    </div>
    <div id="tab-quotation" class="tab-content hidden space-y-6">
        <!-- SECTION 2: PROPOSAL UPLOADS -->
        <section class="rf-panel mt-6 overflow-hidden p-5 sm:p-6" aria-labelledby="proposal-uploads-heading">
            <div class="flex items-center justify-between gap-4 mb-4 border-b border-purple-50 pb-2">
                <h2 id="proposal-uploads-heading" class="text-lg font-bold text-gray-800">Proposal &amp; Presentation Versions</h2>
                @if(!$quoteReadOnly)
                    <form method="POST" action="{{ route('admin.bookings.presentations.store', ['booking' => $booking->id]) }}" enctype="multipart/form-data" class="flex items-center gap-2">
                        @csrf
                        <input type="file" name="proposal_file" class="text-sm text-slate-600" required>
                        <button type="submit" class="bg-purple-700 hover:bg-purple-800 text-white px-4 py-2 rounded-lg text-sm font-semibold">Upload Proposal</button>
                    </form>
                @else
                    <span class="text-sm text-slate-500">Proposal uploads are disabled while this booking is locked.</span>
                @endif
            </div>
            @php $presentations = $booking->presentations()->orderBy('created_at')->get(); @endphp
            @forelse($presentations as $presentation)
                <div class="rounded-lg border border-slate-200 p-4 mb-3 flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="font-semibold text-slate-800">{{ $presentation->version }} · {{ $presentation->file_name }}</p>
                        <p class="text-sm text-slate-500">Uploaded {{ optional($presentation->sent_at)->format('M d, Y h:i A') }}</p>
                        @if($presentation->feedback_text)
                            <p class="text-sm text-slate-600 mt-2">Client feedback: {{ $presentation->feedback_text }}</p>
                        @endif
                    </div>
                    <a href="{{ asset('storage/' . $presentation->file_path) }}" target="_blank" class="text-sm font-semibold text-purple-700">Preview / Download</a>
                </div>
            @empty
                <p class="text-sm text-slate-500">No proposal versions uploaded yet.</p>
            @endforelse
        </section>

        <!-- SECTION 3: ITEM & PRICING BREAKDOWN -->
        <section class="rf-panel mt-6 overflow-hidden p-5 sm:p-6" aria-labelledby="pricing-breakdown-heading">
            <h2 id="pricing-breakdown-heading" class="mb-2 border-b border-purple-50 pb-2 text-lg font-bold text-gray-800">Material review &amp; quotation</h2>
            <p class="mb-4 text-sm leading-6 text-slate-500">AI-suggested materials remain awaiting Admin confirmation until explicitly reviewed. Stock indicators reflect the existing inventory state.</p>
            
            @if(isset($activeQuotation))
                <div class="mb-6 rounded-lg border border-purple-100 bg-purple-50 p-4">
                    <h3 class="text-sm font-bold text-purple-900 mb-2">Active Issued Quotation (v{{ $activeQuotation->version }})</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <p class="text-purple-700 text-xs uppercase font-semibold">Total Cost</p>
                            <p class="font-bold text-purple-900">₱{{ number_format($activeQuotation->final_quoted_price, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-purple-700 text-xs uppercase font-semibold">Valid Until</p>
                            <p class="font-medium text-purple-900">{{ optional($activeQuotation->valid_until)->format('M d, Y') ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-purple-700 text-xs uppercase font-semibold">Status</p>
                            <p class="font-medium text-purple-900">{{ ucfirst($activeQuotation->status) }}</p>
                        </div>
                        <div>
                            <p class="text-purple-700 text-xs uppercase font-semibold">Issued By</p>
                            <p class="font-medium text-purple-900">{{ $activeQuotation->issuedBy?->name ?? 'Admin' }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <form id="mainBookingForm" method="POST" action="{{ route('admin.bookings.update', ['booking' => $booking->id]) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="event_type" value="{{ $booking->event_type }}">
                <input type="hidden" name="event_date" value="{{ optional($booking->event_date)->format('Y-m-d') }}">
                <input type="hidden" name="venue" value="{{ $booking->venue }}">
                <input type="hidden" name="status" value="{{ $booking->status }}">

                <div class="overflow-x-auto -mx-4 sm:mx-0 w-full">
                    <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-purple-50 text-purple-900 text-xs uppercase tracking-wide">
                            <th scope="col" class="px-4 py-3 rounded-tl-lg font-semibold">Item Name</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Qty</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Unit Cost (₱)</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Total Cost (₱)</th>
                            <th scope="col" class="px-4 py-3 rounded-tr-lg font-semibold text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-purple-50">
                        @php
                            $itemsToDisplay = [];
                            $seenItemKeys = [];
                            $addDisplayItem = function ($id, $name, $qty, $unitPrice, $inventoryId = null, $isAiSuggested = false, $notes = null, $confirmedAt = null) use (&$itemsToDisplay, &$seenItemKeys) {
                                $normalizedName = strtolower(trim((string) $name));
                                if ($normalizedName === '') {
                                    return;
                                }

                                if (isset($seenItemKeys[$normalizedName])) {
                                    return;
                                }

                                $seenItemKeys[$normalizedName] = true;
                                $itemsToDisplay[] = [
                                    'id' => $id,
                                    'name' => $name,
                                    'qty' => $qty,
                                    'unit_price' => $unitPrice,
                                    'inventory_id' => $inventoryId,
                                    'is_ai_suggested' => $isAiSuggested,
                                    'notes' => $notes,
                                    'confirmed_at' => $confirmedAt,
                                ];
                            };

                            if ($booking->bookingItems->count() > 0) {
                                foreach ($booking->bookingItems as $bookingItem) {
                                    $addDisplayItem(
                                        $bookingItem->id,
                                        $bookingItem->item_name ?? ($bookingItem->inventoryItem?->name ?? 'Unknown Item'),
                                        $bookingItem->quantity,
                                        $bookingItem->quoted_unit_price,
                                        $bookingItem->inventory_item_id,
                                        (bool) $bookingItem->is_ai_suggested,
                                        $bookingItem->notes,
                                        $bookingItem->confirmed_at,
                                    );
                                }
                            }

                            if ($analysis) {
                                foreach ($analysis->suggested_materials as $item) {
                                    $addDisplayItem(
                                        null,
                                        $item['item_name'] ?? 'Unknown Item',
                                        $item['estimated_quantity'] ?? 1,
                                        $item['estimated_unit_cost_php'] ?? 0,
                                        null,
                                        true,
                                        null,
                                        null,
                                    );
                                }
                            }

                            if (empty($itemsToDisplay) && $booking->inventoryItems->count() > 0) {
                                foreach ($booking->inventoryItems as $inv) {
                                    $addDisplayItem(
                                        null,
                                        $inv->name,
                                        $inv->pivot->quantity,
                                        $inv->pivot->quoted_unit_price,
                                        $inv->id,
                                        false,
                                        null,
                                        null,
                                    );
                                }
                            }
                        @endphp
                        
                        @forelse($itemsToDisplay as $idx => $item)
                            @php
                                $invData = isset($allInventoryItems) ? $allInventoryItems->firstWhere('name', $item['name']) : null;
                                $isOutOfStock = $invData ? ($item['qty'] > $invData->current_stock) : false;
                                $stockStr = $invData ? floatval($invData->current_stock) : 0;
                                $isUnmatchedSuggestion = $item['is_ai_suggested'] && empty($item['inventory_id']);
                                $isConfirmed = !empty($item['confirmed_at']);
                                $rowClass = $isUnmatchedSuggestion ? 'bg-amber-50/40' : ($isOutOfStock ? 'bg-red-50/50' : 'bg-emerald-50/40');
                                $stockBadgeClass = $isUnmatchedSuggestion ? 'bg-amber-100 text-amber-700' : ($isOutOfStock ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700');
                                $stockBadgeLabel = $isUnmatchedSuggestion ? 'Unmatched' : ($isOutOfStock ? 'Out of Stock' : 'In Stock');
                            @endphp
                            <tr class="{{ $rowClass }} hover:bg-slate-50 transition" id="row_{{ $idx }}">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-medium text-slate-800 text-sm" id="display_name_{{ $idx }}">{{ $item['name'] }}</span>
                                        @if($isUnmatchedSuggestion)
                                            <span class="badge badge-warning px-2 py-0.5 text-[10px] uppercase tracking-wider font-bold bg-amber-100 text-amber-700 rounded-full">Unmatched / AI Suggestion</span>
                                            <div class="flex flex-wrap gap-2 mt-1">
                                                <button type="button" onclick="openAiMaterialModal({{ $idx }}, 'link')" class="text-[10px] font-semibold underline text-purple-700">Link to Existing Inventory</button>
                                                <button type="button" onclick="openAiMaterialModal({{ $idx }}, 'promote')" class="text-[10px] font-semibold underline text-emerald-700">Promote to Catalog</button>
                                            </div>
                                        @elseif($isConfirmed)
                                            <span class="px-2 py-0.5 text-[10px] uppercase tracking-wider font-bold bg-emerald-100 text-emerald-700 rounded-full">{{ $item['is_ai_suggested'] ? 'Admin Confirmed' : 'Package Inclusion' }}</span>
                                            <span class="px-2 py-0.5 text-[10px] uppercase tracking-wider font-bold {{ $stockBadgeClass }} rounded-full" title="{{ $isOutOfStock ? 'Only ' . $stockStr . ' left in master inventory' : 'Inventory remains available for this item' }}">{{ $stockBadgeLabel }}</span>
                                        @else
                                            @if($item['is_ai_suggested'])
                                                <span class="px-2 py-0.5 text-[10px] uppercase tracking-wider font-bold bg-amber-100 text-amber-700 rounded-full">AI Suggested - Awaiting Admin Confirmation</span>
                                                @if(!empty($item['id']) && !empty($item['inventory_id']))
                                                    <button type="submit" form="confirmMaterial_{{ $idx }}" class="text-[10px] font-semibold underline text-emerald-700">Confirm Material</button>
                                                @endif
                                            @endif
                                            <span class="px-2 py-0.5 text-[10px] uppercase tracking-wider font-bold {{ $stockBadgeClass }} rounded-full" title="{{ $isOutOfStock ? 'Only ' . $stockStr . ' left in master inventory' : 'Inventory remains available for this item' }}">{{ $stockBadgeLabel }}</span>
                                        @endif
                                        @if(!$quoteReadOnly && $isOutOfStock && $invData && $invData->substitutes->count() > 0)
                                            <button type="button" onclick="openSubstituteModal({{ $idx }}, {{ $invData->id }})" class="text-xs font-semibold text-purple-600 hover:text-purple-800 underline"><i class="fa-solid fa-rotate mr-1"></i>Substitute</button>
                                        @endif
                                    </div>
                                    <input type="hidden" id="input_name_{{ $idx }}" name="items[{{ $idx }}][item_name]" value="{{ $item['name'] }}">
                                    <input type="hidden" id="input_inventory_{{ $idx }}" name="items[{{ $idx }}][inventory_item_id]" value="{{ $item['inventory_id'] ?? '' }}">
                                    <input type="hidden" name="items[{{ $idx }}][is_ai_suggested]" value="{{ !empty($item['is_ai_suggested']) ? '1' : '0' }}">
                                    @if(!empty($item['id']))
                                        <input type="hidden" name="items[{{ $idx }}][booking_item_id]" value="{{ $item['id'] }}">
                                    @endif
                                    {{-- Simplified UI: AI-suggested items are shown as editable rows without inventory linking controls. --}}
                                </td>
                                <td class="px-4 py-3 w-28">
                                    @if($quoteReadOnly)
                                        <div class="text-sm text-slate-800">{{ (int) $item['qty'] }}</div>
                                    @else
                                        <input type="number" id="input_qty_{{ $idx }}" name="items[{{ $idx }}][quantity]" min="0" step="1" inputmode="numeric" value="{{ (int) $item['qty'] }}" class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500" @if($bookingLocked) disabled @endif>
                                    @endif
                                </td>
                                <td class="px-4 py-3 w-36">
                                    @if($quoteReadOnly)
                                        <div class="text-sm text-slate-800">₱ {{ number_format((float) $item['unit_price'], 2) }}</div>
                                    @else
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm">₱</span>
                                            <input type="number" step="0.01" id="input_price_{{ $idx }}" name="items[{{ $idx }}][unit_price]" min="0" value="{{ number_format((float) $item['unit_price'], 2, '.', '') }}" class="w-full border border-slate-300 rounded pl-7 pr-2 py-1.5 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500" @if($bookingLocked) disabled @endif>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-700 font-medium text-sm" id="total_cost_{{ $idx }}">
                                    ₱ {{ number_format((float) $item['qty'] * (float) $item['unit_price'], 2) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if(!$quoteReadOnly)
                                        <label class="inline-flex items-center justify-center cursor-pointer text-red-500 hover:text-red-700 transition">
                                            <input type="checkbox" name="items[{{ $idx }}][remove]" value="1" class="w-4 h-4 rounded border-slate-300 text-red-600 focus:ring-red-500 mr-1.5" @if($bookingLocked) disabled @endif>
                                            <span class="text-xs font-semibold uppercase">Remove</span>
                                        </label>
                                    @else
                                        <span class="text-xs text-slate-500">Read-only</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-slate-500 text-sm">No items suggested or added.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
                </div>

            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-12 lg:items-start">
                <div class="lg:col-span-7 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="mb-2 flex flex-wrap items-center justify-between gap-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Admin Notes / Message to Client</p>
                    </div>
                    <textarea id="admin_notes" name="admin_notes" rows="5" style="min-height: 140px;" class="w-full rounded-lg border border-purple-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-purple-500 focus:ring-2 focus:ring-purple-500 placeholder-slate-400" placeholder="Write a custom message or update for the client...">{{ old('admin_notes', $booking->admin_notes) }}</textarea>

                    <div class="mt-4 flex flex-wrap items-center justify-start gap-3">
                        @php
                            $quotationExpired = $booking->status === 'quotation_sent'
                                && $booking->price_valid_until
                                && \Carbon\Carbon::parse($booking->price_valid_until)->isPast();
                            $tentativeQuote = $booking->tentativeQuotation();
                            $hasTentative = !is_null($tentativeQuote);
                            $reconfirmationDue = $booking->isPriceReconfirmationDue();
                        @endphp

                        @if($hasTentative)
                            <div class="w-full mb-4 rounded-xl border {{ $reconfirmationDue ? 'border-amber-300 bg-amber-50' : 'border-purple-200 bg-purple-50' }} p-4 shadow-sm">
                                <div class="flex items-start gap-3">
                                    <i class="fa-solid {{ $reconfirmationDue ? 'fa-clock-rotate-left text-amber-600' : 'fa-circle-info text-purple-600' }} text-lg mt-0.5"></i>
                                    <div class="flex-1">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <h4 class="text-sm font-bold {{ $reconfirmationDue ? 'text-amber-900' : 'text-purple-900' }}">
                                                {{ $reconfirmationDue ? 'Price Reconfirmation Due (Event Approaches in <=' . \App\Models\Setting::getPriceReconfirmationThresholdDays() . ' Days)' : 'Tentative Long-Term Floral Pricing' }}
                                            </h4>
                                            <span class="inline-flex rounded-full {{ $reconfirmationDue ? 'bg-amber-200 text-amber-900' : 'bg-purple-200 text-purple-900' }} px-2.5 py-0.5 text-xs font-semibold">
                                                v{{ $tentativeQuote->version }} Tentative
                                            </span>
                                        </div>
                                        <p class="mt-1 text-xs text-slate-600">
                                            This quotation's pricing is tentative due to long-term floral market volatility.
                                            Review current material costs, inventory availability, and verified client payments before event execution.
                                        </p>

                                        <div class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs border-t border-slate-200/60 pt-2.5">
                                            <div>
                                                <span class="text-slate-500 block">Quoted Price (v{{ $tentativeQuote->version }})</span>
                                                <strong class="text-slate-800 font-semibold">₱{{ number_format((float) $tentativeQuote->final_quoted_price, 2) }}</strong>
                                            </div>
                                            <div>
                                                <span class="text-slate-500 block">Current BOM Subtotal</span>
                                                <strong class="text-purple-700 font-semibold">₱{{ number_format((float) (($booking->raw_materials_sum ?? 0) * ($booking->multiplier ?? 3.0)), 2) }}</strong>
                                            </div>
                                            <div>
                                                <span class="text-slate-500 block">Verified Payments</span>
                                                <strong class="text-emerald-700 font-semibold">₱{{ number_format((float) $booking->total_paid, 2) }}</strong>
                                            </div>
                                            <div>
                                                <span class="text-slate-500 block">Remaining Balance</span>
                                                <strong class="text-indigo-700 font-semibold">₱{{ number_format((float) $booking->remaining_balance, 2) }}</strong>
                                            </div>
                                        </div>

                                        <div class="mt-4 flex flex-wrap gap-2 pt-2 border-t border-slate-200/60">
                                            <button type="submit" name="action" value="reconfirm_price_unchanged" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-700 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-emerald-800 transition">
                                                <i class="fa-solid fa-check"></i> Reconfirm Current Price (Unchanged)
                                            </button>
                                            <button type="submit" name="action" value="reconfirm_price_revised" class="inline-flex items-center gap-1.5 rounded-lg bg-purple-700 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-purple-800 transition">
                                                <i class="fa-solid fa-rotate"></i> Issue Revised Quotation (Price / Terms Changed)
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @unless($quoteReadOnly && !$hasTentative)
                            @if($quotationExpired)
                                <div class="flex-1 min-w-[220px] flex items-center gap-2 text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-4 py-2">
                                    <i class="fa-solid fa-clock-rotate-left text-amber-500"></i>
                                    Quotation expired on <strong class="ml-1">{{ \Carbon\Carbon::parse($booking->price_valid_until)->format('M d, Y') }}</strong>. Client cannot approve until re-issued.
                                </div>
                                <button type="submit" name="action" value="reissue_quotation"
                                    class="bg-amber-600 hover:bg-amber-700 text-white px-8 py-2.5 rounded-lg text-sm font-bold transition shadow-sm">
                                    <i class="fa-solid fa-rotate-right mr-2"></i>Re-issue Quotation (Refresh 7-Day Window)
                                </button>
                            @else
                                @if($booking->status === 'approved')
                                    <button type="submit" form="finalApproveForm" class="bg-emerald-700 hover:bg-emerald-800 text-white px-8 py-2.5 rounded-lg text-sm font-bold transition shadow-sm">Final Approve &amp; Enable Payment</button>
                                @endif
                                <button type="submit" name="action" value="send_quotation" class="bg-purple-700 hover:bg-purple-800 text-white px-8 py-2.5 rounded-lg text-sm font-bold transition shadow-sm">
                                    Approve &amp; Send Official Quote to Client
                                </button>
                            @endif
                        @else
                            <div class="rounded-lg bg-slate-50 border border-slate-200 p-4 text-sm text-slate-700">
                                This quote is read-only while the booking is in progress or completed.
                            </div>
                        @endunless
                    </div>
                </div>

                <div class="lg:col-span-5">
                    <div class="bg-slate-50 rounded-lg border border-slate-200 p-5 h-full">
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-sm text-slate-600">Raw Wholesale Subtotal (BOM Cost):</span>
                            <span id="raw-wholesale-subtotal" class="font-medium text-slate-800">₱ {{ number_format($booking->raw_materials_sum ?? 0, 2) }}</span>
                        </div>

                        <div class="flex justify-between items-center mb-3">
                            <span class="text-sm text-slate-600">Markup Multiplier:</span>
                            <input id="markup-multiplier" type="number" step="0.1" name="multiplier" value="{{ old('multiplier', $booking->multiplier ?? 3.0) }}" class="w-24 text-right border border-slate-300 rounded px-2 py-1 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500" @if($bookingLocked) disabled @endif>
                        </div>

                        <div class="flex justify-between items-center mb-3">
                            <span class="text-sm text-slate-600">Calculated Quotation Total:</span>
                            <span id="calculated-quotation-total" class="font-medium text-slate-800">₱ {{ number_format(($booking->raw_materials_sum ?? 0) * ($booking->multiplier ?? 3.0), 2) }}</span>
                        </div>

                        <div class="border-t border-slate-200 pt-3 mt-3 flex justify-between items-center">
                            <span class="text-sm font-semibold text-slate-800">Final Quoted Total (Override):</span>
                            <div class="relative w-32">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm font-medium">₱</span>
                                <input id="final-quoted-total-override" type="number" step="0.01" name="final_quoted_price" value="{{ old('final_quoted_price', $booking->final_quoted_price ?: $booking->total_quoted) }}" class="w-full border border-purple-300 bg-white rounded pl-7 pr-2 py-1.5 font-bold text-purple-700 text-right focus:border-purple-500 focus:ring-1 focus:ring-purple-500" @if($bookingLocked) disabled @endif>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    @if($booking->status === 'approved')
        <form id="finalApproveForm" method="POST" action="{{ route('admin.bookings.final-approve', ['booking' => $booking->id]) }}" class="hidden">
            @csrf
        </form>
    @endif

    @foreach($itemsToDisplay as $idx => $item)
        @if($item['is_ai_suggested'] && empty($item['inventory_id']))
            <form id="aiLinkForm_{{ $idx }}" method="POST" action="{{ !empty($item['id']) ? route('admin.bookings.ai.link', ['booking' => $booking->id, 'bookingItem' => $item['id']]) : route('admin.bookings.ai.link', ['booking' => $booking->id]) }}" class="hidden">
                @csrf
                @if(!empty($item['id']))
                    <input type="hidden" name="booking_item_id" value="{{ $item['id'] }}">
                @endif
                <input type="hidden" name="link_inventory_item_id" id="aiLinkInventory_{{ $idx }}">
            </form>
            <form id="aiPromoteForm_{{ $idx }}" method="POST" action="{{ route('admin.bookings.ai.promote', ['booking' => $booking->id]) }}" class="hidden">
                @csrf
                @if(!empty($item['id']))
                    <input type="hidden" name="booking_item_id" value="{{ $item['id'] }}">
                @endif
                <input type="hidden" name="catalog_name" id="aiPromoteName_{{ $idx }}">
                <input type="hidden" name="category" id="aiPromoteCategory_{{ $idx }}">
                <input type="hidden" name="unit" id="aiPromoteUnit_{{ $idx }}">
                <input type="hidden" name="is_perishable" id="aiPromotePerishable_{{ $idx }}" value="1">
            </form>
        @endif
    @endforeach

    @foreach($itemsToDisplay as $idx => $item)
        @if(!empty($item['id']) && !$item['confirmed_at'] && !empty($item['inventory_id']))
            <form method="POST" action="{{ route('admin.bookings.items.confirm', ['booking' => $booking->id, 'bookingItem' => $item['id']]) }}" class="hidden">
                @csrf
                <button type="submit" id="confirmMaterial_{{ $idx }}">Confirm material</button>
            </form>
        @endif
    @endforeach

    <!-- Decline Form Separated -->
    @unless($quoteReadOnly)
        <form method="POST" action="{{ route('admin.bookings.decline', ['booking' => $booking->id]) }}" class="mt-4 flex justify-end">
            @csrf
            <!-- You could include a decline reason modal or text input here if needed, but for simplicity we keep the button isolated -->
            <button type="submit" onclick="return confirm('Are you sure you want to reject this booking?')" class="bg-white border-2 border-red-200 hover:bg-red-50 text-red-600 px-6 py-2.5 rounded-lg text-sm font-bold transition">
                Reject / Decline Booking
            </button>
        </form>
    @endunless

    </div>
    <div id="tab-materials" class="tab-content hidden space-y-6">
        <!-- Materials will go here -->
    </div>
    <div id="tab-preparation" class="tab-content hidden space-y-6">
        <section class="rf-panel overflow-hidden p-5 sm:p-6" aria-labelledby="preparation-heading">
            <h2 id="preparation-heading" class="mb-4 border-b border-purple-50 pb-2 text-lg font-bold text-gray-800">Operational Preparation & Dispatch</h2>
            
            <!-- 1. Preparation Scheduling -->
            <div class="mb-6 rounded-xl border border-slate-200 bg-slate-50 p-5">
                <h3 class="font-semibold text-slate-800 mb-2">1. Schedule Preparation</h3>
                <p class="text-sm text-slate-600 mb-4">Set the date when material reservation and preparation should begin. This is under Admin control and must not be after the event date.</p>
                
                <form method="POST" action="{{ route('admin.bookings.update', $booking) }}" class="flex flex-wrap items-center gap-3">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="status" value="{{ $booking->status }}">
                    <input type="hidden" name="event_type" value="{{ $booking->event_type }}">
                    <input type="hidden" name="event_date" value="{{ optional($booking->event_date)->format('Y-m-d') }}">
                    <input type="hidden" name="venue" value="{{ $booking->venue }}">
                    
                    <input type="date" name="preparation_start_date" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500" value="{{ optional($booking->preparation_start_date)->format('Y-m-d') }}" max="{{ optional($booking->event_date)->format('Y-m-d') }}" required>
                    
                    <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">Save Date</button>
                </form>
            </div>

            <!-- 2. Reserve Materials -->
            @php
                $isPrepDateValid = !is_null($booking->preparation_start_date) && !$booking->preparation_start_date->isFuture();
                $canReserve = in_array($booking->status, ['downpayment_received', 'confirmed', 'in_preparation'], true) && $isPrepDateValid;
            @endphp
            <div class="mb-6 rounded-xl border border-slate-200 bg-slate-50 p-5">
                <h3 class="font-semibold text-slate-800 mb-2">2. Reserve Materials</h3>
                <p class="text-sm text-slate-600 mb-4">Lock the required reusable materials for this event. Requires a valid preparation start date that has been reached.</p>
                
                <form method="POST" action="{{ route('admin.bookings.reserve-materials', $booking) }}">
                    @csrf
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition disabled:opacity-50 disabled:cursor-not-allowed" {{ !$canReserve ? 'disabled' : '' }}>
                        <i class="fa-solid fa-lock mr-2"></i>Reserve Materials
                    </button>
                    @if(!$isPrepDateValid)
                        <p class="mt-2 text-xs text-amber-600 font-medium">Cannot reserve: Preparation start date is missing or in the future.</p>
                    @endif
                </form>
            </div>

            <!-- 3. Confirm Fresh Flowers -->
            @php
                $hasFreshFlowers = $booking->bookingItems()->whereHas('inventoryItem', fn ($q) => $q->where('is_perishable', true))->exists();
            @endphp
            @if($hasFreshFlowers)
            <div class="mb-6 rounded-xl border border-slate-200 bg-slate-50 p-5">
                <h3 class="font-semibold text-slate-800 mb-2">3. Fresh Flower Procurement</h3>
                <p class="text-sm text-slate-600 mb-4">Confirm that all required fresh flowers and perishable items have been procured and are ready for the event.</p>
                
                <form method="POST" action="{{ route('admin.bookings.confirm-fresh-flowers', $booking) }}">
                    @csrf
                    <button type="submit" class="bg-pink-600 hover:bg-pink-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition {{ $booking->areFreshFlowersReady() ? 'opacity-50 cursor-not-allowed' : '' }}" {{ $booking->areFreshFlowersReady() ? 'disabled' : '' }}>
                        <i class="fa-solid fa-seedling mr-2"></i>{{ $booking->areFreshFlowersReady() ? 'Fresh Flowers Confirmed' : 'Confirm Fresh Flowers' }}
                    </button>
                </form>
            </div>
            @endif

            <!-- 4. Dispatch Materials -->
            @php
                // Find all items that have booking_lock for this booking
                $lockedTx = \App\Models\InventoryTransaction::where('booking_id', $booking->id)
                    ->where('transaction_type', 'booking_lock')
                    ->where('quantity_change', '<', 0)
                    ->with('inventoryItem')
                    ->get();
                    
                $hasLockedItems = $lockedTx->isNotEmpty();
                $hasOutstanding = false;
            @endphp
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                <h3 class="font-semibold text-slate-800 mb-2">4. Dispatch Materials</h3>
                <p class="text-sm text-slate-600 mb-4">Dispatch the reserved reusable materials to the event location. This physically deducts the stock.</p>
                
                @if($hasLockedItems)
                    <form method="POST" action="{{ route('admin.bookings.dispatch', $booking) }}" class="space-y-4">
                        @csrf
                        <input type="hidden" name="reason" value="Dispatch for Event #{{ $booking->id }}">
                        
                        <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
                            <table class="w-full text-left text-sm text-slate-600">
                                <thead class="bg-slate-50 text-xs uppercase text-slate-700">
                                    <tr>
                                        <th class="px-4 py-3">Item</th>
                                        <th class="px-4 py-3 text-right">Qty to Dispatch</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($lockedTx as $index => $tx)
                                    @php
                                        // Outstanding reservation logic
                                        $allTx = \App\Models\InventoryTransaction::where('booking_id', $booking->id)
                                            ->where('inventory_item_id', $tx->inventory_item_id)
                                            ->get();
                                        
                                        $lock = $allTx->where('transaction_type', 'booking_lock')->sum(fn($t) => abs((float)$t->quantity_change));
                                        $release = $allTx->where('transaction_type', 'booking_release')->sum('quantity_change');
                                        $netDispatch = abs($allTx->whereIn('transaction_type', ['dispatch', 'dispatch_correction'])->sum('quantity_change'));
                                        
                                        $outstanding = $lock - $release - $netDispatch;
                                    @endphp
                                    @if(round($outstanding, 4) > 0)
                                    @php $hasOutstanding = true; @endphp
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-slate-800">{{ $tx->inventoryItem->name }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <input type="hidden" name="items[{{ $index }}][inventory_item_id]" value="{{ $tx->inventory_item_id }}">
                                            <input type="number" name="items[{{ $index }}][quantity]" value="{{ $outstanding }}" max="{{ $outstanding }}" min="0" step="0.01" class="w-24 rounded-lg border border-slate-300 px-2 py-1 text-right text-sm">
                                        </td>
                                    </tr>
                                    @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        @if($hasOutstanding)
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                                <i class="fa-solid fa-truck-fast mr-2"></i>Dispatch Selected
                            </button>
                        @else
                            <p class="text-sm text-emerald-600 font-medium">All reserved materials have been fully dispatched.</p>
                        @endif
                    </form>
                @else
                    <p class="text-sm text-slate-500 italic">No reserved reusable materials available for dispatch. Please reserve materials first.</p>
                @endif
            </div>
        </section>
    </div>
    <div id="tab-staff" class="tab-content hidden space-y-6">
        <section class="rf-panel mt-6 p-5 sm:p-6" aria-labelledby="staff-assignment-heading">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 id="staff-assignment-heading" class="text-lg font-bold text-slate-900">Operational Staff Assignment</h2>
                    <p class="mt-1 text-sm text-slate-500">Assign this event to one Staff member for operational follow-up. This does not change the Admin handler.</p>
                </div>
                <form method="POST" action="{{ route('admin.bookings.assign-staff', ['booking' => $booking->id]) }}" class="flex w-full flex-col gap-2 sm:w-auto sm:min-w-[300px] sm:flex-row">
                    @csrf
                    <label class="sr-only" for="staff_id">Operational Staff</label>
                    <select id="staff_id" name="staff_id" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-purple-500 focus:ring-2 focus:ring-purple-100">
                        <option value="">Unassigned</option>
                        @foreach($staffUsers as $staffUser)
                            <option value="{{ $staffUser->id }}" {{ (string) $booking->staff_id === (string) $staffUser->id ? 'selected' : '' }}>{{ $staffUser->name }} ({{ $staffUser->email }})</option>
                        @endforeach
                    </select>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-purple-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-purple-800"><i class="fa-solid fa-user-check"></i> Save Assignment</button>
                </form>
            </div>
        </section>

        
    </div>
    <div id="tab-communication" class="tab-content hidden space-y-6">
        <!-- SECTION 2.5: NEGOTIATION FEED -->
        <section class="rf-panel mt-6 overflow-hidden p-5 sm:p-6" aria-labelledby="negotiation-heading">
            <div class="flex items-center justify-between mb-4 border-b border-purple-50 pb-2">
                <h2 id="negotiation-heading" class="text-lg font-bold text-gray-800">Communication &amp; Negotiation</h2>
                @if($booking->status === 'cancellation_requested')
                    <span class="rf-badge rf-badge--danger"><i class="fa-solid fa-exclamation-circle mr-1"></i> Cancellation Requested</span>
                @elseif($booking->status === 'change_requested')
                    <span class="rf-badge rf-badge--warning"><i class="fa-solid fa-sync mr-1"></i> Changes Requested</span>
                @endif
            </div>

            <div class="space-y-4 max-h-[600px] overflow-y-auto mb-6 pr-2">
                @forelse($bookingMessages as $message)
                    <div class="flex {{ $message->sender_type === 'admin' ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-xl rounded-lg p-4 {{ $message->sender_type === 'admin' ? 'bg-purple-50 border border-purple-100 rounded-tr-none' : 'bg-slate-50 border border-slate-200 rounded-tl-none' }}">
                            <div class="flex items-center justify-between gap-4 mb-2">
                                <span class="font-bold text-sm {{ $message->sender_type === 'admin' ? 'text-purple-900' : 'text-slate-800' }}">
                                    {{ $message->sender_type === 'admin' ? 'Admin' : 'Client' }}
                                </span>
                                <span class="text-xs text-slate-500">{{ $message->created_at->format('M d, g:i A') }}</span>
                            </div>
                            <div class="text-sm {{ $message->sender_type === 'admin' ? 'text-purple-800' : 'text-slate-700' }} whitespace-pre-wrap break-words">{{ $message->message }}</div>
                            @if($message->related_quotation_version)
                                <div class="mt-2 text-xs font-semibold text-purple-600 bg-white px-2 py-1 rounded inline-block border border-purple-100">
                                    Ref: Quotation v{{ $message->related_quotation_version }}
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 text-center py-4">No messages yet.</p>
                @endforelse
            </div>

            @if(in_array($booking->status, ['change_requested', 'cancellation_requested']))
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-5">
                    <h3 class="font-bold text-yellow-800 mb-2">
                        {{ $booking->status === 'cancellation_requested' ? 'Action Required: Cancellation Request' : 'Action Required: Change Request' }}
                    </h3>
                    @if($booking->status === 'change_requested')
                        <form action="{{ route('admin.bookings.reply', $booking) }}" method="POST" class="space-y-3" onsubmit="if(this.submitted) return false; this.submitted = true; const btn = this.querySelector('button[type=submit]'); const orig = btn.innerHTML; btn.classList.add('pointer-events-none', 'opacity-50'); btn.innerHTML = 'Sending...'; setTimeout(() => { this.submitted = false; btn.classList.remove('pointer-events-none', 'opacity-50'); btn.innerHTML = orig; }, 5000); return true;">
                            @csrf
                            <input type="hidden" name="submission_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                            <div>
                                <label for="replyMessage" class="block text-sm font-semibold text-yellow-900">Your Reply / Note</label>
                                <textarea id="replyMessage" name="message" rows="3" class="mt-1 w-full rounded-md border-yellow-300 shadow-sm focus:border-yellow-500 focus:ring-yellow-500 text-sm p-2" required></textarea>
                            </div>
                            <div class="flex justify-end gap-2">
                                <button type="submit" name="action" value="reply_only" class="px-4 py-2 bg-yellow-600 text-white text-sm font-bold rounded hover:bg-yellow-700">Send Reply</button>
                            </div>
                        </form>
                    @else
                        <form action="{{ route('admin.bookings.handle-cancellation', $booking) }}" method="POST" class="space-y-3">
                            @csrf
                            <div>
                                <label for="cancellationNote" class="block text-sm font-semibold text-yellow-900">Admin Note (Optional)</label>
                                <textarea id="cancellationNote" name="admin_note" rows="2" class="mt-1 w-full rounded-md border-yellow-300 shadow-sm focus:border-yellow-500 focus:ring-yellow-500 text-sm p-2"></textarea>
                            </div>
                            <div class="flex justify-end gap-2">
                                <button type="submit" name="action" value="deny" class="px-4 py-2 bg-white text-yellow-800 text-sm font-semibold border border-yellow-300 rounded hover:bg-yellow-100">Deny Request</button>
                                <button type="submit" name="action" value="approve" class="px-4 py-2 bg-red-600 text-white text-sm font-bold rounded hover:bg-red-700">Approve Cancellation</button>
                            </div>
                        </form>
                    @endif
                </div>
            @endif
    </div>

    <script>
        function switchTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('block'));
            const selected = document.getElementById(tabId);
            if(selected) {
                selected.classList.remove('hidden');
                selected.classList.add('block');
            }
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('border-purple-500', 'text-purple-600');
                btn.classList.add('border-transparent', 'text-slate-500');
            });
            const activeBtn = document.querySelector(`.tab-button[data-target="${tabId}"]`);
            if(activeBtn) {
                activeBtn.classList.remove('border-transparent', 'text-slate-500');
                activeBtn.classList.add('border-purple-500', 'text-purple-600');
            }
        }

        function openBookingModal(id) {
            document.getElementById(id).classList.remove('hidden');
            document.getElementById(id).classList.add('flex');
        }

        function closeBookingModal(id) {
            document.getElementById(id).classList.add('hidden');
            document.getElementById(id).classList.remove('flex');
        }
    </script>
</x-admin-layout>

<div id="aiMaterialModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4" role="dialog" aria-modal="true" aria-labelledby="aiMaterialModalTitle">
    <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 id="aiMaterialModalTitle" class="text-lg font-bold text-slate-900">Review AI material</h2>
                <p id="aiMaterialModalDescription" class="mt-1 text-sm text-slate-500"></p>
            </div>
            <button type="button" onclick="closeAiMaterialModal()" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg border border-slate-200 text-slate-600" aria-label="Close material review">&times;</button>
        </div>
        <div id="aiMaterialLinkFields" class="mt-5 hidden">
            <label for="aiMaterialInventorySelect" class="block text-sm font-semibold text-slate-700">Existing inventory item</label>
            <select id="aiMaterialInventorySelect" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                <option value="">Select an inventory item</option>
                @foreach($allInventoryItems as $inventoryItem)
                    <option value="{{ $inventoryItem->id }}">{{ $inventoryItem->name }} ({{ $inventoryItem->current_stock }} {{ $inventoryItem->unit }})</option>
                @endforeach
            </select>
        </div>
        <div id="aiMaterialPromoteFields" class="mt-5 hidden space-y-3">
            <div>
                <label for="aiMaterialCatalogName" class="block text-sm font-semibold text-slate-700">Catalog name <span class="text-rose-600">*</span></label>
                <input id="aiMaterialCatalogName" type="text" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm" required>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label for="aiMaterialCategory" class="block text-sm font-semibold text-slate-700">Category</label>
                    <input id="aiMaterialCategory" type="text" value="floral" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                </div>
                <div>
                    <label for="aiMaterialUnit" class="block text-sm font-semibold text-slate-700">Unit</label>
                    <input id="aiMaterialUnit" type="text" value="piece" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                </div>
            </div>
            <label class="inline-flex items-center gap-2 text-sm text-slate-700"><input id="aiMaterialPerishable" type="checkbox" checked class="h-4 w-4 rounded border-slate-300"> Perishable material</label>
        </div>
        <p id="aiMaterialModalError" class="mt-3 hidden text-sm text-rose-700" role="alert"></p>
        <div class="mt-6 flex justify-end gap-3">
            <button type="button" onclick="closeAiMaterialModal()" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</button>
            <button type="button" id="aiMaterialModalSubmit" onclick="submitAiMaterialModal()" class="rounded-lg bg-purple-700 px-4 py-2.5 text-sm font-semibold text-white">Continue</button>
        </div>
    </div>
</div>

<script>
    let activeAiMaterialIndex = null;
    let activeAiMaterialMode = null;

    function openAiMaterialModal(index, mode) {
        activeAiMaterialIndex = index;
        activeAiMaterialMode = mode;
        const modal = document.getElementById('aiMaterialModal');
        const linkFields = document.getElementById('aiMaterialLinkFields');
        const promoteFields = document.getElementById('aiMaterialPromoteFields');
        const description = document.getElementById('aiMaterialModalDescription');
        const error = document.getElementById('aiMaterialModalError');
        error.classList.add('hidden');
        linkFields.classList.toggle('hidden', mode !== 'link');
        promoteFields.classList.toggle('hidden', mode !== 'promote');
        description.textContent = mode === 'link'
            ? 'Choose the existing inventory record that represents this AI suggestion.'
            : 'Provide the catalog details required to create this AI suggestion as a new inventory item.';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        (mode === 'link' ? document.getElementById('aiMaterialInventorySelect') : document.getElementById('aiMaterialCatalogName')).focus();
    }

    function closeAiMaterialModal() {
        const modal = document.getElementById('aiMaterialModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        activeAiMaterialIndex = null;
        activeAiMaterialMode = null;
    }

    function submitAiMaterialModal() {
        const error = document.getElementById('aiMaterialModalError');
        const index = activeAiMaterialIndex;
        if (index === null) return;

        if (activeAiMaterialMode === 'link') {
            const inventoryId = document.getElementById('aiMaterialInventorySelect').value;
            if (!inventoryId) {
                error.textContent = 'Select an existing inventory item before continuing.';
                error.classList.remove('hidden');
                return;
            }
            document.getElementById(`aiLinkInventory_${index}`).value = inventoryId;
            document.getElementById(`aiLinkForm_${index}`).submit();
            return;
        }

        const catalogName = document.getElementById('aiMaterialCatalogName').value.trim();
        if (!catalogName) {
            error.textContent = 'Catalog name is required.';
            error.classList.remove('hidden');
            return;
        }
        document.getElementById(`aiPromoteName_${index}`).value = catalogName;
        document.getElementById(`aiPromoteCategory_${index}`).value = document.getElementById('aiMaterialCategory').value.trim();
        document.getElementById(`aiPromoteUnit_${index}`).value = document.getElementById('aiMaterialUnit').value.trim();
        document.getElementById(`aiPromotePerishable_${index}`).value = document.getElementById('aiMaterialPerishable').checked ? '1' : '0';
        document.getElementById(`aiPromoteForm_${index}`).submit();
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && activeAiMaterialIndex !== null) closeAiMaterialModal();
    });
</script>

<!-- Substitute Modal -->
<div id="substituteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4">
        <div class="flex justify-between items-center p-6 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-800">Select Substitute Item</h3>
            <button onclick="closeSubstituteModal()" class="text-gray-400 hover:text-gray-600"><i class="fa-solid fa-times"></i></button>
        </div>
        <div class="p-6 max-h-[60vh] overflow-y-auto">
            <p class="text-sm text-slate-600 mb-4">Choose a designated alternative for this item:</p>
            <div id="substitutesList" class="space-y-3">
                <!-- Substitutes will be rendered here by JS -->
            </div>
        </div>
        <div class="p-6 border-t border-gray-200 flex justify-end gap-3">
            <button type="button" onclick="closeSubstituteModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">Cancel</button>
        </div>
    </div>
</div>

<script>
    const inventoryData = @json(isset($allInventoryItems) ? $allInventoryItems->keyBy('id') : []);
    let currentRowIdx = null;

    function formatCurrency(value) {
        const numericValue = Number.isFinite(Number(value)) ? Number(value) : 0;
        const formatted = new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(numericValue);

        return '₱ ' + formatted;
    }

    function formatQtyValue(value) {
        const numericValue = Number(value) || 0;
        return String(Math.max(0, Math.trunc(numericValue)));
    }

    function updateLineTotals() {
        let subtotal = 0;

        document.querySelectorAll('tr[id^="row_"]').forEach((row) => {
            const qtyInput = row.querySelector('input[name$="[quantity]"]');
            const unitPriceInput = row.querySelector('input[name$="[unit_price]"]');
            const removeCheckbox = row.querySelector('input[name$="[remove]"]');
            const totalCell = row.querySelector('[id^="total_cost_"]');
            const nameDisplay = row.querySelector('[id^="display_name_"]');

            if (!qtyInput || !unitPriceInput || !totalCell) {
                return;
            }

            const qty = Number(qtyInput.value) || 0;
            const unitPrice = Number(unitPriceInput.value) || 0;
            const isRemoved = removeCheckbox ? removeCheckbox.checked : false;
            const lineTotal = qty * unitPrice;

            qtyInput.value = formatQtyValue(qtyInput.value);
            totalCell.textContent = formatCurrency(lineTotal);

            if (nameDisplay) {
                nameDisplay.style.textDecoration = isRemoved ? 'line-through' : '';
                nameDisplay.style.opacity = isRemoved ? '0.55' : '1';
            }

            row.classList.toggle('opacity-50', isRemoved);

            if (!isRemoved) {
                subtotal += lineTotal;
            }
        });

        const rawSubtotalValue = document.getElementById('raw-wholesale-subtotal');
        const calculatedTotalValue = document.getElementById('calculated-quotation-total');
        const multiplierInput = document.getElementById('markup-multiplier');
        const finalOverrideInput = document.getElementById('final-quoted-total-override');

        if (rawSubtotalValue) {
            rawSubtotalValue.textContent = formatCurrency(subtotal);
        }

        const multiplier = Number(multiplierInput?.value) || 0;
        const calculatedTotal = subtotal * multiplier;

        if (calculatedTotalValue) {
            calculatedTotalValue.textContent = formatCurrency(calculatedTotal);
        }

        if (finalOverrideInput && !finalOverrideInput.matches(':focus')) {
            // Only auto-update if the user hasn't manually overridden the value
            if (finalOverrideInput.dataset.manualOverride !== 'true') {
                finalOverrideInput.value = Number.isFinite(calculatedTotal) ? calculatedTotal.toFixed(2) : '0.00';
            }
        }
    }

    function setupItemCalculationListeners() {
        document.querySelectorAll('tr[id^="row_"]').forEach((row) => {
            const qtyInput = row.querySelector('input[name$="[quantity]"]');
            const unitPriceInput = row.querySelector('input[name$="[unit_price]"]');
            const removeCheckbox = row.querySelector('input[name$="[remove]"]');

            [qtyInput, unitPriceInput].forEach((input) => {
                if (input) {
                    input.addEventListener('input', updateLineTotals);
                    input.addEventListener('change', updateLineTotals);
                }
            });

            if (removeCheckbox) {
                removeCheckbox.addEventListener('change', updateLineTotals);
            }
        });

        const multiplierInput = document.getElementById('markup-multiplier');
        if (multiplierInput) {
            multiplierInput.addEventListener('input', updateLineTotals);
            multiplierInput.addEventListener('change', updateLineTotals);
        }

        const finalOverrideInput = document.getElementById('final-quoted-total-override');
        if (finalOverrideInput) {
            // Initial check: if the server provided a value that differs from the calculation, treat it as a manual override
            const initialVal = Number(finalOverrideInput.value);
            const calculatedTotal = Number(document.getElementById('calculated-quotation-total')?.textContent.replace(/[^0-9.-]+/g, '')) || 0;
            if (initialVal > 0 && Math.abs(initialVal - calculatedTotal) > 0.01) {
                finalOverrideInput.dataset.manualOverride = 'true';
            }

            finalOverrideInput.addEventListener('input', () => {
                finalOverrideInput.dataset.manualOverride = 'true';
            });

            finalOverrideInput.addEventListener('focus', () => {
                finalOverrideInput.dataset.manualEdit = 'true';
            });

            finalOverrideInput.addEventListener('blur', () => {
                finalOverrideInput.dataset.manualEdit = 'false';
                updateLineTotals();
            });
        }
    }

    function openSubstituteModal(rowIdx, itemId) {
        currentRowIdx = rowIdx;
        const item = inventoryData[itemId];
        const listDiv = document.getElementById('substitutesList');
        listDiv.innerHTML = '';

        if (!item || !item.substitutes || item.substitutes.length === 0) {
            listDiv.innerHTML = '<p class="text-sm text-slate-500">No designated substitutes available.</p>';
        } else {
            item.substitutes.forEach(sub => {
                const isLow = sub.current_stock <= 0;
                const stockBadge = isLow 
                    ? `<span class="text-[10px] uppercase font-bold text-red-600 bg-red-100 px-2 py-0.5 rounded-full ml-2">Out of Stock</span>`
                    : `<span class="text-[10px] uppercase font-bold text-green-600 bg-green-100 px-2 py-0.5 rounded-full ml-2">${sub.current_stock} in stock</span>`;

                listDiv.innerHTML += `
                    <div class="border border-slate-200 rounded-lg p-3 flex justify-between items-center hover:bg-slate-50 transition">
                        <div>
                            <p class="font-bold text-sm text-slate-800">${sub.name} ${stockBadge}</p>
                            <p class="text-xs text-slate-500">Unit Cost: ₱${sub.unit_cost}</p>
                        </div>
                        <button type="button" onclick="applySubstitute(${sub.id})" class="px-3 py-1.5 text-xs font-bold text-white bg-purple-600 hover:bg-purple-700 rounded shadow-sm" ${isLow ? 'disabled style="opacity: 0.5;"' : ''}>
                            Select
                        </button>
                    </div>
                `;
            });
        }

        document.getElementById('substituteModal').classList.remove('hidden');
    }

    function closeSubstituteModal() {
        document.getElementById('substituteModal').classList.add('hidden');
        currentRowIdx = null;
    }

    function applySubstitute(subId) {
        if (currentRowIdx === null) return;
        
        const sub = inventoryData[subId];
        if (!sub) return;

        document.getElementById('input_name_' + currentRowIdx).value = sub.name;
        document.getElementById('input_inventory_' + currentRowIdx).value = sub.id;
        
        const displaySpan = document.getElementById('display_name_' + currentRowIdx);
        displaySpan.innerHTML = `${sub.name} <span class="px-2 py-0.5 text-[10px] uppercase tracking-wider font-bold bg-blue-100 text-blue-700 rounded-full ml-2">Swapped</span>`;
        
        const priceInput = document.getElementById('input_price_' + currentRowIdx);
        priceInput.value = sub.unit_cost;

        const qtyInput = document.getElementById('input_qty_' + currentRowIdx);
        const newTotal = (parseFloat(qtyInput.value) * parseFloat(sub.unit_cost)).toFixed(2);
        document.getElementById('total_cost_' + currentRowIdx).innerText = formatCurrency(newTotal);

        closeSubstituteModal();
        
        const row = document.getElementById('row_' + currentRowIdx);
        const badges = row.querySelectorAll('.bg-red-100, button[onclick^="openSubstituteModal"]');
        badges.forEach(b => b.style.display = 'none');

        updateLineTotals();
    }

    document.addEventListener('DOMContentLoaded', function () {
        setupItemCalculationListeners();
        updateLineTotals();
    });
</script>
