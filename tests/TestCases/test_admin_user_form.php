<?php

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\UserAdminController;

try {
    // Get the admin role
    $adminRole = Role::where('name', 'Admin')->first();

    if (!$adminRole) {
        echo "Admin role not found!\n";
        exit(1);
    }

    echo "Admin role found: ID={$adminRole->id}, Name={$adminRole->name}\n";

    // Simulate form request data like what would be sent from the create form
    $requestData = [
        'role_id' => $adminRole->id,
        'full_name' => 'Test Admin via Form',
        'email' => 'testadminform@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'phone_number' => '0900000003',
        'status' => 'active'
    ];

    // Create a mock request
    $request = Request::create('/admin/users', 'POST', $requestData);
    $request->setLaravelSession(app('session.store'));

    // Try to process the request through the controller
    $controller = new UserAdminController();
    $response = $controller->store($request);

    echo "Form submission processed successfully!\n";

    // Check if user was created
    $user = User::where('email', 'testadminform@example.com')->first();
    if ($user) {
        echo "User created successfully via form!\n";
        echo "User ID: {$user->id}\n";
        echo "User Name: {$user->full_name}\n";
        echo "User Role ID: {$user->role_id}\n";
        echo "User Role Name: {$user->role->name}\n";
    } else {
        echo "User was not created despite successful response.\n";
    }

} catch (Exception $e) {
    echo "Error processing form: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
