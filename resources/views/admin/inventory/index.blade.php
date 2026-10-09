<x-admin-layout title="Inventory Management">
    <!-- Breadcrumb & Header -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-purple-600 mb-1">OPERATIONS</p>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 font-serif">Inventory Management</h1>
            <p class="text-sm text-slate-500 mt-1">Track and manage floral materials, props, and event inventory.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.inventory.archived') }}" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 bg-white hover:bg-slate-50 border border-slate-200 rounded-xl transition shadow-2xs">
                <i class="fa-solid fa-box-archive text-slate-400"></i>
                <span>Archived Items</span>
            </a>
        </div>
    </div>

    <!-- Success & Error Alerts -->
    @if(session('success'))
        <div class="mb-5 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl flex items-center justify-between shadow-2xs" role="alert">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-circle-check text-emerald-600"></i>
                <span class="text-sm font-medium">{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 text-sm">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-5 bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl flex items-center justify-between shadow-2xs" role="alert">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-circle-exclamation text-rose-600"></i>
                <span class="text-sm font-medium">{{ session('error') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700 text-sm">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-5 bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl shadow-2xs">
            <div class="flex items-center gap-2 text-sm font-bold text-rose-900 mb-1">
                <i class="fa-solid fa-triangle-exclamation text-rose-600"></i>
                <span>Action Required</span>
            </div>
            <ul class="list-disc pl-5 text-xs space-y-0.5 text-rose-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- 4 KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Card 1: TOTAL ITEMS -->
        <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-xs flex items-center justify-between hover:border-slate-200 transition">
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">TOTAL ITEMS</p>
                <p class="text-2xl sm:text-3xl font-extrabold text-slate-900">{{ number_format($totalItemsCount) }}</p>
                <p class="text-xs text-slate-500 mt-1">All active inventory items</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-boxes-stacked text-lg"></i>
            </div>
        </div>

        <!-- Card 2: IN STOCK -->
        <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-xs flex items-center justify-between hover:border-slate-200 transition">
            <div class="flex-1 pr-2">
                <div class="flex items-center justify-between mb-1">
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">IN STOCK</p>
                    @if($totalItemsCount > 0)
                        <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-100">
                            {{ number_format(($inStockCount / $totalItemsCount) * 100, 1) }}%
                        </span>
                    @endif
                </div>
                <p class="text-2xl sm:text-3xl font-extrabold text-slate-900">{{ number_format($inStockCount) }}</p>
                <p class="text-xs text-slate-500 mt-1">Items meeting stock threshold</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-check-circle text-lg"></i>
            </div>
        </div>

        <!-- Card 3: LOW STOCK -->
        <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-xs flex items-center justify-between hover:border-slate-200 transition">
            <div class="flex-1 pr-2">
                <div class="flex items-center justify-between mb-1">
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">LOW STOCK</p>
                    @if($totalItemsCount > 0)
                        <span class="text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-100">
                            {{ number_format(($lowStockCount / $totalItemsCount) * 100, 1) }}%
                        </span>
                    @endif
                </div>
                <p class="text-2xl sm:text-3xl font-extrabold text-slate-900">{{ number_format($lowStockCount) }}</p>
                <p class="text-xs text-slate-500 mt-1">At or below minimum threshold</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-triangle-exclamation text-lg"></i>
            </div>
        </div>

        <!-- Card 4: SHORTAGE -->
        <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-xs flex items-center justify-between hover:border-slate-200 transition">
            <div class="flex-1 pr-2">
                <div class="flex items-center justify-between mb-1">
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">SHORTAGE</p>
                    @if($totalItemsCount > 0)
                        <span class="text-[10px] font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded-full border border-rose-100">
                            {{ number_format(($shortageCount / $totalItemsCount) * 100, 1) }}%
                        </span>
                    @endif
                </div>
                <p class="text-2xl sm:text-3xl font-extrabold text-slate-900">{{ number_format($shortageCount) }}</p>
                <p class="text-xs text-slate-500 mt-1">Negative available stock</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-circle-exclamation text-lg"></i>
            </div>
        </div>
    </div>

    <!-- Main Container -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden mb-8">
        <!-- Compact Toolbar Form -->
        <form method="GET" action="{{ route('admin.inventory.index') }}" id="inventoryFilterForm" class="p-4 sm:p-5 border-b border-slate-100">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                <!-- Left: Search Box, Show Filters Toggle, Search Action -->
                <div class="flex flex-wrap items-center gap-2.5 flex-1">
                    <!-- Search Input -->
                    <div class="relative flex-1 min-w-[240px] max-w-md">
                        <span class="absolute inset-y-0 left-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-search text-xs"></i>
                        </span>
                        <input
                            type="text"
                            name="search"
                            id="inventorySearchInput"
                            value="{{ request('search') }}"
                            placeholder="Search inventory items..."
                            class="w-full pl-9 pr-3.5 py-2 bg-slate-50/70 border border-slate-200 rounded-xl text-xs sm:text-sm text-slate-800 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition shadow-2xs"
                        >
                    </div>

                    <!-- Show Filters Button -->
                    @php
                        $hasActiveFilters = (
                            (request('category', 'all') !== 'all') ||
                            (request('status', 'all') !== 'all') ||
                            (request('stock_level', 'all') !== 'all') ||
                            (request('perishable', 'all') !== 'all') ||
                            (request('sort', 'default') !== 'default')
                        );
                    @endphp
                    <button
                        type="button"
                        id="filterToggleBtn"
                        onclick="toggleFilterPanel()"
                        aria-expanded="{{ $hasActiveFilters ? 'true' : 'false' }}"
                        aria-controls="filterPanel"
                        class="inline-flex items-center gap-2 px-3.5 py-2 border rounded-xl text-xs font-semibold transition shadow-2xs cursor-pointer {{ $hasActiveFilters ? 'bg-emerald-50 text-emerald-800 border-emerald-300' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50' }}"
                    >
                        <i class="fa-solid fa-filter text-xs {{ $hasActiveFilters ? 'text-emerald-600' : 'text-slate-400' }}"></i>
                        <span id="filterToggleText">{{ $hasActiveFilters ? 'Hide Filters' : 'Show Filters' }}</span>
                        @if($hasActiveFilters)
                            <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>
                        @endif
                        <i id="filterToggleChevron" class="fa-solid fa-chevron-down text-[10px] text-slate-400 transition-transform duration-200 {{ $hasActiveFilters ? 'rotate-180' : '' }}"></i>
                    </button>

                    <!-- Search Button -->
                    <button
                        type="submit"
                        class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs rounded-xl transition shadow-2xs focus:outline-none focus:ring-2 focus:ring-slate-900 cursor-pointer"
                    >
                        Search
                    </button>

                    @if(request('search') || $hasActiveFilters)
                        <a
                            href="{{ route('admin.inventory.index') }}"
                            class="px-3 py-2 text-xs font-semibold text-slate-500 hover:text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition"
                        >
                            Clear
                        </a>
                    @endif
                </div>

                <!-- Right: Action Controls (Inventory Tools ▼ & + Add Item) -->
                <div class="flex items-center gap-2.5 shrink-0 self-start lg:self-center">
                    <!-- Inventory CSV Tools Dropdown Container -->
                    <div class="relative" id="csvToolsContainer">
                        <button
                            type="button"
                            id="csvToolsButton"
                            onclick="toggleCsvDropdown()"
                            aria-haspopup="true"
                            aria-expanded="false"
                            aria-controls="csvToolsMenu"
                            class="inline-flex items-center gap-2 px-3.5 py-2 border border-slate-200 hover:border-emerald-600 bg-white hover:bg-emerald-50/40 text-slate-700 hover:text-emerald-700 font-semibold text-xs rounded-xl transition shadow-2xs focus:outline-none focus:ring-2 focus:ring-emerald-500 cursor-pointer"
                            title="CSV Tools"
                        >
                            <i class="fa-regular fa-file-lines text-emerald-600 text-sm"></i>
                            <span>Inventory Tools</span>
                            <span class="sr-only">CSV Tools</span>
                            <i id="csvToolsChevron" class="fa-solid fa-chevron-down text-[10px] text-slate-400 transition-transform duration-200"></i>
                        </button>

                        <!-- Popover Dropdown Menu -->
                        <div
                            id="csvToolsMenu"
                            style="display:none; width: 520px; max-width: calc(100vw - 2rem);"
                            role="region"
                            aria-labelledby="csvToolsButton"
                            class="absolute right-0 top-full mt-2 z-50 bg-white rounded-2xl shadow-xl border border-slate-100 p-4 sm:p-5 text-left transform transition-all"
                        >
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Column 1: EXPORT (DOWNLOAD) -->
                                <div>
                                    <p class="text-[10px] font-bold tracking-wider text-slate-400 uppercase mb-2.5">EXPORT (DOWNLOAD)</p>
                                    <div class="space-y-2">
                                        <a href="{{ route('admin.inventory.export') }}" onclick="closeCsvDropdown()" class="group flex items-start gap-3 p-2.5 rounded-xl hover:bg-emerald-50/50 border border-transparent hover:border-emerald-100 transition">
                                            <div class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 group-hover:bg-emerald-100 transition">
                                                <i class="fa-solid fa-file-arrow-down text-sm"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-xs font-bold text-slate-900 group-hover:text-emerald-700 transition">Download Inventory CSV</p>
                                                <p class="text-[11px] text-slate-500 mt-0.5">Export all inventory items</p>
                                            </div>
                                        </a>
                                        <a href="{{ route('admin.inventory.template') }}" onclick="closeCsvDropdown()" class="group flex items-start gap-3 p-2.5 rounded-xl hover:bg-slate-50 border border-transparent hover:border-slate-200 transition">
                                            <div class="w-9 h-9 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center shrink-0 group-hover:bg-slate-200 transition">
                                                <i class="fa-regular fa-file-lines text-sm"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-xs font-bold text-slate-900 group-hover:text-emerald-700 transition">Download Template</p>
                                                <p class="text-[11px] text-slate-500 mt-0.5">Get a blank CSV template</p>
                                            </div>
                                        </a>
                                    </div>
                                </div>

                                <!-- Column 2: IMPORT (UPLOAD) -->
                                <div>
                                    <p class="text-[10px] font-bold tracking-wider text-slate-400 uppercase mb-2.5">IMPORT (UPLOAD)</p>
                                    <div class="space-y-2">
                                        <button type="button" onclick="closeCsvDropdown(); openUploadModal();" class="w-full text-left group flex items-start gap-3 p-2.5 rounded-xl bg-emerald-50/70 border border-emerald-100 hover:bg-emerald-100/70 transition cursor-pointer">
                                            <div class="w-9 h-9 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                                <i class="fa-solid fa-file-arrow-up text-sm"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-xs font-bold text-emerald-950">Import Inventory CSV</p>
                                                <p class="text-[11px] text-emerald-700 mt-0.5">Upload and import items</p>
                                            </div>
                                        </button>
                                        <button type="button" onclick="closeCsvDropdown(); openInstructionsModal();" class="w-full text-left group flex items-start gap-3 p-2.5 rounded-xl hover:bg-slate-50 border border-transparent hover:border-slate-200 transition cursor-pointer">
                                            <div class="w-9 h-9 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center shrink-0 group-hover:bg-slate-200 transition">
                                                <i class="fa-solid fa-circle-info text-sm"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-xs font-bold text-slate-900 group-hover:text-emerald-700 transition">Import Instructions</p>
                                                <p class="text-[11px] text-slate-500 mt-0.5">View CSV guidelines</p>
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- + Add Item Button -->
                    <a href="{{ route('admin.inventory.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs rounded-xl shadow-xs hover:shadow transition focus:outline-none focus:ring-2 focus:ring-emerald-500 whitespace-nowrap">
                        <i class="fa-solid fa-plus text-xs"></i>
                        <span>+ Add Item</span>
                    </a>
                </div>
            </div>

            <!-- Collapsible Filter Panel -->
            <div
                id="filterPanel"
                style="display: {{ $hasActiveFilters ? 'block' : 'none' }};"
                class="mt-4 pt-4 border-t border-slate-100"
            >
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                    <!-- Category Filter -->
                    <div>
                        <label for="filterCategory" class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Category</label>
                        <select
                            id="filterCategory"
                            name="category"
                            onchange="this.form.submit()"
                            class="w-full py-1.5 px-3 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-700 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 shadow-2xs transition cursor-pointer"
                        >
                            <option value="all" {{ $currentCategory === 'all' ? 'selected' : '' }}>All Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}" {{ $currentCategory === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label for="filterStatus" class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Status</label>
                        <select
                            id="filterStatus"
                            name="status"
                            onchange="this.form.submit()"
                            class="w-full py-1.5 px-3 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-700 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 shadow-2xs transition cursor-pointer"
                        >
                            <option value="all" {{ $currentStatus === 'all' ? 'selected' : '' }}>All Statuses</option>
                            <option value="in_stock" {{ $currentStatus === 'in_stock' ? 'selected' : '' }}>In Stock</option>
                            <option value="low" {{ $currentStatus === 'low' ? 'selected' : '' }}>Low Stock</option>
                            <option value="shortage" {{ $currentStatus === 'shortage' ? 'selected' : '' }}>Shortage</option>
                        </select>
                    </div>

                    <!-- Perishable Filter -->
                    <div>
                        <label for="filterPerishable" class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Perishable</label>
                        <select
                            id="filterPerishable"
                            name="perishable"
                            onchange="this.form.submit()"
                            class="w-full py-1.5 px-3 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-700 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 shadow-2xs transition cursor-pointer"
                        >
                            <option value="all" {{ $currentPerishable === 'all' ? 'selected' : '' }}>All Types</option>
                            <option value="yes" {{ $currentPerishable === 'yes' ? 'selected' : '' }}>Perishable</option>
                            <option value="no" {{ $currentPerishable === 'no' ? 'selected' : '' }}>Non-perishable</option>
                        </select>
                    </div>

                    <!-- Stock Level Filter -->
                    <div>
                        <label for="filterStockLevel" class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Stock Level</label>
                        <select
                            id="filterStockLevel"
                            name="stock_level"
                            onchange="this.form.submit()"
                            class="w-full py-1.5 px-3 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-700 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 shadow-2xs transition cursor-pointer"
                        >
                            <option value="all" {{ ($currentStockLevel ?? 'all') === 'all' ? 'selected' : '' }}>All Stock Levels</option>
                            <option value="in_stock" {{ ($currentStockLevel ?? '') === 'in_stock' ? 'selected' : '' }}>Above Minimum</option>
                            <option value="low" {{ ($currentStockLevel ?? '') === 'low' ? 'selected' : '' }}>Low Stock</option>
                            <option value="shortage" {{ ($currentStockLevel ?? '') === 'shortage' ? 'selected' : '' }}>Shortage</option>
                        </select>
                    </div>

                    <!-- Sort -->
                    <div>
                        <label for="filterSort" class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Sort</label>
                        <select
                            id="filterSort"
                            name="sort"
                            onchange="this.form.submit()"
                            class="w-full py-1.5 px-3 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-700 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 shadow-2xs transition cursor-pointer"
                        >
                            <option value="default" {{ ($currentSort ?? 'default') === 'default' ? 'selected' : '' }}>Default (Shortage Priority)</option>
                            <option value="name_asc" {{ ($currentSort ?? '') === 'name_asc' ? 'selected' : '' }}>Name (A - Z)</option>
                            <option value="name_desc" {{ ($currentSort ?? '') === 'name_desc' ? 'selected' : '' }}>Name (Z - A)</option>
                            <option value="stock_desc" {{ ($currentSort ?? '') === 'stock_desc' ? 'selected' : '' }}>Current Stock (High - Low)</option>
                            <option value="stock_asc" {{ ($currentSort ?? '') === 'stock_asc' ? 'selected' : '' }}>Current Stock (Low - High)</option>
                            <option value="category_asc" {{ ($currentSort ?? '') === 'category_asc' ? 'selected' : '' }}>Category (A - Z)</option>
                        </select>
                    </div>
                </div>

                <div class="mt-3 flex items-center justify-between pt-3 border-t border-slate-100">
                    <span class="text-xs text-slate-400">Refine the table by selecting one or more parameters.</span>
                    <div class="flex items-center gap-2">
                        @if($hasActiveFilters || request('search'))
                            <a href="{{ route('admin.inventory.index') }}" class="px-3 py-1.5 text-xs font-semibold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-lg transition">
                                Clear Filters
                            </a>
                        @endif
                        <button type="submit" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs rounded-lg transition shadow-2xs">
                            Apply Filters
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Main Scannable Table Container -->
        <div class="overflow-x-auto">
            <table class="w-full table-auto text-left min-w-[850px]">
                <thead class="bg-slate-50/80 border-b border-slate-100">
                    <tr>
                        <th class="px-5 py-3.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider">ITEM</th>
                        <th class="px-5 py-3.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider">CATEGORY</th>
                        <th class="px-5 py-3.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">CURRENT</th>
                        <th class="px-5 py-3.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">RESERVED</th>
                        <th class="px-5 py-3.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">AVAILABLE</th>
                        <th class="px-5 py-3.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">MINIMUM</th>
                        <th class="px-5 py-3.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">STATUS</th>
                        <th class="px-5 py-3.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">ACTION</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($inventoryItems as $item)
                        <tr class="hover:bg-slate-50/70 transition">
                            <!-- ITEM Column -->
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 flex-shrink-0 bg-slate-100 rounded-xl overflow-hidden flex items-center justify-center border border-slate-200">
                                        @if($item->image_path)
                                            <img src="{{ Storage::url($item->image_path) }}" alt="{{ $item->name }}" class="h-full w-full object-cover">
                                        @else
                                            <div class="w-full h-full bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-xs select-none">
                                                {{ strtoupper(substr($item->name, 0, 2)) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-slate-900 truncate" title="{{ $item->name }}">{{ $item->name }}</p>
                                        <p class="text-xs font-mono text-slate-500">{{ $item->item_code ?? ('INV-' . str_pad($item->id, 4, '0', STR_PAD_LEFT)) }}</p>
                                        <p class="text-xs text-slate-400 mt-0.5 flex items-center gap-1.5">
                                            <span>{{ $item->unit }}</span>
                                            <span>&middot;</span>
                                            <span class="{{ $item->is_perishable ? 'text-rose-500 font-medium' : 'text-slate-500' }}">
                                                {{ $item->is_perishable ? 'Perishable' : 'Non-perishable' }}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <!-- CATEGORY Column -->
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200/60">
                                    {{ $item->category }}
                                </span>
                            </td>

                            <!-- CURRENT Column -->
                            <td class="px-5 py-3.5 text-right font-semibold text-slate-700 text-sm">
                                {{ (float) $item->current_stock }}
                            </td>

                            <!-- RESERVED Column -->
                            <td class="px-5 py-3.5 text-right text-sm">
                                @if($item->reserved_stock > 0)
                                    <span class="text-amber-600 font-bold">{{ (float) $item->reserved_stock }}</span>
                                @else
                                    <span class="text-slate-400">0</span>
                                @endif
                            </td>

                            <!-- AVAILABLE Column -->
                            <td class="px-5 py-3.5 text-right text-sm font-bold {{ $item->net_available < 0 ? 'text-rose-600' : 'text-slate-900' }}">
                                {{ (float) $item->net_available }}
                            </td>

                            <!-- MINIMUM Column -->
                            <td class="px-5 py-3.5 text-right text-sm text-slate-500 font-medium">
                                {{ (float) $item->min_stock }}
                            </td>

                            <!-- STATUS Column -->
                            <td class="px-5 py-3.5 text-center">
                                @if($item->warning_level === 'shortage')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        Shortage
                                    </span>
                                @elseif($item->warning_level === 'low')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Low Stock
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        In Stock
                                    </span>
                                @endif
                            </td>

                            <!-- ACTION Column -->
                            <td class="px-5 py-3.5 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <!-- View Button: Opens slide-over drawer -->
                                    <button
                                        type="button"
                                        onclick="openItemDrawer({{ $item->id }})"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 hover:text-emerald-800 border border-emerald-200 text-xs font-semibold rounded-lg transition cursor-pointer"
                                        title="View details for {{ $item->name }}"
                                    >
                                        <i class="fa-regular fa-eye text-xs"></i>
                                        <span>View</span>
                                    </button>

                                    <!-- Edit Link -->
                                    <a
                                        href="{{ route('admin.inventory.edit', $item) }}"
                                        class="p-1.5 text-slate-400 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition"
                                        title="Edit Item"
                                    >
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </a>

                                    <!-- Archive Action -->
                                    <form
                                        action="{{ route('admin.inventory.archive', $item) }}"
                                        method="POST"
                                        class="inline-block"
                                        onsubmit="return confirm('Archive {{ addslashes($item->name) }}?\n\nThis item will be removed from active inventory while its historical records are preserved.');"
                                    >
                                        @csrf
                                        <button
                                            type="submit"
                                            class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition cursor-pointer"
                                            title="Archive Item"
                                        >
                                            <i class="fa-solid fa-box-archive text-xs"></i>
                                        </button>
                                    </form>
                                </div>

                                <!-- Slide-over Drawer Template for Item -->
                                <template id="item-drawer-template-{{ $item->id }}">
                                    <div class="h-full flex flex-col justify-between">
                                        <!-- DRAWER HEADER -->
                                        <div class="p-6 border-b border-slate-100 bg-white sticky top-0 z-10">
                                            <div class="flex items-start justify-between gap-4">
                                                <div class="flex items-center gap-3.5 min-w-0">
                                                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 border border-emerald-100 flex items-center justify-center font-bold text-emerald-700 text-base shrink-0 overflow-hidden shadow-2xs">
                                                        @if($item->image_path)
                                                            <img src="{{ Storage::url($item->image_path) }}" alt="{{ $item->name }}" class="h-full w-full object-cover">
                                                        @else
                                                            {{ strtoupper(substr($item->name, 0, 2)) }}
                                                        @endif
                                                    </div>
                                                    <div class="min-w-0">
                                                        <h2 class="text-lg font-bold text-slate-900 truncate" title="{{ $item->name }}">
                                                            {{ $item->name }}
                                                        </h2>
                                                        <div class="flex items-center gap-2 mt-0.5">
                                                            <span class="text-xs font-mono text-slate-500">{{ $item->item_code ?? ('INV-' . str_pad($item->id, 4, '0', STR_PAD_LEFT)) }}</span>
                                                            <span class="inline-flex items-center px-2 py-0.2 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Active</span>
                                                            <span class="inline-flex items-center px-2 py-0.2 rounded-full text-[10px] font-medium {{ $item->is_perishable ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                                                {{ $item->is_perishable ? 'Perishable' : 'Non-perishable' }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="flex items-center gap-2 shrink-0">
                                                    <a href="{{ route('admin.inventory.edit', $item) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-700 hover:text-slate-900 border border-slate-200 text-xs font-semibold rounded-xl transition">
                                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                                        <span>Edit</span>
                                                    </a>
                                                    <button type="button" onclick="closeItemDrawer()" class="p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition" aria-label="Close drawer">
                                                        <i class="fa-solid fa-xmark text-lg"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Drawer Navigation Tabs -->
                                            <div class="flex items-center gap-2 mt-5 border-b border-slate-100 pb-px">
                                                <button
                                                    type="button"
                                                    onclick="switchDrawerTab('overview-{{ $item->id }}')"
                                                    id="tab-btn-overview-{{ $item->id }}"
                                                    class="drawer-tab-btn active px-3.5 py-2 text-xs font-bold border-b-2 border-emerald-600 text-emerald-700 transition"
                                                >
                                                    Overview
                                                </button>
                                                <button
                                                    type="button"
                                                    onclick="switchDrawerTab('history-{{ $item->id }}')"
                                                    id="tab-btn-history-{{ $item->id }}"
                                                    class="drawer-tab-btn px-3.5 py-2 text-xs font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-800 transition"
                                                >
                                                    Stock History
                                                    <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-600">{{ $item->inventoryTransactions->count() }}</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    onclick="switchDrawerTab('packages-{{ $item->id }}')"
                                                    id="tab-btn-packages-{{ $item->id }}"
                                                    class="drawer-tab-btn px-3.5 py-2 text-xs font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-800 transition"
                                                >
                                                    Package Usage
                                                    <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-600">{{ $item->packages->count() }}</span>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- DRAWER BODY CONTENT -->
                                        <div class="flex-1 overflow-y-auto p-6 space-y-6">
                                            <!-- TAB 1: OVERVIEW -->
                                            <div id="drawer-tab-overview-{{ $item->id }}" class="drawer-tab-pane space-y-6">
                                                <!-- Stock Summary Grid -->
                                                <div>
                                                    <div class="flex items-center justify-between mb-3">
                                                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">STOCK SUMMARY</h3>
                                                        <div>
                                                            @if($item->warning_level === 'shortage')
                                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                                                    Shortage
                                                                </span>
                                                            @elseif($item->warning_level === 'low')
                                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                                                    Low Stock
                                                                </span>
                                                            @else
                                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                                    In Stock
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                                        <div class="bg-slate-50 border border-slate-100 rounded-xl p-3.5 text-center">
                                                            <p class="text-[10px] font-bold text-slate-400 uppercase">CURRENT</p>
                                                            <p class="text-xl font-extrabold text-slate-800 mt-1">{{ (float) $item->current_stock }}</p>
                                                            <p class="text-[10px] text-slate-500 mt-0.5">Physical stock</p>
                                                        </div>
                                                        <div class="bg-slate-50 border border-slate-100 rounded-xl p-3.5 text-center">
                                                            <p class="text-[10px] font-bold text-slate-400 uppercase">RESERVED</p>
                                                            <p class="text-xl font-extrabold text-amber-600 mt-1">{{ (float) $item->reserved_stock }}</p>
                                                            <p class="text-[10px] text-slate-500 mt-0.5">For bookings</p>
                                                        </div>
                                                        <div class="bg-slate-50 border border-slate-100 rounded-xl p-3.5 text-center">
                                                            <p class="text-[10px] font-bold text-slate-400 uppercase">AVAILABLE</p>
                                                            <p class="text-xl font-extrabold {{ $item->net_available < 0 ? 'text-rose-600' : 'text-slate-900' }} mt-1">{{ (float) $item->net_available }}</p>
                                                            <p class="text-[10px] text-slate-500 mt-0.5">Remaining</p>
                                                        </div>
                                                        <div class="bg-slate-50 border border-slate-100 rounded-xl p-3.5 text-center">
                                                            <p class="text-[10px] font-bold text-slate-400 uppercase">MINIMUM</p>
                                                            <p class="text-xl font-extrabold text-slate-700 mt-1">{{ (float) $item->min_stock }}</p>
                                                            <p class="text-[10px] text-slate-500 mt-0.5">Alert limit</p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Stock Explanation Guide -->
                                                <div class="bg-slate-50/80 rounded-xl border border-slate-200/80 p-4 space-y-2 text-xs">
                                                    <p class="font-bold text-slate-700 flex items-center gap-1.5 uppercase tracking-wider text-[10px]">
                                                        <i class="fa-solid fa-circle-info text-emerald-600"></i> STOCK DEFINITIONS
                                                    </p>
                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-slate-600 pt-1">
                                                        <div>
                                                            <span class="font-semibold text-slate-800">Current:</span> Physical stock currently recorded in inventory.
                                                        </div>
                                                        <div>
                                                            <span class="font-semibold text-slate-800">Reserved:</span> Quantity committed to valid bookings.
                                                        </div>
                                                        <div>
                                                            <span class="font-semibold text-slate-800">Available:</span> Stock remaining after valid reservations.
                                                        </div>
                                                        <div>
                                                            <span class="font-semibold text-slate-800">Minimum:</span> Configured minimum stock threshold.
                                                        </div>
                                                    </div>
                                                    @if($item->net_available < 0)
                                                        <div class="mt-2 pt-2 border-t border-rose-100 text-rose-700 font-semibold flex items-center gap-1.5">
                                                            <i class="fa-solid fa-triangle-exclamation"></i>
                                                            <span>Available quantity is below the required level.</span>
                                                        </div>
                                                    @endif
                                                </div>

                                                <!-- Item Information -->
                                                <div>
                                                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">ITEM INFORMATION</h3>
                                                    <div class="bg-white rounded-xl border border-slate-100 divide-y divide-slate-100 text-xs">
                                                        <div class="py-2.5 px-3.5 flex justify-between">
                                                            <span class="text-slate-500 font-medium">Item Name</span>
                                                            <span class="font-bold text-slate-800">{{ $item->name }}</span>
                                                        </div>
                                                        <div class="py-2.5 px-3.5 flex justify-between">
                                                            <span class="text-slate-500 font-medium">Item Code</span>
                                                            <span class="font-mono font-semibold text-slate-700">{{ $item->item_code ?? '—' }}</span>
                                                        </div>
                                                        <div class="py-2.5 px-3.5 flex justify-between">
                                                            <span class="text-slate-500 font-medium">Category</span>
                                                            <span class="font-semibold text-slate-700">{{ $item->category ?? '—' }}</span>
                                                        </div>
                                                        <div class="py-2.5 px-3.5 flex justify-between">
                                                            <span class="text-slate-500 font-medium">Unit</span>
                                                            <span class="font-semibold text-slate-700">{{ $item->unit ?? '—' }}</span>
                                                        </div>
                                                        <div class="py-2.5 px-3.5 flex justify-between">
                                                            <span class="text-slate-500 font-medium">Unit Cost</span>
                                                            <span class="font-bold text-slate-900">₱{{ number_format((float) $item->unit_cost, 2) }}</span>
                                                        </div>
                                                        <div class="py-2.5 px-3.5 flex justify-between">
                                                            <span class="text-slate-500 font-medium">Perishable</span>
                                                            <span class="font-semibold {{ $item->is_perishable ? 'text-rose-600' : 'text-slate-700' }}">
                                                                {{ $item->is_perishable ? 'Yes' : 'No' }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Quick Actions -->
                                                <div>
                                                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">QUICK ACTIONS</h3>
                                                    <div class="grid grid-cols-2 gap-2.5">
                                                        <a href="{{ route('admin.inventory.edit', $item) }}" class="flex items-center justify-center gap-2 p-3 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 hover:text-slate-900 transition">
                                                            <i class="fa-solid fa-pen-to-square text-purple-600"></i>
                                                            <span>Edit Item / Adjust Stock</span>
                                                        </a>
                                                        <form
                                                            action="{{ route('admin.inventory.archive', $item) }}"
                                                            method="POST"
                                                            onsubmit="return confirm('Archive {{ addslashes($item->name) }}?\n\nThis item will be removed from active inventory while its historical records are preserved.');"
                                                        >
                                                            @csrf
                                                            <button type="submit" class="w-full flex items-center justify-center gap-2 p-3 bg-rose-50/60 hover:bg-rose-100/70 border border-rose-200 rounded-xl text-xs font-semibold text-rose-700 transition cursor-pointer">
                                                                <i class="fa-solid fa-box-archive text-rose-600"></i>
                                                                <span>Archive Item</span>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>

                                                <!-- Recent Activity Summary -->
                                                <div>
                                                    <div class="flex items-center justify-between mb-3">
                                                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">RECENT ACTIVITY</h3>
                                                        <button
                                                            type="button"
                                                            onclick="switchDrawerTab('history-{{ $item->id }}')"
                                                            class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 inline-flex items-center gap-1 cursor-pointer"
                                                        >
                                                            <span>View Stock History</span>
                                                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                                        </button>
                                                    </div>

                                                    @php
                                                        $recentTxs = $item->inventoryTransactions->take(3);
                                                    @endphp

                                                    @if($recentTxs->isNotEmpty())
                                                        <div class="space-y-2.5">
                                                            @foreach($recentTxs as $tx)
                                                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 text-xs flex items-start justify-between gap-3">
                                                                    <div class="min-w-0">
                                                                        <div class="flex items-center gap-2">
                                                                            <span class="font-bold text-slate-800 capitalize">{{ str_replace('_', ' ', $tx->transaction_type) }}</span>
                                                                            @if($tx->booking_id)
                                                                                <span class="text-[10px] font-mono bg-purple-50 text-purple-700 px-1.5 py-0.2 rounded border border-purple-100">
                                                                                    Booking #{{ $tx->booking_id }}
                                                                                </span>
                                                                            @endif
                                                                        </div>
                                                                        <p class="text-slate-500 text-[11px] mt-0.5 truncate">{{ $tx->reason ?? 'Ledger update' }}</p>
                                                                        <p class="text-slate-400 text-[10px] mt-0.5">{{ $tx->created_at?->format('M d, Y h:i A') }} &middot; By {{ $tx->performedByUser?->name ?? 'System' }}</p>
                                                                    </div>
                                                                    <div class="shrink-0 text-right">
                                                                        <span class="font-extrabold text-sm {{ (float) $tx->quantity_change > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                                                            {{ (float) $tx->quantity_change > 0 ? '+' : '' }}{{ (float) $tx->quantity_change }}
                                                                        </span>
                                                                        <span class="text-[10px] text-slate-400 block">{{ $item->unit }}</span>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <div class="p-4 bg-slate-50 rounded-xl border border-dashed border-slate-200 text-center text-xs text-slate-400">
                                                            No recent transactions recorded for this item.
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- TAB 2: STOCK HISTORY -->
                                            <div id="drawer-tab-history-{{ $item->id }}" class="drawer-tab-pane hidden space-y-4">
                                                <div class="flex items-center justify-between">
                                                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">TRANSACTION LEDGER</h3>
                                                    <span class="text-xs text-slate-400 font-medium">Sorted newest first</span>
                                                </div>

                                                @if($item->inventoryTransactions->isNotEmpty())
                                                    <div class="divide-y divide-slate-100 border border-slate-100 rounded-xl overflow-hidden bg-white shadow-2xs">
                                                        @foreach($item->inventoryTransactions as $tx)
                                                            <div class="p-3.5 hover:bg-slate-50/60 transition text-xs flex items-start justify-between gap-3">
                                                                <div class="min-w-0">
                                                                    <div class="flex items-center gap-2">
                                                                        <span class="font-bold text-slate-800 capitalize">{{ str_replace('_', ' ', $tx->transaction_type) }}</span>
                                                                        @if($tx->booking_id)
                                                                            <span class="text-[10px] font-mono bg-purple-50 text-purple-700 px-1.5 py-0.2 rounded border border-purple-100">
                                                                                Booking #{{ $tx->booking_id }}
                                                                            </span>
                                                                        @endif
                                                                    </div>
                                                                    @if($tx->reason)
                                                                        <p class="text-slate-600 text-xs mt-1">{{ $tx->reason }}</p>
                                                                    @endif
                                                                    <div class="flex items-center gap-2 text-[10px] text-slate-400 mt-1">
                                                                        <span><i class="fa-regular fa-clock mr-1"></i>{{ $tx->created_at?->format('M d, Y h:i A') }}</span>
                                                                        <span>&middot;</span>
                                                                        <span><i class="fa-regular fa-user mr-1"></i>{{ $tx->performedByUser?->name ?? 'System' }}</span>
                                                                    </div>
                                                                </div>
                                                                <div class="shrink-0 text-right">
                                                                    <span class="font-extrabold text-sm {{ (float) $tx->quantity_change > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                                                        {{ (float) $tx->quantity_change > 0 ? '+' : '' }}{{ (float) $tx->quantity_change }}
                                                                    </span>
                                                                    <span class="text-[10px] text-slate-400 block">{{ $item->unit }}</span>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <div class="p-8 bg-slate-50 rounded-2xl border border-dashed border-slate-200 text-center">
                                                        <i class="fa-solid fa-clock-rotate-left text-2xl text-slate-300 mb-2"></i>
                                                        <p class="text-xs font-semibold text-slate-600">No stock history recorded</p>
                                                        <p class="text-[11px] text-slate-400 mt-0.5">Transactions will appear here when stock is adjusted, locked for bookings, or returned.</p>
                                                    </div>
                                                @endif
                                            </div>

                                            <!-- TAB 3: PACKAGE USAGE -->
                                            <div id="drawer-tab-packages-{{ $item->id }}" class="drawer-tab-pane hidden space-y-4">
                                                <div class="flex items-center justify-between">
                                                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">PACKAGE BOM USAGE</h3>
                                                    <span class="text-xs text-slate-400 font-medium">BOM relationships</span>
                                                </div>

                                                @if($item->packages->isNotEmpty())
                                                    <div class="space-y-3">
                                                        @foreach($item->packages as $pkg)
                                                            <div class="p-4 bg-white border border-slate-200 rounded-xl shadow-2xs hover:border-emerald-300 transition flex items-start justify-between gap-3">
                                                                <div class="min-w-0">
                                                                    <p class="text-sm font-bold text-slate-900 truncate">{{ $pkg->title }}</p>
                                                                    <div class="flex items-center gap-2 text-xs text-slate-500 mt-1">
                                                                        <span class="font-mono text-[11px] bg-slate-100 px-2 py-0.5 rounded">{{ $pkg->package_code ?? ('PKG-' . $pkg->id) }}</span>
                                                                        <span>&middot;</span>
                                                                        <span>{{ $pkg->category ?? 'Standard' }}</span>
                                                                    </div>
                                                                </div>
                                                                <div class="text-right shrink-0">
                                                                    <span class="text-xs font-semibold text-slate-400 block">Required BOM:</span>
                                                                    <span class="text-sm font-extrabold text-emerald-700">
                                                                        {{ (float) $pkg->pivot->quantity }} {{ $item->unit }}
                                                                    </span>
                                                                    <div class="mt-2">
                                                                        <a href="{{ route('admin.packages.edit', $pkg) }}" class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600 hover:text-emerald-700">
                                                                            <span>View Package</span>
                                                                            <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <div class="p-8 bg-slate-50 rounded-2xl border border-dashed border-slate-200 text-center">
                                                        <i class="fa-solid fa-box-archive text-2xl text-slate-300 mb-2"></i>
                                                        <p class="text-xs font-semibold text-slate-600">No package usage</p>
                                                        <p class="text-[11px] text-slate-400 mt-0.5">This item is not currently linked to any package bill of materials (BOM).</p>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- DRAWER FOOTER -->
                                        <div class="p-4 border-t border-slate-100 bg-slate-50/70 flex items-center justify-between">
                                            <span class="text-xs text-slate-400 font-mono">ID #{{ $item->id }}</span>
                                            <button
                                                type="button"
                                                onclick="closeItemDrawer()"
                                                class="px-4 py-2 border border-slate-300 text-xs font-semibold rounded-xl text-slate-700 bg-white hover:bg-slate-50 transition shadow-2xs cursor-pointer"
                                            >
                                                Close
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="h-12 w-12 text-slate-200 mb-3">
                                        <i class="fa-solid fa-box-open text-4xl"></i>
                                    </div>
                                    <p class="text-slate-500 font-medium text-sm">No inventory items found.</p>
                                    @if(request('search') || $hasActiveFilters)
                                        <a href="{{ route('admin.inventory.index') }}" class="text-emerald-600 hover:text-emerald-700 mt-2 text-xs font-semibold">
                                            Clear all filters and search
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        <div class="px-6 py-4 border-t border-slate-100 bg-white flex flex-wrap items-center justify-between gap-3">
            @if($inventoryItems->hasPages())
                <div class="w-full sm:w-auto">
                    {{ $inventoryItems->links() }}
                </div>
            @else
                <p class="text-xs text-slate-500 leading-5">
                    Showing
                    @if($inventoryItems->count() > 0)
                        <span class="font-semibold text-slate-700">1</span>
                        to
                        <span class="font-semibold text-slate-700">{{ $inventoryItems->count() }}</span>
                    @else
                        <span class="font-semibold text-slate-700">0</span>
                    @endif
                    of
                    <span class="font-semibold text-slate-700">{{ $inventoryItems->total() }}</span>
                    items
                </p>
            @endif
        </div>
    </div>

    <!-- Slide-over Drawer Host Container -->
    <div
        id="itemDrawerContainer"
        class="fixed inset-0 z-50 overflow-hidden hidden"
        aria-labelledby="itemDrawerTitle"
        role="dialog"
        aria-modal="true"
    >
        <!-- Backdrop -->
        <div
            id="itemDrawerBackdrop"
            onclick="closeItemDrawer()"
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity opacity-0 duration-300"
        ></div>

        <!-- Sliding Panel -->
        <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
            <div
                id="itemDrawerPanel"
                class="w-screen max-w-xl bg-white shadow-2xl flex flex-col transform translate-x-full transition-transform duration-300 ease-in-out"
            >
                <div id="itemDrawerContent" class="h-full flex flex-col justify-between overflow-y-auto">
                    <!-- Populated dynamically from template -->
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Inventory CSV Modal -->
    <div id="uploadCsvModal" style="display:none;" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="uploadCsvModalTitle">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" aria-hidden="true" onclick="closeUploadModal()"></div>

        <!-- Modal Dialog -->
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 transform transition-all border border-slate-200">
            <!-- Header -->
            <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600">
                        <i class="fa-solid fa-file-arrow-up text-lg" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h3 id="uploadCsvModalTitle" class="text-lg font-bold text-gray-900">Upload Inventory CSV</h3>
                        <p class="text-xs text-gray-500">Import new items or update existing records in bulk</p>
                    </div>
                </div>
                <button type="button" onclick="closeUploadModal()" class="rounded-lg p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition cursor-pointer" aria-label="Close modal">
                    <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>
                </button>
            </div>

            <!-- Upload Form -->
            <form action="{{ route('admin.inventory.import') }}" method="POST" enctype="multipart/form-data" id="uploadCsvForm" class="mt-5 space-y-4">
                @csrf

                <!-- File Input -->
                <div>
                    <label for="csv_file" class="block text-sm font-semibold text-gray-700 mb-1">
                        Select CSV File <span class="text-red-500">*</span>
                    </label>
                    <input type="file" name="csv_file" id="csv_file" accept=".csv,text/csv" required
                           class="block w-full text-sm text-gray-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 file:cursor-pointer border border-gray-300 rounded-lg p-1.5 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                </div>

                <!-- Format Guidance Box -->
                <div class="rounded-xl bg-slate-50 border border-slate-200 p-4 space-y-2">
                    <div class="flex items-center gap-2 text-xs font-bold text-slate-700 uppercase tracking-wider">
                        <i class="fa-solid fa-circle-info text-emerald-600" aria-hidden="true"></i>
                        <span>CSV Columns</span>
                    </div>
                    <p class="text-xs font-mono text-slate-600 bg-white p-2 rounded border border-slate-200 break-all select-all">
                        name, category, is_perishable, current_stock, unit_cost, min_stock, unit
                    </p>
                    <ul class="text-xs text-slate-500 space-y-1 list-disc pl-4">
                        <li><strong>Safe Upsert:</strong> Matches on exact name. Existing items will be updated; new items created.</li>
                        <li><strong>Transactional:</strong> The import is atomic. If any row is invalid, all changes roll back.</li>
                        <li><strong>Duplicate protection:</strong> Duplicate item names inside the file will be rejected.</li>
                    </ul>
                </div>

                <!-- Template quick link -->
                <div class="flex items-center justify-between text-xs text-slate-500 pt-1">
                    <span>Need the template?</span>
                    <a href="{{ route('admin.inventory.template') }}" class="text-emerald-600 hover:text-emerald-700 font-semibold inline-flex items-center gap-1">
                        <i class="fa-solid fa-download" aria-hidden="true"></i> Download CSV Template
                    </a>
                </div>

                <!-- Buttons -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" onclick="closeUploadModal()" class="px-4 py-2 border border-gray-300 text-sm font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-300 transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" id="submitUploadBtn" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition inline-flex items-center gap-2 cursor-pointer">
                        <i class="fa-solid fa-upload" aria-hidden="true"></i> Upload CSV
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- CSV Import Instructions Modal -->
    <div id="importInstructionsModal" style="display:none;" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="importInstructionsModalTitle">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" aria-hidden="true" onclick="closeInstructionsModal()"></div>

        <!-- Modal Dialog -->
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-xl p-6 sm:p-7 transform transition-all border border-slate-200">
            <!-- Header -->
            <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 shrink-0">
                        <i class="fa-solid fa-circle-info text-lg" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h3 id="importInstructionsModalTitle" class="text-lg font-bold text-gray-900">CSV Import Instructions</h3>
                        <p class="text-xs text-gray-500">Format specifications, column definitions, and upload guidelines</p>
                    </div>
                </div>
                <button type="button" onclick="closeInstructionsModal()" class="rounded-lg p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition cursor-pointer" aria-label="Close modal">
                    <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>
                </button>
            </div>

            <!-- Content -->
            <div class="mt-5 space-y-4 text-sm text-gray-600 max-h-[70vh] overflow-y-auto pr-1">
                <!-- Header Format Box -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1.5">Required CSV Header</label>
                    <div class="p-3 bg-gray-50 rounded-xl border border-gray-200 font-mono text-xs text-emerald-800 break-all select-all font-semibold">
                        name,category,is_perishable,current_stock,unit_cost,min_stock,unit
                    </div>
                </div>

                <!-- Required Columns List -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">Required Columns</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                        <div class="p-2.5 rounded-lg border border-gray-100 bg-gray-50/60">
                            <span class="font-bold text-gray-900 font-mono">name</span>
                            <p class="text-gray-500 mt-0.5">Item name (matched exactly for updates)</p>
                        </div>
                        <div class="p-2.5 rounded-lg border border-gray-100 bg-gray-50/60">
                            <span class="font-bold text-gray-900 font-mono">category</span>
                            <p class="text-gray-500 mt-0.5">Category name (e.g., Flowers, Decor)</p>
                        </div>
                        <div class="p-2.5 rounded-lg border border-gray-100 bg-gray-50/60">
                            <span class="font-bold text-gray-900 font-mono">is_perishable</span>
                            <p class="text-gray-500 mt-0.5">1 / yes (perishable) or 0 / no (non-perishable)</p>
                        </div>
                        <div class="p-2.5 rounded-lg border border-gray-100 bg-gray-50/60">
                            <span class="font-bold text-gray-900 font-mono">current_stock</span>
                            <p class="text-gray-500 mt-0.5">Current on-hand quantity (non-negative number)</p>
                        </div>
                        <div class="p-2.5 rounded-lg border border-gray-100 bg-gray-50/60">
                            <span class="font-bold text-gray-900 font-mono">unit_cost</span>
                            <p class="text-gray-500 mt-0.5">Cost per unit (non-negative currency value)</p>
                        </div>
                        <div class="p-2.5 rounded-lg border border-gray-100 bg-gray-50/60">
                            <span class="font-bold text-gray-900 font-mono">min_stock</span>
                            <p class="text-gray-500 mt-0.5">Low-stock alert threshold (non-negative)</p>
                        </div>
                        <div class="p-2.5 rounded-lg border border-gray-100 bg-gray-50/60 sm:col-span-2">
                            <span class="font-bold text-gray-900 font-mono">unit</span>
                            <p class="text-gray-500 mt-0.5">Standard unit of measure (e.g., pcs, stems, rolls, bundles, meters)</p>
                        </div>
                    </div>
                </div>

                <!-- Guidance Points -->
                <div class="p-3.5 bg-emerald-50/60 rounded-xl border border-emerald-100 text-xs text-gray-700 space-y-1.5">
                    <p class="font-bold text-emerald-900 flex items-center gap-1.5">
                        <i class="fa-solid fa-shield-halved text-emerald-600"></i> Import Rules & Guidance
                    </p>
                    <ul class="list-disc pl-4 space-y-1 text-gray-600">
                        <li><strong>Existing items are matched by name</strong> and updated with the new details.</li>
                        <li><strong>New items are created automatically</strong> if the name does not match any existing record.</li>
                        <li><strong>Current stock changes are recorded</strong> through the authoritative inventory adjustment ledger.</li>
                        <li><strong>Upload must use CSV format (.csv)</strong> with UTF-8 encoding.</li>
                        <li><strong>Atomic transactions:</strong> If any row contains invalid data, all rows are rolled back to protect system integrity.</li>
                    </ul>
                </div>
            </div>

            <!-- Footer / Action Buttons -->
            <div class="mt-6 flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-gray-100">
                <a href="{{ route('admin.inventory.template') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-xl transition">
                    <i class="fa-solid fa-download"></i> Download CSV Template
                </a>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="closeInstructionsModal()" class="px-4 py-2 border border-gray-300 text-xs font-semibold rounded-xl text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-300 transition cursor-pointer">
                        Close
                    </button>
                    <button type="button" onclick="closeInstructionsModal(); openUploadModal();" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl shadow-xs focus:outline-none focus:ring-2 focus:ring-emerald-500 transition inline-flex items-center gap-1.5 cursor-pointer">
                        <i class="fa-solid fa-upload"></i> Upload CSV Now
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- UI Scripts -->
    <script>
        // Collapsible Filter Panel Toggle
        function toggleFilterPanel() {
            const panel = document.getElementById('filterPanel');
            const btn = document.getElementById('filterToggleBtn');
            const text = document.getElementById('filterToggleText');
            const chevron = document.getElementById('filterToggleChevron');
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

        // Slide-over Drawer
        function openItemDrawer(itemId) {
            const template = document.getElementById('item-drawer-template-' + itemId);
            const container = document.getElementById('itemDrawerContainer');
            const backdrop = document.getElementById('itemDrawerBackdrop');
            const panel = document.getElementById('itemDrawerPanel');
            const content = document.getElementById('itemDrawerContent');

            if (!template || !container || !panel || !content) return;

            content.innerHTML = template.innerHTML;
            container.classList.remove('hidden');

            requestAnimationFrame(() => {
                backdrop.classList.remove('opacity-0');
                backdrop.classList.add('opacity-100');
                panel.classList.remove('translate-x-full');
                panel.classList.add('translate-x-0');
            });

            document.body.style.overflow = 'hidden';
        }

        function closeItemDrawer() {
            const container = document.getElementById('itemDrawerContainer');
            const backdrop = document.getElementById('itemDrawerBackdrop');
            const panel = document.getElementById('itemDrawerPanel');

            if (!container || !panel) return;

            backdrop.classList.remove('opacity-100');
            backdrop.classList.add('opacity-0');
            panel.classList.remove('translate-x-0');
            panel.classList.add('translate-x-full');

            setTimeout(() => {
                container.classList.add('hidden');
                document.body.style.overflow = '';
            }, 300);
        }

        // Drawer Tabs Switcher
        function switchDrawerTab(tabTarget) {
            const content = document.getElementById('itemDrawerContent');
            if (!content) return;

            const targetPane = content.querySelector('#drawer-tab-' + tabTarget);
            if (!targetPane) return;

            // Hide all panes
            content.querySelectorAll('.drawer-tab-pane').forEach(p => p.classList.add('hidden'));
            targetPane.classList.remove('hidden');

            // Deactivate all tab buttons
            content.querySelectorAll('.drawer-tab-btn').forEach(btn => {
                btn.classList.remove('border-emerald-600', 'text-emerald-700', 'active');
                btn.classList.add('border-transparent', 'text-slate-500');
            });

            // Activate target button
            const activeBtn = content.querySelector('#tab-btn-' + tabTarget);
            if (activeBtn) {
                activeBtn.classList.remove('border-transparent', 'text-slate-500');
                activeBtn.classList.add('border-emerald-600', 'text-emerald-700', 'active');
            }
        }

        // CSV Tools Popover Menu
        function toggleCsvDropdown() {
            const menu = document.getElementById('csvToolsMenu');
            const btn = document.getElementById('csvToolsButton');
            if (!menu || !btn) return;

            const isExpanded = btn.getAttribute('aria-expanded') === 'true';
            if (isExpanded) {
                closeCsvDropdown();
            } else {
                openCsvDropdown();
            }
        }

        function openCsvDropdown() {
            const menu = document.getElementById('csvToolsMenu');
            const btn = document.getElementById('csvToolsButton');
            const chevron = document.getElementById('csvToolsChevron');
            if (!menu || !btn) return;

            menu.style.display = 'block';
            btn.setAttribute('aria-expanded', 'true');
            if (chevron) chevron.classList.add('rotate-180');
        }

        function closeCsvDropdown() {
            const menu = document.getElementById('csvToolsMenu');
            const btn = document.getElementById('csvToolsButton');
            const chevron = document.getElementById('csvToolsChevron');
            if (!menu || !btn) return;

            menu.style.display = 'none';
            btn.setAttribute('aria-expanded', 'false');
            if (chevron) chevron.classList.remove('rotate-180');
        }

        function openUploadModal() {
            closeCsvDropdown();
            const modal = document.getElementById('uploadCsvModal');
            if (modal) {
                modal.style.display = 'flex';
                const fileInput = document.getElementById('csv_file');
                if (fileInput) fileInput.focus();
            }
        }

        function closeUploadModal() {
            const modal = document.getElementById('uploadCsvModal');
            if (modal) modal.style.display = 'none';
        }

        function openInstructionsModal() {
            closeCsvDropdown();
            const modal = document.getElementById('importInstructionsModal');
            if (modal) modal.style.display = 'flex';
        }

        function closeInstructionsModal() {
            const modal = document.getElementById('importInstructionsModal');
            if (modal) modal.style.display = 'none';
        }

        // Click outside listener for CSV Tools popover
        document.addEventListener('click', function (e) {
            const container = document.getElementById('csvToolsContainer');
            if (container && !container.contains(e.target)) {
                closeCsvDropdown();
            }
        });

        // Keyboard navigation (Escape key closes modals and drawers)
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                const instructionsModal = document.getElementById('importInstructionsModal');
                const uploadModal = document.getElementById('uploadCsvModal');
                const csvMenu = document.getElementById('csvToolsMenu');
                const csvBtn = document.getElementById('csvToolsButton');
                const drawerContainer = document.getElementById('itemDrawerContainer');

                if (instructionsModal && instructionsModal.style.display === 'flex') {
                    closeInstructionsModal();
                } else if (uploadModal && uploadModal.style.display === 'flex') {
                    closeUploadModal();
                } else if (csvMenu && csvMenu.style.display === 'block') {
                    closeCsvDropdown();
                    if (csvBtn) csvBtn.focus();
                } else if (drawerContainer && !drawerContainer.classList.contains('hidden')) {
                    closeItemDrawer();
                }
            }
        });
    </script>
</x-admin-layout>
