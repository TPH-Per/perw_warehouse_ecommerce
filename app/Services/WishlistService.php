<?php

namespace App\Services;

use App\Models\Wishlist;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class WishlistService
{
    /**
     * Get user's wishlist with product details
     */
    public function getUserWishlist(int $userId): Collection
    {
        return Wishlist::where('user_id', $userId)
                      ->with([
                          'product.images',
                          'product.variants',
                          'product.category',
                          'product.reviews' => function ($q) {
                              $q->where('is_approved', true);
                          }
                      ])
                      ->latest()
                      ->get()
                      ->map(function ($item) {
                          $product = $item->product;
                          return [
                              'wishlist_id' => $item->id,
                              'product_id' => $product->id,
                              'product_name' => $product->name,
                              'product_slug' => $product->slug,
                              'product_image' => $product->primary_image,
                              'min_price' => $product->min_price,
                              'max_price' => $product->max_price,
                              'price_range' => $product->price_range,
                              'average_rating' => $product->average_rating,
                              'review_count' => $product->review_count,
                              'status' => $product->status,
                              'is_in_stock' => $product->hasStock(),
                              'category' => $product->category?->name,
                              'added_at' => $item->created_at,
                          ];
                      });
    }

    /**
     * Add product to wishlist
     */
    public function addToWishlist(int $userId, int $productId): array
    {
        // Check if product exists and is active
        $product = Product::findOrFail($productId);

        // Check if already in wishlist
        $existing = Wishlist::where('user_id', $userId)
                           ->where('product_id', $productId)
                           ->first();

        if ($existing) {
            return [
                'success' => false,
                'message' => 'Product is already in your wishlist',
                'in_wishlist' => true,
            ];
        }

        Wishlist::create([
            'user_id' => $userId,
            'product_id' => $productId,
        ]);

        return [
            'success' => true,
            'message' => 'Product added to wishlist successfully',
            'in_wishlist' => true,
        ];
    }

    /**
     * Remove product from wishlist
     */
    public function removeFromWishlist(int $userId, int $productId): array
    {
        $deleted = Wishlist::where('user_id', $userId)
                          ->where('product_id', $productId)
                          ->delete();

        if ($deleted) {
            return [
                'success' => true,
                'message' => 'Product removed from wishlist successfully',
                'in_wishlist' => false,
            ];
        }

        return [
            'success' => false,
            'message' => 'Product not found in wishlist',
            'in_wishlist' => false,
        ];
    }

    /**
     * Toggle product in wishlist
     */
    public function toggleWishlist(int $userId, int $productId): array
    {
        return Wishlist::toggleWishlist($userId, $productId);
    }

    /**
     * Check if product is in user's wishlist
     */
    public function isInWishlist(int $userId, int $productId): bool
    {
        return Wishlist::isInWishlist($userId, $productId);
    }

    /**
     * Get wishlist count for user
     */
    public function getWishlistCount(int $userId): int
    {
        return Wishlist::where('user_id', $userId)->count();
    }

    /**
     * Clear all wishlist items for user
     */
    public function clearWishlist(int $userId): int
    {
        return Wishlist::where('user_id', $userId)->delete();
    }

    /**
     * Get products in user's wishlist with stock status
     */
    public function getWishlistWithStockStatus(int $userId): Collection
    {
        return Wishlist::where('user_id', $userId)
                      ->with(['product.variants.inventories'])
                      ->get()
                      ->map(function ($item) {
                          $product = $item->product;
                          $hasStock = $product->hasStock();
                          $isLowStock = $product->isLowStock();

                          return [
                              'wishlist_id' => $item->id,
                              'product' => $product,
                              'stock_status' => $hasStock ? ($isLowStock ? 'low' : 'in_stock') : 'out_of_stock',
                              'total_stock' => $product->total_stock,
                              'can_purchase' => $hasStock && $product->isActive(),
                          ];
                      });
    }

    /**
     * Move wishlist items to cart
     */
    public function moveToCart(int $userId, array $productIds = []): array
    {
        $wishlistItems = Wishlist::where('user_id', $userId)
                                ->when(!empty($productIds), function ($q) use ($productIds) {
                                    return $q->whereIn('product_id', $productIds);
                                })
                                ->with('product.variants')
                                ->get();

        if ($wishlistItems->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No items to move',
                'moved_count' => 0,
            ];
        }

        $movedCount = 0;
        $cartService = new CartService();

        foreach ($wishlistItems as $item) {
            $product = $item->product;
            
            // Skip if product is not active or has no stock
            if (!$product->isActive() || !$product->hasStock()) {
                continue;
            }

            // Get default variant
            $defaultVariant = $product->variants->firstWhere('is_default', true) 
                           ?? $product->variants->first();

            if ($defaultVariant && $defaultVariant->isInStock()) {
                try {
                    $cartService->addToCart($userId, $defaultVariant->id, 1);
                    $item->delete();
                    $movedCount++;
                } catch (\Exception $e) {
                    // Continue with next item if this one fails
                    continue;
                }
            }
        }

        return [
            'success' => $movedCount > 0,
            'message' => $movedCount > 0 
                ? "Successfully moved {$movedCount} item(s) to cart" 
                : 'No items could be moved to cart',
            'moved_count' => $movedCount,
        ];
    }

    /**
     * Get available wishlist products (in stock and active)
     */
    public function getAvailableWishlistProducts(int $userId): Collection
    {
        return Wishlist::where('user_id', $userId)
                      ->whereHas('product', function ($q) {
                          $q->where('status', 'active');
                      })
                      ->with(['product' => function ($q) {
                          $q->with(['variants.inventories']);
                      }])
                      ->get()
                      ->filter(function ($item) {
                          return $item->product->hasStock();
                      })
                      ->values();
    }

    /**
     * Send back-in-stock notifications for wishlist products
     */
    public function notifyBackInStock(int $productId): int
    {
        $product = Product::findOrFail($productId);

        if (!$product->hasStock() || !$product->isActive()) {
            return 0;
        }

        $wishlists = Wishlist::where('product_id', $productId)->get();
        $notificationCount = 0;

        foreach ($wishlists as $wishlist) {
            // You can implement notification logic here
            // For now, we'll just count
            $notificationCount++;
        }

        return $notificationCount;
    }
}
