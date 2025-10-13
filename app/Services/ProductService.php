<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ProductService
{
    /**
     * Get products with filters and pagination
     */
    public function getProducts(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Product::with(['category', 'supplier', 'images', 'variants'])
                       ->where('status', 'active');

        // Apply filters
        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        // Search functionality
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sort options
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Get product by slug with relationships
     */
    public function getProductBySlug(string $slug): array
    {
        $product = Product::where('slug', $slug)
                         ->where('status', 'active')
                         ->with([
                             'category',
                             'supplier',
                             'images',
                             'variants.inventories',
                             'reviews' => function ($query) {
                                 $query->where('status', 'approved')
                                       ->with('user:id,full_name')
                                       ->latest();
                             }
                         ])
                         ->firstOrFail();

        // Calculate average rating
        $averageRating = $product->reviews->avg('rating');
        $reviewCount = $product->reviews->count();

        return [
            'product' => $product,
            'average_rating' => round($averageRating, 1),
            'review_count' => $reviewCount,
        ];
    }

    /**
     * Create a new product
     */
    public function createProduct(array $data): Product
    {
        $data['slug'] = $this->generateUniqueSlug($data['name']);
        $data['status'] = $data['status'] ?? 'draft';

        return Product::create($data);
    }

    /**
     * Update an existing product
     */
    public function updateProduct(Product $product, array $data): Product
    {
        if (isset($data['name']) && $data['name'] !== $product->name) {
            $data['slug'] = $this->generateUniqueSlug($data['name']);
        }

        $product->update($data);
        return $product->fresh(['category', 'supplier']);
    }

    /**
     * Delete a product (soft delete)
     */
    public function deleteProduct(Product $product): bool
    {
        return $product->delete();
    }

    /**
     * Get all categories for product filtering
     */
    public function getCategories(): Collection
    {
        return Category::whereNull('parent_id')
                      ->with('children')
                      ->orderBy('name')
                      ->get();
    }

    /**
     * Get featured products
     */
    public function getFeaturedProducts(int $limit = 10): Collection
    {
        return Product::where('status', 'active')
                     ->where('is_featured', true)
                     ->with(['category', 'images', 'variants'])
                     ->orderBy('created_at', 'desc')
                     ->limit($limit)
                     ->get();
    }

    /**
     * Get related products
     */
    public function getRelatedProducts(Product $product, int $limit = 6): Collection
    {
        return Product::where('status', 'active')
                     ->where('id', '!=', $product->id)
                     ->where('category_id', $product->category_id)
                     ->with(['category', 'images', 'variants'])
                     ->orderBy('created_at', 'desc')
                     ->limit($limit)
                     ->get();
    }

    /**
     * Search products with advanced filters
     */
    public function searchProducts(string $query, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $productQuery = Product::where('status', 'active')
                              ->where(function ($q) use ($query) {
                                  $q->where('name', 'like', "%{$query}%")
                                    ->orWhere('description', 'like', "%{$query}%")
                                    ->orWhereHas('variants', function ($vq) use ($query) {
                                        $vq->where('sku', 'like', "%{$query}%");
                                    });
                              });

        // Apply category filter
        if (isset($filters['category_id'])) {
            $productQuery->where('category_id', $filters['category_id']);
        }

        // Apply price range filter
        if (isset($filters['min_price']) || isset($filters['max_price'])) {
            $productQuery->whereHas('variants', function ($vq) use ($filters) {
                if (isset($filters['min_price'])) {
                    $vq->where('price', '>=', $filters['min_price']);
                }
                if (isset($filters['max_price'])) {
                    $vq->where('price', '<=', $filters['max_price']);
                }
            });
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'relevance';
        switch ($sortBy) {
            case 'price_low':
                $productQuery->join('ProductVariants', 'Products.id', '=', 'ProductVariants.product_id')
                           ->orderBy('ProductVariants.price', 'asc');
                break;
            case 'price_high':
                $productQuery->join('ProductVariants', 'Products.id', '=', 'ProductVariants.product_id')
                           ->orderBy('ProductVariants.price', 'desc');
                break;
            case 'newest':
                $productQuery->orderBy('created_at', 'desc');
                break;
            case 'name':
                $productQuery->orderBy('name', 'asc');
                break;
            default:
                $productQuery->orderBy('created_at', 'desc');
        }

        return $productQuery->with(['category', 'images', 'variants'])
                           ->paginate($perPage);
    }

    /**
     * Get product statistics
     */
    public function getProductStatistics(): array
    {
        $totalProducts = Product::count();
        $activeProducts = Product::where('status', 'active')->count();
        $inactiveProducts = Product::where('status', 'inactive')->count();
        $draftProducts = Product::where('status', 'draft')->count();

        $categoryStats = Product::join('Categories', 'Products.category_id', '=', 'Categories.id')
                               ->selectRaw('Categories.name as category_name, COUNT(*) as product_count')
                               ->groupBy('Categories.id', 'Categories.name')
                               ->orderBy('product_count', 'desc')
                               ->get();

        return [
            'total_products' => $totalProducts,
            'active_products' => $activeProducts,
            'inactive_products' => $inactiveProducts,
            'draft_products' => $draftProducts,
            'category_breakdown' => $categoryStats,
        ];
    }

    /**
     * Update product status
     */
    public function updateProductStatus(Product $product, string $status): Product
    {
        $product->update(['status' => $status]);
        return $product;
    }

    /**
     * Check if product can be deleted
     */
    public function canDeleteProduct(Product $product): array
    {
        $hasOrders = $product->variants()
                           ->whereHas('cartDetails')
                           ->orWhereHas('purchaseOrderDetails')
                           ->exists();

        $hasInventory = $product->variants()
                              ->whereHas('inventories', function ($q) {
                                  $q->where('quantity_on_hand', '>', 0);
                              })
                              ->exists();

        $canDelete = !$hasOrders && !$hasInventory;

        return [
            'can_delete' => $canDelete,
            'reasons' => [
                'has_orders' => $hasOrders,
                'has_inventory' => $hasInventory,
            ]
        ];
    }

    /**
     * Generate unique slug for product
     */
    private function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (Product::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Bulk update product status
     */
    public function bulkUpdateStatus(array $productIds, string $status): int
    {
        return Product::whereIn('id', $productIds)->update(['status' => $status]);
    }

    /**
     * Get products by category
     */
    public function getProductsByCategory(int $categoryId, int $perPage = 15): LengthAwarePaginator
    {
        return Product::where('category_id', $categoryId)
                     ->where('status', 'active')
                     ->with(['category', 'images', 'variants'])
                     ->orderBy('created_at', 'desc')
                     ->paginate($perPage);
    }
}