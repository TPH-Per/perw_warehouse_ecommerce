<?php

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

try {
    // Get the admin role
    $adminRole = Role::where('name', 'Admin')->first();

    if (!$adminRole) {
        echo "Admin role not found!\n";
        exit(1);
    }

    echo "Admin role found: ID={$adminRole->id}, Name={$adminRole->name}\n";

    // Get an existing user's email to test duplicate validation
    $existingUser = User::first();
    if ($existingUser) {
        echo "Existing user found: {$existingUser->email}\n";

        // Test with duplicate email
        $testData = [
            'role_id' => $adminRole->id,
            'full_name' => 'Test Admin User',
            'email' => $existingUser->email, // This should cause a validation error
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone_number' => '0900000005',
            'status' => 'active'
        ];

        $validator = Validator::make($testData, [
            'role_id' => 'required|exists:roles,id',
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone_number' => 'nullable|string|max:20',
            'status' => 'required|in:active,inactive,suspended',
        ]);

        if ($validator->fails()) {
            echo "Validation errors with duplicate email:\n";
            foreach ($validator->errors()->all() as $error) {
                echo "- {$error}\n";
            }
        } else {
            echo "Unexpected: Validation passed even with duplicate email!\n";
        }
    } else {
        echo "No existing users found.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
