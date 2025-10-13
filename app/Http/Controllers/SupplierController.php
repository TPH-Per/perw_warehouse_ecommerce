<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class SupplierController extends Controller
{
    /**
     * Display a listing of suppliers
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Supplier::withCount('products');

            // Search functionality
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('contact_info', 'like', "%{$search}%");
                });
            }

            // Sort options
            $sortBy = $request->get('sort_by', 'name');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $suppliers = $query->paginate($perPage);

            return response()->json([
                'suppliers' => $suppliers,
                'message' => 'Suppliers retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve suppliers.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Display the specified supplier
     */
    public function show(int $id): JsonResponse
    {
        try {
            $supplier = Supplier::with(['products' => function ($query) {
                                   $query->where('status', 'active')
                                         ->select('id', 'supplier_id', 'name', 'slug', 'status')
                                         ->orderBy('name');
                               }])
                               ->withCount('products')
                               ->findOrFail($id);

            return response()->json([
                'supplier' => $supplier,
                'message' => 'Supplier retrieved successfully.'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Supplier not found.',
                'errors' => ['supplier' => ['The requested supplier does not exist.']]
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve supplier.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Store a newly created supplier (Admin only)
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:Suppliers,name',
                'contact_info' => 'required|string|max:500',
            ]);

            $supplier = Supplier::create([
                'name' => $request->name,
                'contact_info' => $request->contact_info,
            ]);

            return response()->json([
                'supplier' => $supplier,
                'message' => 'Supplier created successfully.'
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create supplier.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Update the specified supplier (Admin only)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $supplier = Supplier::findOrFail($id);

            $request->validate([
                'name' => 'sometimes|string|max:255|unique:Suppliers,name,' . $id,
                'contact_info' => 'sometimes|string|max:500',
            ]);

            $supplier->update($request->only(['name', 'contact_info']));

            return response()->json([
                'supplier' => $supplier,
                'message' => 'Supplier updated successfully.'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Supplier not found.',
                'errors' => ['supplier' => ['The requested supplier does not exist.']]
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update supplier.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Remove the specified supplier (Soft delete - Admin only)
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $supplier = Supplier::findOrFail($id);

            // Check if supplier has active products
            $activeProductsCount = $supplier->products()->where('status', 'active')->count();
            
            if ($activeProductsCount > 0) {
                return response()->json([
                    'message' => 'Cannot delete supplier with active products.',
                    'errors' => ['supplier' => ["This supplier has {$activeProductsCount} active products and cannot be deleted."]]
                ], 422);
            }

            $supplier->delete();

            return response()->json([
                'message' => 'Supplier deleted successfully.'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Supplier not found.',
                'errors' => ['supplier' => ['The requested supplier does not exist.']]
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete supplier.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Get supplier statistics
     */
    public function statistics(int $id): JsonResponse
    {
        try {
            $supplier = Supplier::findOrFail($id);

            $stats = [
                'total_products' => $supplier->products()->count(),
                'active_products' => $supplier->products()->where('status', 'active')->count(),
                'inactive_products' => $supplier->products()->where('status', 'inactive')->count(),
                'draft_products' => $supplier->products()->where('status', 'draft')->count(),
            ];

            return response()->json([
                'supplier' => $supplier,
                'statistics' => $stats,
                'message' => 'Supplier statistics retrieved successfully.'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Supplier not found.',
                'errors' => ['supplier' => ['The requested supplier does not exist.']]
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve supplier statistics.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Get all suppliers for dropdown/select options
     */
    public function options(): JsonResponse
    {
        try {
            $suppliers = Supplier::select('id', 'name')
                                ->orderBy('name')
                                ->get();

            return response()->json([
                'suppliers' => $suppliers,
                'message' => 'Supplier options retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve supplier options.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }
}