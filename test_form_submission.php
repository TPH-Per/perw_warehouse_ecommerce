<?php

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;

try {
    // Simulate different form submissions
    echo "Testing form submissions:\n\n";

    // Test 1: No filters
    echo "1. No filters:\n";
    $request1 = Request::create('/admin/users', 'GET', []);
    echo "role_id parameter: " . ($request1->role_id ?? 'NULL') . "\n";
    echo "has('role_id'): " . ($request1->has('role_id') ? 'true' : 'false') . "\n";
    echo "role_id != '': " . (($request1->role_id != '') ? 'true' : 'false') . "\n\n";

    // Test 2: Role filter with value
    echo "2. Role filter with value:\n";
    $request2 = Request::create('/admin/users', 'GET', ['role_id' => '1']);
    echo "role_id parameter: " . ($request2->role_id ?? 'NULL') . "\n";
    echo "has('role_id'): " . ($request2->has('role_id') ? 'true' : 'false') . "\n";
    echo "role_id != '': " . (($request2->role_id != '') ? 'true' : 'false') . "\n\n";

    // Test 3: Role filter with empty value
    echo "3. Role filter with empty value:\n";
    $request3 = Request::create('/admin/users', 'GET', ['role_id' => '']);
    echo "role_id parameter: '" . ($request3->role_id ?? 'NULL') . "'\n";
    echo "has('role_id'): " . ($request3->has('role_id') ? 'true' : 'false') . "\n";
    echo "role_id != '': " . (($request3->role_id != '') ? 'true' : 'false') . "\n\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
