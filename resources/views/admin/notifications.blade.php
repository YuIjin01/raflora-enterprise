<x-admin-layout title="Notifications">
    <div class="mx-auto max-w-[1600px] space-y-6">
        <div class="flex flex-col gap-3 border-b border-slate-200 pb-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-purple-600">Operations</p>
                <h1 class="serif mt-1 text-3xl font-bold text-slate-900">System Notifications</h1>
                <p class="mt-1 text-sm text-slate-500">Review payment verification requests, inventory shortages, and other admin alerts in one place.</p>
            </div>
            <form method="POST" action="{{ route('admin.alerts.read-all') }}">
                @csrf
                <button type="submit" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Dismiss All
                </button>
            </form>
        </div>

    @if($alerts->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-10 text-center text-sm text-slate-500">
            No active notifications at the moment.
        </div>
    @else
        <div class="space-y-4 rounded-2xl border border-slate-200 bg-slate-50/60 p-3">
            @php $renderedBookingIds = []; @endphp
            @foreach($alerts as $alert)
                    @php
                        $alertType = strtolower((string) ($alert->type ?? ''));
                        $isShortage = ($alert->type ?? '') === 'booking_shortage' || ($alert->type ?? '') === 'stock_alert';
                        $bookingId = (int) ($alert->booking_id ?? 0);
                        if ($isShortage) {
                            if ($bookingId > 0 && in_array($bookingId, $renderedBookingIds, true)) {
                                continue;
                            }
                            if ($bookingId > 0) {
                                $renderedBookingIds[] = $bookingId;
                            }
                        }
                        $alertTitle = strtolower((string) ($alert->title ?? ''));
                        $alertMessage = strtolower((string) ($alert->message ?? ''));
                        $isInventoryAlert = !empty($alert->inventory_item_id)
                            || str_contains($alertTitle, 'stock alert')
                            || str_contains($alertTitle, 'shortage')
                            || str_contains($alertTitle, 'inventory')
                            || str_contains($alertType, 'inventory')
                            || str_contains($alertType, 'stock')
                            || ($alertType === 'inventory_shortage');
                        $isBookingShortageAlert = $alertType === 'booking_shortage' || $alertType === 'daily_shortage' || $alertType === 'weekly_shortage';
                        $isPaymentAlert = $alertType === 'payment_pending';
                        $isPhysicalVarianceAlert = $alertType === 'physical_count_variance';
                        $badgeLabel = $isPhysicalVarianceAlert ? 'PHYSICAL COUNT VARIANCE' : ($isInventoryAlert ? 'STOCK ALERT' : ($isPaymentAlert ? 'PAYMENT VERIFICATION' : 'SYSTEM ALERT'));
                        $modalId = 'notification-action-' . ($alert->id ?? uniqid());
                        $modalTitle = $isPhysicalVarianceAlert ? 'Physical Stock Count Review' : ($isInventoryAlert ? 'Stock Shortage Alert' : ($isPaymentAlert ? 'Payment Verification Review' : 'Notification Details'));
                    @endphp
                    <article class="notification-card mb-4 flex cursor-pointer items-center justify-between rounded-2xl border border-rose-200 bg-white p-5 shadow-sm transition hover:shadow-md" data-alert-id="{{ $alert->id ?? uniqid() }}">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <button type="button" onclick="openNotificationModal('{{ $modalId }}')" class="flex-1 text-left">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="bg-rose-500 text-white font-bold text-[10px] px-2.5 py-0.5 rounded-md uppercase tracking-wider">
                                            {{ $badgeLabel }}
                                        </span>
                                        <span class="text-xs text-slate-400">{{ optional($alert->created_at)->diffForHumans() }}</span>
                                    </div>
                                    <h3 class="mt-3 text-lg font-semibold text-slate-900">{{ $alert->title }}</h3>
                                    <p class="mt-2 text-sm text-slate-600">{{ $alert->message }}</p>
                                </div>
                            </button>
                            @if(!($alert->is_dynamic ?? false))
                                <form method="POST" action="{{ route('admin.alerts.read', $alert) }}">
                                    @csrf
                                    <button type="submit" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition">
                                        Mark Read
                                    </button>
                                </form>
                            @endif
                        </div>

                        <div class="mt-4 flex flex-wrap items-center gap-3">
                            @if($isBookingShortageAlert)
                                <button type="button" onclick="openNotificationModal('{{ $modalId }}')" class="flex items-center gap-1.5 text-rose-600 font-semibold text-sm bg-rose-50 hover:bg-rose-100 px-4 py-2 rounded-xl transition">Resolve Booking Shortages →</button>
                            @elseif($alertType === 'general_low_stock')
                                <button type="button" onclick="openNotificationModal('{{ $modalId }}')" class="text-sm font-semibold text-amber-700 hover:text-amber-900">Review Low Stock →</button>
                            @elseif($isPaymentAlert)
                                <button type="button" onclick="openNotificationModal('{{ $modalId }}')" class="text-sm font-semibold text-purple-700 hover:text-purple-900">Review Payment Verification →</button>
                            @endif
                        </div>
                    </article>

                    <!-- MODAL CONTAINER (max-w-4xl) -->
                    <div id="{{ $modalId }}" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="{{ $modalId }}-title" data-alert-id="{{ $alert->id ?? uniqid() }}" onclick="closeNotificationModal('{{ $modalId }}')">
                        <div class="flex h-[75vh] max-h-[85vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl" onclick="event.stopPropagation()">
                            <div class="p-5 border-b flex justify-between items-center bg-white flex-shrink-0">
                                <div>
                                    <h3 id="{{ $modalId }}-title" class="text-lg font-bold text-gray-900">{{ $isBookingShortageAlert ? 'Resolve Inventory Shortages - Booking #' . ($alert->booking_id ?? 'N/A') : $modalTitle }}</h3>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ $alert->title }}</p>
                                </div>
                                <button type="button" aria-label="Close notification details" class="close-modal-btn rounded-lg p-2 text-lg font-bold text-gray-400 hover:bg-slate-100 hover:text-gray-600" onclick="closeNotificationModal('{{ $modalId }}')">✕</button>
                            </div>

                            @if($isBookingShortageAlert && $alert->booking_id)
                                <form method="POST" action="{{ route('admin.notifications.resolve-booking-shortages', ['booking' => $alert->booking_id]) }}" class="flex flex-col flex-1 overflow-hidden" data-booking-shortage-form data-has-missing-items="{{ collect($alert->items ?? [])->contains(fn ($item) => $item['is_missing'] ?? false) ? '1' : '0' }}">
                                    @csrf
                                    <div class="p-5 overflow-y-auto flex-1 min-h-0 space-y-3 bg-gray-50/50">
                                        <div class="bg-amber-50 border border-amber-200/60 rounded-xl p-4 text-xs text-amber-800 flex items-start gap-3">
                                            <span class="mt-0.5 text-amber-600 text-base">⚠</span>
                                            <div>
                                                <p class="font-bold text-sm text-amber-900">Booking Shortage Items</p>
                                                <p class="mt-0.5 text-slate-600">Add stock for catalog items. Items marked as not in current inventory must be linked or added to the catalog first.</p>
                                            </div>
                                        </div>

                                        <div class="space-y-2.5">
                                            @foreach(($alert->items ?? []) as $item)
                                                @php $inventoryItem = \App\Models\InventoryItem::find($item['inventory_item_id']); @endphp
                                                <div class="p-3 {{ ($item['is_shortage'] ?? true) ? 'border-rose-200 bg-white' : 'border-emerald-200 bg-emerald-50/70' }} border rounded-xl shadow-sm flex items-center justify-between gap-3">
                                                    
                                                    <!-- COLUMN 1: ITEM NAME & REQUIRED -->
                                                    <div class="flex-1 min-w-[180px] pr-2">
                                                        <p class="font-bold text-xs text-slate-800 leading-tight">{{ $inventoryItem?->name ?? $item['name'] }}</p>
                                                        <p class="mt-1 text-[11px] text-slate-500 font-medium">Required: <span class="text-slate-800 font-bold">{{ number_format((float) ($item['required'] ?? 0), 0) }}</span></p>
                                                    </div>

                                                    <!-- COLUMN 2: STATS BADGES (PHYSICAL, RESERVED, SHORTFALL) -->
                                                    <div class="flex items-center gap-1.5 flex-shrink-0">
                                                        <div class="rounded-md border border-slate-100 bg-slate-50/80 px-2.5 py-1 text-center min-w-[76px]">
                                                            <p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Physical</p>
                                                            <p class="text-xs text-slate-800 font-bold leading-tight">{{ number_format((float) ($item['current_stock'] ?? 0), 0) }}</p>
                                                        </div>
                                                        <div class="rounded-md border border-slate-100 bg-slate-50/80 px-2.5 py-1 text-center min-w-[76px]">
                                                            <p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Reserved</p>
                                                            <p class="text-xs text-slate-800 font-bold leading-tight">{{ number_format((float) ($item['reserved_stock'] ?? 0), 0) }}</p>
                                                        </div>
                                                        <div class="rounded-md border {{ ($item['is_shortage'] ?? true) ? 'border-rose-200/80 bg-rose-50' : 'border-emerald-200 bg-emerald-100' }} px-2.5 py-1 text-center min-w-[76px]">
                                                            <p class="text-[9px] font-bold uppercase tracking-wider {{ ($item['is_shortage'] ?? true) ? 'text-rose-500' : 'text-emerald-600' }}">{{ ($item['is_shortage'] ?? true) ? 'Shortfall' : 'Available' }}</p>
                                                            <p class="text-xs {{ ($item['is_shortage'] ?? true) ? 'text-rose-600' : 'text-emerald-700' }} font-extrabold leading-tight">{{ ($item['is_shortage'] ?? true) ? number_format((float) ($item['shortfall'] ?? 0), 0) : 'In Stock' }}</p>
                                                        </div>
                                                    </div>

                                                    <!-- COLUMN 3: ADD STOCK INPUT -->
                                                    <div class="w-28 flex-shrink-0 pl-1">
                                                        @if(!($item['is_shortage'] ?? true))
                                                            <span class="block rounded-lg border border-emerald-200 bg-emerald-100 px-2 py-2 text-center text-[10px] font-bold leading-tight text-emerald-700">Available</span>
                                                        @elseif($item['is_missing'] ?? false)
                                                            <span class="block rounded-lg border border-amber-200 bg-amber-50 px-2 py-2 text-center text-[10px] font-bold leading-tight text-amber-700">Not in current inventory</span>
                                                        @else
                                                            <label class="block text-[9px] font-bold uppercase tracking-wider text-slate-400 mb-1 text-center" for="stock_{{ $alert->id }}_{{ $item['inventory_item_id'] }}">Add Stock</label>
                                                            <input type="number" 
                                                                   min="0" 
                                                                   step="0.01" 
                                                                   name="stock[{{ $item['inventory_item_id'] }}]" 
                                                                   id="stock_{{ $alert->id }}_{{ $item['inventory_item_id'] }}" 
                                                                   data-shortfall="{{ $item['shortfall'] ?? 0 }}" 
                                                                   data-is-shortage="1"
                                                                   value="" 
                                                                   placeholder="0" 
                                                                   class="add-stock-input w-full rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-medium text-center focus:border-purple-500 focus:ring-1 focus:ring-purple-500 focus:outline-none">
                                                        @endif
                                                    </div>

                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex items-center justify-between gap-3 flex-shrink-0">
                                        <button type="button" class="px-4 py-2 rounded-xl border border-slate-300 bg-white text-slate-700 font-semibold text-xs hover:bg-slate-100 transition" onclick="closeNotificationModal('{{ $modalId }}')">Cancel</button>
                                        <div class="flex items-center gap-3">
                                            <button type="button" class="px-4 py-2 rounded-xl border border-slate-300 bg-white text-slate-700 font-semibold text-xs hover:bg-slate-100 transition" data-substitution-btn onclick="window.location.href='{{ route('admin.bookings.show', ['booking' => $alert->booking_id]) }}'">
                                                Request Substitution / Edit Booking
                                            </button>
                                            @php
                                                $missingBookingItemIds = collect($alert->items ?? [])->where('is_missing', true)->pluck('booking_item_id')->filter()->values();
                                            @endphp
                                            @if($missingBookingItemIds->isNotEmpty())
                                                <button type="submit" form="promote-missing-ai-items-{{ $modalId }}" class="px-5 py-2 rounded-xl bg-emerald-600 text-white font-semibold text-xs transition shadow-sm hover:bg-emerald-700">
                                                    Add Missing AI Items to Inventory
                                                </button>
                                            @endif
                                            <button type="submit" name="action" value="restock_and_resolve" class="px-5 py-2 rounded-xl font-semibold text-xs transition shadow-sm bg-slate-200 text-slate-400 cursor-not-allowed" data-resolve-btn disabled>
                                                Resolve &amp; Restock
                                            </button>
                                        </div>
                                    </div>
                                </form>
                                @if($missingBookingItemIds->isNotEmpty())
                                    <form id="promote-missing-ai-items-{{ $modalId }}" method="POST" action="{{ route('admin.notifications.promote-booking-items', ['booking' => $alert->booking_id]) }}" class="hidden">
                                        @csrf
                                        @foreach($missingBookingItemIds as $bookingItemId)
                                            <input type="hidden" name="booking_item_ids[]" value="{{ $bookingItemId }}">
                                        @endforeach
                                    </form>
                                @endif
                            @elseif($alertType === 'general_low_stock')
                                <div class="flex flex-col flex-1 overflow-hidden">
                                    <div class="p-6 overflow-y-auto flex-1 min-h-0 space-y-3 bg-gray-50/50">
                                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                                            <p class="text-sm font-semibold text-amber-700">General low-stock items</p>
                                            <p class="mt-2 text-sm text-amber-800">These catalog items are at or below their configured minimum stock level.</p>
                                        </div>
                                        @foreach(($alert->items ?? []) as $item)
                                            <div class="flex items-center justify-between gap-4 rounded-xl border border-amber-200 bg-white p-4">
                                                <div>
                                                    <p class="font-semibold text-slate-800">{{ $item['name'] }}</p>
                                                    <p class="mt-1 text-xs text-slate-500">Minimum: {{ number_format((float) ($item['required'] ?? 0), 0) }} · Available: {{ number_format((float) ($item['net_available'] ?? 0), 0) }}</p>
                                                </div>
                                                <span class="rounded-lg bg-amber-100 px-3 py-2 text-xs font-bold text-amber-700">Restock needed</span>
                                            </div>
                                        @endforeach
                                        <a href="{{ route('admin.inventory.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">Manage Inventory</a>
                                    </div>
                                    <div class="flex justify-end border-t border-slate-100 bg-white p-4">
                                        <button type="button" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" onclick="closeNotificationModal('{{ $modalId }}')">Close</button>
                                    </div>
                                </div>
                            @elseif($isInventoryAlert)
                                @php
                                    $inventoryItem = $alert->inventory_item_id ? \App\Models\InventoryItem::find($alert->inventory_item_id) : null;
                                    $physicalStock = (float) ($inventoryItem?->current_stock ?? 0);
                                    $reservedStock = (float) ($inventoryItem?->reserved_stock ?? 0);
                                    $netAvailable = max(0.0, $physicalStock - $reservedStock);
                                    $shortageAmount = max(0.0, (float) ($inventoryItem?->min_stock ?? 0) - $netAvailable);
                                @endphp
                                <div class="flex flex-col flex-1 overflow-hidden">
                                    <div class="p-6 overflow-y-auto flex-1 min-h-0 space-y-4 bg-gray-50/50">
                                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                                            <p class="text-sm font-semibold text-amber-700">Stock shortage alert</p>
                                            <p class="mt-2 text-sm text-amber-800">Resolve the shortage directly from this modal by adding physical stock and closing the alert immediately.</p>
                                        </div>

                                        @if($inventoryItem)
                                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                                <div class="grid gap-3 sm:grid-cols-3">
                                                    <div>
                                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Physical stock</p>
                                                        <p class="mt-1 text-lg font-semibold text-slate-900">{{ number_format($physicalStock, 0) }}</p>
                                                    </div>
                                                    <div>
                                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Reserved</p>
                                                        <p class="mt-1 text-lg font-semibold text-slate-900">{{ number_format($reservedStock, 0) }}</p>
                                                    </div>
                                                    <div>
                                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Shortage amount</p>
                                                        <p class="mt-1 text-lg font-semibold text-amber-700">{{ number_format($shortageAmount, 0) }}</p>
                                                    </div>
                                                </div>

                                                <form class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end" data-restock-form data-route="{{ route('admin.notifications.resolve-shortage', ['inventoryItem' => $inventoryItem->id]) }}" data-alert-id="{{ $alert->id ?? uniqid() }}">
                                                    @csrf
                                                    <div class="flex-1">
                                                        <label class="block text-sm font-semibold text-slate-700" for="additional_stock_{{ $inventoryItem->id }}">Add Stock Quantity</label>
                                                        <input type="number" min="0" step="0.01" name="additional_stock" id="additional_stock_{{ $inventoryItem->id }}" value="0" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-purple-500 focus:outline-none">
                                                    </div>
                                                    <button type="submit" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700 transition">Restock &amp; Resolve Alert</button>
                                                </form>
                                                <div class="mt-3 hidden rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700" data-restock-status></div>
                                            </div>
                                        @else
                                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                                                Inventory item details are unavailable for this alert.
                                            </div>
                                        @endif

                                        <div class="flex flex-wrap gap-3">
                                            @php $bookingId = (int) ($alert->booking_id ?? 0); @endphp
                                            @if($bookingId > 0)
                                                <a href="{{ route('admin.bookings.show', ['booking' => $bookingId]) }}" class="inline-flex items-center rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-700 transition">
                                                    View Booking (#{{ $bookingId }})
                                                </a>
                                            @endif
                                            <a href="{{ route('admin.inventory.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                                                Manage Inventory
                                            </a>
                                        </div>
                                    </div>

                                    <div class="p-4 border-t bg-white flex justify-end gap-3 flex-shrink-0">
                                        <button type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200" onclick="closeNotificationModal('{{ $modalId }}')">Close</button>
                                    </div>
                                </div>
                            @else
                                <div class="flex flex-col flex-1 overflow-hidden">
                                    <div class="p-6 overflow-y-auto flex-1 min-h-0 space-y-4 bg-gray-50/50">
                                        @if($isPaymentAlert && $alert->booking_id)
                                            @php $booking = \App\Models\Booking::with(['client', 'payments'])->find($alert->booking_id); $latestPayment = $booking?->payments->sortByDesc('created_at')->first(); @endphp
                                            <div class="rounded-xl border border-purple-200 bg-purple-50 p-4">
                                                <div class="space-y-3 text-sm text-slate-700">
                                                    <div><span class="font-semibold">Booking:</span> #{{ $booking?->id ?? $alert->booking_id }}</div>
                                                    <div><span class="font-semibold">Client:</span> {{ $booking?->client?->full_name ?? 'Guest' }}</div>
                                                    <div><span class="font-semibold">Event:</span> {{ $booking?->event_type ? ucfirst($booking->event_type) : 'N/A' }}</div>
                                                    <div><span class="font-semibold">Date:</span> {{ optional($booking?->event_date)->format('F j, Y') }}</div>
                                                    <div><span class="font-semibold">Venue:</span> {{ $booking?->venue ?? 'Not set' }}</div>
                                                    <div><span class="font-semibold">Method:</span> {{ $latestPayment ? strtoupper(str_replace('_', ' ', $latestPayment->payment_type)) : 'N/A' }}</div>
                                                    <div><span class="font-semibold">Reference:</span> {{ $latestPayment?->reference_number ?? 'N/A' }}</div>
                                                </div>
                                                @if($latestPayment)
                                                    <form method="POST" action="{{ route('admin.payments.verify', ['payment' => $latestPayment->id]) }}" class="mt-4">
                                                        @csrf
                                                        <div class="flex gap-2">
                                                            <button type="submit" class="flex-1 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 transition">Verify &amp; Lock Booking</button>
                                                            <button type="button" onclick="document.getElementById('reject-payment-alert-{{ $latestPayment->id }}').submit();" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 transition">Reject</button>
                                                        </div>
                                                    </form>
                                                    <form id="reject-payment-alert-{{ $latestPayment->id }}" method="POST" action="{{ route('admin.payments.reject', ['payment' => $latestPayment->id]) }}" class="hidden">
                                                        @csrf
                                                    </form>
                                                @endif
                                            </div>
                                        @elseif($isPhysicalVarianceAlert)
                                            <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                                                <p class="text-sm font-semibold text-blue-900">Physical Stock Count Discrepancy</p>
                                                <p class="mt-2 text-sm text-blue-800">{{ $alert->message }}</p>
                                                <div class="mt-4 flex flex-wrap gap-2">
                                                    <form method="POST" action="{{ route('admin.inventory.adjustments.approve', $alert) }}" class="flex-1">
                                                        @csrf
                                                        <button type="submit" class="w-full rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 transition">Approve &amp; Apply Adjustment</button>
                                                    </form>
                                                    <form method="POST" action="{{ route('admin.inventory.adjustments.reject', $alert) }}">
                                                        @csrf
                                                        <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 transition">Reject</button>
                                                    </form>
                                                </div>
                                            </div>
                                        @else
                                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                                                Additional actions are not available for this alert yet.
                                            </div>
                                        @endif
                                    </div>

                                    <div class="p-4 border-t bg-white flex justify-end gap-3 flex-shrink-0">
                                        <button type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200" onclick="closeNotificationModal('{{ $modalId }}')">Close</button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
        </div>
    @endif

    <script>
        function openNotificationModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeNotificationModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-booking-shortage-form]').forEach(function (form) {
                const resolveButton = form.querySelector('[data-resolve-btn]');
                const substitutionButton = form.querySelector('[data-substitution-btn]');
                const hasMissingItems = form.getAttribute('data-has-missing-items') === '1';

                function updateButtonState() {
                    const inputs = form.querySelectorAll('.add-stock-input');
                    
                    const satisfiesAllShortfalls = !hasMissingItems && inputs.length > 0 && Array.from(inputs).every(function (input) {
                        const enteredValue = parseFloat(input.value) || 0;
                        const requiredShortfall = parseFloat(input.getAttribute('data-shortfall')) || 0;
                        return enteredValue >= requiredShortfall && requiredShortfall > 0;
                    });

                    if (resolveButton) {
                        resolveButton.disabled = !satisfiesAllShortfalls;
                        
                        if (satisfiesAllShortfalls) {
                            // Active State: Force direct solid rose color and crisp white text
                            resolveButton.style.backgroundColor = "#e11d48";
                            resolveButton.style.color = "#ffffff";
                            resolveButton.style.cursor = "pointer";
                            resolveButton.style.opacity = "1";
                            resolveButton.classList.remove('bg-slate-200', 'text-slate-400', 'cursor-not-allowed');
                            resolveButton.classList.add('bg-rose-600', 'text-white', 'hover:bg-rose-700');
                        } else {
                            // Disabled State: Clear readable gray background and dark text
                            resolveButton.style.backgroundColor = "#e2e8f0";
                            resolveButton.style.color = "#94a3b8";
                            resolveButton.style.cursor = "not-allowed";
                            resolveButton.style.opacity = "1";
                            resolveButton.classList.remove('bg-rose-600', 'text-white', 'hover:bg-rose-700');
                            resolveButton.classList.add('bg-slate-200', 'text-slate-400', 'cursor-not-allowed');
                        }
                    }

                    if (substitutionButton) {
                        substitutionButton.disabled = satisfiesAllShortfalls;
                        substitutionButton.style.opacity = satisfiesAllShortfalls ? '0.5' : '1';
                        substitutionButton.style.cursor = satisfiesAllShortfalls ? 'not-allowed' : 'pointer';
                    }
                }

                form.querySelectorAll('.add-stock-input').forEach(function (input) {
                    input.addEventListener('input', updateButtonState);
                    input.addEventListener('change', updateButtonState);
                });

                updateButtonState();
            });
        });
    </script>
</x-admin-layout>
