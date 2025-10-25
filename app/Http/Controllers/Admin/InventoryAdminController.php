<?php

namespace App\Http\Controllers\Admin;

use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class InventoryAdminController extends AdminController
{
    public function __construct(private InventoryService $inventoryService)
    {
    }

    /**
     * Record an inbound receipt for one or many items.
     */
    public function inbound(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'nullable|exists:product_variants,id',
            'items.*.sku' => 'nullable|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string|max:500',
        ], [
            'items.required' => 'Phiếu nhập phải có ít nhất 1 sản phẩm.',
        ]);

        $user = Auth::user();
        if ($user && $user->role->name === 'Inventory Manager' && (int) $user->warehouse_id !== (int) $request->warehouse_id) {
            return back()->with('error', 'Bạn không có quyền nhập kho khác.');
        }

        $items = collect($request->input('items'))
            ->map(function (array $item) {
                $variantId = $item['product_variant_id'] ?? null;
                $sku = $item['sku'] ?? null;

                if (!$variantId && !$sku) {
                    throw ValidationException::withMessages([
                        'items' => 'Mỗi dòng phải chọn sản phẩm hoặc nhập SKU.',
                    ]);
                }

                return [
                    'variant_id' => $variantId,
                    'sku' => $sku,
                    'quantity' => (int) $item['quantity'],
                    'notes' => $item['notes'] ?? null,
                ];
            })
            ->all();

        $rawResults = $this->inventoryService->inboundBatch(
            (int) $request->warehouse_id,
            $items,
            $request->input('notes')
        );

        $variantMap = ProductVariant::query()
            ->with('product')
            ->whereIn('id', collect($rawResults)->pluck('transaction.product_variant_id')->unique())
            ->get()
            ->keyBy('id');

        $results = collect($rawResults)->map(function (array $result) use ($variantMap) {
            $variantId = $result['transaction']['product_variant_id'];
            $variant = $variantMap->get($variantId);

            $result['variant'] = $variant ? [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'name' => $variant->name,
                'product_name' => $variant->product->name ?? '',
                'full_label' => trim(($variant->product->name ?? '') . ($variant->name ? " ({$variant->name})" : '')),
            ] : null;

            return $result;
        })->toArray();

        return back()
            ->with('success', 'Đã ghi nhận phiếu nhập.')
            ->with('inbound_result', $results);
    }

    /**
     * Search product variants for the inbound autocomplete.
     */
    public function searchVariants(Request $request)
    {
        $term = trim((string) $request->input('q', ''));

        $variants = ProductVariant::query()
            ->with('product')
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($search) use ($term) {
                    $search->where('name', 'like', "%{$term}%")
                        ->orWhere('sku', 'like', "%{$term}%")
                        ->orWhereHas('product', function ($subQuery) use ($term) {
                            $subQuery->where('name', 'like', "%{$term}%");
                        });
                });
            })
            ->limit(20)
            ->get()
            ->map(function (ProductVariant $variant) {
                $productName = $variant->product->name ?? '';
                $variantName = $variant->name ? " ({$variant->name})" : '';

                return [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'label' => trim("{$productName}{$variantName}") ?: $variant->sku,
                    'product_name' => $productName,
                    'variant_name' => $variant->name,
                    'price' => $variant->price,
                ];
            });

        return response()->json($variants);
    }

    public function index(Request $request)
    {
        $query = Inventory::with(['productVariant.product', 'warehouse']);

        // Filter by user's assigned warehouse if they are a warehouse-specific manager
        $user = Auth::user();
        if ($user && $user->role->name === 'Inventory Manager' && $user->warehouse_id) {
            $query->where('warehouse_id', $user->warehouse_id);
        }

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('productVariant.product', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })->orWhereHas('warehouse', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        // Filter by warehouse (only for admins)
        if ($request->has('warehouse_id') && (!$user || $user->role->name === 'Admin')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // Filter by low stock
        if ($request->has('low_stock') && $request->low_stock) {
            $query->whereColumn('quantity_on_hand', '<=', 'reorder_level');
        }

        $inventories = $query->paginate(20);
        $warehouses = Warehouse::all();

        // Return appropriate view based on user role
        $viewPrefix = ($user && ($user->role->name === 'Manager' || $user->role->name === 'Inventory Manager')) ? 'manager' : 'admin';
        return view("{$viewPrefix}.inventory.index", compact('inventories', 'warehouses'));
    }

    /**
     * Show the form for editing the specified inventory.
     */
    public function edit(Inventory $inventory)
    {
        $user = Auth::user();

        // Only inventory managers and admins can edit inventory records
        if ($user && $user->role->name !== 'Inventory Manager' && $user->role->name !== 'Admin') {
            return back()->with('error', 'Unauthorized access.');
        }

        // Check if user has access to this inventory
        if ($user && $user->role->name === 'Inventory Manager' && $user->warehouse_id) {
            if ($inventory->warehouse_id !== $user->warehouse_id) {
                return back()->with('error', 'Unauthorized access to this inventory.');
            }
        }

        $inventory->load(['productVariant.product', 'warehouse']);
        $warehouses = Warehouse::all();

        // Return appropriate view based on user role
        $viewPrefix = ($user && ($user->role->name === 'Manager' || $user->role->name === 'Inventory Manager')) ? 'manager' : 'admin';
        return view("{$viewPrefix}.inventory.edit", compact('inventory', 'warehouses'));
    }

    /**
     * Update the specified inventory in storage.
     */
    public function update(Request $request, Inventory $inventory)
    {
        $user = Auth::user();

        // Only inventory managers and admins can update inventory records
        if ($user && $user->role->name !== 'Inventory Manager' && $user->role->name !== 'Admin') {
            return back()->with('error', 'Unauthorized access.');
        }

        // Check if user has access to this inventory
        if ($user && $user->role->name === 'Inventory Manager' && $user->warehouse_id) {
            if ($inventory->warehouse_id !== $user->warehouse_id) {
                return back()->with('error', 'Unauthorized access to this inventory.');
            }
        }

        $validator = Validator::make($request->all(), [
            'quantity_on_hand' => 'required|integer|min:0',
            'quantity_reserved' => 'required|integer|min:0',
            'reorder_level' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Update inventory record
            $inventory->update([
                'quantity_on_hand' => $request->quantity_on_hand,
                'quantity_reserved' => $request->quantity_reserved,
                'reorder_level' => $request->reorder_level,
            ]);

            return redirect()->route('manager.inventory.show', $inventory->id)->with('success', 'Inventory record updated successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update inventory record: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created inventory record
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Only inventory managers and admins can create inventory records
        if ($user && $user->role->name !== 'Inventory Manager' && $user->role->name !== 'Admin') {
            return back()->with('error', 'Unauthorized access.');
        }

        $validator = Validator::make($request->all(), [
            'product_variant_id' => 'required|exists:product_variants,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'quantity_on_hand' => 'required|integer|min:0',
            'quantity_reserved' => 'required|integer|min:0',
            'reorder_level' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Check if user has access to this warehouse
        if ($user && $user->role->name === 'Inventory Manager' && $user->warehouse_id) {
            if ($request->warehouse_id !== $user->warehouse_id) {
                return back()->with('error', 'Unauthorized access to this warehouse.');
            }
        }

        try {
            // Check if inventory record already exists
            $existingInventory = Inventory::where('product_variant_id', $request->product_variant_id)
                ->where('warehouse_id', $request->warehouse_id)
                ->first();

            if ($existingInventory) {
                return back()->with('error', 'Inventory record already exists for this product variant in this warehouse. Please adjust the existing record instead.');
            }

            // Create new inventory record
            $inventory = Inventory::create([
                'product_variant_id' => $request->product_variant_id,
                'warehouse_id' => $request->warehouse_id,
                'quantity_on_hand' => $request->quantity_on_hand,
                'quantity_reserved' => $request->quantity_reserved,
                'reorder_level' => $request->reorder_level,
            ]);

            // Create initial transaction record
            InventoryTransaction::create([
                'product_variant_id' => $inventory->product_variant_id,
                'warehouse_id' => $inventory->warehouse_id,
                'type' => 'inbound',
                'quantity' => $inventory->quantity_on_hand,
                'notes' => 'Initial inventory creation',
            ]);

            return back()->with('success', 'Inventory record created successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create inventory record: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified inventory
     */
    public function show(Inventory $inventory)
    {
        // Check if user has access to this inventory
        $user = Auth::user();
        if ($user && $user->role->name === 'Inventory Manager' && $user->warehouse_id) {
            if ($inventory->warehouse_id !== $user->warehouse_id) {
                abort(403, 'Unauthorized access to this inventory.');
            }
        }

        $inventory->load(['productVariant.product', 'warehouse']);

        // Get recent transactions using product_variant_id and warehouse_id
        $transactions = InventoryTransaction::where('product_variant_id', $inventory->product_variant_id)
            ->where('warehouse_id', $inventory->warehouse_id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Return appropriate view based on user role
        $viewPrefix = ($user && ($user->role->name === 'Manager' || $user->role->name === 'Inventory Manager')) ? 'manager' : 'admin';
        return view("{$viewPrefix}.inventory.show", compact('inventory', 'transactions'));
    }

    /**
     * Adjust inventory quantity
     */
    public function adjust(Request $request, Inventory $inventory)
    {
        // Check if user has access to this inventory
        $user = Auth::user();
        if ($user && $user->role->name === 'Inventory Manager' && $user->warehouse_id) {
            if ($inventory->warehouse_id !== $user->warehouse_id) {
                return back()->with('error', 'Unauthorized access to this inventory.');
            }
        }

        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer',
            'type' => 'required|in:inbound,outbound,adjustment',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $quantity = (int) $request->quantity;
        $transactionType = $request->type;
        $note = $request->input('notes');
        if ($request->filled('reference_number')) {
            $referenceSuffix = 'Ref: ' . $request->reference_number;
            $note = $note ? "{$note} ({$referenceSuffix})" : $referenceSuffix;
        }

        if ($transactionType === 'inbound') {
            try {
                $this->inventoryService->inbound(
                    $inventory->warehouse_id,
                    $inventory->product_variant_id,
                    abs($quantity),
                    $note
                );

                return back()->with('success', 'Inventory adjusted successfully!');
            } catch (\Throwable $e) {
                return back()->with('error', 'Failed to adjust inventory: ' . $e->getMessage())->withInput();
            }
        }

        try {
            DB::beginTransaction();

            // Calculate new quantity based on transaction type
            $newQuantity = $inventory->quantity_on_hand;
            if ($transactionType === 'adjustment' && $quantity > 0) {
                $newQuantity += abs($quantity);
            } elseif ($transactionType === 'outbound' || ($transactionType === 'adjustment' && $quantity < 0)) {
                $newQuantity -= abs($quantity);
            }

            // Prevent negative inventory
            if ($newQuantity < 0) {
                return back()->with('error', 'Insufficient inventory. Cannot reduce below zero.');
            }

            // Create transaction record using product_variant_id and warehouse_id
            $transactionQuantity = abs($quantity);
            if ($transactionType === 'outbound' || ($transactionType === 'adjustment' && $quantity < 0)) {
                $transactionQuantity = -abs($quantity);
            }

            $transaction = InventoryTransaction::create([
                'product_variant_id' => $inventory->product_variant_id,
                'warehouse_id' => $inventory->warehouse_id,
                'type' => $transactionType,
                'quantity' => $transactionQuantity,
                'notes' => $note,
            ]);

            // Update inventory
            $inventory->update(['quantity_on_hand' => $newQuantity]);

            DB::commit();

            return back()->with('success', 'Inventory adjusted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to adjust inventory: ' . $e->getMessage());
        }
    }

    /**
     * Transfer inventory between warehouses
     */
    public function transfer(Request $request)
    {
        $user = Auth::user();

        // Only admins can transfer inventory between warehouses
        if ($user && $user->role->name !== 'Admin') {
            return back()->with('error', 'Only administrators can transfer inventory between warehouses.');
        }

        $validator = Validator::make($request->all(), [
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'product_variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            // Get source inventory
            $sourceInventory = Inventory::where('warehouse_id', $request->from_warehouse_id)
                ->where('product_variant_id', $request->product_variant_id)
                ->first();

            if (!$sourceInventory) {
                return back()->with('error', 'Source inventory not found.');
            }

            // Check if user has access to source inventory (for warehouse-specific managers)
            if ($user && $user->role->name === 'Inventory Manager' && $user->warehouse_id) {
                if ($sourceInventory->warehouse_id !== $user->warehouse_id) {
                    return back()->with('error', 'Unauthorized access to source inventory.');
                }
            }

            if ($sourceInventory->quantity_on_hand < $request->quantity) {
                return back()->with('error', 'Insufficient inventory in source warehouse.');
            }

            // Get or create destination inventory
            $destInventory = Inventory::firstOrCreate(
                [
                    'warehouse_id' => $request->to_warehouse_id,
                    'product_variant_id' => $request->product_variant_id,
                ],
                [
                    'quantity_on_hand' => 0,
                    'quantity_reserved' => 0,
                    'reorder_level' => 10,
                ]
            );

            // Create outbound transaction for source using product_variant_id and warehouse_id
            InventoryTransaction::create([
                'product_variant_id' => $sourceInventory->product_variant_id,
                'warehouse_id' => $sourceInventory->warehouse_id,
                'type' => 'outbound',
                'quantity' => -$request->quantity,
                'notes' => 'Transfer to Warehouse ID: ' . $request->to_warehouse_id . '. ' . ($request->notes ?? ''),
            ]);

            // Create inbound transaction for destination using product_variant_id and warehouse_id
            InventoryTransaction::create([
                'product_variant_id' => $destInventory->product_variant_id,
                'warehouse_id' => $destInventory->warehouse_id,
                'type' => 'inbound',
                'quantity' => $request->quantity,
                'notes' => 'Transfer from Warehouse ID: ' . $request->from_warehouse_id . '. ' . ($request->notes ?? ''),
            ]);

            // Update inventory quantities
            $sourceInventory->decrement('quantity_on_hand', $request->quantity);
            $destInventory->increment('quantity_on_hand', $request->quantity);

            DB::commit();

            return back()->with('success', 'Inventory transferred successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to transfer inventory: ' . $e->getMessage());
        }
    }

    /**
     * Set reorder level for inventory
     */
    public function setReorderLevel(Request $request, Inventory $inventory)
    {
        // Check if user has access to this inventory
        $user = Auth::user();
        if ($user && $user->role->name === 'Inventory Manager' && $user->warehouse_id) {
            if ($inventory->warehouse_id !== $user->warehouse_id) {
                return $this->errorResponse('Unauthorized access to this inventory.');
            }
        }

        $validator = Validator::make($request->all(), [
            'reorder_level' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), $validator->errors()->toArray());
        }

        try {
            $inventory->update(['reorder_level' => $request->reorder_level]);

            return $this->successResponse('Reorder level updated successfully!', ['inventory' => $inventory]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update reorder level: ' . $e->getMessage());
        }
    }

    /**
     * Get low stock items
     */
    public function lowStock(Request $request)
    {
        $query = Inventory::with(['productVariant.product', 'warehouse'])
            ->whereColumn('quantity_on_hand', '<=', 'reorder_level');

        // Filter by user's assigned warehouse if they are a warehouse-specific manager
        $user = Auth::user();
        if ($user && $user->role->name === 'Inventory Manager' && $user->warehouse_id) {
            $query->where('warehouse_id', $user->warehouse_id);
        } else if ($request->has('warehouse_id')) {
            // Filter by warehouse (only for admins or when no specific warehouse is assigned)
            $query->where('warehouse_id', $request->warehouse_id);
        }

        $inventories = $query->orderBy('quantity_on_hand', 'asc')->paginate(20);
        $warehouses = Warehouse::all();

        // Return appropriate view based on user role
        $viewPrefix = ($user && ($user->role->name === 'Manager' || $user->role->name === 'Inventory Manager')) ? 'manager' : 'admin';
        return view("{$viewPrefix}.inventory.low-stock", compact('inventories', 'warehouses'));
    }

    /**
     * Get inventory statistics
     */
    public function statistics(Request $request)
    {
        // Filter by user's assigned warehouse if they are a warehouse-specific manager
        $user = Auth::user();
        $warehouseId = null;

        if ($user && $user->role->name === 'Inventory Manager' && $user->warehouse_id) {
            $warehouseId = $user->warehouse_id;
        } else if ($request->has('warehouse_id')) {
            $warehouseId = $request->warehouse_id;
        }

        $query = Inventory::query();

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $stats = [
            'total_items' => $query->count(),
            'low_stock_items' => $query->clone()->whereColumn('quantity_on_hand', '<=', 'reorder_level')->count(),
            'out_of_stock_items' => $query->clone()->where('quantity_on_hand', 0)->count(),
            'total_value' => $query->clone()->sum(DB::raw('quantity_on_hand * reorder_level')),
        ];

        // Return appropriate view based on user role
        $viewPrefix = ($user && ($user->role->name === 'Manager' || $user->role->name === 'Inventory Manager')) ? 'manager' : 'admin';
        return view("{$viewPrefix}.inventory.statistics", compact('stats'));
    }

    /**
     * Get inventory transactions
     */
    public function transactions(Request $request)
    {
        $query = InventoryTransaction::with(['productVariant.product', 'warehouse', 'order']);

        // Filter by user's assigned warehouse if they are a warehouse-specific manager
        $user = Auth::user();
        if ($user && $user->role->name === 'Inventory Manager' && $user->warehouse_id) {
            $query->where('warehouse_id', $user->warehouse_id);
        } else if ($request->has('warehouse_id')) {
            // Filter by warehouse (only for admins or when no specific warehouse is assigned)
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // Filter by product
        if ($request->has('product_id')) {
            $query->whereHas('productVariant', function($q) use ($request) {
                $q->where('product_id', $request->product_id);
            });
        }

        // Filter by transaction type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('created_at', '<=', $request->end_date);
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(20);
        $warehouses = Warehouse::all();
        $products = ProductVariant::with('product')->get()->pluck('product.name', 'product.id')->unique();

        // Return appropriate view based on user role
        $viewPrefix = ($user && ($user->role->name === 'Manager' || $user->role->name === 'Inventory Manager')) ? 'manager' : 'admin';
        return view("{$viewPrefix}.inventory.transactions", compact('transactions', 'warehouses', 'products'));
    }

    /**
     * Export inventory data to CSV
     */
    public function export(Request $request)
    {
        $query = Inventory::with(['productVariant.product', 'warehouse']);

        // Filter by user's assigned warehouse if they are a warehouse-specific manager
        $user = Auth::user();
        if ($user && $user->role->name === 'Inventory Manager' && $user->warehouse_id) {
            $query->where('warehouse_id', $user->warehouse_id);
        } else if ($request->has('warehouse_id')) {
            // Filter by warehouse (only for admins or when no specific warehouse is assigned)
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('low_stock') && $request->low_stock) {
            $query->whereColumn('quantity_on_hand', '<=', 'reorder_level');
        }

        $inventories = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="inventory_' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function() use ($inventories) {
            $file = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($file, ['Product', 'Variant', 'Warehouse', 'Quantity On Hand', 'Quantity Reserved', 'Reorder Level']);

            // Add data
            foreach ($inventories as $inventory) {
                fputcsv($file, [
                    $inventory->productVariant->product->name,
                    $inventory->productVariant->name,
                    $inventory->warehouse->name,
                    $inventory->quantity_on_hand,
                    $inventory->quantity_reserved,
                    $inventory->reorder_level,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Remove the specified inventory record from storage.
     */
    public function destroy(Inventory $inventory)
    {
        $user = Auth::user();

        // Only inventory managers and admins can delete inventory records
        if ($user && $user->role->name !== 'Inventory Manager' && $user->role->name !== 'Admin') {
            return back()->with('error', 'Unauthorized access.');
        }

        // Check if user has access to this inventory
        if ($user && $user->role->name === 'Inventory Manager' && $user->warehouse_id) {
            if ($inventory->warehouse_id !== $user->warehouse_id) {
                return back()->with('error', 'Unauthorized access to this inventory.');
            }
        }

        try {
            // Check if there are any transactions for this inventory
            $transactionCount = InventoryTransaction::where('product_variant_id', $inventory->product_variant_id)
                ->where('warehouse_id', $inventory->warehouse_id)
                ->count();

            if ($transactionCount > 0) {
                return back()->with('error', 'Cannot delete inventory record with existing transactions. You can set quantity to zero instead.');
            }

            // Delete the inventory record
            $inventory->delete();

            return back()->with('success', 'Inventory record deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete inventory record: ' . $e->getMessage());
        }
    }
}
