<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;

class PackageController extends Controller
{
    /**
     * Display a listing of the packages.
     */
    public function index(): View
    {
        $packages = Package::with(['inventoryItems', 'images'])->where('is_archived', false)->get();
        $inventoryItems = \App\Models\InventoryItem::orderBy('category')->orderBy('name')->get();
        $inventoryCategories = \App\Models\InventoryItem::select('category')->distinct()->pluck('category')->filter()->values();
        return view('admin.packages', [
            'packages' => $packages,
            'inventoryItems' => $inventoryItems,
            'inventoryCategories' => $inventoryCategories
        ]);
    }

    /**
     * Show the form for creating a new package.
     */
    public function create(): View
    {
        $inventoryItems = \App\Models\InventoryItem::orderBy('category')->orderBy('name')->get();
        $inventoryCategories = \App\Models\InventoryItem::select('category')->distinct()->pluck('category')->filter()->values();
        
        return view('admin.packages.create', [
            'inventoryItems' => $inventoryItems,
            'inventoryCategories' => $inventoryCategories
        ]);
    }

    /**
     * Display a listing of archived packages.
     */
    public function archived(): View
    {
        $packages = Package::with(['inventoryItems', 'images'])->where('is_archived', true)->get();
        $inventoryItems = \App\Models\InventoryItem::orderBy('category')->orderBy('name')->get();
        $inventoryCategories = \App\Models\InventoryItem::select('category')->distinct()->pluck('category')->filter()->values();
        return view('admin.packages-archived', [
            'packages' => $packages,
            'inventoryItems' => $inventoryItems,
            'inventoryCategories' => $inventoryCategories
        ]);
    }

    /**
     * Show the form for editing the specified package.
     */
    public function edit(Package $package): View
    {
        $package->load(['inventoryItems', 'images']);
        $inventoryItems = \App\Models\InventoryItem::orderBy('category')->orderBy('name')->get();
        $inventoryCategories = \App\Models\InventoryItem::select('category')->distinct()->pluck('category')->filter()->values();
        
        return view('admin.packages.edit', [
            'package' => $package,
            'inventoryItems' => $inventoryItems,
            'inventoryCategories' => $inventoryCategories
        ]);
    }

