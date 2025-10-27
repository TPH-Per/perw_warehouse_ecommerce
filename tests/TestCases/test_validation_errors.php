<?php

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Role;
use Illuminate\Support\Facades\Validator;

try {
    // Get the admin role
    $adminRole = Role::where('name', 'Admin')->first();

    if (!$adminRole) {
        echo "Admin role not found!\n";
        exit(1);
    }

    echo "Admin role found: ID={$adminRole->id}, Name={$adminRole->name}\n";

    // Simulate form request data that might cause issues
    $testData = [
        'role_id' => $adminRole->id,
        'full_name' => 'Test Admin User',
        'email' => 'testadminvalidation@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'phone_number' => '0900000004',
        'status' => 'active'
    ];

    // Apply the same validation rules as in the controller
    $validator = Validator::make($testData, [
        'role_id' => 'required|exists:roles,id',
        'full_name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|string|min:8|confirmed',
        'phone_number' => 'nullable|string|max:20',
        'status' => 'required|in:active,inactive,suspended',
    ]);

    if ($validator->fails()) {
        echo "Validation errors found:\n";
        foreach ($validator->errors()->all() as $error) {
            echo "- {$error}\n";
        }
    } else {
        echo "Validation passed successfully!\n";
    }

    // Also test with a string role_id (which might happen in form submission)
    $testDataWithStringRoleId = array_merge($testData, ['role_id' => (string)$adminRole->id]);

    $validator2 = Validator::make($testDataWithStringRoleId, [
        'role_id' => 'required|exists:roles,id',
        'full_name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|string|min:8|confirmed',
        'phone_number' => 'nullable|string|max:20',
        'status' => 'required|in:active,inactive,suspended',
    ]);

    if ($validator2->fails()) {
        echo "\nValidation errors with string role_id:\n";
        foreach ($validator2->errors()->all() as $error) {
            echo "- {$error}\n";
        }
    } else {
        echo "\nValidation with string role_id also passed!\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
