<x-admin-layout title="Return Audit">
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Return Audit & Reconciliation</h1>
                <p class="text-sm text-slate-500 mt-1">Review returned assets, assess condition, and reconcile inventory stock adjustments.</p>
            </div>
            <a href="{{ route('admin.return-tracking') }}" class="inline-flex items-center gap-2 bg-white border border-slate-300 text-slate-700 px-4 py-2 rounded-xl text-sm font-semibold hover:bg-slate-50 transition shadow-2xs">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                <span>Back to Return Tracking</span>
            </a>
        </div>

        @if(session('success'))
            <x-alert type="success" :inline="true">{{ session('success') }}</x-alert>
        @endif
        @if($errors->any())
            <x-alert type="danger" :inline="true">
                <div class="font-semibold mb-1">Please fix the following return errors:</div>
                <ul class="list-disc pl-5 text-xs space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        @php
            $displayStatus = $booking->status_display_label;
            $statusBadgeClass = match ($booking->status) {
                'downpayment_received' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                'payment_pending' => 'bg-amber-100 text-amber-800 border-amber-200',
                'completed' => 'bg-green-100 text-green-800 border-green-200',
                'pending_resolution' => 'bg-orange-100 text-orange-800 border-orange-200',
                'pending_return' => 'bg-amber-100 text-amber-800 border-amber-200',
                'cancelled' => 'bg-rose-100 text-rose-800 border-rose-200',
                default => 'bg-slate-100 text-slate-800 border-slate-200',
            };
            $isArchivedAudit = ($booking->status === 'completed') || ($booking->status === 'cancelled' && $return->status === 'Completed');
        @endphp

        @if($isArchivedAudit)
            <div class="rounded-2xl border border-blue-200 bg-blue-50/80 p-4 text-sm text-blue-900 shadow-xs flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-lock text-base"></i>
                </div>
                <div>
                    <p class="font-bold">Return Audit Completed &amp; Archived</p>
                    <p class="text-xs text-blue-700 mt-0.5">This return audit record is finalized and locked. Historical return quantities, condition observations, and damage charge adjudications are preserved in read-only mode.</p>
                </div>
            </div>
        @endif

        <!-- Summary & Guidance Cards -->
        <div class="grid gap-6 lg:grid-cols-2">
            <!-- Booking Summary -->
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
                <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                    <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <i class="fa-solid fa-file-invoice text-brand-700"></i>
                        <span>Booking Information</span>
                    </h2>
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold border {{ $statusBadgeClass }}">
                        {{ $displayStatus }}
                    </span>
                </div>
                <div class="grid grid-cols-2 gap-4 text-sm text-slate-700">
                    <div>
                        <span class="text-xs text-slate-400 font-semibold block uppercase tracking-wider">Booking Number</span>
                        <span class="font-bold text-slate-900">#{{ $booking->booking_number ?? $booking->id }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-400 font-semibold block uppercase tracking-wider">Client Name</span>
                        <span class="font-medium text-slate-900">{{ $booking->client?->full_name ?? $booking->guest_name ?? 'Guest Client' }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-400 font-semibold block uppercase tracking-wider">Event Type</span>
                        <span class="font-medium text-slate-900">{{ ucfirst($booking->event_type ?? 'N/A') }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-400 font-semibold block uppercase tracking-wider">Event Date</span>
                        <span class="font-medium text-slate-900">{{ optional($booking->event_date)->format('F j, Y') ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-400 font-semibold block uppercase tracking-wider">Venue</span>
                        <span class="font-medium text-slate-900 truncate block">{{ $booking->venue ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-400 font-semibold block uppercase tracking-wider">Damage Charge</span>
                        <span class="font-bold text-rose-600">₱{{ number_format($return->total_damage_charge ?? 0, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Return & Inventory Reconciliation Guidance -->
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
                <h2 class="text-base font-bold text-slate-900 mb-4 flex items-center gap-2 border-b border-slate-100 pb-3">
                    <i class="fa-solid fa-boxes-packing text-emerald-600"></i>
                    <span>Inventory Reconciliation Guide</span>
                </h2>
                <div class="space-y-3 text-xs text-slate-600">
                    <div class="flex items-start gap-2.5 p-2 rounded-xl bg-emerald-50/60 border border-emerald-100">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold bg-emerald-100 text-emerald-800 shrink-0">
                            <i class="fa-solid fa-circle-check"></i> Good
                        </span>
                        <p class="text-slate-700 leading-relaxed">
                            Restores physical item stock to available inventory automatically upon saving.
                        </p>
                    </div>
                    <div class="flex items-start gap-2.5 p-2 rounded-xl bg-amber-50/60 border border-amber-100">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold bg-amber-100 text-amber-800 shrink-0">
                            <i class="fa-solid fa-triangle-exclamation"></i> Damaged
                        </span>
                        <p class="text-slate-700 leading-relaxed">
                            Does not restore active stock. Recorded in damage audit ledger. Adjudicate client charge decision below.
                        </p>
                    </div>
                    <div class="flex items-start gap-2.5 p-2 rounded-xl bg-rose-50/60 border border-rose-100">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold bg-rose-100 text-rose-800 shrink-0">
                            <i class="fa-solid fa-circle-xmark"></i> Lost
                        </span>
                        <p class="text-slate-700 leading-relaxed">
                            Item was not physically returned. Does not restore stock. Adjudicate client replacement fee below.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.return-tracking.update', $return) }}" class="space-y-6">
            @csrf
            @method('PUT')

            @if($return->returnItems->isEmpty())
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-sm text-amber-900 shadow-xs flex items-center gap-3">
                    <i class="fa-solid fa-circle-info text-amber-600 text-lg"></i>
                    <div>
                        <p class="font-bold">Zero-Hardware Booking</p>
                        <p class="text-xs text-amber-800 mt-0.5">This booking contains no rental or returnable hardware (service or fresh flowers only). Click below to finalize the return audit.</p>
                    </div>
                </div>
            @endif

            <!-- Return Items Table -->
            <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-xs w-full">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50/80 text-slate-700 text-xs uppercase tracking-wide border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-4">Item Details</th>
                            <th class="px-4 py-4">Dispatched (Expected)</th>
                            <th class="px-4 py-4 min-w-[320px]">Returned Quantities (Good / Damaged / Lost)</th>
                            <th class="px-4 py-4">Condition & Evidence</th>
                            <th class="px-4 py-4 min-w-[200px]">Decision & Charge</th>
                            <th class="px-4 py-4 min-w-[220px]">Notes & Reason</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($return->returnItems as $returnItem)
                            @php
                                $inventoryItem = $returnItem->inventoryItem;
                                $dispatched = (float) ($dispatchedQuantities[$returnItem->inventory_item_id] ?? 0);
                                $good = old('items.' . $returnItem->id . '.quantity_good', $returnItem->quantity_good);
                                $damaged = old('items.' . $returnItem->id . '.quantity_damaged', $returnItem->quantity_damaged);
                                $lost = old('items.' . $returnItem->id . '.quantity_lost', $returnItem->quantity_lost);
                                $totalAccounted = (float) $good + (float) $damaged + (float) $lost;
                                $unaccounted = max(0, $dispatched - $totalAccounted);
                            @endphp
                            <tr class="hover:bg-slate-50/50 transition">
                                <!-- Item Details -->
                                <td class="px-4 py-4 align-top">
                                    <div class="font-bold text-slate-900">{{ $inventoryItem?->name ?? 'Unknown item' }}</div>
                                    <div class="text-xs text-slate-500 mt-0.5">
                                        <span class="font-semibold">SKU:</span> {{ $inventoryItem?->sku ?? 'N/A' }}
                                        @if($inventoryItem?->category)
                                            <span class="mx-1 text-slate-300">•</span>
                                            <span class="capitalize">{{ $inventoryItem->category }}</span>
                                        @endif
                                    </div>
                                    <input type="hidden" name="items[{{ $returnItem->id }}][inventory_item_id]" value="{{ $returnItem->inventory_item_id }}">
                                </td>

                                <!-- Dispatched Quantity -->
                                <td class="px-4 py-4 align-top">
                                    <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 text-slate-800 font-bold text-sm">
                                        <i class="fa-solid fa-box text-xs text-slate-500"></i>
                                        <span>{{ $dispatched }}</span>
                                    </div>
                                    <p class="text-[11px] text-slate-400 mt-1">Expected return</p>
                                </td>

                                <!-- Returned Quantities Input -->
                                <td class="px-4 py-4 align-top">
                                    <div class="space-y-2.5">
                                        <div class="grid grid-cols-3 gap-2">
                                            <!-- Good Quantity -->
                                            <div>
                                                <label class="block text-xs font-semibold text-emerald-800 mb-1" for="good-{{ $returnItem->id }}">
                                                    <i class="fa-solid fa-circle-check text-[10px] mr-0.5 text-emerald-600"></i> Good
                                                </label>
                                                <input
                                                    id="good-{{ $returnItem->id }}"
                                                    type="number"
                                                    name="items[{{ $returnItem->id }}][quantity_good]"
                                                    value="{{ $good }}"
                                                    min="0"
                                                    max="{{ $dispatched }}"
                                                    step="1"
                                                    {{ $isArchivedAudit ? 'disabled' : '' }}
                                                    class="w-full rounded-lg border border-emerald-300 bg-emerald-50/30 px-2.5 py-1.5 text-sm text-slate-900 font-semibold focus:border-brand-600 focus:ring-1 focus:ring-brand-500 disabled:bg-slate-100 disabled:text-slate-500"
                                                >
                                            </div>

                                            <!-- Damaged Quantity -->
                                            <div>
                                                <label class="block text-xs font-semibold text-amber-800 mb-1" for="damaged-{{ $returnItem->id }}">
                                                    <i class="fa-solid fa-triangle-exclamation text-[10px] mr-0.5 text-amber-600"></i> Damaged
                                                </label>
                                                <input
                                                    id="damaged-{{ $returnItem->id }}"
                                                    type="number"
                                                    name="items[{{ $returnItem->id }}][quantity_damaged]"
                                                    value="{{ $damaged }}"
                                                    min="0"
                                                    max="{{ $dispatched }}"
                                                    step="1"
                                                    {{ $isArchivedAudit ? 'disabled' : '' }}
                                                    class="w-full rounded-lg border border-amber-300 bg-amber-50/30 px-2.5 py-1.5 text-sm text-slate-900 font-semibold focus:border-amber-500 focus:ring-1 focus:ring-amber-500 disabled:bg-slate-100 disabled:text-slate-500"
                                                >
                                            </div>

                                            <!-- Lost Quantity -->
                                            <div>
                                                <label class="block text-xs font-semibold text-rose-800 mb-1" for="lost-{{ $returnItem->id }}">
                                                    <i class="fa-solid fa-circle-xmark text-[10px] mr-0.5 text-rose-600"></i> Lost
                                                </label>
                                                <input
                                                    id="lost-{{ $returnItem->id }}"
                                                    type="number"
                                                    name="items[{{ $returnItem->id }}][quantity_lost]"
                                                    value="{{ $lost }}"
                                                    min="0"
                                                    max="{{ $dispatched }}"
                                                    step="1"
                                                    {{ $isArchivedAudit ? 'disabled' : '' }}
                                                    class="w-full rounded-lg border border-rose-300 bg-rose-50/30 px-2.5 py-1.5 text-sm text-slate-900 font-semibold focus:border-rose-500 focus:ring-1 focus:ring-rose-500 disabled:bg-slate-100 disabled:text-slate-500"
                                                >
                                            </div>
                                        </div>

                                        <!-- Accounting Status Bar -->
                                        <div class="flex items-center justify-between text-xs text-slate-500 pt-1.5 border-t border-slate-100">
                                            <span>Accounted: <strong class="text-slate-800">{{ $totalAccounted }}</strong> / {{ $dispatched }}</span>
                                            @if($unaccounted > 0)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800">
                                                    <i class="fa-solid fa-triangle-exclamation text-[10px]"></i>
                                                    {{ $unaccounted }} remaining
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800">
                                                    <i class="fa-solid fa-circle-check text-[10px]"></i>
                                                    Fully accounted
                                                </span>
                                            @endif
                                        </div>

                                        <p class="text-[11px] text-slate-500">
                                            <i class="fa-solid fa-rotate text-emerald-600 mr-0.5"></i>
                                            Good quantity returns to inventory stock upon save.
                                        </p>
                                    </div>
                                </td>

                                <!-- Condition & Evidence -->
                                <td class="px-4 py-4 align-top">
                                    <div class="space-y-2">
                                        <div>
                                            @php
                                                $cond = strtolower((string) ($returnItem->condition ?? 'pending'));
                                            @endphp
                                            @if($cond === 'good')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    <i class="fa-solid fa-circle-check text-[10px]"></i> Good
                                                </span>
                                            @elseif($cond === 'damaged')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                                    <i class="fa-solid fa-triangle-exclamation text-[10px]"></i> Damaged
                                                </span>
                                            @elseif($cond === 'lost')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                                    <i class="fa-solid fa-circle-xmark text-[10px]"></i> Lost
                                                </span>
                                            @elseif($cond === 'mixed')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-brand-50 text-brand-700 border border-brand-200">
                                                    <i class="fa-solid fa-cubes text-[10px]"></i> Mixed Condition
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                                    <i class="fa-regular fa-clock text-[10px]"></i> Pending Review
                                                </span>
                                            @endif
                                        </div>

                                        @if($returnItem->evidences->isNotEmpty())
                                            <div class="mt-2 text-xs">
                                                <span class="font-semibold text-slate-700 block mb-1">Staff Evidence:</span>
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach($returnItem->evidences as $evidence)
                                                        <a href="{{ route('secure.evidence.show', $evidence) }}" target="_blank" class="inline-flex items-center gap-1 text-xs border border-brand-200 rounded-lg px-2 py-1 text-brand-700 hover:bg-brand-50 transition" title="View Evidence">
                                                            <i class="fa-solid fa-image text-brand-700"></i>
                                                            <span>View photo</span>
                                                        </a>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-xs text-slate-400 italic block">No staff evidence uploaded</span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Decision & Charge -->
                                <td class="px-4 py-4 align-top">
                                    <div class="space-y-2.5">
                                        <div class="relative">
                                            <select
                                                name="items[{{ $returnItem->id }}][charge_decision]"
                                                {{ $isArchivedAudit ? 'disabled' : '' }}
                                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-1 focus:ring-brand-500 disabled:bg-slate-100 disabled:text-slate-500 bg-white"
                                            >
                                                <option value="pending" {{ old('items.' . $returnItem->id . '.charge_decision', $returnItem->charge_decision) === 'pending' ? 'selected' : '' }}>Pending Decision</option>
                                                <option value="no_charge" {{ old('items.' . $returnItem->id . '.charge_decision', $returnItem->charge_decision) === 'no_charge' ? 'selected' : '' }}>No Charge (Waived)</option>
                                                <option value="charge" {{ in_array(old('items.' . $returnItem->id . '.charge_decision', $returnItem->charge_decision), ['charge', 'charge_client'], true) ? 'selected' : '' }}>Charge Client</option>
                                            </select>
                                        </div>

                                        <div class="flex items-center gap-1.5">
                                            <span class="text-slate-500 font-semibold text-sm">₱</span>
                                            <input
                                                type="number"
                                                name="items[{{ $returnItem->id }}][damage_charge]"
                                                value="{{ old('items.' . $returnItem->id . '.damage_charge', $returnItem->damage_charge ?? 0) }}"
                                                min="0"
                                                step="0.01"
                                                {{ $isArchivedAudit ? 'disabled' : '' }}
                                                class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-semibold focus:border-brand-600 focus:ring-1 focus:ring-brand-500 disabled:bg-slate-100 disabled:text-slate-500"
                                                placeholder="Charge amount"
                                            >
                                        </div>
                                    </div>
                                </td>

                                <!-- Notes & Reason -->
                                <td class="px-4 py-4 align-top">
                                    <div class="space-y-2">
                                        <textarea
                                            name="items[{{ $returnItem->id }}][notes]"
                                            rows="2"
                                            {{ $isArchivedAudit ? 'disabled' : '' }}
                                            class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs focus:border-brand-600 focus:ring-1 focus:ring-brand-500 disabled:bg-slate-100 disabled:text-slate-500"
                                            placeholder="Physical return notes..."
                                        >{{ old('items.' . $returnItem->id . '.notes', $returnItem->notes) }}</textarea>

                                        <textarea
                                            name="items[{{ $returnItem->id }}][charge_reason]"
                                            rows="1"
                                            {{ $isArchivedAudit ? 'disabled' : '' }}
                                            class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs focus:border-brand-600 focus:ring-1 focus:ring-brand-500 disabled:bg-slate-100 disabled:text-slate-500"
                                            placeholder="Reason for damage/loss fee..."
                                        >{{ old('items.' . $returnItem->id . '.charge_reason', $returnItem->charge_reason) }}</textarea>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">
                                    No non-perishable booking items were found for this return record.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Audit Notes Card -->
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
                <label class="mb-2 block text-sm font-bold text-slate-800">Overall Audit Notes</label>
                <textarea
                    name="notes"
                    rows="3"
                    {{ $isArchivedAudit ? 'disabled' : '' }}
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-700 focus:border-brand-600 focus:ring-1 focus:ring-brand-500 disabled:bg-slate-100 disabled:text-slate-500"
                    placeholder="Enter overall notes about the return inspection and equipment condition..."
                >{{ old('notes', $return->notes) }}</textarea>
            </div>

            <!-- Footer Actions & Safety Notice -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
                <div>
                    <span class="text-xs text-slate-500 flex items-center gap-1.5">
                        <i class="fa-solid fa-shield-halved text-brand-700"></i>
                        <span>Saving updates the return record and reconciles eligible good stock back into active inventory.</span>
                    </span>
                </div>

                <div class="flex items-center gap-3 justify-end">
                    <a href="{{ route('admin.return-tracking') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                        {{ $isArchivedAudit ? 'Back to Return Tracking' : 'Cancel' }}
                    </a>
                    @if($isArchivedAudit)
                        <a href="{{ route('admin.bookings.show', $booking) }}" class="inline-flex items-center justify-center rounded-xl bg-slate-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-900 transition">
                            Back to Booking Review
                        </a>
                    @else
                        @if($return->returnItems->isEmpty())
                            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-700 px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-800 transition shadow-2xs cursor-pointer">
                                <i class="fa-solid fa-check"></i>
                                <span>Complete Zero-Hardware Audit</span>
                            </button>
                        @else
                            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-700 px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-800 transition shadow-2xs cursor-pointer">
                                <i class="fa-solid fa-boxes-packing"></i>
                                <span>Save &amp; Reconcile Return</span>
                            </button>
                        @endif
                    @endif
                </div>
            </div>
        </form>
    </div>
</x-admin-layout>
