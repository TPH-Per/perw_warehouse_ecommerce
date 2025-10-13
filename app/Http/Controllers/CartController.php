<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Get authenticated user ID
     */
    private function getAuthenticatedUserId(): int
    {
        $userId = Auth::id();
        if (!$userId) {
            throw new \Exception('User not authenticated');
        }
        return $userId;
    }

    /**
     * Display the user's cart
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $result = $this->cartService->getCartWithItems($userId);

            return response()->json([
                'cart' => $result['cart'],
                'message' => 'Cart retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve cart.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Add item to cart
     */
    public function add(Request $request): JsonResponse
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $result = $this->cartService->addToCart(
                $userId,
                $request->product_variant_id,
                $request->quantity
            );

            return response()->json([
                'cart_item' => $result['cart_item'],
                'message' => $result['message']
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'not available') || str_contains($e->getMessage(), 'stock')) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors' => ['product' => [$e->getMessage()]]
                ], 422);
            }
            
            return response()->json([
                'message' => 'Failed to add item to cart.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Update cart item quantity
     */
    public function update(Request $request, int $variantId): JsonResponse
    {
        try {
            $request->validate([
                'quantity' => 'required|integer|min:1',
            ]);

            $userId = $this->getAuthenticatedUserId();
            $result = $this->cartService->updateCartItem(
                $userId,
                $variantId,
                $request->quantity
            );

            return response()->json([
                'cart_item' => $result['cart_item'],
                'message' => $result['message']
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Cart item not found.',
                'errors' => ['cart_item' => ['The requested cart item does not exist.']]
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'stock')) {
                return response()->json([
                    'message' => 'Insufficient stock.',
                    'errors' => ['quantity' => [$e->getMessage()]]
                ], 422);
            }
            
            return response()->json([
                'message' => 'Failed to update cart item.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Remove item from cart
     */
    public function remove(int $variantId): JsonResponse
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $this->cartService->removeFromCart($userId, $variantId);

            return response()->json([
                'message' => 'Item removed from cart successfully.'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Cart item not found.',
                'errors' => ['cart_item' => ['The requested cart item does not exist.']]
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to remove item from cart.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Clear all items from cart
     */
    public function clear(Request $request): JsonResponse
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $this->cartService->clearCart($userId);

            return response()->json([
                'message' => 'Cart cleared successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to clear cart.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Get cart item count for user
     */
    public function count(Request $request): JsonResponse
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $result = $this->cartService->getCartCount($userId);

            return response()->json([
                'item_count' => $result['item_count'],
                'total_quantity' => $result['total_quantity'],
                'message' => 'Cart count retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get cart count.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }
}