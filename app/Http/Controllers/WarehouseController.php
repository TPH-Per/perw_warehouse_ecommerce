<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class WarehouseController extends Controller
{
    /**
     * Display a listing of warehouses
     */
    public function index(): JsonResponse
    {
        try {
            $warehouses = Warehouse::withCount('inventories')
                                  ->orderBy('name')
                                  ->get();

            return response()->json([
                'warehouses' => $warehouses,
                'message' => 'Warehouses retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve warehouses.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Display the specified warehouse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $warehouse = Warehouse::with(['inventories.productVariant.product'])
                                 ->withCount('inventories')
                                 ->findOrFail($id);

            return response()->json([
                'warehouse' => $warehouse,
                'message' => 'Warehouse retrieved successfully.'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Warehouse not found.',
                'errors' => ['warehouse' => ['The requested warehouse does not exist.']]
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve warehouse.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Store a newly created warehouse (Admin only)
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:Warehouses,name',
                'location' => 'required|string|max:255',
            ]);

            $warehouse = Warehouse::create([
                'name' => $request->name,
                'location' => $request->location,
            ]);

            return response()->json([
                'warehouse' => $warehouse,
                'message' => 'Warehouse created successfully.'
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create warehouse.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Update the specified warehouse (Admin only)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $warehouse = Warehouse::findOrFail($id);

            $request->validate([
                'name' => 'sometimes|string|max:255|unique:Warehouses,name,' . $id,
                'location' => 'sometimes|string|max:255',
            ]);

            $warehouse->update($request->only(['name', 'location']));

            return response()->json([
                'warehouse' => $warehouse,
                'message' => 'Warehouse updated successfully.'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Warehouse not found.',
                'errors' => ['warehouse' => ['The requested warehouse does not exist.']]
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update warehouse.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Remove the specified warehouse (Soft delete - Admin only)
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $warehouse = Warehouse::findOrFail($id);

            // Check if warehouse has inventory
            if ($warehouse->inventories()->exists()) {
                return response()->json([
                    'message' => 'Cannot delete warehouse with inventory.',
                    'errors' => ['warehouse' => ['This warehouse contains inventory items and cannot be deleted.']]
                ], 422);
            }

            $warehouse->delete();

            return response()->json([
                'message' => 'Warehouse deleted successfully.'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Warehouse not found.',
                'errors' => ['warehouse' => ['The requested warehouse does not exist.']]
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete warehouse.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }
}