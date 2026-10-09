<?php
\ = 'resources/views/admin/bookings.blade.php';
\ = file_get_contents(\);

\ = <<<HTML
                                <td class="px-6 py-4 text-right align-top">
                                    <a href="{{ route('admin.bookings.edit', ['booking' => \\\->id]) }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-white border border-slate-300 px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition w-full sm:w-auto shadow-sm whitespace-nowrap">
                                        View / Review Booking <i class="fa-solid fa-arrow-right text-slate-400"></i>
                                    </a>
                                </td>
HTML;

\ = <<<HTML
                                <td class="px-6 py-4 text-right align-top">
                                    <div class="flex flex-col gap-2 items-end">
                                        <a href="{{ route('admin.bookings.edit', ['booking' => \\\->id]) }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-white border border-slate-300 px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition w-full sm:w-auto shadow-sm whitespace-nowrap">
                                            View / Review Booking <i class="fa-solid fa-arrow-right text-slate-400"></i>
                                        </a>
                                        @if(in_array(\\\->status, ['payment_pending', 'payment_submitted', 'pending_resolution']) && \\\->payments->where('status', 'pending')->isNotEmpty())
                                            <a href="{{ route('admin.bookings.edit', ['booking' => \\\->id]) }}" aria-label="Verify payment for booking {{ \\\->id }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700 transition w-full sm:w-auto shadow-sm whitespace-nowrap">
                                                Verify Payment
                                            </a>
                                        @endif
                                    </div>
                                </td>
HTML;

if (strpos(\, \) !== false) {
    \ = str_replace(\, \, \);
    file_put_contents(\, \);
    echo "Replaced successfully.\\n";
} else {
    echo "Search string not found.\\n";
}
?>
