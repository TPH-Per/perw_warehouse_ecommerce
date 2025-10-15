<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Check table counts
$tables = [
    'users',
    'addresses',
    'products',
    'product_variants',
    'product_images',
    'inventories',
    'purchase_orders',
    'payments',
    'shipments'
];

echo "Database record counts:\n";
echo "=====================\n";

foreach ($tables as $table) {
    if (Schema::hasTable($table)) {
        $count = DB::table($table)->count();
        echo sprintf("%-20s: %d\n", $table, $count);
    } else {
        echo sprintf("%-20s: Table does not exist\n", $table);
    }
}
