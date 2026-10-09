<x-admin-layout title="Packages">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-gray-800 font-serif">Packages</h2>
            <p class="text-sm text-gray-500 mt-1">Manage public booking packages, pricing, and master inventory mappings (BOM).</p>
        </div>
        <div>
            <a href="{{ route('admin.packages.archived') }}" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 transition">
                <i class="fa-solid fa-box-archive mr-2"></i> Archived Packages
            </a>
        </div>
    </div>

    <!-- Tabs -->
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <a href="{{ route('admin.packages.index') }}" class="border-purple-500 text-purple-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Active Packages
            </a>
            <a href="{{ route('admin.packages.archived') }}" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Archived Packages
            </a>
        </nav>
    </div>

    <!-- Search & Filter Toolbar -->
    <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-4 sm:p-5 mb-6 overflow-visible">
        <form method="GET" action="{{ route('admin.packages.index') }}" id="packageFilterForm">
            <input type="hidden" name="filter_expanded" id="packageFilterExpandedInput" value="{{ request('filter_expanded', '0') }}">

            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <!-- Left: Search input, Show Filters toggle, Search button -->
                <div class="flex flex-wrap items-center gap-2.5 sm:gap-3 flex-1">
                    <!-- Keyword Search Input -->
                    <div class="relative flex-1 min-w-[200px] sm:min-w-[260px] max-w-sm">
                        <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-gray-400">
                            <i class="fa-solid fa-magnifying-glass text-xs"></i>
                        </span>
                        <input
                            type="text"
                            name="search"
                            id="packageSearchInput"
                            value="{{ $currentSearch ?? request('search') }}"
                            placeholder="Search package name, category, or keyword..."
                            class="w-full pl-9 pr-3.5 py-2 bg-gray-50/70 border border-gray-200 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition shadow-2xs"
                        >
                    </div>

                    @php
                        $hasActivePackageFilters = (($currentCategory ?? 'all') !== 'all') || (($currentSort ?? 'latest') !== 'latest');
                        $isPackageFilterOpen = request('filter_expanded') === '1';
                    @endphp

                    <!-- Show Filters Button -->
                    <button
                        type="button"
                        id="packageToggleFiltersBtn"
                        onclick="togglePackageFilterPanel()"
                        aria-expanded="{{ $isPackageFilterOpen ? 'true' : 'false' }}"
                        aria-controls="packageFilterPanel"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 border rounded-xl text-sm font-medium transition shadow-2xs focus:outline-none focus:ring-2 focus:ring-purple-500 cursor-pointer {{ $hasActivePackageFilters ? 'border-purple-300 bg-purple-50 text-purple-700 font-semibold' : 'border-gray-200 hover:border-purple-300 bg-white hover:bg-purple-50/50 text-gray-700 hover:text-purple-700' }}"
                    >
                        <i class="fa-solid fa-sliders text-xs {{ $hasActivePackageFilters ? 'text-purple-600' : 'text-gray-500' }}"></i>
                        <span id="packageToggleFiltersText">{{ $isPackageFilterOpen ? 'Hide Filters' : 'Show Filters' }}</span>
                        @if($hasActivePackageFilters)
                            <span class="w-1.5 h-1.5 rounded-full bg-purple-600 inline-block" title="Filters are active"></span>
                        @endif
                        <i id="packageFiltersChevron" class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200 {{ $isPackageFilterOpen ? 'rotate-180' : '' }}"></i>
                    </button>

                    <!-- Search Submit Button -->
                    <button
                        type="submit"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold rounded-xl transition shadow-2xs focus:outline-none focus:ring-2 focus:ring-purple-500 cursor-pointer"
                    >
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                        <span>Search</span>
                    </button>

                    @if(!empty($currentSearch))
                        <a
                            href="{{ route('admin.packages.index') }}"
                            class="px-3 py-2 text-xs font-semibold text-gray-500 hover:text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition"
                        >
                            Clear
                        </a>
                    @endif
                </div>

                <!-- Right: Action Controls (Package Tools & Add Package) -->
                <div class="flex items-center gap-2.5 shrink-0 self-end lg:self-center">
                    <!-- Package Tools Dropdown Container -->
                    <div class="relative" id="packageToolsContainer">
                        <button type="button" id="packageToolsButton" onclick="togglePackageToolsDropdown()" aria-haspopup="true" aria-expanded="false" aria-controls="packageToolsMenu" class="inline-flex items-center gap-2 px-3.5 py-2 border border-purple-300 hover:border-purple-600 bg-white hover:bg-purple-50/50 text-purple-700 font-semibold text-sm rounded-xl transition shadow-2xs focus:outline-none focus:ring-2 focus:ring-purple-500 cursor-pointer">
                            <i class="fa-regular fa-file-lines text-purple-600 text-sm"></i>
                            <span>Package Tools</span>
                            <i id="packageToolsChevron" class="fa-solid fa-chevron-down text-[10px] text-purple-600 transition-transform duration-200"></i>
                        </button>

                        <!-- Popover Dropdown Menu -->
                        <div id="packageToolsMenu" style="display:none; width: 660px; max-width: calc(100vw - 2rem);" role="region" aria-labelledby="packageToolsButton" class="absolute right-0 top-full mt-2.5 z-50 bg-white rounded-2xl shadow-xl border border-gray-100 p-4 sm:p-5 text-left transform transition-all">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem;" class="gap-4">
                                <!-- Column 1: EXPORT -->
                                <div>
                                    <p class="text-[11px] font-bold tracking-wider text-gray-400 uppercase mb-2.5">EXPORT</p>
                                    <div class="space-y-2">
                                        <a href="{{ route('admin.packages.export.packages') }}" onclick="closePackageToolsDropdown()" class="group flex items-start gap-3 p-2.5 rounded-xl hover:bg-purple-50/50 border border-transparent hover:border-purple-100 transition">
                                            <div class="w-9 h-9 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center shrink-0 group-hover:bg-purple-100 transition">
                                                <i class="fa-solid fa-file-arrow-down text-sm"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-bold text-gray-900 group-hover:text-purple-700 transition">Export Packages</p>
                                                <p class="text-xs text-gray-500 mt-0.5">Package master CSV</p>
                                            </div>
                                        </a>
                                        <a href="{{ route('admin.packages.export.materials') }}" onclick="closePackageToolsDropdown()" class="group flex items-start gap-3 p-2.5 rounded-xl hover:bg-purple-50/50 border border-transparent hover:border-purple-100 transition">
                                            <div class="w-9 h-9 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center shrink-0 group-hover:bg-purple-100 transition">
                                                <i class="fa-solid fa-boxes-stacked text-sm"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-bold text-gray-900 group-hover:text-purple-700 transition">Export Materials</p>
                                                <p class="text-xs text-gray-500 mt-0.5">Package BOM CSV</p>
                                            </div>
                                        </a>
                                    </div>
                                </div>

                                <!-- Column 2: IMPORT -->
                                <div>
                                    <p class="text-[11px] font-bold tracking-wider text-gray-400 uppercase mb-2.5">IMPORT</p>
                                    <div class="space-y-2">
                                        <button type="button" onclick="closePackageToolsDropdown(); openImportPackagesModal();" class="w-full text-left group flex items-start gap-3 p-2.5 rounded-xl bg-purple-50/70 border border-purple-100 hover:bg-purple-100/70 transition cursor-pointer">
                                            <div class="w-9 h-9 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center shrink-0">
                                                <i class="fa-solid fa-file-arrow-up text-sm"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-bold text-purple-950">Import Packages</p>
                                                <p class="text-xs text-purple-700 mt-0.5">Upload package CSV</p>
                                            </div>
                                        </button>
                                        <button type="button" onclick="closePackageToolsDropdown(); openImportMaterialsModal();" class="w-full text-left group flex items-start gap-3 p-2.5 rounded-xl bg-purple-50/70 border border-purple-100 hover:bg-purple-100/70 transition cursor-pointer">
                                            <div class="w-9 h-9 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center shrink-0">
                                                <i class="fa-solid fa-layer-group text-sm"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-bold text-purple-950">Import Materials</p>
                                                <p class="text-xs text-purple-700 mt-0.5">Upload BOM CSV</p>
                                            </div>
                                        </button>
                                        <button type="button" onclick="closePackageToolsDropdown(); openPackageInstructionsModal();" class="w-full text-left group flex items-start gap-3 p-2.5 rounded-xl hover:bg-gray-50 border border-transparent hover:border-gray-200 transition cursor-pointer">
                                            <div class="w-9 h-9 rounded-lg bg-gray-100 text-gray-600 flex items-center justify-center shrink-0 group-hover:bg-gray-200 transition">
                                                <i class="fa-solid fa-circle-info text-sm"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-bold text-gray-900 group-hover:text-purple-700 transition">Import Instructions</p>
                                                <p class="text-xs text-gray-500 mt-0.5">View specifications</p>
                                            </div>
                                        </button>
                                    </div>
                                </div>

                                <!-- Column 3: TEMPLATES -->
                                <div>
                                    <p class="text-[11px] font-bold tracking-wider text-gray-400 uppercase mb-2.5">TEMPLATES</p>
                                    <div class="space-y-2">
                                        <a href="{{ route('admin.packages.template.packages') }}" onclick="closePackageToolsDropdown()" class="group flex items-start gap-3 p-2.5 rounded-xl hover:bg-gray-50 border border-transparent hover:border-gray-200 transition">
                                            <div class="w-9 h-9 rounded-lg bg-gray-100 text-gray-600 flex items-center justify-center shrink-0 group-hover:bg-gray-200 transition">
                                                <i class="fa-regular fa-file-lines text-sm"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-bold text-gray-900 group-hover:text-purple-700 transition">Package Template</p>
                                                <p class="text-xs text-gray-500 mt-0.5">Blank packages.csv</p>
                                            </div>
                                        </a>
                                        <a href="{{ route('admin.packages.template.materials') }}" onclick="closePackageToolsDropdown()" class="group flex items-start gap-3 p-2.5 rounded-xl hover:bg-gray-50 border border-transparent hover:border-gray-200 transition">
                                            <div class="w-9 h-9 rounded-lg bg-gray-100 text-gray-600 flex items-center justify-center shrink-0 group-hover:bg-gray-200 transition">
                                                <i class="fa-solid fa-table-list text-sm"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-bold text-gray-900 group-hover:text-purple-700 transition">Materials Template</p>
                                                <p class="text-xs text-gray-500 mt-0.5">Blank BOM CSV</p>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('admin.packages.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm rounded-xl shadow-xs hover:shadow transition focus:outline-none focus:ring-2 focus:ring-emerald-500 whitespace-nowrap">
                        <i class="fa-solid fa-plus text-xs"></i>
                        <span>Add Package</span>
                    </a>
                </div>
            </div>

            <!-- Collapsible Filter Panel (Category, Sort, Reset) -->
            <div
                id="packageFilterPanel"
                style="{{ $isPackageFilterOpen ? 'display: block;' : 'display: none;' }}"
                class="mt-4 pt-4 border-t border-gray-100"
            >
                <div class="bg-gray-50/80 p-3.5 sm:p-4 rounded-xl border border-gray-100 flex flex-wrap items-center gap-3 sm:gap-4">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-1.5 shrink-0">
                        <i class="fa-solid fa-filter text-purple-600 text-[11px]"></i> Filters & Sort:
                    </span>

                    <!-- Category Filter Dropdown -->
                    <div class="relative min-w-[170px]">
                        <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-gray-400">
                            <i class="fa-solid fa-shapes text-xs"></i>
                        </span>
                        <select
                            name="category"
                            id="packageCategoryFilter"
                            onchange="this.form.submit()"
                            style="padding-left: 2.35rem; padding-right: 2rem;"
                            class="w-full py-2 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-700 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-2xs appearance-none transition cursor-pointer"
                        >
                            <option value="all" {{ ($currentCategory ?? 'all') === 'all' ? 'selected' : '' }}>All Categories</option>
                            @foreach($packageCategories as $cat)
                                <option value="{{ $cat }}" {{ ($currentCategory ?? '') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                        <span class="absolute inset-y-0 right-2.5 flex items-center pointer-events-none text-gray-400">
                            <i class="fa-solid fa-chevron-down text-[10px]"></i>
                        </span>
                    </div>

                    <!-- Sort Dropdown -->
                    <div class="relative min-w-[170px]">
                        <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-gray-400">
                            <i class="fa-solid fa-arrow-down-wide-short text-xs"></i>
                        </span>
                        <select
                            name="sort"
                            id="packageSortFilter"
                            onchange="this.form.submit()"
                            style="padding-left: 2.35rem; padding-right: 2rem;"
                            class="w-full py-2 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-700 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-2xs appearance-none transition cursor-pointer"
                        >
                            <option value="latest" {{ ($currentSort ?? 'latest') === 'latest' ? 'selected' : '' }}>Latest First</option>
                            <option value="oldest" {{ ($currentSort ?? '') === 'oldest' ? 'selected' : '' }}>Oldest First</option>
                            <option value="name_asc" {{ ($currentSort ?? '') === 'name_asc' ? 'selected' : '' }}>Name (A-Z)</option>
                            <option value="name_desc" {{ ($currentSort ?? '') === 'name_desc' ? 'selected' : '' }}>Name (Z-A)</option>
                            <option value="price_asc" {{ ($currentSort ?? '') === 'price_asc' ? 'selected' : '' }}>Price (Low to High)</option>
                            <option value="price_desc" {{ ($currentSort ?? '') === 'price_desc' ? 'selected' : '' }}>Price (High to Low)</option>
                        </select>
                        <span class="absolute inset-y-0 right-2.5 flex items-center pointer-events-none text-gray-400">
                            <i class="fa-solid fa-chevron-down text-[10px]"></i>
                        </span>
                    </div>

                    <!-- Clear / Reset Link -->
                    <a
                        href="{{ route('admin.packages.index') }}"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-gray-600 hover:text-gray-900 bg-white hover:bg-gray-100 border border-gray-200 rounded-xl transition shadow-2xs sm:ml-auto"
                    >
                        <i class="fa-solid fa-rotate-left text-[11px] text-gray-400"></i>
                        <span>Reset Filters</span>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Package Grid -->
    @if($packages->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6 relative">
            @foreach($packages as $package)
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 group hover:shadow-xl hover:shadow-purple-900/5 transition-all duration-300 overflow-hidden flex flex-col relative">
                    @php
                        $coverImage = $package->images->first() ? asset('storage/' . $package->images->first()->image_path) : '';
                        $imageCount = $package->images->count();
                    @endphp
                    <div class="relative w-full overflow-hidden bg-slate-100" style="aspect-ratio: 3/2;" onclick="openLightbox('{{ $package->id }}')" role="button" tabindex="0">
                        @if($coverImage)
                            <img src="{{ $coverImage }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700 ease-in-out cursor-pointer" alt="{{ $package->title }}">
                            <div class="absolute bottom-2 right-2 bg-slate-900/75 backdrop-blur-md text-white text-[10px] font-bold px-2 py-1 rounded-md flex items-center gap-1.5 pointer-events-none">
                                <i class="fa-solid fa-camera"></i> {{ $imageCount }}
                            </div>
                        @else
                            <div class="w-full h-full bg-purple-50 flex items-center justify-center text-purple-300 group-hover:scale-110 transition-transform duration-700 ease-in-out cursor-pointer">
                                <i class="fa-solid fa-gift text-5xl"></i>
                            </div>
                        @endif
                        @if(!$package->is_active)
                            <div class="absolute top-2 right-2 bg-slate-900/75 backdrop-blur-md text-white text-[10px] font-bold px-2 py-1 rounded-md flex items-center pointer-events-none">
                                Inactive
                            </div>
                        @endif
                    </div>
                    <div class="p-4 sm:p-5 flex-1 flex flex-col justify-between">
                        <div class="text-center">
                            @if($package->category)
                                <div class="mb-1"><span class="text-[10px] font-bold tracking-widest uppercase text-purple-600">{{ $package->category }}</span></div>
                            @endif
                            <h3 class="font-bold text-[#1e293b] text-[15px] sm:text-base mb-1 line-clamp-1 group-hover:text-purple-700 transition-colors serif">{{ $package->title }}</h3>
                            <p class="text-lg sm:text-xl font-extrabold text-purple-700 mb-4">₱{{ number_format($package->price, 2) }}</p>
                            
                            @if($package->description)
                                <p class="text-xs text-slate-500 mb-3 line-clamp-2">{{ $package->description }}</p>
                            @endif
                        </div>
                        
                        <!-- Admin Actions -->
                        <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                            <button onclick='openViewModal(@json($package))' class="text-sm font-semibold text-purple-600 hover:text-purple-800 flex items-center gap-1.5 transition-colors" type="button">
                                <i class="fa-regular fa-eye"></i> View
                            </button>
                            <a href="{{ route('admin.packages.edit', $package->id) }}" class="text-sm font-semibold text-blue-500 hover:text-blue-700 flex items-center gap-1.5 transition-colors">
                                <i class="fa-solid fa-pen"></i> Edit
                            </a>
                            <form action="{{ route('admin.packages.archive', $package) }}" method="POST" id="archive-form-{{ $package->id }}" class="inline-block m-0">
                                @csrf
                                <button type="button" onclick="openConfirmModal('archive-form-{{ $package->id }}', 'Archive Package?', 'Archived packages will no longer be available for new bookings.', 'Archive Package', 'archive', this)" class="text-sm font-semibold text-orange-500 hover:text-orange-700 flex items-center gap-1.5 transition-colors">
                                    <i class="fa-solid fa-box-archive"></i> Archive
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        <div class="mt-8 flex justify-center">
        </div>
    @else
        <!-- Empty State -->
        <div class="text-center py-16 bg-white rounded-2xl shadow-sm border border-slate-100">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-50 mb-4">
                <i class="fa-solid fa-gift text-2xl text-slate-400"></i>
            </div>
            @if(!empty($currentSearch) || (($currentCategory ?? 'all') !== 'all'))
                <h3 class="text-lg font-medium text-slate-900">No matching packages found</h3>
                <p class="mt-1 text-sm text-slate-500 mb-6">Try adjusting your keyword search or category filter.</p>
                <a href="{{ route('admin.packages.index') }}" class="inline-flex items-center px-4 py-2 border border-purple-200 text-purple-700 bg-purple-50 hover:bg-purple-100 rounded-xl text-sm font-semibold transition">
                    Clear Filters
                </a>
            @else
                <h3 class="text-lg font-medium text-slate-900">No active packages</h3>
                <p class="mt-1 text-sm text-slate-500 mb-6">Get started by creating a new package.</p>
                <a href="{{ route('admin.packages.create') }}" class="inline-flex items-center px-4 py-2 border border-slate-200 shadow-sm text-sm font-medium rounded-md text-slate-700 bg-white hover:bg-slate-50 transition">
                    <i class="fa-solid fa-plus mr-2"></i> Add Package
                </a>
            @endif
        </div>
    @endif

    <!-- View Package Modal -->
    <div id="viewModal" class="fixed inset-0 z-[100] hidden flex-col items-center justify-center bg-slate-900/80 backdrop-blur-[2px] transition-opacity duration-300 p-4 sm:p-6" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Modal panel -->
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-3xl flex flex-col relative overflow-hidden my-auto mx-auto max-h-[90vh]">
            <button onclick="closeModal('viewModal')" class="absolute top-4 right-4 sm:top-5 sm:right-5 z-20 w-12 h-12 rounded-full flex items-center justify-center bg-white text-slate-800 hover:bg-slate-100 shadow-md transition-transform hover:scale-105 border border-slate-100">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
            
            <div class="px-6 sm:px-8 py-5 border-b border-gray-100 shrink-0 pr-20">
                <h3 class="text-2xl font-bold text-slate-900 serif">View Package</h3>
            </div>
            
            <div class="px-6 sm:px-8 py-6 space-y-6 overflow-y-auto flex-1 bg-slate-50/50">
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                    <h4 class="text-xs font-bold tracking-widest uppercase text-slate-400 mb-4 border-b border-slate-100 pb-2">1. Package Information</h4>
                    <div class="grid grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <span class="block text-xs font-medium text-slate-500 mb-1">Package Name</span>
                            <span id="view_title" class="block text-sm font-semibold text-slate-900"></span>
                        </div>
                        <div>
                            <span class="block text-xs font-medium text-slate-500 mb-1">Package Code</span>
                            <span id="view_package_code" class="block text-sm font-mono text-slate-900 bg-slate-100 px-2 py-0.5 rounded w-max"></span>
                        </div>
                        <div>
                            <span class="block text-xs font-medium text-slate-500 mb-1">Category</span>
                            <span id="view_category" class="block text-sm text-slate-700"></span>
                        </div>
                        <div>
                            <span class="block text-xs font-medium text-slate-500 mb-1">Price</span>
                            <span id="view_price" class="block text-sm font-semibold text-purple-700"></span>
                        </div>
                        <div class="col-span-2">
                            <span class="block text-xs font-medium text-slate-500 mb-1">Status</span>
                            <span id="view_status_badge" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"></span>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                    <h4 class="text-xs font-bold tracking-widest uppercase text-slate-400 mb-4 border-b border-slate-100 pb-2">2. Description</h4>
                    <p id="view_description" class="text-sm text-slate-700 whitespace-pre-wrap leading-relaxed"></p>
                </div>

                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                    <h4 class="text-xs font-bold tracking-widest uppercase text-slate-400 mb-4 border-b border-slate-100 pb-2">3. Included in the Package</h4>
                    <p id="view_included_items" class="text-sm text-slate-700 whitespace-pre-wrap leading-relaxed"></p>
                </div>

                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                    <h4 class="text-xs font-bold tracking-widest uppercase text-slate-400 mb-4 border-b border-slate-100 pb-2">4. Master Inventory Mapping</h4>
                    <div id="view_inventory_container" class="space-y-2">
                        <!-- Populated by JS -->
                    </div>
                </div>
            </div>
            
            <div class="px-6 sm:px-8 py-4 border-t border-gray-100 flex justify-end gap-3 bg-white shrink-0">
                <button type="button" onclick="closeModal('viewModal')" class="px-6 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition shadow-sm">Close</button>
            </div>
        </div>
    </div>

    <!-- Shared Lightbox JavaScript & Markup -->
    @php
        $lightboxData = $packages->map(function($p) {
            return [
                'id' => (string) $p->id,
                'title' => $p->title,
                'tags' => [$p->category],
                'images' => $p->images->pluck('image_path')->map(fn($path) => asset('storage/' . $path))->toArray()
            ];
        })->toArray();
    @endphp

    <div id="galleryLightbox" onclick="closeLightbox()" class="fixed inset-0 z-[100] hidden flex-col items-center justify-center bg-slate-900/80 backdrop-blur-[2px] opacity-0 transition-opacity duration-300 overflow-y-auto p-4 sm:p-6" style="padding: 1.5rem; justify-content: center; align-items: center;">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-3xl flex flex-col relative overflow-hidden my-auto mx-auto" onclick="event.stopPropagation()" style="border-radius: 20px; max-width: 768px; margin-left: auto; margin-right: auto; margin-top: auto; margin-bottom: auto; transform: translateZ(0);">
            <button onclick="closeLightbox()" class="absolute top-4 right-4 sm:top-5 sm:right-5 z-20 w-12 h-12 rounded-full flex items-center justify-center bg-white text-slate-800 hover:bg-slate-100 shadow-md transition-transform hover:scale-105 border border-slate-100" style="border-radius: 9999px; width: 44px; height: 44px;">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>

            <div class="relative w-full bg-slate-100 flex justify-center items-center" style="width: 100%; height: 470px;">
                <img id="lightboxMainImg" src="" class="w-full h-full object-contain" alt="Package Image" style="width: 100%; height: 100%; object-fit: contain;">
                
                <button onclick="prevImage(event)" id="lightboxPrevBtn" class="absolute left-3 sm:left-5 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full flex items-center justify-center bg-white text-slate-800 hover:bg-slate-50 shadow-md transition-transform hover:scale-105 z-10 hidden border border-slate-100" style="border-radius: 9999px; width: 44px; height: 44px; transform: translateY(-50%); top: 50%;">
                    <i class="fa-solid fa-chevron-left text-base"></i>
                </button>
                <button onclick="nextImage(event)" id="lightboxNextBtn" class="absolute right-3 sm:right-5 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full flex items-center justify-center bg-white text-slate-800 hover:bg-slate-50 shadow-md transition-transform hover:scale-105 z-10 hidden border border-slate-100" style="border-radius: 9999px; width: 44px; height: 44px; transform: translateY(-50%); top: 50%;">
                    <i class="fa-solid fa-chevron-right text-base"></i>
                </button>
            </div>

            <div class="flex flex-col p-6 sm:px-8 sm:pb-8 sm:pt-6 w-full" style="padding: 1.5rem 2rem 2rem 2rem; width: 100%;">
                <div class="text-center text-slate-800 font-medium text-sm sm:text-base mb-4">
                    <span id="lightboxCounter"></span>
                </div>
                <div class="flex justify-center w-full mb-6 sm:mb-8" style="justify-content: center; width: 100%;">
                    <div id="lightboxThumbnails" class="flex gap-2 sm:gap-3 overflow-x-auto snap-x max-w-full px-1 scrollbar-hide" style="scrollbar-width: none; gap: 0.75rem;">
                    </div>
                </div>
                <div class="w-full text-left" style="width: 100%; text-align: left;">
                    <h3 id="lightboxTitle" class="text-2xl sm:text-3xl font-bold text-slate-900 serif mb-2"></h3>
                    <div class="flex flex-wrap items-center gap-3 sm:gap-4 text-slate-600 text-sm sm:text-[15px] font-medium" style="display: flex; gap: 1rem;">
                        <div class="flex items-center gap-1.5" style="display: flex; gap: 0.375rem;">
                            <i class="fa-solid fa-tag text-slate-400"></i>
                            <span id="lightboxCategory"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Package Master CSV Import Modal -->
    <div id="importPackagesModal" style="display:none;" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="importPackagesModalTitle">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" aria-hidden="true" onclick="closeImportPackagesModal()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 sm:p-7 transform transition-all border border-slate-200">
            <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center text-purple-600 shrink-0">
                        <i class="fa-solid fa-file-arrow-up text-lg" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h3 id="importPackagesModalTitle" class="text-lg font-bold text-gray-900">Import Packages CSV</h3>
                        <p class="text-xs text-gray-500">Upload package master records to create or update packages</p>
                    </div>
                </div>
                <button type="button" onclick="closeImportPackagesModal()" class="rounded-lg p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-purple-500 transition" aria-label="Close modal">
                    <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>
                </button>
            </div>

            <form action="{{ route('admin.packages.import.packages') }}" method="POST" enctype="multipart/form-data" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label for="package_csv_file" class="block text-sm font-semibold text-gray-700 mb-1">
                        Select Packages CSV File <span class="text-red-500">*</span>
                    </label>
                    <input type="file" name="csv_file" id="package_csv_file" accept=".csv,text/csv" required
                           class="block w-full text-sm text-gray-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100 file:cursor-pointer border border-gray-300 rounded-lg p-1.5 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition">
                </div>

                <div class="rounded-xl bg-slate-50 border border-slate-200 p-4 space-y-2">
                    <div class="flex items-center gap-2 text-xs font-bold text-slate-700 uppercase tracking-wider">
                        <i class="fa-solid fa-circle-info text-purple-600" aria-hidden="true"></i>
                        <span>Expected Columns</span>
                    </div>
                    <p class="text-xs font-mono text-slate-600 bg-white p-2 rounded border border-slate-200 break-all select-all">
                        package_code, package_name, category, description, price, is_active, included_items
                    </p>
                    <ul class="text-xs text-slate-500 space-y-1 list-disc pl-4">
                        <li><strong>Safe Upsert:</strong> Matches existing package by <code>package_code</code>; creates new package if code is new or blank.</li>
                        <li><strong>Transactional:</strong> Import is atomic. If any row contains errors, all changes roll back.</li>
                        <li><strong>Inclusions:</strong> Multiple client-facing items can be separated by semicolons (<code>;</code>).</li>
                    </ul>
                </div>

                <div class="flex items-center justify-between text-xs text-slate-500 pt-1">
                    <span>Need the template?</span>
                    <a href="{{ route('admin.packages.template.packages') }}" class="text-purple-600 hover:text-purple-700 font-semibold inline-flex items-center gap-1">
                        <i class="fa-solid fa-download" aria-hidden="true"></i> Download Package Template
                    </a>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" onclick="closeImportPackagesModal()" class="px-4 py-2 border border-gray-300 text-sm font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-300 transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500 transition inline-flex items-center gap-2">
                        <i class="fa-solid fa-upload" aria-hidden="true"></i> Upload Packages
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Package Materials (BOM) CSV Import Modal -->
    <div id="importMaterialsModal" style="display:none;" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="importMaterialsModalTitle">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" aria-hidden="true" onclick="closeImportMaterialsModal()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 sm:p-7 transform transition-all border border-slate-200">
            <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center text-purple-600 shrink-0">
                        <i class="fa-solid fa-layer-group text-lg" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h3 id="importMaterialsModalTitle" class="text-lg font-bold text-gray-900">Import Materials CSV (BOM)</h3>
                        <p class="text-xs text-gray-500">Map packages to existing Inventory Management items</p>
                    </div>
                </div>
                <button type="button" onclick="closeImportMaterialsModal()" class="rounded-lg p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-purple-500 transition" aria-label="Close modal">
                    <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>
                </button>
            </div>

            <form action="{{ route('admin.packages.import.materials') }}" method="POST" enctype="multipart/form-data" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label for="materials_csv_file" class="block text-sm font-semibold text-gray-700 mb-1">
                        Select Materials CSV File <span class="text-red-500">*</span>
                    </label>
                    <input type="file" name="csv_file" id="materials_csv_file" accept=".csv,text/csv" required
                           class="block w-full text-sm text-gray-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100 file:cursor-pointer border border-gray-300 rounded-lg p-1.5 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition">
                </div>

                <div class="rounded-xl bg-slate-50 border border-slate-200 p-4 space-y-2">
                    <div class="flex items-center gap-2 text-xs font-bold text-slate-700 uppercase tracking-wider">
                        <i class="fa-solid fa-circle-info text-purple-600" aria-hidden="true"></i>
                        <span>Expected Columns</span>
                    </div>
                    <p class="text-xs font-mono text-slate-600 bg-white p-2 rounded border border-slate-200 break-all select-all">
                        package_code, item_code, quantity
                    </p>
                    <ul class="text-xs text-slate-500 space-y-1 list-disc pl-4">
                        <li><strong>Item Code:</strong> Must match an existing Inventory Item code (e.g. <code>FRE-0001</code>).</li>
                        <li><strong>Safe Definitions:</strong> Package BOM mapping does <em>not</em> deduct or reserve inventory stock.</li>
                        <li><strong>Archived Items:</strong> Archived inventory items cannot be newly attached to packages.</li>
                    </ul>
                </div>

                <div class="flex items-center justify-between text-xs text-slate-500 pt-1">
                    <span>Need the template?</span>
                    <a href="{{ route('admin.packages.template.materials') }}" class="text-purple-600 hover:text-purple-700 font-semibold inline-flex items-center gap-1">
                        <i class="fa-solid fa-download" aria-hidden="true"></i> Download Materials Template
                    </a>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" onclick="closeImportMaterialsModal()" class="px-4 py-2 border border-gray-300 text-sm font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-300 transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500 transition inline-flex items-center gap-2">
                        <i class="fa-solid fa-upload" aria-hidden="true"></i> Upload Materials
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Package CSV Instructions Modal -->
    <div id="packageInstructionsModal" style="display:none;" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="packageInstructionsModalTitle">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" aria-hidden="true" onclick="closePackageInstructionsModal()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-xl p-6 sm:p-7 transform transition-all border border-slate-200">
            <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center text-purple-600 shrink-0">
                        <i class="fa-solid fa-circle-info text-lg" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h3 id="packageInstructionsModalTitle" class="text-lg font-bold text-gray-900">Package CSV Instructions</h3>
                        <p class="text-xs text-gray-500">Master package details and Bill of Materials (BOM) guidelines</p>
                    </div>
                </div>
                <button type="button" onclick="closePackageInstructionsModal()" class="rounded-lg p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-purple-500 transition" aria-label="Close modal">
                    <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>
                </button>
            </div>

            <div class="mt-5 space-y-4 text-sm text-gray-600 max-h-[70vh] overflow-y-auto pr-1">
                <!-- Section 1: Package Master -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-purple-700 mb-1.5 flex items-center gap-1.5">
                        <i class="fa-solid fa-gift"></i> 1. Package Master CSV (packages.csv)
                    </h4>
                    <p class="text-xs text-gray-500 mb-2">Defines package identity, pricing, categories, and client-facing highlights.</p>
                    <div class="p-2.5 bg-gray-50 rounded-xl border border-gray-200 font-mono text-xs text-purple-900 break-all select-all font-semibold mb-2">
                        package_code,package_name,category,description,price,is_active,included_items
                    </div>
                    <ul class="text-xs text-gray-600 space-y-1 list-disc pl-4">
                        <li><strong>package_code:</strong> Unique identifier (max 20 chars). If empty, auto-generated upon creation. Existing codes update matching packages.</li>
                        <li><strong>package_name / title:</strong> Display title for clients and staff (required).</li>
                        <li><strong>category:</strong> Event or bundle classification (e.g., Wedding, Corporate).</li>
                        <li><strong>price:</strong> Retail package price in PHP (must be non-negative).</li>
                        <li><strong>is_active:</strong> <code>1</code> for active or <code>0</code> for inactive.</li>
                        <li><strong>included_items:</strong> Marketing highlights separated by semicolons (e.g. <code>Bridal Bouquet; 3 Corsages</code>).</li>
                    </ul>
                </div>

                <!-- Section 2: Package Materials BOM -->
                <div class="pt-3 border-t border-gray-100">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-purple-700 mb-1.5 flex items-center gap-1.5">
                        <i class="fa-solid fa-layer-group"></i> 2. Package Materials CSV (package_materials.csv)
                    </h4>
                    <p class="text-xs text-gray-500 mb-2">Links packages to physical Inventory Management items (Bill of Materials).</p>
                    <div class="p-2.5 bg-gray-50 rounded-xl border border-gray-200 font-mono text-xs text-purple-900 break-all select-all font-semibold mb-2">
                        package_code,item_code,quantity
                    </div>
                    <ul class="text-xs text-gray-600 space-y-1 list-disc pl-4">
                        <li><strong>package_code:</strong> Must match an existing package code.</li>
                        <li><strong>item_code:</strong> Must match an existing inventory item code (e.g., <code>FRE-0001</code>).</li>
                        <li><strong>quantity:</strong> Amount required for this package (greater than zero; whole number for integer units).</li>
                        <li><strong>Archived Items:</strong> Archived inventory items cannot be newly added to packages.</li>
                    </ul>
                </div>

                <!-- Important Note -->
                <div class="p-3.5 bg-purple-50/60 rounded-xl border border-purple-100 text-xs text-gray-700 space-y-1.5">
                    <p class="font-bold text-purple-900 flex items-center gap-1.5">
                        <i class="fa-solid fa-shield-halved text-purple-600"></i> Inventory Safety Notice
                    </p>
                    <p class="text-gray-600">
                        Package master records and BOM mappings are <strong>definition data</strong> only. Creating or importing packages never reduces <code>current_stock</code>, never creates reservations, and never alters client bookings or payments.
                    </p>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-gray-100">
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.packages.template.packages') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-purple-700 bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-xl transition">
                        <i class="fa-solid fa-download"></i> Package Template
                    </a>
                    <a href="{{ route('admin.packages.template.materials') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-purple-700 bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-xl transition">
                        <i class="fa-solid fa-download"></i> Materials Template
                    </a>
                </div>
                <button type="button" onclick="closePackageInstructionsModal()" class="px-4 py-2 border border-gray-300 text-xs font-semibold rounded-xl text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-300 transition">
                    Close
                </button>
            </div>
        </div>
    </div>

    <script>
        function openModal(id) {
            const modal = document.getElementById(id);
            modal.style.display = 'flex';
            setTimeout(() => {
                modal.classList.remove('hidden');
            }, 10);
            document.body.classList.add('overflow-hidden');
        }
        function closeModal(id) {
            const modal = document.getElementById(id);
            modal.style.display = 'none';
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
        
        function openViewModal(pkg) {
            document.getElementById('view_title').innerText = pkg.title || 'N/A';
            document.getElementById('view_package_code').innerText = pkg.package_code || 'N/A';
            document.getElementById('view_category').innerText = pkg.category || 'N/A';
            document.getElementById('view_price').innerText = '₱' + (parseFloat(pkg.price) || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('view_description').innerText = pkg.description || 'No description provided.';
            document.getElementById('view_included_items').innerText = Array.isArray(pkg.included_items) && pkg.included_items.length > 0 ? pkg.included_items.join(', ') : 'No items specified.';
            
            var statusBadge = document.getElementById('view_status_badge');
            if (pkg.is_active) {
                statusBadge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800';
                statusBadge.innerText = 'Active';
            } else {
                statusBadge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800';
                statusBadge.innerText = 'Archived / Inactive';
            }

            var invContainer = document.getElementById('view_inventory_container');
            invContainer.innerHTML = '';
            
            var hasInventory = false;
            if (pkg.inventory_items && Array.isArray(pkg.inventory_items)) {
                pkg.inventory_items.forEach(function(item) {
                    if (item.pivot && item.pivot.quantity > 0) {
                        hasInventory = true;
                        const isArchived = Boolean(item.deleted_at);
                        invContainer.innerHTML += `
                            <div class="flex items-center justify-between border-b border-gray-100 pb-2.5 last:border-0 last:pb-0">
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <p class="text-[13px] font-semibold text-gray-800">${item.name}</p>
                                        <span class="font-mono text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded border border-slate-200">${item.item_code || 'N/A'}</span>
                                        ${isArchived ? '<span class="bg-amber-100 text-amber-800 text-[10px] font-bold px-1.5 py-0.5 rounded">Archived</span>' : ''}
                                    </div>
                                    <div class="flex items-center gap-2 text-[11px] text-gray-500 mt-0.5">
                                        <span>${item.category}</span>
                                        <span>•</span>
                                        <span>In Stock: <strong class="text-slate-700">${parseFloat(item.current_stock) || 0}</strong> ${item.unit}</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-[13px] font-bold text-purple-700">
                                        ${parseFloat(item.pivot.quantity)} <span class="text-gray-500 font-normal">${item.unit}</span>
                                    </div>
                                    <span class="text-[10px] text-gray-400 uppercase tracking-wider">Required</span>
                                </div>
                            </div>
                        `;
                    }
                });
            }
            
            if (!hasInventory) {
                invContainer.innerHTML = '<p class="text-[13px] text-gray-500 italic">No inventory mapping configured.</p>';
            }

            openModal('viewModal');
        }
        // Lightbox Functions
        const packageData = @json($lightboxData);
        
        let currentPackageId = null;
        let currentImageIndex = 0;
        let currentImages = [];

        let lightbox, lightboxTitle, lightboxMainImg, lightboxCounter, lightboxThumbnails, lightboxPrevBtn, lightboxNextBtn, lightboxCategory;

        function openLightbox(id) {
            if (!lightbox) {
                lightbox = document.getElementById('galleryLightbox');
                lightboxTitle = document.getElementById('lightboxTitle');
                lightboxMainImg = document.getElementById('lightboxMainImg');
                lightboxCounter = document.getElementById('lightboxCounter');
                lightboxThumbnails = document.getElementById('lightboxThumbnails');
                lightboxPrevBtn = document.getElementById('lightboxPrevBtn');
                lightboxNextBtn = document.getElementById('lightboxNextBtn');
                lightboxCategory = document.getElementById('lightboxCategory');
            }

            const item = packageData.find(g => g.id === String(id));
            if (!item || !item.images || item.images.length === 0) return;

            currentPackageId = id;
            currentImages = item.images;
            currentImageIndex = 0;
            
            lightboxTitle.textContent = item.title;
            if (lightboxCategory) lightboxCategory.textContent = item.tags[0] || 'Uncategorized';
            
            lightboxThumbnails.innerHTML = '';
            currentImages.forEach((imgUrl, index) => {
                lightboxThumbnails.innerHTML += `
                    <button onclick="goToImage(${index})" class="flex-shrink-0 snap-center w-20 h-20 rounded-md overflow-hidden border-2 transition-all duration-300 ${index === 0 ? 'border-green-600 opacity-100 shadow-sm' : 'border-transparent opacity-50 hover:opacity-100'}" style="width: 80px; height: 80px; border-radius: 8px;">
                        <img src="${imgUrl}" class="w-full h-full object-cover" style="width: 100%; height: 100%; object-fit: cover;">
                    </button>
                `;
            });

            updateLightboxView();
            
            lightbox.classList.remove('hidden');
            setTimeout(() => {
                lightbox.classList.remove('opacity-0');
            }, 10);
            document.body.classList.add('overflow-hidden');
        }

        function closeLightbox() {
            lightbox.classList.add('opacity-0');
            setTimeout(() => {
                lightbox.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
                currentPackageId = null;
            }, 300);
        }

        function updateLightboxView() {
            lightboxMainImg.src = currentImages[currentImageIndex];
            lightboxCounter.textContent = `${currentImageIndex + 1} / ${currentImages.length}`;
            
            lightboxPrevBtn.classList.toggle('hidden', currentImages.length <= 1);
            lightboxNextBtn.classList.toggle('hidden', currentImages.length <= 1);
            
            Array.from(lightboxThumbnails.children).forEach((btn, index) => {
                if (index === currentImageIndex) {
                    btn.classList.remove('border-transparent', 'opacity-50');
                    btn.classList.add('border-green-600', 'opacity-100', 'shadow-sm');
                    btn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                } else {
                    btn.classList.add('border-transparent', 'opacity-50');
                    btn.classList.remove('border-green-600', 'opacity-100', 'shadow-sm');
                }
            });
        }

        function prevImage(e) {
            if (e) e.stopPropagation();
            if (currentImages.length <= 1) return;
            currentImageIndex = (currentImageIndex - 1 + currentImages.length) % currentImages.length;
            updateLightboxView();
        }

        function nextImage(e) {
            if (e) e.stopPropagation();
            if (currentImages.length <= 1) return;
            currentImageIndex = (currentImageIndex + 1) % currentImages.length;
            updateLightboxView();
        }

        function goToImage(index) {
            currentImageIndex = index;
            updateLightboxView();
        }

        document.addEventListener('keydown', (e) => {
            if (!lightbox || lightbox.classList.contains('hidden')) return;
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft') prevImage();
            if (e.key === 'ArrowRight') nextImage();
        });

        function togglePackageFilterPanel() {
            const panel = document.getElementById('packageFilterPanel');
            const btn = document.getElementById('packageToggleFiltersBtn');
            const text = document.getElementById('packageToggleFiltersText');
            const chevron = document.getElementById('packageFiltersChevron');
            const input = document.getElementById('packageFilterExpandedInput');
            if (!panel) return;

            const isHidden = panel.style.display === 'none' || panel.style.display === '';
            if (isHidden) {
                panel.style.display = 'block';
                if (text) text.textContent = 'Hide Filters';
                if (chevron) chevron.classList.add('rotate-180');
                if (btn) btn.setAttribute('aria-expanded', 'true');
                if (input) input.value = '1';
            } else {
                panel.style.display = 'none';
                if (text) text.textContent = 'Show Filters';
                if (chevron) chevron.classList.remove('rotate-180');
                if (btn) btn.setAttribute('aria-expanded', 'false');
                if (input) input.value = '0';
            }
        }

        // Package Tools Popover and Modal Controls
        function togglePackageToolsDropdown() {
            const menu = document.getElementById('packageToolsMenu');
            const btn = document.getElementById('packageToolsButton');
            if (!menu || !btn) return;

            const isExpanded = btn.getAttribute('aria-expanded') === 'true';
            if (isExpanded) {
                closePackageToolsDropdown();
            } else {
                openPackageToolsDropdown();
            }
        }

        function openPackageToolsDropdown() {
            const menu = document.getElementById('packageToolsMenu');
            const btn = document.getElementById('packageToolsButton');
            const chevron = document.getElementById('packageToolsChevron');
            if (!menu || !btn) return;

            menu.style.display = 'block';
            btn.setAttribute('aria-expanded', 'true');
            if (chevron) {
                chevron.classList.add('rotate-180');
            }
        }

        function closePackageToolsDropdown() {
            const menu = document.getElementById('packageToolsMenu');
            const btn = document.getElementById('packageToolsButton');
            const chevron = document.getElementById('packageToolsChevron');
            if (!menu || !btn) return;

            menu.style.display = 'none';
            btn.setAttribute('aria-expanded', 'false');
            if (chevron) {
                chevron.classList.remove('rotate-180');
            }
        }

        function openImportPackagesModal() {
            closePackageToolsDropdown();
            const modal = document.getElementById('importPackagesModal');
            if (modal) {
                modal.style.display = 'flex';
                const fileInput = document.getElementById('package_csv_file');
                if (fileInput) fileInput.focus();
            }
        }

        function closeImportPackagesModal() {
            const modal = document.getElementById('importPackagesModal');
            if (modal) {
                modal.style.display = 'none';
            }
        }

        function openImportMaterialsModal() {
            closePackageToolsDropdown();
            const modal = document.getElementById('importMaterialsModal');
            if (modal) {
                modal.style.display = 'flex';
                const fileInput = document.getElementById('materials_csv_file');
                if (fileInput) fileInput.focus();
            }
        }

        function closeImportMaterialsModal() {
            const modal = document.getElementById('importMaterialsModal');
            if (modal) {
                modal.style.display = 'none';
            }
        }

        function openPackageInstructionsModal() {
            closePackageToolsDropdown();
            const modal = document.getElementById('packageInstructionsModal');
            if (modal) {
                modal.style.display = 'flex';
            }
        }

        function closePackageInstructionsModal() {
            const modal = document.getElementById('packageInstructionsModal');
            if (modal) {
                modal.style.display = 'none';
            }
        }

        // Click outside listener for Package Tools popover
        document.addEventListener('click', function (e) {
            const container = document.getElementById('packageToolsContainer');
            if (container && !container.contains(e.target)) {
                closePackageToolsDropdown();
            }
        });

        // Keyboard navigation (Escape key closes modals and popover)
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                const instructionsModal = document.getElementById('packageInstructionsModal');
                const packagesModal = document.getElementById('importPackagesModal');
                const materialsModal = document.getElementById('importMaterialsModal');
                const menu = document.getElementById('packageToolsMenu');
                const btn = document.getElementById('packageToolsButton');

                if (instructionsModal && instructionsModal.style.display === 'flex') {
                    closePackageInstructionsModal();
                } else if (packagesModal && packagesModal.style.display === 'flex') {
                    closeImportPackagesModal();
                } else if (materialsModal && materialsModal.style.display === 'flex') {
                    closeImportMaterialsModal();
                } else if (menu && menu.style.display === 'block') {
                    closePackageToolsDropdown();
                    if (btn) btn.focus();
                }
            }
        });
    </script>
</x-admin-layout>
