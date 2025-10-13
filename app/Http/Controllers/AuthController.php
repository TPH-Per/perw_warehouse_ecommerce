<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class AuthController extends Controller
{
    /**
     * Register a new user with improved error handling
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        
        try {
            // Check if user already exists (additional safety check)
            $existingUser = User::where('email', $data['email'])->first();
            if ($existingUser) {
                return response()->json([
                    'message' => 'Registration failed.',
                    'errors' => ['email' => ['This email address is already registered.']]
                ], 422);
            }
            
            $user = User::create([
                'role_id' => 2, // Default is End User
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'password_hash' => Hash::make($data['password']),
                'phone_number' => $data['phone_number'] ?? null,
                'status' => 'active', // Set default status to active
            ]);
            
            // Create token for the newly registered user
            $token = $user->createToken('auth_token', ['*'], now()->addDays(30))->plainTextToken;
            
            // Load relationships
            $user->load(['role']);
            
            return response()->json([
                'message' => 'User registered successfully.',
                'user' => [
                    'id' => $user->id,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'status' => $user->status,
                    'role' => $user->role,
                    'is_admin' => $user->isAdmin(),
                ],
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_at' => now()->addDays(30)->toISOString(),
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Registration failed due to server error.',
                'errors' => ['server' => ['An unexpected error occurred during registration.']]
            ], 500);
        }
    }

    /**
     * Login user with improved authentication logic
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        
        try {
            // Find user by email first
            $user = User::where('email', $credentials['email'])->first();
            
            if (!$user) {
                return response()->json([
                    'message' => 'Invalid login credentials.',
                    'errors' => ['email' => ['The provided credentials do not match our records.']]
                ], 401);
            }
            
            // Check if account is active before attempting authentication
            if ($user->status !== 'active') {
                return response()->json([
                    'message' => 'Your account is not active.',
                    'errors' => ['account' => ['Your account has been deactivated. Please contact support.']]
                ], 403);
            }
            
            // Check if user is soft deleted
            if ($user->trashed()) {
                return response()->json([
                    'message' => 'Account not found.',
                    'errors' => ['account' => ['This account no longer exists.']]
                ], 404);
            }
            
            // Verify password
            if (!Hash::check($credentials['password'], $user->password_hash)) {
                return response()->json([
                    'message' => 'Invalid login credentials.',
                    'errors' => ['password' => ['The provided password is incorrect.']]
                ], 401);
            }
            
            // Revoke all existing tokens for security
            $user->tokens()->delete();
            
            // Create new token
            $token = $user->createToken('auth_token', ['*'], now()->addDays(30))->plainTextToken;
            
            // Load relationships
            $user->load(['role', 'addresses']);
            
            return response()->json([
                'message' => 'Login successful.',
                'user' => [
                    'id' => $user->id,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'avatar_url' => $user->avatar_url,
                    'status' => $user->status,
                    'role' => $user->role,
                    'addresses' => $user->addresses,
                    'is_admin' => $user->isAdmin(),
                ],
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_at' => now()->addDays(30)->toISOString(),
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Login failed due to server error.',
                'errors' => ['server' => ['An unexpected error occurred. Please try again later.']]
            ], 500);
        }
    }

    /**
     * Logout user with improved error handling
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'message' => 'No authenticated user found.',
                    'errors' => ['auth' => ['You are not currently logged in.']]
                ], 401);
            }
            
            // Delete current access token
            $currentToken = $user->currentAccessToken();
            if ($currentToken) {
                $currentToken->delete();
            }
            
            return response()->json([
                'message' => 'Logged out successfully.'
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Logout failed due to server error.',
                'errors' => ['server' => ['An unexpected error occurred during logout.']]
            ], 500);
        }
    }
    
    /**
     * Logout from all devices
     */
    public function logoutAll(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'message' => 'No authenticated user found.',
                    'errors' => ['auth' => ['You are not currently logged in.']]
                ], 401);
            }
            
            // Delete all tokens for this user
            $user->tokens()->delete();
            
            return response()->json([
                'message' => 'Logged out from all devices successfully.'
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Logout failed due to server error.',
                'errors' => ['server' => ['An unexpected error occurred during logout.']]
            ], 500);
        }
    }
    
    /**
     * Get authenticated user information
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'message' => 'No authenticated user found.',
                    'errors' => ['auth' => ['You are not currently logged in.']]
                ], 401);
            }
            
            // Load relationships
            $user->load(['role', 'addresses']);
            
            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'avatar_url' => $user->avatar_url,
                    'status' => $user->status,
                    'role' => $user->role,
                    'addresses' => $user->addresses,
                    'is_admin' => $user->isAdmin(),
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ]
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve user information.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }
}