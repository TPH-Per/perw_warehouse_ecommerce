<?php

namespace App\Http\Controllers;

use App\Services\ProductService;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class ProductController extends Controller
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }
    /**
     * Display a listing of products with filters and search
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'category_id' => $request->get('category_id'),
                'supplier_id' => $request->get('supplier_id'),
                'search' => $request->get('search'),
                'sort_by' => $request->get('sort_by', 'created_at'),
                'sort_order' => $request->get('sort_order', 'desc'),
            ];

            $perPage = $request->get('per_page', 15);
            $products = $this->productService->getProducts(array_filter($filters), $perPage);

            return response()->json([
                'products' => $products,
                'message' => 'Products retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve products.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Display the specified product by slug
     */
    public function show(string $slug): JsonResponse
    {
        try {
            $result = $this->productService->getProductBySlug($slug);

            return response()->json([
                'product' => $result['product'],
                'average_rating' => $result['average_rating'],
                'review_count' => $result['review_count'],
                'message' => 'Product retrieved successfully.'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Product not found.',
                'errors' => ['product' => ['The requested product does not exist.']]
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve product.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Store a newly created product (Admin only)
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'category_id' => 'required|exists:Categories,id',
                'supplier_id' => 'required|exists:Suppliers,id',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'status' => 'sometimes|in:active,inactive,draft'
            ]);

            $product = $this->productService->createProduct($request->validated());

            return response()->json([
                'product' => $product->load(['category', 'supplier']),
                'message' => 'Product created successfully.'
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create product.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Update the specified product (Admin only)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);

            $request->validate([
                'category_id' => 'sometimes|exists:Categories,id',
                'supplier_id' => 'sometimes|exists:Suppliers,id',
                'name' => 'sometimes|string|max:255',
                'description' => 'sometimes|nullable|string',
                'status' => 'sometimes|in:active,inactive,draft'
            ]);

            $updatedProduct = $this->productService->updateProduct($product, $request->validated());

            return response()->json([
                'product' => $updatedProduct,
                'message' => 'Product updated successfully.'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Product not found.',
                'errors' => ['product' => ['The requested product does not exist.']]
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update product.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Remove the specified product (Soft delete - Admin only)
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);
            
            // Check if product can be deleted
            $deleteCheck = $this->productService->canDeleteProduct($product);
            
            if (!$deleteCheck['can_delete']) {
                $reasons = [];
                if ($deleteCheck['reasons']['has_orders']) {
                    $reasons[] = 'Product has existing orders';
                }
                if ($deleteCheck['reasons']['has_inventory']) {
                    $reasons[] = 'Product has inventory stock';
                }
                
                return response()->json([
                    'message' => 'Cannot delete product.',
                    'errors' => ['product' => $reasons]
                ], 422);
            }

            $this->productService->deleteProduct($product);

            return response()->json([
                'message' => 'Product deleted successfully.'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Product not found.',
                'errors' => ['product' => ['The requested product does not exist.']]
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete product.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Get all categories for product filtering
     */
    public function categories(): JsonResponse
    {
        try {
            $categories = $this->productService->getCategories();

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
     * Search products
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->get('q', '');
            
            if (empty($query)) {
                return response()->json([
                    'message' => 'Search query is required.',
                    'errors' => ['query' => ['Please provide a search term.']]
                ], 422);
            }

            $filters = [
                'category_id' => $request->get('category_id'),
                'min_price' => $request->get('min_price'),
                'max_price' => $request->get('max_price'),
                'sort_by' => $request->get('sort_by', 'relevance'),
            ];

            $perPage = $request->get('per_page', 15);
            $products = $this->productService->searchProducts($query, array_filter($filters), $perPage);

            return response()->json([
                'products' => $products,
                'search_query' => $query,
                'message' => 'Search completed successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Search failed.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }
}