    /**
     * Store a newly created package in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:50',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'included_items' => 'nullable|string', // Still keeping text as fallback/description
            'images' => 'nullable|array',
            'images.*' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
            'inventory_items' => 'nullable|array',
            'inventory_items.*' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    if ($value <= 0) {
                        $fail('Quantity must be greater than zero.');
                        return;
                    }
                    $parts = explode('.', $attribute);
                    $itemId = $parts[1];
                    $item = \App\Models\InventoryItem::find($itemId);
                    if (!$item) {
                        $fail('Invalid inventory item.');
                        return;
                    }
                    $integerUnits = ['pcs', 'stems', 'bunches', 'rolls', 'blocks', 'sets', 'units'];
                    if (in_array(strtolower($item->unit), $integerUnits)) {
                        // Check if value has decimals
                        if (floor($value) != $value) {
                            $fail("Quantity must be a whole number for {$item->unit}.");
                        }
                    }
                }
            ],
        ]);

        $itemsText = array_filter(array_map('trim', explode(',', $validated['included_items'] ?? '')));

        $isActive = $request->has('is_active') ? $request->boolean('is_active') : true;

        $package = Package::create([
            'title' => $validated['title'],
            'category' => $validated['category'] ?? null,
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'included_items' => $itemsText,
            'is_active' => $isActive,
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $imagePath = $image->store('packages', 'public');
                \App\Models\PackageImage::create([
                    'package_id' => $package->id,
                    'image_path' => $imagePath,
                ]);
            }
        }

        // Sync physical inventory BOM mapping
        if (!empty($validated['inventory_items'])) {
            $syncData = [];
            foreach ($validated['inventory_items'] as $itemId => $quantity) {
                if ($quantity > 0) {
                    $syncData[$itemId] = ['quantity' => $quantity];
                }
            }
            $package->inventoryItems()->sync($syncData);
        }

        return back()->with('success', 'Package created successfully.');
    }

    /**
     * Update the specified package in storage.
     */
    public function update(Request $request, Package $package): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:50',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'included_items' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'nullable|image|max:2048',
            'remove_images' => 'nullable|string',
            'is_active' => 'boolean',
            'inventory_items' => 'nullable|array',
            'inventory_items.*' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    if ($value <= 0) {
                        $fail('Quantity must be greater than zero.');
                        return;
                    }
                    $parts = explode('.', $attribute);
                    $itemId = $parts[1];
                    $item = \App\Models\InventoryItem::find($itemId);
                    if (!$item) {
                        $fail('Invalid inventory item.');
                        return;
                    }
                    $integerUnits = ['pcs', 'stems', 'bunches', 'rolls', 'blocks', 'sets', 'units'];
                    if (in_array(strtolower($item->unit), $integerUnits)) {
                        if (floor($value) != $value) {
                            $fail("Quantity must be a whole number for {$item->unit}.");
                        }
                    }
                }
            ],
        ]);

        $hasChanges = false;
        $itemsText = array_filter(array_map('trim', explode(',', $validated['included_items'] ?? '')));
        // Normalize for comparison
        $newItemsText = implode(',', $itemsText);
        $oldItemsText = implode(',', $package->included_items ?? []);
        $isActive = $request->boolean('is_active');

        if (
            $package->title !== $validated['title'] ||
            $package->category !== ($validated['category'] ?? null) ||
            $package->description !== ($validated['description'] ?? null) ||
            (float) $package->price !== (float) $validated['price'] ||
            $oldItemsText !== $newItemsText ||
            (bool) $package->is_active !== $isActive
        ) {
            $hasChanges = true;
            $package->title = $validated['title'];
            $package->category = $validated['category'] ?? null;
            $package->description = $validated['description'] ?? null;
            $package->price = $validated['price'];
            $package->included_items = $itemsText;
            $package->is_active = $isActive;
            $package->save();
        }

        if ($request->filled('remove_images')) {
            $hasChanges = true;
            $removeParam = $request->remove_images;
            if (str_starts_with(trim($removeParam), '[')) {
                $removeImageIds = json_decode($removeParam, true) ?: [];
            } else {
                $removeImageIds = array_filter(explode(',', $removeParam));
            }
            $imagesToRemove = \App\Models\PackageImage::whereIn('id', $removeImageIds)
                ->where('package_id', $package->id)
                ->get();
                
            foreach ($imagesToRemove as $img) {
                Storage::disk('public')->delete($img->image_path);
                $img->delete();
            }
        }

        if ($request->hasFile('images')) {
            $hasChanges = true;
            foreach ($request->file('images') as $image) {
                $imagePath = $image->store('packages', 'public');
                \App\Models\PackageImage::create([
                    'package_id' => $package->id,
                    'image_path' => $imagePath,
                ]);
            }
        }

        // Check BOM changes
        $newSyncData = [];
        if (isset($validated['inventory_items'])) {
            foreach ($validated['inventory_items'] as $itemId => $quantity) {
                if ($quantity > 0) {
                    $newSyncData[$itemId] = ['quantity' => $quantity];
                }
            }
        }

        $oldSyncData = [];
        foreach ($package->inventoryItems as $item) {
            $oldSyncData[$item->id] = ['quantity' => $item->pivot->quantity];
        }

        // Compare BOM
        $bomChanged = false;
        if (count($newSyncData) !== count($oldSyncData)) {
            $bomChanged = true;
        } else {
            foreach ($newSyncData as $id => $data) {
                if (!isset($oldSyncData[$id]) || $oldSyncData[$id]['quantity'] != $data['quantity']) {
                    $bomChanged = true;
                    break;
                }
            }
        }

        if ($bomChanged) {
            $hasChanges = true;
            $package->inventoryItems()->sync($newSyncData);
        }

        if (!$hasChanges) {
            return back()->with('info', 'No changes were made to this package.');
        }

        return back()->with('success', 'Package updated successfully.');
    }

    /**
     * Archive the specified package.
     */
    public function archive(Package $package): RedirectResponse
    {
        $package->is_archived = true;
        $package->save();

        return back()->with('success', 'Package archived successfully.');
    }

    /**
     * Restore the specified archived package.
     */
    public function restore(Package $package): RedirectResponse
    {
        $package->is_archived = false;
        $package->save();

        return back()->with('success', 'Package restored successfully.');
    }
}
