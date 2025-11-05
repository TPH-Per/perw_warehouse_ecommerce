<?php
echo "Testing API endpoints...\n";

// Test the login endpoint
echo "1. Testing login endpoint: http://localhost:8000/api/auth/login\n";
echo "   Expected: Should accept POST requests with email and password\n";

// Test the register endpoint
echo "2. Testing register endpoint: http://localhost:8000/api/auth/register\n";
echo "   Expected: Should accept POST requests with user registration data\n";

// Test the provinces endpoint
echo "3. Testing provinces endpoint: http://localhost:8000/api/provinces\n";
echo "   Expected: Should return province data\n";

echo "\nTo test these endpoints, you can use curl commands like:\n";
echo "curl -X POST http://localhost:8000/api/auth/login \\\n";
echo "  -H \"Content-Type: application/json\" \\\n";
echo "  -d '{\"email\": \"alice.tran@email.com\", \"password\": \"password\"}'\n";
