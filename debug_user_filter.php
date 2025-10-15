<?php

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;

try {
    // Simulate a request with role filter
    echo "Testing user filtering with role ID:\n";

    // Get the first role
    $role = Role::first();
    if ($role) {
        echo "Filtering by role: {$role->name} (ID: {$role->id})\n";

        // Test the query
        $query = User::with(['role', 'orders']);
        $query->where('role_id', $role->id);
        $users = $query->paginate(20);

        echo "Found {$users->total()} users with this role\n";

        // Show first few users
        foreach ($users->take(3) as $user) {
            echo "- {$user->full_name} (Role: {$user->role->name})\n";
        }
    } else {
        echo "No roles found\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
