<?php

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;

try {
    echo "Testing exact scenario:\n\n";

    // Simulate a real form submission with role filter
    $request = Request::create('/admin/users', 'GET', [
        'role_id' => '2',  // String value like from form
        'search' => '',
        'status' => ''
    ]);

    echo "Request parameters: " . json_encode($request->all()) . "\n";
    echo "Role ID type: " . gettype($request->role_id) . "\n\n";

    // Simulate exact controller logic
    $query = User::with(['role', 'orders']);

    // Search functionality
    if ($request->has('search') && $request->search != '') {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('full_name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('phone_number', 'like', "%{$search}%");
        });
    }

    // Filter by role
    if ($request->has('role_id') && $request->role_id != '') {
        echo "Applying role filter with value: '" . $request->role_id . "' (type: " . gettype($request->role_id) . ")\n";
        $query->where('role_id', $request->role_id);
    }

    // Filter by status
    if ($request->has('status') && $request->status != '') {
        $query->where('status', $request->status);
    }

    $users = $query->paginate(20);

    echo "Found {$users->total()} users\n";

    // Show sample users
    echo "Sample users:\n";
    foreach ($users->take(5) as $user) {
        echo "- {$user->full_name} (Role: {$user->role->name}, Status: {$user->status})\n";
    }

    // Test the view logic for selected option
    echo "\nTesting view logic for selected option:\n";
    $roles = Role::all();
    foreach ($roles as $role) {
        $isSelected = $request->role_id == $role->id;
        echo "- {$role->name} (ID: {$role->id}): " . ($isSelected ? 'SELECTED' : 'not selected') . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
