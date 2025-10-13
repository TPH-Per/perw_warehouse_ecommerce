<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Warehouse;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class InventoryService
{
    /**
     * Get authenticated user ID (for audit trails)
     */
    private function getAuthenticatedUserId(): ?int
    {
        return Auth::id();
    }

    /**
     * Get inventory levels with filters and pagination
     */
    public function getInventoryLevels(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Inventory::with([
            'productVariant.product:id,name,slug,status',
            'warehouse:id,name,location'
        ]);

        // Apply filters
        if (isset($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (isset($filters['product_id'])) {
            $query->whereHas('productVariant', function ($q) use ($filters) {
                $q->where('product_id', $filters['product_id']);
            });
        }

        if (isset($filters['low_stock']) && $filters['low_stock']) {
            $query->where('quantity_on_hand', '<=', 10);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('productVariant', function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                  ->orWhereHas('product', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Sort options
        $sortBy = $filters['sort_by'] ?? 'updated_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Get inventory for a specific product variant
     */
    public function getProductVariantInventory(int $productVariantId): array
    {
        $inventories = Inventory::where('product_variant_id', $productVariantId)
                              ->with(['warehouse:id,name,location'])
                              ->get();

        $productVariant = ProductVariant::with('product:id,name,slug')
                                       ->findOrFail($productVariantId);

        $totalStock = $inventories->sum('quantity_on_hand');
        $totalReserved = $inventories->sum('quantity_reserved');
        $availableStock = $totalStock - $totalReserved;

        return [
            'product_variant' => $productVariant,
            'inventories' => $inventories,
            'summary' => [
                'total_stock' => $totalStock,
                'total_reserved' => $totalReserved,
                'available_stock' => $availableStock,
            ]
        ];
    }

    /**
     * Adjust inventory levels
     */
    public function adjustInventory(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $inventory = Inventory::firstOrCreate(
                [
                    'product_variant_id' => $data['product_variant_id'],
                    'warehouse_id' => $data['warehouse_id'],
                ],
                [
                    'quantity_on_hand' => 0,
                    'quantity_reserved' => 0,
                ]
            );

            $oldQuantity = $inventory->quantity_on_hand;
            $newQuantity = $this->calculateNewQuantity($oldQuantity, $data['adjustment_type'], $data['quantity']);

            $inventory->update(['quantity_on_hand' => $newQuantity]);

            // Record transaction
            $transaction = InventoryTransaction::create([
                'product_variant_id' => $data['product_variant_id'],
                'warehouse_id' => $data['warehouse_id'],
                'transaction_type' => 'adjustment',
                'quantity_change' => $newQuantity - $oldQuantity,
                'quantity_after' => $newQuantity,
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $this->getAuthenticatedUserId(),
            ]);

            return [
                'inventory' => $inventory->load(['productVariant.product', 'warehouse']),
                'transaction' => [
                    'old_quantity' => $oldQuantity,
                    'new_quantity' => $newQuantity,
                    'change' => $newQuantity - $oldQuantity,
                ]
            ];
        });
    }

    /**
     * Process inbound inventory (receiving stock)
     */
    public function processInbound(array $items, ?string $supplierReference = null, ?string $notes = null): array
    {
        return DB::transaction(function () use ($items, $supplierReference, $notes) {
            $processedItems = [];

            foreach ($items as $item) {
                $inventory = Inventory::firstOrCreate(
                    [
                        'product_variant_id' => $item['product_variant_id'],
                        'warehouse_id' => $item['warehouse_id'],
                    ],
                    [
                        'quantity_on_hand' => 0,
                        'quantity_reserved' => 0,
                    ]
                );

                $oldQuantity = $inventory->quantity_on_hand;
                $newQuantity = $oldQuantity + $item['quantity'];
                
                $inventory->update(['quantity_on_hand' => $newQuantity]);

                // Record transaction
                InventoryTransaction::create([
                    'product_variant_id' => $item['product_variant_id'],
                    'warehouse_id' => $item['warehouse_id'],
                    'transaction_type' => 'inbound',
                    'quantity_change' => $item['quantity'],
                    'quantity_after' => $newQuantity,
                    'reason' => 'Stock receiving',
                    'notes' => $notes,
                    'reference_number' => $supplierReference,
                    'created_by' => $this->getAuthenticatedUserId(),
                ]);

                $processedItems[] = [
                    'product_variant_id' => $item['product_variant_id'],
                    'warehouse_id' => $item['warehouse_id'],
                    'quantity_received' => $item['quantity'],
                    'old_quantity' => $oldQuantity,
                    'new_quantity' => $newQuantity,
                ];
            }

            return $processedItems;
        });
    }

    /**
     * Reserve inventory for orders
     */
    public function reserveInventory(int $productVariantId, int $warehouseId, int $quantity): bool
    {
        return DB::transaction(function () use ($productVariantId, $warehouseId, $quantity) {
            $inventory = Inventory::where('product_variant_id', $productVariantId)
                                 ->where('warehouse_id', $warehouseId)
                                 ->lockForUpdate()
                                 ->first();

            if (!$inventory || ($inventory->quantity_on_hand - $inventory->quantity_reserved) < $quantity) {
                return false;
            }

            $inventory->update([
                'quantity_reserved' => $inventory->quantity_reserved + $quantity
            ]);

            // Record reservation transaction
            InventoryTransaction::create([
                'product_variant_id' => $productVariantId,
                'warehouse_id' => $warehouseId,
                'transaction_type' => 'reserved',
                'quantity_change' => -$quantity,
                'quantity_after' => $inventory->quantity_on_hand - $inventory->quantity_reserved,
                'reason' => 'Order reservation',
                'created_by' => $this->getAuthenticatedUserId(),
            ]);

            return true;
        });
    }

    /**
     * Release reserved inventory
     */
    public function releaseReservedInventory(int $productVariantId, int $warehouseId, int $quantity): bool
    {
        return DB::transaction(function () use ($productVariantId, $warehouseId, $quantity) {
            $inventory = Inventory::where('product_variant_id', $productVariantId)
                                 ->where('warehouse_id', $warehouseId)
                                 ->lockForUpdate()
                                 ->first();

            if (!$inventory || $inventory->quantity_reserved < $quantity) {
                return false;
            }

            $inventory->update([
                'quantity_reserved' => $inventory->quantity_reserved - $quantity
            ]);

            // Record release transaction
            InventoryTransaction::create([
                'product_variant_id' => $productVariantId,
                'warehouse_id' => $warehouseId,
                'transaction_type' => 'released',
                'quantity_change' => $quantity,
                'quantity_after' => $inventory->quantity_on_hand - $inventory->quantity_reserved,
                'reason' => 'Reservation released',
                'created_by' => $this->getAuthenticatedUserId(),
            ]);

            return true;
        });
    }

    /**
     * Complete inventory fulfillment (reduce actual stock)
     */
    public function fulfillInventory(int $productVariantId, int $warehouseId, int $quantity): bool
    {
        return DB::transaction(function () use ($productVariantId, $warehouseId, $quantity) {
            $inventory = Inventory::where('product_variant_id', $productVariantId)
                                 ->where('warehouse_id', $warehouseId)
                                 ->lockForUpdate()
                                 ->first();

            if (!$inventory || $inventory->quantity_reserved < $quantity || $inventory->quantity_on_hand < $quantity) {
                return false;
            }

            $inventory->update([
                'quantity_on_hand' => $inventory->quantity_on_hand - $quantity,
                'quantity_reserved' => $inventory->quantity_reserved - $quantity
            ]);

            // Record fulfillment transaction
            InventoryTransaction::create([
                'product_variant_id' => $productVariantId,
                'warehouse_id' => $warehouseId,
                'transaction_type' => 'outbound',
                'quantity_change' => -$quantity,
                'quantity_after' => $inventory->quantity_on_hand,
                'reason' => 'Order fulfillment',
                'created_by' => $this->getAuthenticatedUserId(),
            ]);

            return true;
        });
    }

    /**
     * Get inventory statistics
     */
    public function getStatistics(?int $warehouseId = null): array
    {
        $query = Inventory::query();
        
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $stats = $query->selectRaw('
            COUNT(*) as total_items,
            SUM(quantity_on_hand) as total_stock,
            SUM(quantity_reserved) as total_reserved,
            SUM(CASE WHEN quantity_on_hand <= 10 THEN 1 ELSE 0 END) as low_stock_items,
            SUM(CASE WHEN quantity_on_hand = 0 THEN 1 ELSE 0 END) as out_of_stock_items
        ')->first();

        $warehouseStats = Inventory::with('warehouse:id,name')
                                 ->selectRaw('
                                     warehouse_id,
                                     COUNT(*) as item_count,
                                     SUM(quantity_on_hand) as total_stock
                                 ')
                                 ->groupBy('warehouse_id')
                                 ->get();

        return [
            'overall_statistics' => [
                'total_items' => $stats->total_items ?? 0,
                'total_stock' => $stats->total_stock ?? 0,
                'total_reserved' => $stats->total_reserved ?? 0,
                'available_stock' => ($stats->total_stock ?? 0) - ($stats->total_reserved ?? 0),
                'low_stock_items' => $stats->low_stock_items ?? 0,
                'out_of_stock_items' => $stats->out_of_stock_items ?? 0,
            ],
            'warehouse_statistics' => $warehouseStats,
        ];
    }

    /**
     * Get warehouses list
     */
    public function getWarehouses(): Collection
    {
        return Warehouse::withCount('inventories')
                       ->orderBy('name')
                       ->get();
    }

    /**
     * Check stock availability
     */
    public function checkStockAvailability(int $productVariantId, int $quantity, ?int $warehouseId = null): array
    {
        $query = Inventory::where('product_variant_id', $productVariantId);
        
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $inventories = $query->with('warehouse:id,name')->get();
        
        $totalAvailable = $inventories->sum(function ($inventory) {
            return $inventory->quantity_on_hand - $inventory->quantity_reserved;
        });

        $isAvailable = $totalAvailable >= $quantity;
        
        $warehouses = $inventories->map(function ($inventory) {
            return [
                'warehouse_id' => $inventory->warehouse_id,
                'warehouse_name' => $inventory->warehouse->name,
                'available_quantity' => $inventory->quantity_on_hand - $inventory->quantity_reserved,
            ];
        });

        return [
            'is_available' => $isAvailable,
            'total_available' => $totalAvailable,
            'requested_quantity' => $quantity,
            'warehouses' => $warehouses,
        ];
    }

    /**
     * Calculate new quantity based on adjustment type
     */
    private function calculateNewQuantity(int $currentQuantity, string $adjustmentType, int $quantity): int
    {
        return match ($adjustmentType) {
            'addition' => $currentQuantity + $quantity,
            'subtraction' => max(0, $currentQuantity - $quantity),
            'set' => $quantity,
            default => $currentQuantity,
        };
    }
}