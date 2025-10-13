<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class RoleController extends Controller
{
    /**
     * Display a listing of roles
     */
    public function index(): JsonResponse
    {
        try {
            $roles = Role::withCount('users')
                        ->orderBy('name')
                        ->get();

            return response()->json([
                'roles' => $roles,
                'message' => 'Roles retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve roles.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Display the specified role
     */
    public function show(int $id): JsonResponse
    {
        try {
            $role = Role::with(['users' => function ($query) {
                           $query->select('id', 'role_id', 'full_name', 'email', 'status')
                                 ->orderBy('full_name');
                       }])
                       ->withCount('users')
                       ->findOrFail($id);

            return response()->json([
                'role' => $role,
                'message' => 'Role retrieved successfully.'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Role not found.',
                'errors' => ['role' => ['The requested role does not exist.']]
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve role.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Store a newly created role (Super Admin only)
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:Roles,name',
                'description' => 'nullable|string|max:500',
            ]);

            $role = Role::create([
                'name' => $request->name,
                'description' => $request->description,
            ]);

            return response()->json([
                'role' => $role,
                'message' => 'Role created successfully.'
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create role.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Update the specified role (Super Admin only)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $role = Role::findOrFail($id);

            // Prevent modification of system roles
            if (in_array($id, [1, 2])) { // Assuming 1=Admin, 2=End User
                return response()->json([
                    'message' => 'Cannot modify system roles.',
                    'errors' => ['role' => ['System roles cannot be modified.']]
                ], 422);
            }

            $request->validate([
                'name' => 'sometimes|string|max:255|unique:Roles,name,' . $id,
                'description' => 'sometimes|nullable|string|max:500',
            ]);

            $role->update($request->only(['name', 'description']));

            return response()->json([
                'role' => $role,
                'message' => 'Role updated successfully.'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Role not found.',
                'errors' => ['role' => ['The requested role does not exist.']]
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update role.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Remove the specified role (Super Admin only)
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $role = Role::findOrFail($id);

            // Prevent deletion of system roles
            if (in_array($id, [1, 2])) { // Assuming 1=Admin, 2=End User
                return response()->json([
                    'message' => 'Cannot delete system roles.',
                    'errors' => ['role' => ['System roles cannot be deleted.']]
                ], 422);
            }

            // Check if role has users
            if ($role->users()->exists()) {
                return response()->json([
                    'message' => 'Cannot delete role with users.',
                    'errors' => ['role' => ['This role has assigned users and cannot be deleted.']]
                ], 422);
            }

            $role->delete();

            return response()->json([
                'message' => 'Role deleted successfully.'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Role not found.',
                'errors' => ['role' => ['The requested role does not exist.']]
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete role.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Get role statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = Role::with('users')
                        ->get()
                        ->map(function ($role) {
                            return [
                                'id' => $role->id,
                                'name' => $role->name,
                                'user_count' => $role->users->count(),
                                'active_users' => $role->users->where('status', 'active')->count(),
                                'inactive_users' => $role->users->where('status', 'inactive')->count(),
                            ];
                        });

            $totalRoles = Role::count();
            $totalUsers = Role::withCount('users')->get()->sum('users_count');

            return response()->json([
                'statistics' => [
                    'total_roles' => $totalRoles,
                    'total_users' => $totalUsers,
                    'role_breakdown' => $stats,
                ],
                'message' => 'Role statistics retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve role statistics.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Get roles for dropdown/select options
     */
    public function options(): JsonResponse
    {
        try {
            $roles = Role::select('id', 'name')
                        ->orderBy('name')
                        ->get();

            return response()->json([
                'roles' => $roles,
                'message' => 'Role options retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve role options.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }
}