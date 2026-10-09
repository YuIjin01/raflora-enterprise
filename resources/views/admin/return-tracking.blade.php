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
            <x-alert type="success" :inline="true">{{ session('success') }}</x-alert>
        @endif
        @if($errors->any())
            <x-alert type="danger" :inline="true">
                <div class="font-semibold mb-1">Please fix the following errors:</div>
                <ul class="list-disc pl-5 text-xs space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <!-- Primary Toolbar: Search & Filter Area -->
        <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-4 sm:p-5">
            <form method="GET" action="{{ route('admin.return-tracking') }}" id="returnFilterForm" class="space-y-4">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                    <!-- Compact Search Area -->
                    <div class="flex flex-wrap items-center gap-2.5 flex-1">
                        <!-- Search Box -->
                        <div class="relative flex-1 min-w-[220px] max-w-md">
                            <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-gray-400">
                                <i class="fa-solid fa-search text-xs"></i>
                            </span>
                            <input
                                type="text"
                                name="search"
                                value="{{ $currentSearch ?? '' }}"
                                placeholder="Search booking or client..."
                                class="w-full pl-9 pr-3.5 py-2 bg-gray-50/70 border border-gray-200 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition shadow-2xs"
                            >
                        </div>

                        <!-- Show Filters Toggle Button -->
                        <button
                            type="button"
                            id="toggleFiltersBtn"
                            onclick="toggleFilterPanel()"
                            aria-expanded="{{ !empty($hasActiveFilters) ? 'true' : 'false' }}"
                            aria-controls="returnFilterPanel"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 border rounded-xl text-sm font-medium transition shadow-2xs focus:outline-none focus:ring-2 focus:ring-purple-500 cursor-pointer {{ !empty($hasActiveFilters) ? 'border-purple-300 bg-purple-50 text-purple-700 font-semibold' : 'border-gray-200 hover:border-purple-300 bg-white hover:bg-purple-50/50 text-gray-700 hover:text-purple-700' }}"
                        >
                            <i class="fa-solid fa-sliders text-xs {{ !empty($hasActiveFilters) ? 'text-purple-600' : 'text-gray-500' }}"></i>
                            <span id="toggleFiltersText">{{ !empty($hasActiveFilters) ? 'Hide Filters' : 'Show Filters' }}</span>
                            @if(!empty($hasActiveFilters))
                                <span class="w-1.5 h-1.5 rounded-full bg-purple-600 inline-block" title="Filters are active"></span>
                            @endif
                            <i id="filtersChevron" class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200 {{ !empty($hasActiveFilters) ? 'rotate-180' : '' }}"></i>
                        </button>

                        <!-- Search Submit Button -->
                        <button
                            type="submit"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold rounded-xl transition shadow-2xs focus:outline-none focus:ring-2 focus:ring-purple-500 cursor-pointer"
                        >
                            <i class="fa-solid fa-magnifying-glass text-xs"></i>
                            <span>Search</span>
                        </button>

                        @if(!empty($currentSearch) || !empty($hasActiveFilters))
                            <a
                                href="{{ route('admin.return-tracking') }}"
                                class="px-3 py-2 text-xs font-semibold text-gray-500 hover:text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition"
                            >
                                Clear
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Secondary Filters Collapsible Panel -->
                <div id="returnFilterPanel" style="{{ !empty($hasActiveFilters) ? 'display: block;' : 'display: none;' }}" class="pt-4 border-t border-gray-100">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Status Filter -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1.5">Return Status</label>
                            <div class="relative">
                                <select name="status" onchange="this.form.submit()" class="w-full py-2 pl-3 pr-8 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-700 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-2xs appearance-none transition cursor-pointer">
                                    <option value="all" {{ ($currentStatus ?? 'all') === 'all' ? 'selected' : '' }}>All Statuses</option>
                                    <option value="Pending" {{ ($currentStatus ?? '') === 'Pending' ? 'selected' : '' }}>Pending Audit</option>
                                    <option value="Partially Returned" {{ ($currentStatus ?? '') === 'Partially Returned' ? 'selected' : '' }}>Partially Returned</option>
                                    <option value="Completed" {{ ($currentStatus ?? '') === 'Completed' ? 'selected' : '' }}>Completed</option>
                                </select>
                                <span class="absolute inset-y-0 right-2.5 flex items-center pointer-events-none text-gray-400">
                                    <i class="fa-solid fa-chevron-down text-[10px]"></i>
                                </span>
                            </div>
                        </div>

                        <!-- Event Date Filter -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1.5">Event Date</label>
                            <div class="relative">
                                <input
                                    type="date"
                                    name="event_date"
                                    value="{{ $currentEventDate ?? '' }}"
                                    onchange="this.form.submit()"
                                    class="w-full py-2 px-3 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-700 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-2xs transition"
                                >
                            </div>
                        </div>

                        <!-- Sort Order -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1.5">Sort Records</label>
                            <div class="relative">
                                <select name="sort" onchange="this.form.submit()" class="w-full py-2 pl-3 pr-8 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-700 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-2xs appearance-none transition cursor-pointer">
                                    <option value="default" {{ ($currentSort ?? 'default') === 'default' ? 'selected' : '' }}>Status Priority (Pending First)</option>
                                    <option value="latest" {{ ($currentSort ?? '') === 'latest' ? 'selected' : '' }}>Latest Return Created</option>
                                    <option value="oldest" {{ ($currentSort ?? '') === 'oldest' ? 'selected' : '' }}>Oldest Return Created</option>
                                    <option value="event_date_desc" {{ ($currentSort ?? '') === 'event_date_desc' ? 'selected' : '' }}>Event Date (Newest First)</option>
                                    <option value="event_date_asc" {{ ($currentSort ?? '') === 'event_date_asc' ? 'selected' : '' }}>Event Date (Oldest First)</option>
                                    <option value="booking_id" {{ ($currentSort ?? '') === 'booking_id' ? 'selected' : '' }}>Booking # (High to Low)</option>
                                </select>
                                <span class="absolute inset-y-0 right-2.5 flex items-center pointer-events-none text-gray-400">
                                    <i class="fa-solid fa-chevron-down text-[10px]"></i>
                                </span>
                            </div>
                        </div>
                    </div>

                    @if(!empty($hasActiveFilters))
                        <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                            <span>Secondary filters are active</span>
                            <a href="{{ route('admin.return-tracking') }}" class="font-semibold text-purple-600 hover:text-purple-700">
                                Reset All Filters
                            </a>
                        </div>
                    @endif
                </div>
            </form>
        </div>

        <!-- Returns Data Table -->
        <section class="bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden" aria-labelledby="returns-table-heading">
            <h2 id="returns-table-heading" class="sr-only">Return tracking records</h2>
            <div class="overflow-x-auto w-full">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200">
                            <th scope="col" class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Booking / Client</th>
                            <th scope="col" class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Event Details</th>
                            <th scope="col" class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Damage Charge</th>
                            <th scope="col" class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($returns as $return)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-700 border border-purple-100 flex items-center justify-center font-bold text-sm shrink-0">
                                            #{{ $return->booking_id }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-bold text-slate-900 truncate">
                                                {{ $return->booking?->client?->full_name ?? $return->booking?->guest_name ?? 'N/A' }}
                                            </p>
                                            <p class="text-xs text-slate-500 truncate">
                                                {{ $return->booking?->client?->email ?? $return->booking?->guest_email ?? 'N/A' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-medium text-slate-800">{{ $return->booking?->event_type ? ucfirst($return->booking->event_type) : 'N/A' }}</p>
                                    <p class="text-xs text-slate-500 mt-0.5">
                                        <i class="fa-regular fa-calendar text-[11px] mr-1 text-slate-400"></i>
                                        {{ optional($return->booking?->event_date)->format('M d, Y') ?? 'N/A' }}
                                        @if($return->booking?->event_time)
                                            <span class="mx-1 text-slate-300">•</span>
                                            {{ $return->booking->event_time }}
                                        @endif
                                    </p>
                                </td>
                                <td class="px-6 py-4">
                                    @if($return->status == 'Completed')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <i class="fa-solid fa-circle-check text-[10px]"></i>
                                            Items Returned
                                        </span>
                                    @elseif($return->status == 'Partially Returned')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                            <i class="fa-solid fa-triangle-exclamation text-[10px]"></i>
                                            Partially Returned
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                            <i class="fa-regular fa-clock text-[10px]"></i>
                                            Pending Return Audit
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if($return->total_damage_charge > 0)
                                        <span class="text-sm font-bold text-rose-600">₱{{ number_format($return->total_damage_charge, 2) }}</span>
                                    @else
                                        <span class="text-sm text-slate-400">₱0.00</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a
                                        href="{{ route('admin.return-tracking.show', $return) }}"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-purple-50 hover:bg-purple-100 text-purple-700 hover:text-purple-800 border border-purple-200 text-xs font-semibold rounded-lg transition shadow-2xs"
                                    >
                                        <i class="fa-solid fa-clipboard-check text-purple-600 text-xs"></i>
                                        <span>Review Return</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-14 text-center">
                                    @if(!empty($currentSearch) || !empty($hasActiveFilters))
                                        <div class="max-w-md mx-auto text-slate-500">
                                            <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center mx-auto mb-3">
                                                <i class="fa-solid fa-magnifying-glass text-xl"></i>
                                            </div>
                                            <p class="text-sm font-semibold text-slate-800">No matching returns found</p>
                                            <p class="text-xs text-slate-500 mt-1">No returns matched your current search query or active filter criteria.</p>
                                            <div class="mt-4">
                                                <a href="{{ route('admin.return-tracking') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold rounded-lg transition shadow-2xs">
                                                    <i class="fa-solid fa-rotate-left text-xs"></i>
                                                    <span>Clear Filters</span>
                                                </a>
                                            </div>
                                        </div>
                                    @else
                                        <div class="max-w-md mx-auto text-slate-500">
                                            <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                                                <i class="fa-solid fa-boxes-stacked text-xl"></i>
                                            </div>
                                            <p class="text-sm font-semibold text-slate-800">No pending returns</p>
                                            <p class="text-xs text-slate-500 mt-1">Completed events requiring asset return processing will appear here.</p>
                                        </div>
                                    @endif
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

    <script>
        function toggleFilterPanel() {
            const panel = document.getElementById('returnFilterPanel');
            const text = document.getElementById('toggleFiltersText');
            const chevron = document.getElementById('filtersChevron');
            const btn = document.getElementById('toggleFiltersBtn');
            if (!panel) return;

            const isHidden = panel.style.display === 'none' || panel.classList.contains('hidden');
            if (isHidden) {
                panel.style.display = 'block';
                panel.classList.remove('hidden');
                if (text) text.textContent = 'Hide Filters';
                if (chevron) chevron.classList.add('rotate-180');
                if (btn) btn.setAttribute('aria-expanded', 'true');
            } else {
                panel.style.display = 'none';
                panel.classList.add('hidden');
                if (text) text.textContent = 'Show Filters';
                if (chevron) chevron.classList.remove('rotate-180');
                if (btn) btn.setAttribute('aria-expanded', 'false');
            }
        }
    </script>
</x-admin-layout>
