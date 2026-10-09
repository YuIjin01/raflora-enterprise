<x-admin-layout title="Return Tracking">
    <div class="space-y-6">
        <!-- Header Section -->
        <div class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Return Tracking</h1>
                <p class="text-sm text-slate-500 mt-1">Manage post-event asset returns, damages, and inventory adjustments.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="rf-alert rf-alert--success" role="status">
                <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                {{ session('success') }}
            </div>
        @endif
        @if($errors->any())
            <div class="rf-alert rf-alert--danger" role="alert">
                <i class="fa-solid fa-circle-xmark" aria-hidden="true"></i>
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Returns Data Table -->
        <section class="rf-panel overflow-hidden" aria-labelledby="returns-table-heading">
            <h2 id="returns-table-heading" class="sr-only">Return tracking records</h2>
            <div class="overflow-x-auto w-full">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th scope="col" class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Booking / Client</th>
                            <th scope="col" class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Event Details</th>
                            <th scope="col" class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Damage Charge</th>
                            <th scope="col" class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($returns as $return)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center font-bold text-sm">
                                            #{{ $return->booking_id }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-900">{{ $return->booking?->client?->first_name ?? $return->booking?->guest_name ?? 'N/A' }} {{ $return->booking?->client?->last_name ?? '' }}</p>
                                            <p class="text-xs text-slate-500">{{ $return->booking?->client?->email ?? $return->booking?->guest_email ?? 'N/A' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-medium text-slate-700">{{ $return->booking?->event_type ?? 'N/A' }}</p>
                                    <p class="text-xs text-slate-500">{{ optional($return->booking?->event_date)->format('M d, Y') ?? 'N/A' }} at {{ $return->booking?->event_time ?? 'N/A' }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    @if($return->status == 'Completed')
                                        <span class="rf-badge rf-badge--info">
                                            Items Returned
                                        </span>
                                    @elseif($return->status == 'Partially Returned')
                                        <span class="rf-badge rf-badge--warning">
                                            Partially Returned
                                        </span>
                                    @else
                                        <span class="rf-badge rf-badge--neutral">
                                            Pending Return Audit
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if($return->total_damage_charge > 0)
                                        <span class="text-sm font-bold text-red-600">₱{{ number_format($return->total_damage_charge, 2) }}</span>
                                    @else
                                        <span class="text-sm text-slate-400">₱0.00</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('admin.return-tracking.show', $return) }}" class="rf-btn rf-btn-outline text-xs">
                                        Manage Return
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                    <i class="fa-solid fa-box-open text-4xl text-slate-300 mb-3 block"></i>
                                    <p class="text-sm font-medium">No pending returns found.</p>
                                    <p class="text-xs mt-1">Returns are automatically tracked once a booking is marked as completed.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($returns->hasPages())
                <div class="p-4 border-t border-slate-200">
                    {{ $returns->links() }}
                </div>
            @endif
        </section>
    </div>
</x-admin-layout>
