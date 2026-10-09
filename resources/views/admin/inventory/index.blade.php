<x-admin-layout title="Inventory Management">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 font-serif">Inventory Management</h2>
            <p class="text-sm text-gray-500 mt-1">Manage your materials, decor, and equipment inventory for all floral events.</p>
        </div>
        <div>
            <a href="{{ route('admin.inventory.archived') }}" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 transition">
                <i class="fa-solid fa-box-archive mr-2"></i> Archived Items
            </a>
        </div>
    </div>
    <!-- Success/Error Alerts -->
    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Status / KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <!-- Total Items -->
        <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-5 flex items-center justify-between hover:shadow-sm transition">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Total Items</p>
                <p class="text-2xl sm:text-3xl font-black text-gray-900">{{ $totalItemsCount }}</p>
                <p class="text-xs text-gray-400 mt-1">All inventory items</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-boxes-stacked text-xl"></i>
            </div>
        </div>

        <!-- In Stock -->
        <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-5 flex items-center justify-between hover:shadow-sm transition">
            <div class="flex-1 pr-3">
                <div class="flex items-center justify-between mb-1">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">In Stock</p>
                    @if($totalItemsCount > 0)
                        <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-100">
                            {{ number_format(($inStockCount / $totalItemsCount) * 100, 1) }}%
                        </span>
                    @endif
                </div>
                <p class="text-2xl sm:text-3xl font-black text-gray-900">{{ $inStockCount }}</p>
                <p class="text-xs text-gray-400 mt-1">Items with sufficient stock</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-check-circle text-xl"></i>
            </div>
        </div>

        <!-- Low Stock -->
        <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-5 flex items-center justify-between hover:shadow-sm transition">
            <div class="flex-1 pr-3">
                <div class="flex items-center justify-between mb-1">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Low Stock</p>
                    @if($totalItemsCount > 0)
                        <span class="text-[11px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-100">
                            {{ number_format(($lowStockCount / $totalItemsCount) * 100, 1) }}%
                        </span>
                    @endif
                </div>
                <p class="text-2xl sm:text-3xl font-black text-gray-900">{{ $lowStockCount }}</p>
                <p class="text-xs text-gray-400 mt-1">Items below minimum</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-triangle-exclamation text-xl"></i>
            </div>
        </div>

        <!-- Shortage -->
        <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-5 flex items-center justify-between hover:shadow-sm transition">
            <div class="flex-1 pr-3">
                <div class="flex items-center justify-between mb-1">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Shortage</p>
                    @if($totalItemsCount > 0)
                        <span class="text-[11px] font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded-full border border-rose-100">
                            {{ number_format(($shortageCount / $totalItemsCount) * 100, 1) }}%
                        </span>
                    @endif
                </div>
                <p class="text-2xl sm:text-3xl font-black text-gray-900">{{ $shortageCount }}</p>
                <p class="text-xs text-gray-400 mt-1">Items out of stock</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-circle-exclamation text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-xs border border-gray-100 overflow-visible mb-8">
        <!-- Header & Filters Toolbar -->
        <div class="p-4 sm:p-5 border-b border-gray-100">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <!-- Left: Search and Filters Form -->
                <form method="GET" action="{{ route('admin.inventory.index') }}" class="flex flex-wrap items-center gap-3 flex-1">
                    <!-- Search -->
                    <div class="relative flex-1 min-w-[220px] max-w-sm">
                        <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-gray-400">
                            <i class="fa-solid fa-search text-xs"></i>
                        </span>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search item name, code, or category..." class="w-full pl-9 pr-3.5 py-2 bg-gray-50/70 border border-gray-200 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition shadow-2xs">
                    </div>
                    
                    <!-- Category -->
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-gray-400">
                            <i class="fa-solid fa-shapes text-xs"></i>
                        </span>
                        <select name="category" onchange="this.form.submit()" style="padding-left: 2.35rem; padding-right: 2rem;" class="py-2 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-700 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 shadow-2xs appearance-none transition cursor-pointer">
                            <option value="all" {{ $currentCategory === 'all' ? 'selected' : '' }}>All Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}" {{ $currentCategory === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                        <span class="absolute inset-y-0 right-2.5 flex items-center pointer-events-none text-gray-400">
                            <i class="fa-solid fa-chevron-down text-[10px]"></i>
                        </span>
                    </div>
                    
                    <!-- Status -->
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-gray-400">
                            <i class="fa-regular fa-clock text-xs"></i>
                        </span>
                        <select name="status" onchange="this.form.submit()" style="padding-left: 2.35rem; padding-right: 2rem;" class="py-2 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-700 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 shadow-2xs appearance-none transition cursor-pointer">
                            <option value="all" {{ $currentStatus === 'all' ? 'selected' : '' }}>All Statuses</option>
                            <option value="in_stock" {{ $currentStatus === 'in_stock' ? 'selected' : '' }}>In Stock</option>
                            <option value="low" {{ $currentStatus === 'low' ? 'selected' : '' }}>Low Stock</option>
                            <option value="shortage" {{ $currentStatus === 'shortage' ? 'selected' : '' }}>Shortage</option>
                        </select>
                        <span class="absolute inset-y-0 right-2.5 flex items-center pointer-events-none text-gray-400">
                            <i class="fa-solid fa-chevron-down text-[10px]"></i>
                        </span>
                    </div>
                    
                    <!-- Perishable -->
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-gray-400">
                            <i class="fa-solid fa-leaf text-xs"></i>
                        </span>
                        <select name="perishable" onchange="this.form.submit()" style="padding-left: 2.35rem; padding-right: 2rem;" class="py-2 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-700 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 shadow-2xs appearance-none transition cursor-pointer">
                            <option value="all" {{ $currentPerishable === 'all' ? 'selected' : '' }}>All Perishable</option>
                            <option value="yes" {{ $currentPerishable === 'yes' ? 'selected' : '' }}>Perishable</option>
                            <option value="no" {{ $currentPerishable === 'no' ? 'selected' : '' }}>Non-perishable</option>
                        </select>
                        <span class="absolute inset-y-0 right-2.5 flex items-center pointer-events-none text-gray-400">
                            <i class="fa-solid fa-chevron-down text-[10px]"></i>
                        </span>
                    </div>
                    
                    <!-- Clear -->
                    @if(request('search') || $currentCategory !== 'all' || $currentStatus !== 'all' || $currentPerishable !== 'all')
                        <a href="{{ route('admin.inventory.index') }}" class="px-3 py-2 text-xs font-semibold text-gray-500 bg-gray-100 hover:bg-gray-200 rounded-xl transition">Clear</a>
                    @endif
                </form>

                <!-- Right: Action Controls (CSV Tools & Add Item) -->
                <div class="flex items-center gap-2.5 shrink-0 self-end lg:self-center">
                    <!-- CSV Tools Dropdown Container -->
                    <div class="relative" id="csvToolsContainer">
                        <button type="button" id="csvToolsButton" onclick="toggleCsvDropdown()" aria-haspopup="true" aria-expanded="false" aria-controls="csvToolsMenu" class="inline-flex items-center gap-2 px-3.5 py-2 border border-emerald-600/70 hover:border-emerald-600 bg-white hover:bg-emerald-50/50 text-emerald-700 font-semibold text-sm rounded-xl transition shadow-2xs focus:outline-none focus:ring-2 focus:ring-emerald-500 cursor-pointer">
                            <i class="fa-regular fa-file-lines text-emerald-600 text-sm"></i>
                            <span>CSV Tools</span>
                            <i id="csvToolsChevron" class="fa-solid fa-chevron-down text-[10px] text-emerald-600 transition-transform duration-200"></i>
                        </button>

                        <!-- Popover Dropdown Menu -->
                        <div id="csvToolsMenu" style="display:none; width: 540px; max-width: calc(100vw - 2rem);" role="region" aria-labelledby="csvToolsButton" class="absolute right-0 top-full mt-2.5 z-50 bg-white rounded-2xl shadow-xl border border-gray-100 p-4 sm:p-5 text-left transform transition-all">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 1rem;" class="gap-4">
                                <!-- Column 1: EXPORT (DOWNLOAD) -->
                                <div>
                                    <p class="text-[11px] font-bold tracking-wider text-gray-400 uppercase mb-2.5">EXPORT (DOWNLOAD)</p>
                                    <div class="space-y-2">
                                        <a href="{{ route('admin.inventory.export') }}" onclick="closeCsvDropdown()" class="group flex items-start gap-3 p-2.5 rounded-xl hover:bg-emerald-50/50 border border-transparent hover:border-emerald-100 transition">
                                            <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 group-hover:bg-emerald-100 transition">
                                                <i class="fa-solid fa-file-arrow-down text-base"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-bold text-gray-900 group-hover:text-emerald-700 transition">Download Inventory CSV</p>
                                                <p class="text-xs text-gray-500 mt-0.5">Export all inventory items</p>
                                            </div>
                                        </a>
                                        <a href="{{ route('admin.inventory.template') }}" onclick="closeCsvDropdown()" class="group flex items-start gap-3 p-2.5 rounded-xl hover:bg-gray-50 border border-transparent hover:border-gray-200 transition">
                                            <div class="w-10 h-10 rounded-lg bg-gray-100 text-gray-600 flex items-center justify-center shrink-0 group-hover:bg-gray-200 transition">
                                                <i class="fa-regular fa-file-lines text-base"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-bold text-gray-900 group-hover:text-emerald-700 transition">Download Template</p>
                                                <p class="text-xs text-gray-500 mt-0.5">Get a blank CSV template</p>
                                            </div>
                                        </a>
                                    </div>
                                </div>

                                <!-- Column 2: IMPORT (UPLOAD) -->
                                <div>
                                    <p class="text-[11px] font-bold tracking-wider text-gray-400 uppercase mb-2.5">IMPORT (UPLOAD)</p>
                                    <div class="space-y-2">
                                        <button type="button" onclick="closeCsvDropdown(); openUploadModal();" class="w-full text-left group flex items-start gap-3 p-2.5 rounded-xl bg-emerald-50/70 border border-emerald-100 hover:bg-emerald-100/70 transition cursor-pointer">
                                            <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                                <i class="fa-solid fa-file-arrow-up text-base"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-bold text-emerald-950">Import Inventory CSV</p>
                                                <p class="text-xs text-emerald-700 mt-0.5">Upload and import inventory items</p>
                                            </div>
                                        </button>
                                        <button type="button" onclick="closeCsvDropdown(); openInstructionsModal();" class="w-full text-left group flex items-start gap-3 p-2.5 rounded-xl hover:bg-gray-50 border border-transparent hover:border-gray-200 transition cursor-pointer">
                                            <div class="w-10 h-10 rounded-lg bg-gray-100 text-gray-600 flex items-center justify-center shrink-0 group-hover:bg-gray-200 transition">
                                                <i class="fa-solid fa-circle-info text-base"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-bold text-gray-900 group-hover:text-emerald-700 transition">Import Instructions</p>
                                                <p class="text-xs text-gray-500 mt-0.5">View CSV format and guidelines</p>
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Add Item Button -->
                    <a href="{{ route('admin.inventory.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm rounded-xl shadow-xs hover:shadow transition focus:outline-none focus:ring-2 focus:ring-emerald-500 whitespace-nowrap">
                        <i class="fa-solid fa-plus text-xs"></i>
                        <span>Add Item</span>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full table-auto text-left min-w-[800px]">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-center w-12">#</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Item</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Current</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Reserved</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Available</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Minimum</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-center">Status</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($inventoryItems as $item)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 text-sm text-gray-500 text-center font-medium">
                                {{ $loop->iteration }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 flex-shrink-0 bg-gray-100 rounded overflow-hidden flex items-center justify-center">
                                        @if($item->image_path)
                                            <img src="{{ Storage::url($item->image_path) }}" alt="{{ $item->name }}" class="h-full w-full object-cover">
                                        @else
                                            <i class="fa-solid fa-box-open text-gray-400"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="text-base font-bold text-gray-900">{{ $item->name }}</p>
                                        <p class="text-sm text-gray-500">{{ $item->item_code }}</p>
                                        <p class="text-sm text-gray-400 mt-0.5">
                                            {{ $item->unit }} &middot; <span class="{{ $item->is_perishable ? 'text-rose-500' : '' }}">{{ $item->is_perishable ? 'Perishable' : 'Non-perishable' }}</span>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-base font-medium text-gray-700">
                                {{ $item->category }}
                            </td>
                            <td class="px-6 py-4 text-lg font-semibold text-gray-700 text-right">
                                {{ (float) $item->current_stock }}
                            </td>
                            <td class="px-6 py-4 text-lg text-right">
                                @if($item->reserved_stock > 0)
                                    <span class="text-amber-600 font-semibold">{{ (float) $item->reserved_stock }}</span>
                                @else
                                    <span class="text-gray-400">0</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-xl font-black text-right {{ $item->net_available < 0 ? 'text-red-600' : 'text-gray-900' }}">
                                {{ (float) $item->net_available }}
                            </td>
                            <td class="px-6 py-4 text-lg font-medium text-gray-500 text-right">
                                {{ (float) $item->min_stock }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($item->warning_level === 'shortage')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200">
                                        Shortage
                                    </span>
                                @elseif($item->warning_level === 'low')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 border border-amber-200">
                                        Low Stock
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        In Stock
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <a href="{{ route('admin.inventory.edit', $item) }}" class="inline-flex items-center justify-center w-8 h-8 rounded text-gray-400 hover:text-purple-600 hover:bg-purple-50 transition" title="Edit Item">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <form action="{{ route('admin.inventory.archive', $item) }}" method="POST" class="inline-block" onsubmit="return confirm('Archive {{ addslashes($item->name) }}?\n\nThis item will be removed from active inventory while its historical records are preserved.');">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center justify-center w-8 h-8 rounded text-gray-400 hover:text-red-600 hover:bg-red-50 transition" title="Archive Item">
                                        <i class="fa-solid fa-box-archive"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="h-12 w-12 text-gray-200 mb-3"><i class="fa-solid fa-box-open text-4xl"></i></div>
                                    <p class="text-gray-500 font-medium">No inventory items found.</p>
                                    @if(request('search') || $currentCategory !== 'all')
                                        <a href="{{ route('admin.inventory.index') }}" class="text-purple-600 hover:text-purple-700 mt-2 text-sm">Clear filters</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200">
            @if($inventoryItems->hasPages())
                {{ $inventoryItems->links() }}
            @else
                <p class="text-sm text-gray-700 leading-5">
                    Showing
                    @if($inventoryItems->count() > 0)
                        <span class="font-medium">1</span>
                        to
                        <span class="font-medium">{{ $inventoryItems->count() }}</span>
                    @else
                        <span class="font-medium">0</span>
                    @endif
                    of
                    <span class="font-medium">{{ $inventoryItems->total() }}</span>
                    items
                </p>
            @endif
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
                <button type="button" onclick="closeUploadModal()" class="rounded-lg p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition" aria-label="Close modal">
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
                    <button type="button" onclick="closeUploadModal()" class="px-4 py-2 border border-gray-300 text-sm font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-300 transition">
                        Cancel
                    </button>
                    <button type="submit" id="submitUploadBtn" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition inline-flex items-center gap-2">
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
                <button type="button" onclick="closeInstructionsModal()" class="rounded-lg p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition" aria-label="Close modal">
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
                    <button type="button" onclick="closeInstructionsModal()" class="px-4 py-2 border border-gray-300 text-xs font-semibold rounded-xl text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-300 transition">
                        Close
                    </button>
                    <button type="button" onclick="closeInstructionsModal(); openUploadModal();" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl shadow-xs focus:outline-none focus:ring-2 focus:ring-emerald-500 transition inline-flex items-center gap-1.5">
                        <i class="fa-solid fa-upload"></i> Upload CSV Now
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
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
            if (chevron) {
                chevron.classList.add('rotate-180');
            }
        }

        function closeCsvDropdown() {
            const menu = document.getElementById('csvToolsMenu');
            const btn = document.getElementById('csvToolsButton');
            const chevron = document.getElementById('csvToolsChevron');
            if (!menu || !btn) return;

            menu.style.display = 'none';
            btn.setAttribute('aria-expanded', 'false');
            if (chevron) {
                chevron.classList.remove('rotate-180');
            }
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
            if (modal) {
                modal.style.display = 'none';
            }
        }

        function openInstructionsModal() {
            closeCsvDropdown();
            const modal = document.getElementById('importInstructionsModal');
            if (modal) {
                modal.style.display = 'flex';
            }
        }

        function closeInstructionsModal() {
            const modal = document.getElementById('importInstructionsModal');
            if (modal) {
                modal.style.display = 'none';
            }
        }

        // Click outside listener for CSV Tools popover
        document.addEventListener('click', function (e) {
            const container = document.getElementById('csvToolsContainer');
            if (container && !container.contains(e.target)) {
                closeCsvDropdown();
            }
        });

        // Keyboard navigation (Escape key closes modals and popover)
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                const instructionsModal = document.getElementById('importInstructionsModal');
                const uploadModal = document.getElementById('uploadCsvModal');
                const csvMenu = document.getElementById('csvToolsMenu');
                const csvBtn = document.getElementById('csvToolsButton');

                if (instructionsModal && instructionsModal.style.display === 'flex') {
                    closeInstructionsModal();
                } else if (uploadModal && uploadModal.style.display === 'flex') {
                    closeUploadModal();
                } else if (csvMenu && csvMenu.style.display === 'block') {
                    closeCsvDropdown();
                    if (csvBtn) csvBtn.focus();
                }
            }
        });
    </script>
</x-admin-layout>
