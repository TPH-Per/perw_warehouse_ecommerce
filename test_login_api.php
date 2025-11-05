<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

echo "=== TESTING LOGIN API ===\n\n";

// Get all active End User accounts
$customers = DB::table('users')
    ->join('roles', 'users.role_id', '=', 'roles.id')
    ->where('roles.name', 'End User')
    ->where('users.status', 'active')
    ->select('users.id', 'users.name', 'users.email', 'users.password', 'users.status')
    ->get();

echo "Found " . count($customers) . " active End User accounts:\n\n";

$testPasswords = ['password', '123sinhtobO', 'password123', '12345678'];

foreach ($customers as $customer) {
    echo "-----------------------------------\n";
    echo "Name: {$customer->name}\n";
    echo "Email: {$customer->email}\n";
    echo "Status: {$customer->status}\n";
    echo "Password hash: " . substr($customer->password, 0, 40) . "...\n";
    echo "\nTesting passwords:\n";

    $foundPassword = false;
    foreach ($testPasswords as $pass) {
        $check = Hash::check($pass, $customer->password);
        if ($check) {
            echo "  ✓ WORKS WITH: '{$pass}'\n";
            $foundPassword = true;

            // Test via API
            echo "\n  Testing API Login...\n";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/auth/login');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'email' => $customer->email,
                'password' => $pass
            ]));

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                $data = json_decode($response, true);
                if (isset($data['token'])) {
                    echo "  ✓ API LOGIN SUCCESS!\n";
                    echo "  Token: " . substr($data['token'], 0, 20) . "...\n";
                } else {
                    echo "  ✗ API Response missing token\n";
                    echo "  Response: " . substr($response, 0, 100) . "\n";
                }
            } else {
                echo "  ✗ API LOGIN FAILED (HTTP {$httpCode})\n";
                echo "  Response: " . substr($response, 0, 200) . "\n";
            }

            break;
        }
    }

    if (!$foundPassword) {
        echo "  ✗ None of the test passwords work\n";
        echo "  Password may have been set to something else\n";
    }

    echo "\n";
}

echo "\n=== RECOMMENDED TEST CREDENTIALS ===\n\n";
echo "Use these credentials to test login:\n\n";

foreach ($customers as $customer) {
    foreach ($testPasswords as $pass) {
        if (Hash::check($pass, $customer->password)) {
            echo "Email: {$customer->email}\n";
            echo "Password: {$pass}\n";
            echo "---\n";
            break;
        }
    }
}

echo "\n=== TESTING NEW REGISTRATION ===\n\n";

// Test registration to ensure it works
$testEmail = "testlogin" . time() . "@example.com";
$testPassword = "TestPassword123";

echo "Creating new test user...\n";
echo "Email: {$testEmail}\n";
echo "Password: {$testPassword}\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/auth/register');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'name' => 'Test Login User',
    'email' => $testEmail,
    'password' => $testPassword,
    'password_confirmation' => $testPassword,
    'phone_number' => '1234567890'
]));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 201) {
    echo "✓ Registration successful!\n\n";

    // Now test login
    echo "Testing login with new account...\n";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/auth/login');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'email' => $testEmail,
        'password' => $testPassword
    ]));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $data = json_decode($response, true);
        echo "✓ LOGIN SUCCESSFUL!\n";
        echo "Token: " . substr($data['token'], 0, 30) . "...\n\n";
        echo "==================================\n";
        echo "✓ YOUR LOGIN CREDENTIALS:\n";
        echo "==================================\n";
        echo "Email: {$testEmail}\n";
        echo "Password: {$testPassword}\n";
        echo "==================================\n";
    } else {
        echo "✗ Login failed (HTTP {$httpCode})\n";
        echo "Response: {$response}\n";
    }
} else {
    echo "✗ Registration failed (HTTP {$httpCode})\n";
    echo "Response: {$response}\n";
}
