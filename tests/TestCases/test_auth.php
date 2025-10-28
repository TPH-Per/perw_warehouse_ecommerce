<?php

require_once 'vendor/autoload.php';

// Test bcrypt
$hash = password_hash('password', PASSWORD_BCRYPT);
echo "Hash: " . $hash . "\n";

// Check if it's a bcrypt hash
if (strpos($hash, '$2y$') === 0) {
    echo "This is a bcrypt hash\n";
} else {
    echo "This is NOT a bcrypt hash\n";
}

// Test password validation
if (password_verify('password', $hash)) {
    echo "Password validation: SUCCESS\n";
} else {
    echo "Password validation: FAILED\n";
}
