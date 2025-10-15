<?php

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

try {
    // Get the admin role
    $adminRole = Role::where('name', 'Admin')->first();

    if (!$adminRole) {
        echo "Admin role not found!\n";
        exit(1);
    }

    echo "Admin role found: ID={$adminRole->id}, Name={$adminRole->name}\n";

    // Try to create a new user with admin role
    $newUser = User::create([
        'name' => 'Test Admin User',
        'role_id' => $adminRole->id,
        'full_name' => 'Test Admin User',
        'email' => 'testadmin@example.com',
        'password' => Hash::make('password123'),
        'phone_number' => '0900000002',
        'status' => 'active'
    ]);

    echo "User created successfully!\n";
    echo "User ID: {$newUser->id}\n";
    echo "User Name: {$newUser->full_name}\n";
    echo "User Role: {$newUser->role->name}\n";

} catch (Exception $e) {
    echo "Error creating user: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
