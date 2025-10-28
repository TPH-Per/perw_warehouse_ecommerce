<?php

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;

try {
    echo "Testing form submission with role filter:\n\n";

    // Simulate a request with role filter
    $request = Request::create('/admin/users', 'GET', ['role_id' => '2']);

    echo "Request parameters: " . json_encode($request->all()) . "\n";
    echo "Has role_id: " . ($request->has('role_id') ? 'true' : 'false') . "\n";
    echo "Role ID value: '" . ($request->role_id ?? 'NULL') . "'\n";
    echo "Role ID is not empty: " . (($request->role_id != '') ? 'true' : 'false') . "\n";

    // Test the controller logic
    if ($request->has('role_id') && $request->role_id != '') {
        echo "Controller would apply role filter with ID: " . $request->role_id . "\n";
    } else {
        echo "Controller would NOT apply role filter\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
