<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Get authenticated admin user ID
     */
    private function getAuthenticatedUserId(): int
    {
        $userId = Auth::id();
        if (!$userId) {
            throw new \Exception('Admin not authenticated');
        }
        return $userId;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        // Get all users, excluding Admins and soft deleted users
        $users = User::where('id', '!=', $this->getAuthenticatedUserId()) // Don't show current admin
                     ->where('role_id', '!=', 1) // Don't show other Admins
                     ->whereNull('deleted_at') // Only non-deleted users
                     ->with('role') // Load role information
                     ->get();

        return response()->json($users);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $user = User::where('id', $id)
                    ->where('id', '!=', $this->getAuthenticatedUserId())
                    ->where('role_id', '!=', 1)
                    ->whereNull('deleted_at')
                    ->with('role', 'addresses')
                    ->firstOrFail(); // Return 404 if not found

        return response()->json($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = User::where('id', $id)
                    ->where('id', '!=', $this->getAuthenticatedUserId())
                    ->where('role_id', '!=', 1)
                    ->whereNull('deleted_at')
                    ->firstOrFail();

        // Basic validation
        $request->validate([
            'full_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:Users,email,' . $id,
            'phone_number' => 'sometimes|nullable|string|max:20',
            'status' => 'sometimes|in:active,inactive,banned'
        ]);

        $data = $request->all();

        // Update allowed fields
        $user->full_name = $data['full_name'] ?? $user->full_name;
        $user->email = $data['email'] ?? $user->email;
        $user->phone_number = $data['phone_number'] ?? $user->phone_number;
        $user->status = $data['status'] ?? $user->status;

        $user->save();

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => $user->load('role')
        ]);
    }

    /**
     * Remove the specified resource from storage (Soft Delete).
     */
    public function destroy(string $id): JsonResponse
    {
        $user = User::where('id', $id)
                    ->where('id', '!=', $this->getAuthenticatedUserId())
                    ->where('role_id', '!=', 1)
                    ->whereNull('deleted_at')
                    ->firstOrFail();

        $user->delete(); // Perform soft delete

        return response()->json(['message' => 'User deleted successfully.']);
    }
}