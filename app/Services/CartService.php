<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartDetail;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CartService
{
    /**
     * Get or create cart for user
     */
    public function getOrCreateCart(int $userId): Cart
    {
        return Cart::firstOrCreate(['user_id' => $userId]);
    }

    /**
     * Get cart with items and calculations
     */
    public function getCartWithItems(int $userId): array
    {
        $cart = $this->getOrCreateCart($userId);
        
        $cartItems = CartDetail::where('cart_id', $cart->id)
                              ->with([
                                  'productVariant.product:id,name,slug,status',
                                  'productVariant.inventories'
                              ])
                              ->get();

        // Calculate totals
        $subtotal = 0;
        $totalItems = 0;
        
        $items = $cartItems->map(function ($item) use (&$subtotal, &$totalItems) {
            $variant = $item->productVariant;
            $price = $variant->price ?? 0;
            $lineTotal = $price * $item->quantity;
            $subtotal += $lineTotal;
            $totalItems += $item->quantity;

            // Check stock availability
            $availableStock = $variant->inventories->sum('quantity_on_hand');
            
            return [
                'id' => $item->id,
                'product_variant_id' => $item->product_variant_id,
                'quantity' => $item->quantity,
                'price' => $price,
                'line_total' => $lineTotal,
                'available_stock' => $availableStock,
                'is_in_stock' => $availableStock >= $item->quantity,
                'product' => $variant->product,
                'variant' => [
                    'id' => $variant->id,
                    'name' => $variant->variant_name,
                    'sku' => $variant->sku,
                    'price' => $variant->price,
                ]
            ];
        });

        return [
            'cart' => [
                'id' => $cart->id,
                'items' => $items,
                'subtotal' => $subtotal,
                'total_items' => $totalItems,
                'item_count' => $cartItems->count(),
            ]
        ];
    }

    /**
     * Add item to cart
     */
    public function addToCart(int $userId, int $productVariantId, int $quantity): array
    {
        return DB::transaction(function () use ($userId, $productVariantId, $quantity) {
            $cart = $this->getOrCreateCart($userId);
            
            $variant = ProductVariant::with(['product', 'inventories'])->findOrFail($productVariantId);
            
            // Check if product is active
            if ($variant->product->status !== 'active') {
                throw new \Exception('Product is not available.');
            }

            // Check stock availability
            $availableStock = $variant->inventories->sum('quantity_on_hand');
            
            // Check if item already exists in cart
            $existingItem = CartDetail::where('cart_id', $cart->id)
                                     ->where('product_variant_id', $productVariantId)
                                     ->first();

            $newQuantity = $quantity;
            if ($existingItem) {
                $newQuantity += $existingItem->quantity;
            }

            if ($newQuantity > $availableStock) {
                throw new \Exception("Only {$availableStock} items available in stock.");
            }

            if ($existingItem) {
                $existingItem->update(['quantity' => $newQuantity]);
                $cartItem = $existingItem;
            } else {
                $cartItem = CartDetail::create([
                    'cart_id' => $cart->id,
                    'product_variant_id' => $productVariantId,
                    'quantity' => $quantity,
                ]);
            }

            return [
                'cart_item' => $cartItem->load('productVariant.product'),
                'message' => 'Item added to cart successfully.'
            ];
        });
    }

    /**
     * Update cart item quantity
     */
    public function updateCartItem(int $userId, int $variantId, int $quantity): array
    {
        return DB::transaction(function () use ($userId, $variantId, $quantity) {
            $cart = Cart::where('user_id', $userId)->firstOrFail();
            
            $cartItem = CartDetail::where('cart_id', $cart->id)
                                 ->where('product_variant_id', $variantId)
                                 ->with('productVariant.inventories')
                                 ->firstOrFail();

            // Check stock availability
            $availableStock = $cartItem->productVariant->inventories->sum('quantity_on_hand');
            
            if ($quantity > $availableStock) {
                throw new \Exception("Only {$availableStock} items available in stock.");
            }

            $cartItem->update(['quantity' => $quantity]);

            return [
                'cart_item' => $cartItem->load('productVariant.product'),
                'message' => 'Cart item updated successfully.'
            ];
        });
    }

    /**
     * Remove item from cart
     */
    public function removeFromCart(int $userId, int $variantId): bool
    {
        return DB::transaction(function () use ($userId, $variantId) {
            $cart = Cart::where('user_id', $userId)->firstOrFail();
            
            $cartItem = CartDetail::where('cart_id', $cart->id)
                                 ->where('product_variant_id', $variantId)
                                 ->firstOrFail();

            return $cartItem->delete();
        });
    }

    /**
     * Clear all items from cart
     */
    public function clearCart(int $userId): bool
    {
        $cart = Cart::where('user_id', $userId)->first();
        
        if ($cart) {
            return CartDetail::where('cart_id', $cart->id)->delete();
        }

        return true;
    }

    /**
     * Get cart item count and totals
     */
    public function getCartCount(int $userId): array
    {
        $cart = Cart::where('user_id', $userId)->first();
        
        $itemCount = 0;
        $totalQuantity = 0;
        
        if ($cart) {
            $cartDetails = CartDetail::where('cart_id', $cart->id)->get();
            $itemCount = $cartDetails->count();
            $totalQuantity = $cartDetails->sum('quantity');
        }

        return [
            'item_count' => $itemCount,
            'total_quantity' => $totalQuantity,
        ];
    }

    /**
     * Validate cart items for checkout
     */
    public function validateCartForCheckout(int $userId): array
    {
        $cartData = $this->getCartWithItems($userId);
        $cart = $cartData['cart'];
        
        if (empty($cart['items'])) {
            throw new \Exception('Cart is empty.');
        }

        $issues = [];
        $totalAmount = 0;

        foreach ($cart['items'] as $item) {
            // Check if item is still in stock
            if (!$item['is_in_stock']) {
                $issues[] = "Product '{$item['product']['name']}' is out of stock.";
                continue;
            }

            // Check if product is still active
            if ($item['product']['status'] !== 'active') {
                $issues[] = "Product '{$item['product']['name']}' is no longer available.";
                continue;
            }

            $totalAmount += $item['line_total'];
        }

        if (!empty($issues)) {
            throw new \Exception('Cart validation failed: ' . implode(' ', $issues));
        }

        return [
            'valid' => true,
            'total_amount' => $totalAmount,
            'item_count' => count($cart['items']),
            'items' => $cart['items']
        ];
    }

    /**
     * Get cart by cart ID (for admin/internal use)
     */
    public function getCartById(int $cartId): Cart
    {
        return Cart::with([
            'cartDetails.productVariant.product',
            'user:id,full_name,email'
        ])->findOrFail($cartId);
    }

    /**
     * Merge guest cart with user cart
     */
    public function mergeGuestCart(int $userId, array $guestCartItems): array
    {
        return DB::transaction(function () use ($userId, $guestCartItems) {
            $userCart = $this->getOrCreateCart($userId);
            $mergedItems = [];

            foreach ($guestCartItems as $guestItem) {
                try {
                    $result = $this->addToCart(
                        $userId,
                        $guestItem['product_variant_id'],
                        $guestItem['quantity']
                    );
                    $mergedItems[] = $result['cart_item'];
                } catch (\Exception $e) {
                    // Skip items that can't be merged (out of stock, etc.)
                    continue;
                }
            }

            return [
                'merged_items' => $mergedItems,
                'cart' => $this->getCartWithItems($userId)
            ];
        });
    }

    /**
     * Calculate cart shipping cost (basic implementation)
     */
    public function calculateShipping(int $userId, array $shippingData = []): array
    {
        $cartData = $this->getCartWithItems($userId);
        $subtotal = $cartData['cart']['subtotal'];
        
        // Basic shipping calculation - can be extended
        $shippingCost = 0;
        
        if ($subtotal < 500000) { // Free shipping over 500k VND
            $shippingCost = 30000; // Standard shipping cost
        }

        // Additional shipping options can be calculated here
        $shippingOptions = [
            'standard' => [
                'name' => 'Standard Shipping',
                'cost' => $shippingCost,
                'delivery_time' => '3-5 business days'
            ],
            'express' => [
                'name' => 'Express Shipping',
                'cost' => $shippingCost + 20000,
                'delivery_time' => '1-2 business days'
            ]
        ];

        return [
            'subtotal' => $subtotal,
            'shipping_options' => $shippingOptions,
            'free_shipping_threshold' => 500000
        ];
    }

    /**
     * Apply coupon to cart
     */
    public function applyCoupon(int $userId, string $couponCode): array
    {
        // This is a placeholder for coupon functionality
        // In a real implementation, you would validate the coupon
        // and calculate the discount
        
        throw new \Exception('Coupon functionality not implemented yet.');
    }
}