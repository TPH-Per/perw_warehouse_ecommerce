<?php

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    // Check products table structure
    $result = DB::select('SHOW CREATE TABLE products');
    echo "Products table structure:\n";
    echo $result[0]->{'Create Table'} . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
