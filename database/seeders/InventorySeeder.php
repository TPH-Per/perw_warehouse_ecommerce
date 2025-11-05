<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\Warehouse;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouseNames = ['Kho TP.HCM', 'Kho Ha Noi'];
        $warehouses = Warehouse::whereIn('name', $warehouseNames)->get()->keyBy('name');

        if ($warehouses->count() !== count($warehouseNames)) {
            echo "Warehouses not found. Please run WarehouseSeeder first.\n";
            return;
        }

        $warehouseIds = $warehouses->pluck('id')->all();

        Inventory::withTrashed()
            ->whereNotIn('warehouse_id', $warehouseIds)
            ->forceDelete();

        InventoryTransaction::withTrashed()
            ->whereNotIn('warehouse_id', $warehouseIds)
            ->forceDelete();

        $productVariants = ProductVariant::select('id', 'name', 'sku')->get();

        if ($productVariants->isEmpty()) {
            echo "No product variants found. Please run AllModelsSeeder first.\n";
            return;
        }

        foreach ($productVariants as $variant) {
            foreach ($warehouses as $warehouse) {
                $quantityOnHand = rand(60, 500);
                $quantityReserved = rand(0, (int) round($quantityOnHand * 0.3));
                $reorderLevel = rand(20, 80);

                Inventory::updateOrCreate(
                    [
                        'product_variant_id' => $variant->id,
                        'warehouse_id' => $warehouse->id,
                    ],
                    [
                        'quantity_on_hand' => $quantityOnHand,
                        'quantity_reserved' => min($quantityReserved, $quantityOnHand),
                        'reorder_level' => $reorderLevel,
                    ]
                );
            }
        }

        $variantIdList = $productVariants->pluck('id')->all();
        $warehouseIdList = array_values($warehouseIds);
        $orderIdList = PurchaseOrder::pluck('id')->all();
        $transactionTypes = ['inbound', 'outbound'];

        InventoryTransaction::factory()
            ->count(40)
            ->state(function () use ($variantIdList, $warehouseIdList, $orderIdList, $transactionTypes) {
                $type = Arr::random($transactionTypes);
                $orderId = null;

                if ($type === 'outbound' && !empty($orderIdList)) {
                    $orderId = Arr::random($orderIdList);
                }

                return [
                    'product_variant_id' => Arr::random($variantIdList),
                    'warehouse_id' => Arr::random($warehouseIdList),
                    'order_id' => $orderId,
                    'quantity' => $type === 'inbound' ? rand(20, 150) : rand(5, 80),
                    'type' => $type,
                    'notes' => sprintf('Factory seeded %s transaction for warehouse stock', $type),
                ];
            })
            ->create();

        InventoryTransaction::factory()->count(40)->create();

        echo "Inventory seeded for Kho TP.HCM and Kho Ha Noi with 80 inventory transactions.\n";
    }
}
