<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Booking;
use App\Models\AdminAlert;
use App\Models\InventoryTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InventoryController extends Controller
{
    /**
     * Display the inventory management dashboard with calculated shortages.
     */
    public function index(Request $request): View
    {
        // Get the requested category filter
        $category = $request->query('category', 'all');
        $status = $request->query('status', 'all');
        $perishable = $request->query('perishable', 'all');
        $search = $request->query('search');

        // Fetch all inventory items with substitutes and reserved stock calculation
        $query = InventoryItem::with('substitutes')->withSum(['bookings as reserved_stock' => function ($query) {
            $query->whereDate('bookings.event_date', '>=', Carbon::today())
                  ->whereNotIn('bookings.status', ['cancelled', 'completed', 'declined']);
        }], 'booking_items.quantity');
        
        if ($category !== 'all') {
            $query->where('category', $category);
        }
        
        if ($perishable !== 'all') {
            $query->where('is_perishable', $perishable === 'yes');
        }
        
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('item_code', 'like', "%{$search}%");
            });
        }
        
        if ($status !== 'all') {
            $query->groupBy('inventory_items.id');
        }

        if ($status === 'in_stock') {
            $query->havingRaw('(current_stock - COALESCE(reserved_stock, 0)) > min_stock');
        } elseif ($status === 'low') {
            $query->havingRaw('(current_stock - COALESCE(reserved_stock, 0)) <= min_stock AND (current_stock - COALESCE(reserved_stock, 0)) >= 0');
        } elseif ($status === 'shortage') {
            $query->havingRaw('(current_stock - COALESCE(reserved_stock, 0)) < 0');
        }

        $query->orderByRaw('CASE WHEN (current_stock - COALESCE(reserved_stock, 0)) < 0 THEN 2 WHEN (current_stock - COALESCE(reserved_stock, 0)) <= min_stock THEN 1 ELSE 0 END DESC')
              ->orderByRaw('CASE WHEN (current_stock - COALESCE(reserved_stock, 0)) <= min_stock THEN min_stock - (current_stock - COALESCE(reserved_stock, 0)) ELSE 0 END DESC')
              ->orderBy('name');
              
        // Use subquery count to correctly handle HAVING clauses on aliases with pagination
        $page = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
        $perPage = 10;
        $total = \Illuminate\Support\Facades\DB::query()->fromSub(clone $query, 'sub')->count();
        $items = $query->forPage($page, $perPage)->get();
        $inventoryItems = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        // Get unique categories for the filter dropdown
        $categories = InventoryItem::select('category')->distinct()->pluck('category');

        // Compute system-wide stats for cards (unfiltered)
        $allStatsItems = InventoryItem::withSum(['bookings as reserved_stock' => function ($q) {
            $q->whereDate('bookings.event_date', '>=', Carbon::today())
              ->whereNotIn('bookings.status', ['cancelled', 'completed', 'declined']);
        }], 'booking_items.quantity')->get();

        $totalItemsCount = $allStatsItems->count();
        $inStockCount = 0;
        $lowStockCount = 0;
        $shortageCount = 0;

        foreach ($allStatsItems as $statsItem) {
            $reserved = (float) ($statsItem->reserved_stock ?? 0);
            $net = $statsItem->current_stock - $reserved;
            if ($net < 0) {
                $shortageCount++;
            } elseif ($net <= $statsItem->min_stock) {
                $lowStockCount++;
            } else {
                $inStockCount++;
            }
        }

        foreach ($inventoryItems as $item) {
            $item->reserved_stock = (float) ($item->reserved_stock ?? 0);
            $item->net_available = $item->current_stock - $item->reserved_stock;
            
            // Determine warning status for UI display
            if ($item->net_available < 0) {
                $item->warning_level = 'shortage'; // Red
            } elseif ($item->net_available <= $item->min_stock) {
                $item->warning_level = 'low'; // Yellow
            } else {
                $item->warning_level = 'ok'; // Green
            }
        }

        return view('admin.inventory.index', [
            'inventoryItems' => $inventoryItems,
            'categories' => $categories,
            'currentCategory' => $category,
            'currentStatus' => $status,
            'currentPerishable' => $perishable,
            'totalItemsCount' => $totalItemsCount,
            'inStockCount' => $inStockCount,
            'lowStockCount' => $lowStockCount,
            'shortageCount' => $shortageCount,
        ]);
    }

    /**
     * Show the form for creating a new inventory item.
     */
    public function create(): View
    {
        $inventoryItems = InventoryItem::orderBy('name')->get();
        $inventoryCategories = InventoryItem::select('category')->distinct()->pluck('category');
        return view('admin.inventory.create', [
            'inventoryItems' => $inventoryItems,
            'inventoryCategories' => $inventoryCategories,
        ]);
    }

    /**
     * Store a newly created inventory item.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'is_perishable' => 'boolean',
            'current_stock' => 'required|numeric|min:0',
            'unit_cost' => 'required|numeric|min:0',
            'min_stock' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'substitute_ids' => 'nullable|array',
            'substitute_ids.*' => 'exists:inventory_items,id',
            'image' => 'nullable|image|max:2048',
        ]);

        $validated['is_perishable'] = $request->has('is_perishable');

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('inventory-images', 'public');
        }

        $item = InventoryItem::create($validated);
        
        $prefix = strtoupper(substr($item->category ?? 'INV', 0, 3));
        $code = $prefix . '-' . str_pad($item->id, 4, '0', STR_PAD_LEFT);
        $i = 1;
        while (\App\Models\InventoryItem::where('item_code', $code)->where('id', '!=', $item->id)->exists()) {
            $code = $prefix . '-' . str_pad($item->id, 4, '0', STR_PAD_LEFT) . '-' . $i++;
        }
        $item->update(['item_code' => $code]);
        
        if ((float) $item->current_stock != 0) {
            \App\Models\InventoryTransaction::create([
                'inventory_item_id' => $item->id,
                'booking_id' => null,
                'quantity_change' => (float) $item->current_stock,
                'transaction_type' => 'adjustment',
                'reason' => 'Initial stock on creation',
                'performed_by' => \Illuminate\Support\Facades\Auth::id(),
            ]);
        }

        if (!empty($validated['substitute_ids'])) {
            $item->substitutes()->sync($validated['substitute_ids']);
        }

        return redirect()->route('admin.inventory.index')->with('success', 'Inventory item added successfully.');
    }

    /**
     * Show the form for editing the specified inventory item.
     */
    public function edit(InventoryItem $inventoryItem): View
    {
        $inventoryItems = InventoryItem::where('id', '!=', $inventoryItem->id)->orderBy('name')->get();
        $inventoryCategories = InventoryItem::select('category')->distinct()->pluck('category');
        
        return view('admin.inventory.edit', [
            'inventoryItem' => $inventoryItem,
            'inventoryItems' => $inventoryItems,
            'inventoryCategories' => $inventoryCategories,
        ]);
    }

    /**
     * Update the specified inventory item.
     */
    public function update(Request $request, InventoryItem $inventoryItem)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'is_perishable' => 'boolean',
            'current_stock' => 'required|numeric|min:0',
            'unit_cost' => 'required|numeric|min:0',
            'min_stock' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'substitute_ids' => 'nullable|array',
            'substitute_ids.*' => 'exists:inventory_items,id',
            'image' => 'nullable|image|max:2048',
        ]);

        $validated['is_perishable'] = $request->has('is_perishable');
        
        if ($request->hasFile('image')) {
            if ($inventoryItem->image_path && \Storage::disk('public')->exists($inventoryItem->image_path)) {
                \Storage::disk('public')->delete($inventoryItem->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('inventory-images', 'public');
        }
        
        $oldStock = (float) $inventoryItem->current_stock;

        $inventoryItem->update($validated);
        
        $newStock = (float) $inventoryItem->current_stock;
        
        if ($oldStock !== $newStock) {
            \App\Models\InventoryTransaction::create([
                'inventory_item_id' => $inventoryItem->id,
                'booking_id' => null,
                'quantity_change' => $newStock - $oldStock,
                'transaction_type' => 'adjustment',
                'reason' => 'Admin manual stock update',
                'performed_by' => \Illuminate\Support\Facades\Auth::id(),
            ]);
        }

        $inventoryItem->substitutes()->sync($validated['substitute_ids'] ?? []);

        return redirect()->route('admin.inventory.index')->with('success', 'Inventory item updated successfully.');
    }

    public function archive(InventoryItem $inventoryItem)
    {
        // Check if there are active bookings using this item to prevent breaking historical data
        $activeBookingsCount = \App\Models\Booking::whereDate('event_date', '>=', Carbon::today())
            ->whereNotIn('status', ['cancelled', 'completed', 'declined'])
            ->whereHas('inventoryItems', function($query) use ($inventoryItem) {
                $query->where('inventory_items.id', $inventoryItem->id);
            })->count();

        if ($activeBookingsCount > 0) {
            return redirect()->route('admin.inventory.index')->with('error', 'Cannot archive item: it is reserved for upcoming active bookings.');
        }

        $inventoryItem->delete();

        return redirect()->route('admin.inventory.index')->with('success', 'Inventory item archived successfully.');
    }

    public function archived(): View
    {
        $inventoryItems = InventoryItem::onlyTrashed()->orderBy('deleted_at', 'desc')->get();
        return view('admin.inventory.archived', [
            'inventoryItems' => $inventoryItems,
        ]);
    }

    public function restore($id)
    {
        $item = InventoryItem::withTrashed()->findOrFail($id);
        $item->restore();
        return redirect()->route('admin.inventory.archived')->with('success', 'Inventory item restored successfully.');
    }

    /**
     * Resolve inventory shortage by restocking an item directly from notifications.
     */
    public function resolveShortage(Request $request, InventoryItem $inventoryItem)
    {
        $validated = $request->validate([
            'additional_stock' => ['required', 'numeric', 'min:0'],
            'alert_id' => ['nullable', 'integer', 'exists:admin_alerts,id'],
        ]);

        $stockToAdd = (float) $validated['additional_stock'];
        $wasRestocked = false;
        $isDuplicate = false;

        if ($stockToAdd > 0) {
            try {
                DB::transaction(function () use ($inventoryItem, $stockToAdd, $validated, &$wasRestocked, &$isDuplicate) {
                    $lockedItem = InventoryItem::query()->lockForUpdate()->findOrFail($inventoryItem->id);

                    $performedBy = Auth::id() ?? auth()->id();

                    $isDuplicate = InventoryTransaction::where('inventory_item_id', $lockedItem->id)
                        ->whereNull('booking_id')
                        ->where('transaction_type', 'procurement')
                        ->where('quantity_change', $stockToAdd)
                        ->where('performed_by', $performedBy)
                        ->where('created_at', '>=', now()->subSeconds(10))
                        ->exists();

                    if ($isDuplicate) {
                        return;
                    }

                    $lockedItem->increment('current_stock', $stockToAdd);

                    InventoryTransaction::create([
                        'inventory_item_id' => $lockedItem->id,
                        'booking_id' => null,
                        'quantity_change' => $stockToAdd,
                        'transaction_type' => 'procurement',
                        'reason' => 'Admin inline restock',
                        'performed_by' => $performedBy,
                    ]);

                    if (!empty($validated['alert_id'])) {
                        AdminAlert::where('id', $validated['alert_id'])
                            ->where('is_read', false)
                            ->update(['is_read' => true]);
                    }

                    $wasRestocked = true;
                });
            } catch (\Throwable $e) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to restock inventory: ' . $e->getMessage(),
                    ], 500);
                }

                return redirect()->back()->with('error', 'Failed to restock inventory: ' . $e->getMessage());
            }
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $isDuplicate ? 'Shortage resolution already processed.' : 'Inventory restocked successfully.',
                'current_stock' => (float) $inventoryItem->fresh()->current_stock,
                'is_duplicate' => $isDuplicate,
            ]);
        }

        return redirect()->route('admin.notifications')->with('success', 'Inventory restocked successfully.');
    }
}
