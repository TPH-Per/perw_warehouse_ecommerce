<?php

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    // Check inventory table structure
    $columns = DB::select("SHOW COLUMNS FROM inventories");
    echo "Inventory table columns:\n";
    foreach ($columns as $column) {
        echo "- " . $column->Field . " (" . $column->Type . ")\n";
    }

    echo "\nFirst inventory record:\n";
    $inventory = DB::table('inventories')->first();
    if ($inventory) {
        print_r($inventory);
    } else {
        echo "No inventory records found\n";
    }

    echo "\nCount of inventory records: " . DB::table('inventories')->count() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
