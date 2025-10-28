<?php

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Role;
use Illuminate\Http\Request;

try {
    echo "Testing data types for role filtering:\n\n";

    // Get a role
    $role = Role::first();
    if ($role) {
        echo "Role ID from database:\n";
        echo "  Value: " . $role->id . "\n";
        echo "  Type: " . gettype($role->id) . "\n\n";

        // Simulate request with string role ID
        $request = Request::create('/admin/users', 'GET', ['role_id' => '1']);
        echo "Request role_id parameter:\n";
        echo "  Value: '" . $request->role_id . "'\n";
        echo "  Type: " . gettype($request->role_id) . "\n\n";

        // Test comparison
        echo "Comparison results:\n";
        echo "  request('role_id') == role->id: " . (($request->role_id == $role->id) ? 'true' : 'false') . "\n";
        echo "  request('role_id') === role->id: " . (($request->role_id === $role->id) ? 'true' : 'false') . "\n";
        echo "  (int)request('role_id') === role->id: " . (((int)$request->role_id === $role->id) ? 'true' : 'false') . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
