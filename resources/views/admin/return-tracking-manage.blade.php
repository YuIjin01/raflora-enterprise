<x-admin-layout title="Return Audit">
    <div class="space-y-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Return Audit</h1>
                <p class="text-sm text-slate-500 mt-1">Review returned assets and finalize the booking after the return inspection.</p>
            </div>
            <a href="{{ route('admin.return-tracking') }}" class="bg-white border border-slate-300 text-slate-700 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-slate-50 transition">Back to Return Tracking</a>
        </div>

        @if(session('success'))
            <div class="rounded-lg bg-green-50 border border-green-200 p-4 text-sm text-green-800">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-800">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            $displayStatus = $booking->status_display_label;
            $statusBadgeClass = match ($booking->status) {
                'downpayment_received' => 'bg-emerald-100 text-emerald-800',
                'payment_pending' => 'bg-amber-100 text-amber-800',
                'completed' => 'bg-green-100 text-green-800',
                default => 'bg-slate-100 text-slate-800',
            };
            $isArchivedAudit = ($booking->status === 'completed') || ($booking->status === 'cancelled' && $return->status === 'Completed');
        @endphp

        @if($isArchivedAudit)
            <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900 shadow-sm flex items-center gap-3">
                <i class="fa-solid fa-lock text-blue-600 text-lg"></i>
                <div>
                    <p class="font-bold">Return Audit Completed &amp; Archived</p>
                    <p class="text-xs text-blue-700">This return audit record is finalized and locked. Historical return quantities, condition observations, and damage charge adjudications are preserved in read-only mode.</p>
                </div>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Booking Summary</h2>
                <div class="space-y-3 text-sm text-slate-700">
                    <div><span class="font-semibold">Booking #</span> {{ $booking->booking_number ?? $booking->id }}</div>
                    <div><span class="font-semibold">Client</span> {{ $booking->client?->full_name ?? $booking->guest_name ?? 'Guest' }}</div>
                    <div><span class="font-semibold">Event</span> {{ ucfirst($booking->event_type ?? 'N/A') }}</div>
                    <div><span class="font-semibold">Event Date</span> {{ optional($booking->event_date)->format('F j, Y') ?? 'N/A' }}</div>
                    <div><span class="font-semibold">Status</span>
                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusBadgeClass }}">
                            {{ $displayStatus }}
                        </span>
                    </div>
                    <div><span class="font-semibold">Damage Charge</span> ₱{{ number_format($return->total_damage_charge ?? 0, 2) }}</div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Return Instructions</h2>
                <p class="text-sm text-slate-600">Adjust quantities and conditions for each returned item. The booking will move to <span class="font-semibold">Return Completed</span> once the return audit reaches completion.</p>
                <p class="text-sm text-slate-600 mt-2">If items are still outstanding, the booking will remain in the return workflow until the issue is resolved.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.return-tracking.update', $return) }}" class="space-y-6">
            @csrf
            @method('PUT')

            @if($return->returnItems->isEmpty())
                <div class="rounded-3xl border border-amber-200 bg-amber-50 p-6 text-sm text-amber-900 shadow-sm mb-6">
                    This booking contains no rental or returnable hardware.
                </div>
            @endif

            <div class="overflow-x-auto rounded-3xl border border-slate-200 bg-white shadow-sm w-full">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 text-slate-700 text-xs uppercase tracking-wide">
                        <tr>
                            <th class="px-4 py-4">Item</th>
                            <th class="px-4 py-4">Dispatched</th>
                            <th class="px-4 py-4 w-80">Quantities (Good / Damaged / Lost)</th>
                            <th class="px-4 py-4">Observation & Evidence</th>
                            <th class="px-4 py-4">Decision & Charge</th>
                            <th class="px-4 py-4">Notes & Reason</th>
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
                            <tr>
                                <td class="px-4 py-4 align-top">
                                    <div class="font-semibold text-slate-900">{{ $inventoryItem?->name ?? 'Unknown item' }}</div>
                                    <div class="text-xs text-slate-500">SKU: {{ $inventoryItem?->sku ?? 'N/A' }}</div>
                                    <input type="hidden" name="items[{{ $returnItem->id }}][inventory_item_id]" value="{{ $returnItem->inventory_item_id }}">
                                </td>
                                <td class="px-4 py-4 text-sm font-semibold text-slate-700 align-top">
                                    {{ $dispatched }}
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <div class="space-y-2">
                                        <div class="grid grid-cols-3 gap-2">
                                            <div>
                                                <label class="block text-xs font-semibold text-emerald-700 mb-1" for="good-{{ $returnItem->id }}">Good</label>
                                                <input id="good-{{ $returnItem->id }}" type="number" name="items[{{ $returnItem->id }}][quantity_good]" value="{{ $good }}" min="0" max="{{ $dispatched }}" step="1" {{ $isArchivedAudit ? 'disabled' : '' }} class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm text-slate-800 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 disabled:bg-slate-100 disabled:text-slate-500">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-semibold text-amber-700 mb-1" for="damaged-{{ $returnItem->id }}">Damaged</label>
                                                <input id="damaged-{{ $returnItem->id }}" type="number" name="items[{{ $returnItem->id }}][quantity_damaged]" value="{{ $damaged }}" min="0" max="{{ $dispatched }}" step="1" {{ $isArchivedAudit ? 'disabled' : '' }} class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm text-slate-800 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 disabled:bg-slate-100 disabled:text-slate-500">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-semibold text-rose-700 mb-1" for="lost-{{ $returnItem->id }}">Lost</label>
                                                <input id="lost-{{ $returnItem->id }}" type="number" name="items[{{ $returnItem->id }}][quantity_lost]" value="{{ $lost }}" min="0" max="{{ $dispatched }}" step="1" {{ $isArchivedAudit ? 'disabled' : '' }} class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm text-slate-800 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 disabled:bg-slate-100 disabled:text-slate-500">
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between text-xs text-slate-500 pt-1 border-t border-slate-100">
                                            <span>Accounted: <strong class="text-slate-700">{{ $totalAccounted }}</strong> / {{ $dispatched }}</span>
                                            @if($unaccounted > 0)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">{{ $unaccounted }} remaining</span>
                                            @else
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Fully accounted</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <div class="space-y-2">
                                        <div class="text-xs">
                                            <span class="text-slate-500">Condition:</span>
                                            <span class="font-semibold text-slate-800 uppercase tracking-wide ml-1">{{ $returnItem->condition ?? 'pending' }}</span>
                                        </div>
                                        @if($returnItem->evidences->isNotEmpty())
                                            <div class="mt-2 text-xs">
                                                <span class="font-semibold text-slate-700 block mb-1">Staff Evidence:</span>
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach($returnItem->evidences as $evidence)
                                                        <a href="{{ route('secure.evidence.show', $evidence) }}" target="_blank" class="inline-block border border-slate-200 rounded p-1 hover:border-purple-500 transition" title="View Evidence">
                                                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                        </a>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-xs text-slate-400 italic">No evidence uploaded</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <div class="space-y-3">
                                        <select name="items[{{ $returnItem->id }}][charge_decision]" {{ $isArchivedAudit ? 'disabled' : '' }} class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 disabled:bg-slate-100 disabled:text-slate-500">
                                            <option value="pending" {{ old('items.' . $returnItem->id . '.charge_decision', $returnItem->charge_decision) === 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="no_charge" {{ old('items.' . $returnItem->id . '.charge_decision', $returnItem->charge_decision) === 'no_charge' ? 'selected' : '' }}>No Charge</option>
                                            <option value="charge" {{ in_array(old('items.' . $returnItem->id . '.charge_decision', $returnItem->charge_decision), ['charge', 'charge_client'], true) ? 'selected' : '' }}>Charge Client</option>
                                        </select>
                                        <div class="flex items-center gap-2">
                                            <span class="text-slate-500">₱</span>
                                            <input type="number" name="items[{{ $returnItem->id }}][damage_charge]" value="{{ old('items.' . $returnItem->id . '.damage_charge', $returnItem->damage_charge ?? 0) }}" min="0" step="0.01" {{ $isArchivedAudit ? 'disabled' : '' }} class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 disabled:bg-slate-100 disabled:text-slate-500" placeholder="Charge Amount">
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <div class="space-y-2">
                                        <textarea name="items[{{ $returnItem->id }}][notes]" rows="2" {{ $isArchivedAudit ? 'disabled' : '' }} class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 disabled:bg-slate-100 disabled:text-slate-500" placeholder="Return Notes">{{ old('items.' . $returnItem->id . '.notes', $returnItem->notes) }}</textarea>
                                        <textarea name="items[{{ $returnItem->id }}][charge_reason]" rows="1" {{ $isArchivedAudit ? 'disabled' : '' }} class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 disabled:bg-slate-100 disabled:text-slate-500" placeholder="Reason for charge">{{ old('items.' . $returnItem->id . '.charge_reason', $returnItem->charge_reason) }}</textarea>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">
                                    No non-perishable booking items were found for this record.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <label class="mb-2 block text-sm font-semibold text-slate-700">Audit Notes</label>
                <textarea name="notes" rows="4" {{ $isArchivedAudit ? 'disabled' : '' }} class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-700 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 disabled:bg-slate-100 disabled:text-slate-500">{{ old('notes', $return->notes) }}</textarea>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                <a href="{{ route('admin.return-tracking') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                    {{ $isArchivedAudit ? 'Back to Return Tracking' : 'Cancel' }}
                </a>
                @if($isArchivedAudit)
                    <a href="{{ route('admin.bookings.show', $booking) }}" class="inline-flex items-center justify-center rounded-lg bg-slate-800 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-900 transition">
                        Back to Booking Review
                    </a>
                @else
                    @if($return->returnItems->isEmpty())
                        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-white border border-purple-700 px-5 py-3 text-sm font-semibold text-purple-700 hover:bg-purple-50 transition">
                            Mark as Completed (No Hardware to Return)
                        </button>
                    @else
                        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-purple-700 px-5 py-3 text-sm font-semibold text-white hover:bg-purple-800 transition">
                            Save Return Audit
                        </button>
                    @endif
                @endif
            </div>
        </form>
    </div>
</x-admin-layout>

