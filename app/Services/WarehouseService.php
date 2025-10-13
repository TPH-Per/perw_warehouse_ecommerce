<?php

namespace App\Services;

use App\Models\Warehouse;
use App\Models\Inventory;
use Illuminate\Database\Eloquent\Collection;

class WarehouseService
{
    /**
     * Get all warehouses
     */
    public function getWarehouses(): Collection
    {
        return Warehouse::withCount('inventories')
                       ->orderBy('name')
                       ->get();
    }

    /**
     * Get warehouse by ID
     */
    public function getWarehouseById(int $id): Warehouse
    {
        return Warehouse::with(['inventories.productVariant.product'])
                       ->findOrFail($id);
    }

    /**
     * Create a new warehouse
     */
    public function createWarehouse(array $data): Warehouse
    {
        return Warehouse::create($data);
    }

    /**
     * Update an existing warehouse
     */
    public function updateWarehouse(Warehouse $warehouse, array $data): Warehouse
    {
        $warehouse->update($data);
        return $warehouse->fresh();
    }

    /**
     * Delete a warehouse
     */
    public function deleteWarehouse(Warehouse $warehouse): bool
    {
        // Check if warehouse can be deleted
        $canDelete = $this->canDeleteWarehouse($warehouse);
        
        if (!$canDelete['can_delete']) {
            throw new \Exception($canDelete['reason']);
        }

        return $warehouse->delete();
    }

    /**
     * Get warehouse inventory summary
     */
    public function getWarehouseInventorySummary(int $warehouseId): array
    {
        $warehouse = Warehouse::findOrFail($warehouseId);
        
        $inventories = Inventory::where('warehouse_id', $warehouseId)
                               ->with(['productVariant.product'])
                               ->get();

        $totalProducts = $inventories->count();
        $totalStock = $inventories->sum('quantity_on_hand');
        $totalReserved = $inventories->sum('quantity_reserved');
        $lowStockItems = $inventories->where('quantity_on_hand', '<=', 10)->count();

        $topProducts = $inventories->sortByDesc('quantity_on_hand')
                                 ->take(10)
                                 ->values();

        return [
            'warehouse' => $warehouse,
            'total_products' => $totalProducts,
            'total_stock' => $totalStock,
            'total_reserved' => $totalReserved,
            'low_stock_items' => $lowStockItems,
            'top_products' => $topProducts,
        ];
    }

    /**
     * Get warehouse capacity and utilization
     */
    public function getWarehouseCapacity(int $warehouseId): array
    {
        $warehouse = Warehouse::findOrFail($warehouseId);
        
        // This is a simplified calculation
        // In a real system, you might have actual capacity data
        $totalItems = Inventory::where('warehouse_id', $warehouseId)
                              ->sum('quantity_on_hand');
        
        // Assume each warehouse has a capacity of 10,000 items for demo
        $maxCapacity = 10000;
        $utilizationPercentage = $maxCapacity > 0 ? ($totalItems / $maxCapacity) * 100 : 0;

        return [
            'warehouse' => $warehouse,
            'current_items' => $totalItems,
            'max_capacity' => $maxCapacity,
            'utilization_percentage' => round($utilizationPercentage, 2),
            'available_capacity' => $maxCapacity - $totalItems,
        ];
    }

    /**
     * Get all warehouses for dropdown/select
     */
    public function getAllWarehousesForSelect(): Collection
    {
        return Warehouse::select('id', 'name', 'location')
                       ->orderBy('name')
                       ->get();
    }

    /**
     * Search warehouses
     */
    public function searchWarehouses(string $query): Collection
    {
        return Warehouse::where('name', 'like', "%{$query}%")
                       ->orWhere('location', 'like', "%{$query}%")
                       ->withCount('inventories')
                       ->orderBy('name')
                       ->get();
    }

