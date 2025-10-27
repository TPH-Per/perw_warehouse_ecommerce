<?php

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Role;

try {
    // Check roles
    $roles = Role::all();
    echo "Available roles:\n";
    foreach ($roles as $role) {
        echo "- {$role->id}: {$role->name}\n";
    }

    // Check users with roles
    echo "\nUsers with roles:\n";
    $users = User::with('role')->limit(5)->get();
    foreach ($users as $user) {
        echo "- {$user->id}: {$user->full_name} (Role: {$user->role->name})\n";
    }

    // Test filtering by role
    echo "\nTesting role filter:\n";
    $roleId = $roles->first()->id ?? null;
    if ($roleId) {
        $filteredUsers = User::where('role_id', $roleId)->count();
        echo "Users with role ID {$roleId}: {$filteredUsers}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
