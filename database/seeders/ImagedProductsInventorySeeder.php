<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductVariant;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Warehouse;

class ImagedProductsInventorySeeder extends Seeder
{
    /**
     * Ensure inventory and transactions exist in TP.HCM and Ha Noi for variants of products that have images.
     */
    public function run(): void
    {
        $tphcm = Warehouse::where('name', 'Kho TP.HCM')->first();
        $hanoi = Warehouse::where('name', 'Kho Ha Noi')->first();

        if (!$tphcm || !$hanoi) {
            echo "Warehouses not found. Please run WarehouseSeeder first.\n";
            return;
        }

        $variants = ProductVariant::whereHas('product.images')
            ->with('product')
            ->get();

        if ($variants->isEmpty()) {
            echo "No product variants found for products with images.\n";
            return;
        }

        $warehouses = [$tphcm, $hanoi];
        $txnTypes = ['inbound', 'outbound'];

        foreach ($variants as $variant) {
            foreach ($warehouses as $wh) {
                $onHand = rand(40, 200);
                $reserved = rand(0, (int) round($onHand * 0.2));
                $reorder = rand(10, 50);

                Inventory::updateOrCreate(
                    [
                        'product_variant_id' => $variant->id,
                        'warehouse_id' => $wh->id,
                    ],
                    [
                        'quantity_on_hand' => $onHand,
                        'quantity_reserved' => $reserved,
                        'reorder_level' => $reorder,
                    ]
                );

                // Create several transactions to simulate movement
                $existing = InventoryTransaction::where('product_variant_id', $variant->id)
                    ->where('warehouse_id', $wh->id)
                    ->count();

                $target = 4; // total per variant per warehouse
                for ($i = $existing; $i < $target; $i++) {
                    $type = $txnTypes[array_rand($txnTypes)];
                    $qty = $type === 'inbound' ? rand(5, 60) : rand(2, 30);

                    InventoryTransaction::create([
                        'product_variant_id' => $variant->id,
                        'warehouse_id' => $wh->id,
                        'order_id' => null,
                        'type' => $type,
                        'quantity' => $qty,
                        'notes' => 'Auto-seeded txn for imaged product',
                    ]);
                }
            }
        }

        echo "Inventory ensured for imaged products at TP.HCM and Ha Noi.\n";
    }
}

