<?php

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Inventory;
use Illuminate\Support\Facades\Route;

try {
    // Get first inventory item
    $inventory = Inventory::first();

    if ($inventory) {
        echo "Inventory ID: " . $inventory->id . "\n";

        // Test route generation
        $route = route('admin.inventory.show', $inventory->id);
        echo "Generated route: " . $route . "\n";

        // Also test with the inventory object directly
        $route2 = route('admin.inventory.show', $inventory);
        echo "Generated route (with object): " . $route2 . "\n";

        echo "Routes match: " . ($route === $route2 ? "YES" : "NO") . "\n";
    } else {
        echo "No inventory found\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "File: " . $e->getFile() . "\n";
}
