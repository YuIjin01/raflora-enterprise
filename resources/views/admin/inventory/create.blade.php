<x-admin-layout title="Add Inventory Item">
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.inventory.index') }}" class="w-10 h-10 rounded-full flex items-center justify-center bg-white border border-gray-200 text-gray-500 hover:text-purple-700 hover:bg-purple-50 transition">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <h2 class="text-xl font-bold text-gray-800">Add Inventory Item</h2>
                <p class="text-sm text-gray-500">Create a new item in your inventory catalog.</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden max-w-4xl">
        <form action="{{ route('admin.inventory.store') }}" method="POST" enctype="multipart/form-data" class="p-6 sm:p-8 space-y-8">
            @csrf
            
            <!-- SECTION 1 — INVENTORY INFORMATION -->
            <div>
                <h4 class="text-xs font-bold tracking-widest uppercase text-slate-400 mb-4 border-b border-slate-100 pb-2">1. Inventory Information</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Item Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition shadow-sm" placeholder="e.g. Red Roses">
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Item Code</label>
                        <input type="text" disabled class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-50 text-gray-500 shadow-sm" placeholder="Auto-generated after saving">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                        <input type="text" name="category" value="{{ old('category') }}" required placeholder="e.g. Flowers, Decor, Equipment" maxlength="50" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition shadow-sm">
                        @error('category') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Unit <span class="text-red-500">*</span></label>
                        <input type="text" name="unit" value="{{ old('unit') }}" required placeholder="e.g. stems, pcs, bunches, rolls, blocks" maxlength="50" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition shadow-sm">
                        @error('unit') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_perishable" value="1" class="sr-only peer" {{ old('is_perishable') ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                            <span class="ml-3 text-sm font-medium text-gray-700">Perishable Item (e.g. fresh flowers)</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- SECTION 2 — ITEM IMAGE -->
            <div>
                <h4 class="text-xs font-bold tracking-widest uppercase text-slate-400 mb-4 border-b border-slate-100 pb-2">2. Item Image (Optional)</h4>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Upload Photo</label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg bg-gray-50 hover:bg-gray-100 transition relative">
                        <div class="space-y-1 text-center">
                            <i class="fa-solid fa-cloud-arrow-up text-3xl text-gray-400 mb-2"></i>
                            <div class="flex text-sm text-gray-600 justify-center">
                                <label for="file-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-green-600 hover:text-green-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-green-500 px-2 py-0.5">
                                    <span>Upload a file</span>
                                    <input id="file-upload" name="image" type="file" class="sr-only" accept="image/png, image/jpeg, image/webp">
                                </label>
                            </div>
                            <p class="text-xs text-gray-500">PNG, JPG or WEBP · Max 2MB</p>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Recommended size: 500x500px, Max 2MB.</p>
                    @error('image') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- SECTION 3 — STOCK INFORMATION -->
            <div>
                <h4 class="text-xs font-bold tracking-widest uppercase text-slate-400 mb-4 border-b border-slate-100 pb-2">3. Stock Information</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Current Stock <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" min="0" name="current_stock" value="{{ old('current_stock') }}" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition shadow-sm" placeholder="0">
                        <p class="text-xs text-gray-500 mt-1">Decimal values are allowed.</p>
                        @error('current_stock') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Minimum Stock <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" min="0" name="min_stock" value="{{ old('min_stock') }}" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition shadow-sm" placeholder="0">
                        <p class="text-xs text-gray-500 mt-1">Triggers low stock alert when Available drops below this.</p>
                        @error('min_stock') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Unit Cost (₱) <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" min="0" name="unit_cost" value="{{ old('unit_cost') }}" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition shadow-sm" placeholder="0.00">
                        @error('unit_cost') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <!-- SECTION 4 — SEASONAL SUBSTITUTES -->
            <div>
                <h4 class="text-xs font-bold tracking-widest uppercase text-slate-400 mb-4 border-b border-slate-100 pb-2">4. Seasonal Substitutes (Optional)</h4>
                <div>
                    <select name="substitute_ids[]" multiple class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition shadow-sm text-sm h-32">
                        @foreach($inventoryItems as $invItem)
                            <option value="{{ $invItem->id }}" {{ (is_array(old('substitute_ids')) && in_array($invItem->id, old('substitute_ids'))) ? 'selected' : '' }}>
                                {{ $invItem->name }} ({{ $invItem->category }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Hold Ctrl (Windows) or Cmd (Mac) to select multiple substitutes.</p>
                    @error('substitute_ids') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="pt-4 border-t border-gray-100 flex justify-end gap-3">
                <a href="{{ route('admin.inventory.index') }}" class="px-5 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition shadow-sm">Cancel</a>
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-semibold px-6 py-2.5 rounded-lg shadow-sm transition">Save Item</button>
            </div>
        </form>
    </div>
</x-admin-layout>
