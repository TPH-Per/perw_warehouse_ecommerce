<?php

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Role;

try {
    echo "Verifying role filtering functionality:\n\n";

    // Get all roles
    $roles = Role::all();
    echo "Available roles:\n";
    foreach ($roles as $role) {
        $count = User::where('role_id', $role->id)->count();
        echo "- {$role->name} (ID: {$role->id}): {$count} users\n";
    }

    echo "\nTotal users: " . User::count() . "\n";

    // Test filtering by each role
    echo "\nTesting filtering by role:\n";
    foreach ($roles as $role) {
        $query = User::with(['role', 'orders']);
        $query->where('role_id', $role->id);
        $users = $query->paginate(20);

        echo "- {$role->name}: {$users->total()} users found\n";
    }

    // Test the actual controller logic
    echo "\nTesting controller logic:\n";

    // Simulate no filter
    $query1 = User::with(['role', 'orders']);
    $users1 = $query1->paginate(20);
    echo "No filter: {$users1->total()} users\n";

    // Simulate role filter
    $roleId = $roles->first()->id ?? null;
    if ($roleId) {
        $query2 = User::with(['role', 'orders']);
        $query2->where('role_id', $roleId);
        $users2 = $query2->paginate(20);
        echo "Role filter (ID: {$roleId}): {$users2->total()} users\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
