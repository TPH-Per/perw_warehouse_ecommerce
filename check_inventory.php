<?php

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Inventory;

try {
    // Get first inventory item
    $inventory = Inventory::first();

    if ($inventory) {
        echo "Inventory found with ID: " . $inventory->id . "\n";
        echo "Product Variant ID: " . $inventory->product_variant_id . "\n";
        echo "Warehouse ID: " . $inventory->warehouse_id . "\n";
        echo "Quantity on hand: " . $inventory->quantity_on_hand . "\n";
    } else {
        echo "No inventory found\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
