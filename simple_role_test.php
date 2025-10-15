<?php

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Role;

try {
    echo "Simple role filter test:\n\n";

    // Get a role that has users
    $role = Role::whereHas('users')->first();

    if ($role) {
        echo "Testing with role: {$role->name} (ID: {$role->id})\n";

        // Test 1: Count users with this role
        $count1 = User::where('role_id', $role->id)->count();
        echo "Direct count: {$count1} users\n";

        // Test 2: Use the same query as controller
        $query = User::with(['role', 'orders']);
        $query->where('role_id', $role->id);
        $users = $query->paginate(20);
        echo "Controller-style query: {$users->total()} users\n";

        // Show first few users
        echo "Sample users:\n";
        foreach ($users->take(3) as $user) {
            echo "- {$user->full_name} (Role: {$user->role->name})\n";
        }
    } else {
        echo "No roles with users found\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
