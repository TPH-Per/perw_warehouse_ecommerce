<?php

namespace App\Http\Controllers;

use App\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class CategoryController extends Controller
{
    protected CategoryService $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }
    /**
     * Display a listing of categories
     */
    public function index(): JsonResponse
    {
        try {
            $categories = $this->categoryService->getCategories();

            return response()->json([
                'categories' => $categories,
                'message' => 'Categories retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve categories.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Display the specified category
     */
    public function show(int $id): JsonResponse
    {
        try {
            $category = $this->categoryService->getCategoryById($id);

            return response()->json([
                'category' => $category,
                'message' => 'Category retrieved successfully.'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Category not found.',
                'errors' => ['category' => ['The requested category does not exist.']]
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve category.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Store a newly created category (Admin only)
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'parent_id' => 'nullable|exists:Categories,id',
                'name' => 'required|string|max:255|unique:Categories,name',
            ]);

            $category = $this->categoryService->createCategory($request->validated());

            return response()->json([
                'category' => $category->load('parent'),
                'message' => 'Category created successfully.'
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create category.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Update the specified category (Admin only)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $category = $this->categoryService->getCategoryById($id);

            $request->validate([
                'parent_id' => 'nullable|exists:Categories,id|not_in:' . $id,
                'name' => 'sometimes|string|max:255|unique:Categories,name,' . $id,
            ]);

            $updatedCategory = $this->categoryService->updateCategory($category, $request->validated());

            return response()->json([
                'category' => $updatedCategory,
                'message' => 'Category updated successfully.'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Category not found.',
                'errors' => ['category' => ['The requested category does not exist.']]
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update category.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Remove the specified category (Soft delete - Admin only)
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $category = $this->categoryService->getCategoryById($id);
            $this->categoryService->deleteCategory($category);

            return response()->json([
                'message' => 'Category deleted successfully.'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Category not found.',
                'errors' => ['category' => ['The requested category does not exist.']]
            ], 404);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Cannot delete')) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors' => ['category' => [$e->getMessage()]]
                ], 422);
            }
            
            return response()->json([
                'message' => 'Failed to delete category.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Get category tree structure
     */
    public function tree(): JsonResponse
    {
        try {
            $categories = $this->categoryService->getCategoryTree();

            return response()->json([
                'categories' => $categories,
                'message' => 'Category tree retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve category tree.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }
}