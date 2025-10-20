<?php

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    // Check inventory_transactions table structure
    $columns = DB::select('DESCRIBE inventory_transactions');
    echo "inventory_transactions table structure:\n";
    foreach ($columns as $column) {
        echo "- {$column->Field} ({$column->Type})\n";
    }

    echo "\n";

    // Check inventories table structure
    $columns = DB::select('DESCRIBE inventories');
    echo "inventories table structure:\n";
    foreach ($columns as $column) {
        echo "- {$column->Field} ({$column->Type})\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
