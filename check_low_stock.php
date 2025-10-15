<?php

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Inventory;

try {
    // Get low stock items (same query as in DashboardController)
    $lowStockItems = Inventory::with(['productVariant.product', 'warehouse'])
        ->whereColumn('quantity_on_hand', '<=', 'reorder_level')
        ->orderBy('quantity_on_hand', 'asc')
        ->limit(10)
        ->get();

    echo "Low stock items count: " . $lowStockItems->count() . "\n";

    foreach ($lowStockItems as $index => $item) {
        echo "Item #" . ($index + 1) . "\n";
        echo "Item ID: " . ($item->id ?? 'NULL') . "\n";
        echo "Product: " . ($item->productVariant->product->name ?? 'N/A') . "\n";
        echo "SKU: " . ($item->productVariant->sku ?? 'N/A') . "\n";
        echo "Warehouse: " . ($item->warehouse->name ?? 'N/A') . "\n";
        echo "Quantity on hand: " . ($item->quantity_on_hand ?? 'NULL') . "\n";
        echo "Reorder level: " . ($item->reorder_level ?? 'NULL') . "\n";
        echo "Is ID null? " . (is_null($item->id) ? 'YES' : 'NO') . "\n";
        echo "---\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