    /**
     * Get warehouse statistics
     */
    public function getWarehouseStatistics(): array
    {
        $totalWarehouses = Warehouse::count();
        $totalInventoryItems = Inventory::sum('quantity_on_hand');
        $averageItemsPerWarehouse = $totalWarehouses > 0 ? $totalInventoryItems / $totalWarehouses : 0;

        $warehousesByCapacity = Warehouse::withCount('inventories')
                                       ->orderBy('inventories_count', 'desc')
                                       ->get();

        $lowStockWarehouses = Warehouse::whereHas('inventories', function ($query) {
                                        $query->where('quantity_on_hand', '<=', 10);
                                    })
                                    ->withCount(['inventories' => function ($query) {
                                        $query->where('quantity_on_hand', '<=', 10);
                                    }])
                                    ->get();

        return [
            'total_warehouses' => $totalWarehouses,
            'total_inventory_items' => $totalInventoryItems,
            'average_items_per_warehouse' => round($averageItemsPerWarehouse, 2),
            'warehouses_by_capacity' => $warehousesByCapacity,
            'low_stock_warehouses' => $lowStockWarehouses,
        ];
    }

    /**
     * Check if warehouse can be deleted
     */
    public function canDeleteWarehouse(Warehouse $warehouse): array
    {
        // Check if warehouse has inventory
        if ($warehouse->inventories()->exists()) {
            return [
                'can_delete' => false,
                'reason' => 'Cannot delete warehouse with inventory. This warehouse contains inventory items and cannot be deleted.'
            ];
        }

        return [
            'can_delete' => true,
            'reason' => null
        ];
    }

    /**
     * Transfer inventory between warehouses
     */
    public function transferInventory(int $fromWarehouseId, int $toWarehouseId, int $productVariantId, int $quantity): array
    {
        $fromWarehouse = Warehouse::findOrFail($fromWarehouseId);
        $toWarehouse = Warehouse::findOrFail($toWarehouseId);

        // Check source inventory
        $sourceInventory = Inventory::where('warehouse_id', $fromWarehouseId)
                                  ->where('product_variant_id', $productVariantId)
                                  ->first();

        if (!$sourceInventory || $sourceInventory->quantity_on_hand < $quantity) {
            throw new \Exception('Insufficient inventory in source warehouse.');
        }

        // Get or create destination inventory
        $destinationInventory = Inventory::firstOrCreate([
            'warehouse_id' => $toWarehouseId,
            'product_variant_id' => $productVariantId,
        ], [
            'quantity_on_hand' => 0,
            'quantity_reserved' => 0,
        ]);

        // Perform transfer
        $sourceInventory->decrement('quantity_on_hand', $quantity);
        $destinationInventory->increment('quantity_on_hand', $quantity);

        return [
            'from_warehouse' => $fromWarehouse,
            'to_warehouse' => $toWarehouse,
            'quantity_transferred' => $quantity,
            'source_remaining' => $sourceInventory->fresh()->quantity_on_hand,
            'destination_total' => $destinationInventory->fresh()->quantity_on_hand,
        ];
    }

    /**
     * Get warehouse performance metrics
     */
    public function getWarehousePerformance(): array
    {
        $warehouses = Warehouse::with(['inventories'])
                              ->get()
                              ->map(function ($warehouse) {
                                  $totalStock = $warehouse->inventories->sum('quantity_on_hand');
                                  $totalReserved = $warehouse->inventories->sum('quantity_reserved');
                                  $lowStockItems = $warehouse->inventories->where('quantity_on_hand', '<=', 10)->count();
                                  
                                  return [
                                      'id' => $warehouse->id,
                                      'name' => $warehouse->name,
                                      'location' => $warehouse->location,
                                      'total_stock' => $totalStock,
                                      'total_reserved' => $totalReserved,
                                      'low_stock_items' => $lowStockItems,
                                      'total_products' => $warehouse->inventories->count(),
                                  ];
                              });

        return [
            'warehouses' => $warehouses,
            'total_stock_all_warehouses' => $warehouses->sum('total_stock'),
            'total_reserved_all_warehouses' => $warehouses->sum('total_reserved'),
            'total_low_stock_items' => $warehouses->sum('low_stock_items'),
        ];
    }

    /**
     * Get low stock alerts for warehouse
     */
    public function getLowStockAlerts(int $warehouseId, int $threshold = 10): Collection
    {
        return Inventory::where('warehouse_id', $warehouseId)
                       ->where('quantity_on_hand', '<=', $threshold)
                       ->with(['productVariant.product'])
                       ->orderBy('quantity_on_hand')
                       ->get();
    }

    /**
     * Bulk update warehouse information
     */
    public function bulkUpdateWarehouses(array $warehouseIds, array $data): int
    {
        return Warehouse::whereIn('id', $warehouseIds)->update($data);
    }
}