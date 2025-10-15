<?php

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;

try {
    echo "=== Comprehensive User Filter Test ===\n\n";

    // 1. Check roles and users
    echo "1. Roles and Users:\n";
    $roles = Role::all();
    foreach ($roles as $role) {
        $count = User::where('role_id', $role->id)->count();
        echo "   - {$role->name} (ID: {$role->id}): {$count} users\n";
    }
    echo "   Total users: " . User::count() . "\n\n";

    // 2. Test controller logic with each role
    echo "2. Controller Logic Tests:\n";
    foreach ($roles as $role) {
        $requestData = ['role_id' => $role->id];
        $request = Request::create('/admin/users', 'GET', $requestData);

        $query = User::with(['role', 'orders']);

        // Apply role filter (exact same logic as controller)
        if ($request->has('role_id') && $request->role_id != '') {
            $query->where('role_id', $request->role_id);
        }

        $users = $query->paginate(20);
        echo "   - Role {$role->name}: {$users->total()} users\n";
    }
    echo "\n";

    // 3. Test form submission simulation
    echo "3. Form Submission Simulation:\n";
    $adminRole = Role::where('name', 'Admin')->first();
    if ($adminRole) {
        $requestData = ['role_id' => $adminRole->id];
        $request = Request::create('/admin/users', 'GET', $requestData);

        echo "   Request data: " . json_encode($request->all()) . "\n";
        echo "   Has role_id: " . ($request->has('role_id') ? 'true' : 'false') . "\n";
        echo "   Role ID value: '" . ($request->role_id ?? 'NULL') . "'\n";
        echo "   Role ID is not empty: " . (($request->role_id != '') ? 'true' : 'false') . "\n";

        // Apply controller logic
        $query = User::with(['role', 'orders']);
        if ($request->has('role_id') && $request->role_id != '') {
            echo "   Applying role filter for ID: " . $request->role_id . "\n";
            $query->where('role_id', $request->role_id);
        }

        $users = $query->paginate(20);
        echo "   Result: {$users->total()} users found\n";

        // Show sample users
        echo "   Sample users:\n";
        foreach ($users->take(3) as $user) {
            echo "     - {$user->full_name} (Role: {$user->role->name})\n";
        }
    }

    echo "\n=== Test Complete ===\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
