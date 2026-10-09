<x-admin-layout title="Add Package">
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.packages.index') }}" class="w-10 h-10 rounded-full flex items-center justify-center bg-white border border-gray-200 text-gray-500 hover:text-purple-700 hover:bg-purple-50 transition">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <h2 class="text-xl font-bold text-gray-800">Add New Package</h2>
                <p class="text-sm text-gray-500">Create a new package with pricing and optional inventory mapping.</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden max-w-4xl">
        <form action="{{ route('admin.packages.store') }}" method="POST" enctype="multipart/form-data" class="p-6 sm:p-8 space-y-8" onkeydown="if(event.key === 'Enter' && event.target.tagName !== 'TEXTAREA') { event.preventDefault(); }">
            @csrf
            
            <!-- SECTION 1 — PACKAGE INFORMATION -->
            <div>
                <h4 class="text-xs font-bold tracking-widest uppercase text-slate-400 mb-4 border-b border-slate-100 pb-2">1. Package Information</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Package Name <span class="text-red-500">*</span></label>
                        <input type="text" name="title" value="{{ old('title') }}" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition shadow-sm" placeholder="e.g. Classic Wedding Package">
                        @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                        <input type="text" name="category" value="{{ old('category') }}" required placeholder="e.g. Wedding, Birthday" maxlength="50" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition shadow-sm">
                        @error('category') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Package Price (₱) <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" min="0" name="price" value="{{ old('price') }}" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition shadow-sm" placeholder="0.00">
                        <p class="text-xs text-gray-500 mt-1">Must be 0 or greater.</p>
                        @error('price') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Package Code</label>
                        <input type="text" readonly disabled placeholder="Generated automatically upon save" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed shadow-sm">
                        <p class="text-xs text-gray-500 mt-1">Generated automatically when the package is created. It remains unchanged if the package name is edited.</p>
                    </div>
                </div>
            </div>

            <!-- SECTION 2 — DESCRIPTION -->
            <div>
                <h4 class="text-xs font-bold tracking-widest uppercase text-slate-400 mb-4 border-b border-slate-100 pb-2">2. Description</h4>
                <div>
                    <textarea name="description" rows="3" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition shadow-sm resize-y" placeholder="Brief description of the package">{{ old('description') }}</textarea>
                    @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- SECTION 3 — INCLUDED IN THE PACKAGE -->
            <div>
                <h4 class="text-xs font-bold tracking-widest uppercase text-slate-400 mb-4 border-b border-slate-100 pb-2">3. Included in the Package</h4>
                <div>
                    <input type="text" name="included_items" value="{{ old('included_items') }}" placeholder="e.g. 50 Red Roses, Venue Setup" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition shadow-sm">
                    <p class="text-xs text-gray-500 mt-1">Items shown to clients when selecting this package. Use a comma-separated list.</p>
                    @error('included_items') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- SECTION 4 — PACKAGE IMAGES -->
            <div>
                <h4 class="text-xs font-bold tracking-widest uppercase text-slate-400 mb-4 border-b border-slate-100 pb-2">4. Package Images</h4>
                <div>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg bg-gray-50 hover:bg-gray-100 transition relative">
                        <div class="space-y-1 text-center">
                            <i class="fa-solid fa-cloud-arrow-up text-3xl text-gray-400"></i>
                            <div class="flex text-sm text-gray-600 justify-center">
                                <label for="images" class="relative cursor-pointer bg-white rounded-md font-medium text-purple-600 hover:text-purple-500 focus-within:outline-none px-1">
                                    <span>Upload Images</span>
                                    <input id="images" name="images[]" type="file" multiple class="sr-only" accept="image/jpeg,image/png,image/jpg,image/gif">
                                </label>
                                <p class="pl-1">or drag and drop</p>
                            </div>
                            <p class="text-xs text-gray-500">PNG, JPG, GIF up to 5MB</p>
                        </div>
                    </div>
                    <div id="file-list" class="mt-3 text-sm text-gray-600 space-y-1"></div>
                    @error('images') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    @error('images.*') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- SECTION 5 — MASTER INVENTORY MAPPING -->
            <div>
                <h4 class="text-xs font-bold tracking-widest uppercase text-slate-400 mb-4 border-b border-slate-100 pb-2">5. Master Inventory Mapping</h4>
                <p class="text-xs text-gray-500 mb-3">Select the inventory items required to prepare one package and enter the quantity needed. Leave all items unselected if this package does not require physical inventory mapping.</p>
                
                <div class="flex gap-3 mb-3">
                    <div class="flex-1">
                        <input type="text" id="add_inv_search" onkeyup="filterInventory('add')" placeholder="Search inventory items..." class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition shadow-sm">
                    </div>
                    <div class="w-1/3">
                        <select id="add_inv_category" onchange="filterInventory('add')" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition shadow-sm">
                            <option value="">All Categories</option>
                            @foreach($inventoryCategories as $cat)
                                <option value="{{ $cat }}">{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                <div class="border border-gray-200 rounded-xl p-4 bg-gray-50 block overflow-y-auto overflow-x-hidden" style="max-height: 400px;">
                    <div class="space-y-3">
                        @foreach($inventoryItems as $item)
                            <div class="flex items-center justify-between group py-1 add-inv-row" data-name="{{ $item->name }}" data-category="{{ $item->category }}">
                                <div class="flex items-center gap-3 pr-3 flex-1 min-w-0">
                                    <input type="checkbox" id="add_inv_check_{{ $item->id }}" class="h-4 w-4 rounded border-gray-300 text-purple-600 focus:ring-purple-500 add-inv-check" onchange="toggleInvQty('add', {{ $item->id }})">
                                    <div>
                                        <p class="text-[13px] font-semibold text-gray-700 truncate">{{ $item->name }}</p>
                                        <p class="text-[11px] text-gray-500 truncate">{{ $item->category }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <label class="text-xs text-gray-500 hidden sm:block">Quantity:</label>
                                    <input type="number" name="inventory_items[{{ $item->id }}]" id="add_inv_qty_{{ $item->id }}" min="0.01" step="0.01" placeholder="0" disabled class="w-24 px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition shadow-sm text-sm disabled:opacity-50 disabled:bg-gray-100">
                                    <span class="text-[13px] text-gray-500 w-8">{{ $item->unit }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <p class="text-xs text-gray-600 font-semibold mt-2" id="add_inv_summary">0 inventory items selected</p>
                @if($errors->has('inventory_items.*') || $errors->has('inventory_items'))
                    <div class="mt-2 text-red-500 text-xs">
                        There was an error with your inventory mappings. Please check the values.
                        @foreach($errors->get('inventory_items.*') as $msg)
                            <div class="mt-1">{{ $msg[0] }}</div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- SECTION 6 — PACKAGE STATUS -->
            <div>
                <h4 class="text-xs font-bold tracking-widest uppercase text-slate-400 mb-4 border-b border-slate-100 pb-2">6. Package Status</h4>
                <div class="flex items-center gap-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="sr-only peer" checked>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                        <span class="ml-3 text-sm font-medium text-gray-700">Active (Visible to Clients)</span>
                    </label>
                </div>
            </div>

            <div class="pt-4 border-t border-gray-100 flex justify-end gap-3">
                <a href="{{ route('admin.packages.index') }}" class="px-5 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition shadow-sm">Cancel</a>
                <button type="submit" class="btn-primary px-6 py-2.5 rounded-lg shadow-sm">Save Package</button>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('images').addEventListener('change', function(e) {
            const fileList = document.getElementById('file-list');
            fileList.innerHTML = '';
            for (let i = 0; i < this.files.length; i++) {
                const p = document.createElement('p');
                p.className = 'text-sm text-green-700 bg-green-50 px-3 py-1.5 rounded-md inline-flex items-center gap-2 mr-2 mb-2 border border-green-200';
                p.innerHTML = '<i class="fa-solid fa-image"></i> ' + this.files[i].name;
                fileList.appendChild(p);
            }
        });

        function toggleInvQty(mode, id) {
            const checkbox = document.getElementById(mode + '_inv_check_' + id);
            const qtyInput = document.getElementById(mode + '_inv_qty_' + id);
            if (checkbox.checked) {
                qtyInput.disabled = false;
                qtyInput.required = true;
                if (!qtyInput.value || parseFloat(qtyInput.value) <= 0) {
                    qtyInput.value = '1';
                }
            } else {
                qtyInput.disabled = true;
                qtyInput.required = false;
                qtyInput.value = '';
            }
            updateInvSummary(mode);
        }

        function updateInvSummary(mode) {
            const checkboxes = document.querySelectorAll('.' + mode + '-inv-check:checked');
            const summary = document.getElementById(mode + '_inv_summary');
            if (summary) {
                summary.textContent = checkboxes.length + ' inventory items selected';
            }
        }

        function filterInventory(mode) {
            const searchInput = document.getElementById(mode + '_inv_search').value.toLowerCase();
            const categorySelect = document.getElementById(mode + '_inv_category').value.toLowerCase();
            const rows = document.querySelectorAll('.' + mode + '-inv-row');
            
            rows.forEach(row => {
                const name = row.dataset.name.toLowerCase();
                const category = row.dataset.category.toLowerCase();
                
                const matchesSearch = name.includes(searchInput);
                const matchesCategory = categorySelect === '' || category === categorySelect;
                
                if (matchesSearch && matchesCategory) {
                    row.style.display = 'flex';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</x-admin-layout>
