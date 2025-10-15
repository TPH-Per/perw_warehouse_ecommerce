<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartDetail;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class CartApiController extends Controller
{
    /**
     * Get user's cart
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get or create cart
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);

        // Load cart details with product information
        $cart->load([
            'cartDetails.variant.product.images' => function ($query) {
                $query->where('is_primary', true);
            }
        ]);

        // Calculate totals
        $totalItems = $cart->cartDetails->sum('quantity');
        $totalAmount = $cart->cartDetails->sum(function ($detail) {
            return $detail->price * $detail->quantity;
        });

        return response()->json([
            'id' => $cart->id,
            'user_id' => $cart->user_id,
            'created_at' => $cart->created_at,
            'updated_at' => $cart->updated_at,
            'cart_details' => $cart->cartDetails->map(function ($detail) {
                return [
                    'id' => $detail->id,
                    'cart_id' => $detail->cart_id,
                    'product_variant_id' => $detail->product_variant_id,
                    'quantity' => $detail->quantity,
                    'price' => $detail->price,
                    'subtotal' => $detail->price * $detail->quantity,
                    'created_at' => $detail->created_at,
                    'variant' => $detail->variant ? [
                        'id' => $detail->variant->id,
                        'sku' => $detail->variant->sku,
                        'size' => $detail->variant->size,
                        'color' => $detail->variant->color,
                        'price' => $detail->variant->price,
                        'stock_quantity' => $detail->variant->stock_quantity,
                        'product' => $detail->variant->product ? [
                            'id' => $detail->variant->product->id,
                            'name' => $detail->variant->product->name,
                            'slug' => $detail->variant->product->slug,
                            'images' => $detail->variant->product->images,
                        ] : null,
                    ] : null,
                ];
            }),
            'total_items' => $totalItems,
            'total_amount' => $totalAmount,
        ]);
    }

    /**
     * Add item to cart
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $variant = ProductVariant::findOrFail($validated['product_variant_id']);

        // Check stock availability
        if ($variant->stock_quantity < $validated['quantity']) {
            return response()->json([
                'message' => 'Insufficient stock available'
            ], 400);
        }

        // Get or create cart
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);

        // Check if item already exists in cart
        $cartDetail = CartDetail::where('cart_id', $cart->id)
            ->where('product_variant_id', $validated['product_variant_id'])
            ->first();

        if ($cartDetail) {
            // Update quantity
            $newQuantity = $cartDetail->quantity + $validated['quantity'];

            if ($variant->stock_quantity < $newQuantity) {
                return response()->json([
                    'message' => 'Insufficient stock available'
                ], 400);
            }

            $cartDetail->quantity = $newQuantity;
            $cartDetail->save();
        } else {
            // Create new cart item
            CartDetail::create([
                'cart_id' => $cart->id,
                'product_variant_id' => $validated['product_variant_id'],
                'quantity' => $validated['quantity'],
                'price' => $variant->price,
            ]);
        }

        // Return updated cart
        return $this->index($request);
    }

    /**
     * Update cart item
     */
    public function update(Request $request, $itemId)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);

        $user = $request->user();
        $cart = Cart::where('user_id', $user->id)->firstOrFail();

        $cartDetail = CartDetail::where('cart_id', $cart->id)
            ->where('id', $itemId)
            ->firstOrFail();

        if ($validated['quantity'] == 0) {
            // Remove item if quantity is 0
            $cartDetail->delete();
        } else {
            // Check stock availability
            $variant = ProductVariant::findOrFail($cartDetail->product_variant_id);

            if ($variant->stock_quantity < $validated['quantity']) {
                return response()->json([
                    'message' => 'Insufficient stock available'
                ], 400);
            }

            $cartDetail->quantity = $validated['quantity'];
            $cartDetail->save();
        }

        // Return updated cart
        return $this->index($request);
    }

    /**
     * Remove item from cart
     */
    public function destroy(Request $request, $itemId)
    {
        $user = $request->user();
        $cart = Cart::where('user_id', $user->id)->firstOrFail();

        $cartDetail = CartDetail::where('cart_id', $cart->id)
            ->where('id', $itemId)
            ->firstOrFail();

        $cartDetail->delete();

        // Return updated cart
        return $this->index($request);
    }

    /**
     * Clear entire cart
     */
    public function clear(Request $request)
    {
        $user = $request->user();
        $cart = Cart::where('user_id', $user->id)->first();

        if ($cart) {
            CartDetail::where('cart_id', $cart->id)->delete();
        }

        return response()->json([
            'message' => 'Cart cleared successfully'
        ]);
    }
}
