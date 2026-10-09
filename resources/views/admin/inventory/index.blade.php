<x-admin-layout title="Inventory Management">
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Inventory Management</h2>
                <p class="text-sm text-gray-500">Manage your materials, decor, and equipment inventory.</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
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

    <!-- Status Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Items -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-1">Total Items</p>
                <p class="text-3xl font-bold text-gray-800">{{ $totalItemsCount }}</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-purple-50 flex items-center justify-center text-purple-600">
                <i class="fa-solid fa-boxes-stacked text-xl"></i>
            </div>
        </div>

        <!-- In Stock -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-emerald-600 uppercase tracking-wider mb-1">In Stock</p>
                <p class="text-3xl font-bold text-gray-800">{{ $inStockCount }}</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600">
                <i class="fa-solid fa-check-circle text-xl"></i>
            </div>
        </div>

        <!-- Low Stock -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-amber-600 uppercase tracking-wider mb-1">Low Stock</p>
                <p class="text-3xl font-bold text-gray-800">{{ $lowStockCount }}</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-amber-50 flex items-center justify-center text-amber-600">
                <i class="fa-solid fa-triangle-exclamation text-xl"></i>
            </div>
        </div>

        <!-- Shortage -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-red-600 uppercase tracking-wider mb-1">Shortage</p>
                <p class="text-3xl font-bold text-gray-800">{{ $shortageCount }}</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center text-red-600">
                <i class="fa-solid fa-circle-exclamation text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-8">
        <!-- Header & Filters -->
        <div class="p-6 border-b border-gray-200">
            <form method="GET" action="{{ route('admin.inventory.index') }}" class="flex flex-wrap lg:flex-nowrap items-center gap-3 w-full">
                <!-- Search -->
                <div class="relative w-full lg:flex-1 min-w-[200px]">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                        <i class="fa-solid fa-search text-sm"></i>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search item name or code..." class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition shadow-sm">
                </div>
                
                <!-- Category -->
                <select name="category" onchange="this.form.submit()" class="w-full sm:w-auto border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 bg-white shadow-sm transition">
                    <option value="all" {{ $currentCategory === 'all' ? 'selected' : '' }}>All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ $currentCategory === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
                
                <!-- Status -->
                <select name="status" onchange="this.form.submit()" class="w-full sm:w-auto border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 bg-white shadow-sm transition">
                    <option value="all" {{ $currentStatus === 'all' ? 'selected' : '' }}>All Statuses</option>
                    <option value="in_stock" {{ $currentStatus === 'in_stock' ? 'selected' : '' }}>In Stock</option>
                    <option value="low" {{ $currentStatus === 'low' ? 'selected' : '' }}>Low Stock</option>
                    <option value="shortage" {{ $currentStatus === 'shortage' ? 'selected' : '' }}>Shortage</option>
                </select>
                
                <!-- Perishable -->
                <select name="perishable" onchange="this.form.submit()" class="w-full sm:w-auto border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 bg-white shadow-sm transition">
                    <option value="all" {{ $currentPerishable === 'all' ? 'selected' : '' }}>All Perishable</option>
                    <option value="yes" {{ $currentPerishable === 'yes' ? 'selected' : '' }}>Perishable</option>
                    <option value="no" {{ $currentPerishable === 'no' ? 'selected' : '' }}>Non-perishable</option>
                </select>
                
                <!-- Clear -->
                @if(request('search') || $currentCategory !== 'all' || $currentStatus !== 'all' || $currentPerishable !== 'all')
                    <a href="{{ route('admin.inventory.index') }}" class="w-full sm:w-auto px-4 py-2 text-sm font-medium text-gray-500 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-gray-700 transition text-center whitespace-nowrap">Clear</a>
                @endif
                
                <!-- Add Item -->
                <a href="{{ route('admin.inventory.create') }}" class="btn-primary w-full sm:w-auto whitespace-nowrap text-center">
                    <i class="fa-solid fa-plus mr-1" aria-hidden="true"></i> Add Item
                </a>
            </form>
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
</x-admin-layout>
