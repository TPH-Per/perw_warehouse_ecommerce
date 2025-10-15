<?php

namespace App\Http\Controllers\Admin;

use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Warehouse;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InventoryAdminController extends AdminController
{
    /**
     * Display a listing of inventory
     */
    public function index(Request $request)
    {
        $query = Inventory::with(['productVariant.product', 'warehouse']);

        // Search by product name or SKU
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('productVariant', function($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                  ->orWhereHas('product', function($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by warehouse
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // Filter by low stock
        if ($request->has('low_stock') && $request->low_stock) {
            $query->where('quantity_on_hand', '<=', DB::raw('reorder_level'));
        }

        // Filter by out of stock
        if ($request->has('out_of_stock') && $request->out_of_stock) {
            $query->where('quantity_on_hand', '<=', 0);
        }

        $inventories = $query->paginate(20);
        $warehouses = Warehouse::all();

        // Return appropriate view based on user role
        $viewPrefix = auth()->user()->role->name === 'Manager' ? 'manager' : 'admin';
        return view("{$viewPrefix}.inventory.index", compact('inventories', 'warehouses'));
    }

    /**
     * Show inventory details
     */
    public function show(Inventory $inventory)
    {
        $inventory->load(['productVariant.product', 'warehouse']);

        // Get recent transactions
        $transactions = InventoryTransaction::where('inventory_id', $inventory->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Return appropriate view based on user role
        $viewPrefix = auth()->user()->role->name === 'Manager' ? 'manager' : 'admin';
        return view("{$viewPrefix}.inventory.show", compact('inventory', 'transactions'));
    }

    /**
     * Adjust inventory quantity
     */
    public function adjust(Request $request, Inventory $inventory)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer',
            'transaction_type' => 'required|in:inbound,outbound,adjustment',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $quantity = $request->quantity;
            $transactionType = $request->transaction_type;

            // Calculate new quantity based on transaction type
            $newQuantity = $inventory->quantity_on_hand;
            if ($transactionType === 'inbound' || ($transactionType === 'adjustment' && $quantity > 0)) {
                $newQuantity += abs($quantity);
            } elseif ($transactionType === 'outbound' || ($transactionType === 'adjustment' && $quantity < 0)) {
                $newQuantity -= abs($quantity);
            }

            // Prevent negative inventory
            if ($newQuantity < 0) {
                return back()->with('error', 'Insufficient inventory. Cannot reduce below zero.');
            }

            // Create transaction record
            $transaction = InventoryTransaction::create([
                'inventory_id' => $inventory->id,
                'transaction_type' => $transactionType,
                'quantity' => $quantity,
                'reference_number' => $request->reference_number,
                'notes' => $request->notes,
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

            // Create outbound transaction for source
            InventoryTransaction::create([
                'inventory_id' => $sourceInventory->id,
                'transaction_type' => 'outbound',
                'quantity' => -$request->quantity,
                'reference_number' => $request->reference_number ?? 'TRANSFER-' . now()->timestamp,
                'notes' => 'Transfer to Warehouse ID: ' . $request->to_warehouse_id . '. ' . ($request->notes ?? ''),
            ]);

            // Create inbound transaction for destination
            InventoryTransaction::create([
                'inventory_id' => $destInventory->id,
                'transaction_type' => 'inbound',
                'quantity' => $request->quantity,
                'reference_number' => $request->reference_number ?? 'TRANSFER-' . now()->timestamp,
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
        $warehouseId = $request->get('warehouse_id');

        $query = Inventory::with(['productVariant.product', 'warehouse'])
            ->whereColumn('quantity_on_hand', '<=', 'reorder_level');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $inventories = $query->orderBy('quantity_on_hand', 'asc')->paginate(20);
        $warehouses = Warehouse::all();

        // Return appropriate view based on user role
        $viewPrefix = auth()->user()->role->name === 'Manager' ? 'manager' : 'admin';
        return view("{$viewPrefix}.inventory.low-stock", compact('inventories', 'warehouses'));
    }

    /**
     * Get inventory statistics
     */
    public function statistics(Request $request)
    {
        $warehouseId = $request->get('warehouse_id');

        $query = Inventory::query();

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $stats = [
            'total_items' => $query->count(),
            'total_quantity' => $query->sum('quantity_on_hand'),
            'total_reserved' => $query->sum('quantity_reserved'),
            'total_available' => $query->sum(DB::raw('quantity_on_hand - quantity_reserved')),
            'low_stock_items' => Inventory::whereColumn('quantity_on_hand', '<=', 'reorder_level')
                ->when($warehouseId, function($q) use ($warehouseId) {
                    $q->where('warehouse_id', $warehouseId);
                })
                ->count(),
            'out_of_stock_items' => Inventory::where('quantity_on_hand', '<=', 0)
                ->when($warehouseId, function($q) use ($warehouseId) {
                    $q->where('warehouse_id', $warehouseId);
                })
                ->count(),
            'warehouses' => Warehouse::withCount('inventories')->get(),
        ];

        return $this->successResponse('Statistics retrieved successfully', $stats);
    }

    /**
     * Get inventory transactions
     */
    public function transactions(Request $request)
    {
        $query = InventoryTransaction::with(['inventory.productVariant.product', 'inventory.warehouse']);

        // Filter by warehouse
        if ($request->has('warehouse_id')) {
            $query->whereHas('inventory', function($q) use ($request) {
                $q->where('warehouse_id', $request->warehouse_id);
            });
        }

        // Filter by transaction type
        if ($request->has('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(50);
        $warehouses = Warehouse::all();

        // Return appropriate view based on user role
        $viewPrefix = auth()->user()->role->name === 'Manager' ? 'manager' : 'admin';
        return view("{$viewPrefix}.inventory.transactions", compact('transactions', 'warehouses'));
    }

    /**
     * Export inventory to CSV
     */
    public function export(Request $request)
    {
        $query = Inventory::with(['productVariant.product', 'warehouse']);

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        $inventories = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="inventory_' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function() use ($inventories) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, ['SKU', 'Product', 'Warehouse', 'On Hand', 'Reserved', 'Available', 'Reorder Level', 'Status']);

            // CSV data
            foreach ($inventories as $inventory) {
                $available = $inventory->quantity_on_hand - $inventory->quantity_reserved;
                $status = $inventory->quantity_on_hand <= 0 ? 'Out of Stock' :
                         ($inventory->quantity_on_hand <= $inventory->reorder_level ? 'Low Stock' : 'In Stock');

                fputcsv($file, [
                    $inventory->productVariant->sku,
                    $inventory->productVariant->product->name . ' - ' . $inventory->productVariant->variant_name,
                    $inventory->warehouse->name,
                    $inventory->quantity_on_hand,
                    $inventory->quantity_reserved,
                    $available,
                    $inventory->reorder_level,
                    $status,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Create inventory for product variant
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_variant_id' => 'required|exists:product_variants,id',
            'quantity_on_hand' => 'required|integer|min:0',
            'reorder_level' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), $validator->errors()->toArray());
        }

        // Check if inventory already exists
        $existing = Inventory::where('warehouse_id', $request->warehouse_id)
            ->where('product_variant_id', $request->product_variant_id)
            ->first();

        if ($existing) {
            return $this->errorResponse('Inventory already exists for this product variant in this warehouse.');
        }

        try {
            DB::beginTransaction();

            $inventory = Inventory::create([
                'warehouse_id' => $request->warehouse_id,
                'product_variant_id' => $request->product_variant_id,
                'quantity_on_hand' => $request->quantity_on_hand,
                'quantity_reserved' => 0,
                'reorder_level' => $request->reorder_level,
            ]);

            // Create initial inbound transaction if quantity > 0
            if ($request->quantity_on_hand > 0) {
                InventoryTransaction::create([
                    'inventory_id' => $inventory->id,
                    'transaction_type' => 'inbound',
                    'quantity' => $request->quantity_on_hand,
                    'reference_number' => 'INITIAL-' . now()->timestamp,
                    'notes' => 'Initial inventory creation',
                ]);
            }

            DB::commit();

            return $this->successResponse('Inventory created successfully!', ['inventory' => $inventory]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to create inventory: ' . $e->getMessage());
        }
    }
}
