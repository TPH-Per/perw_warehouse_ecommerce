<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthApiController extends Controller
{
    /**
     * Login user
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::with('role')->where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Check if user is allowed (endUser role only)
        if ($user->role && $user->role->name !== 'endUser') {
            return response()->json([
                'message' => 'Access denied. End-user accounts only.'
            ], 403);
        }

        // Check if user is active
        if ($user->status !== 'active') {
            return response()->json([
                'message' => 'Your account is not active. Please contact support.'
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'role' => [
                    'id' => $user->role->id,
                    'name' => $user->role->name,
                ],
            ],
            'token' => $token,
        ]);
    }

    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone_number' => 'nullable|string|max:20',
        ]);

        // Get endUser role
        $endUserRole = Role::where('name', 'endUser')->first();
        if (!$endUserRole) {
            return response()->json([
                'message' => 'EndUser role not found. Please contact administrator.'
            ], 500);
        }

        $user = User::create([
            'name' => $request->name,
            'full_name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,  // Let Laravel auto-hash via cast
            'phone_number' => $request->phone_number ?? null,
            'role_id' => $endUserRole->id,
            'status' => 'active',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'role' => [
                    'id' => $user->role->id,
                    'name' => $user->role->name,
                ],
            ],
            'token' => $token,
        ], 201);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get authenticated user
     */
    public function user(Request $request)
    {
        $user = $request->user()->load('role');

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'role' => [
                'id' => $user->role->id,
                'name' => $user->role->name,
            ],
        ]);
    }
}
