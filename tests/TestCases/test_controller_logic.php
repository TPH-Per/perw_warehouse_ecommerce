<?php

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;

try {
    echo "Testing exact controller logic:\n\n";

    // Get a role with users
    $role = Role::whereHas('users')->first();

    if ($role) {
        echo "Testing with role: {$role->name} (ID: {$role->id})\n";

        // Simulate the exact controller logic
        $requestData = ['role_id' => $role->id];
        $request = Request::create('/admin/users', 'GET', $requestData);

        echo "Request data: " . json_encode($request->all()) . "\n";

        $query = User::with(['role', 'orders']);

        // Apply role filter (exact same logic as controller)
        if ($request->has('role_id') && $request->role_id != '') {
            echo "Applying role filter for ID: " . $request->role_id . "\n";
            $query->where('role_id', $request->role_id);
        }

        $users = $query->paginate(20);

        echo "Found {$users->total()} users with role ID {$role->id}\n";

        // Show sample users
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
