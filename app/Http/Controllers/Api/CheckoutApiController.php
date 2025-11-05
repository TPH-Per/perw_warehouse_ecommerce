<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\PaymentMethod;
use App\Models\ShippingMethod;
use Illuminate\Http\Request;

class CheckoutApiController extends Controller
{
    /**
     * Get checkout information (cart summary, payment methods, shipping methods)
     */
    public function show(Request $request)
    {
        $user = $request->user();

        // Get cart
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);
        $cart->load(['cartDetails.variant.product.images' => function ($q) {
            $q->where('is_primary', true);
        }]);

        if ($cart->cartDetails->isEmpty()) {
            return response()->json([
                'message' => 'Cart is empty'
            ], 400);
        }

        // Get payment and shipping methods
        $paymentMethods = PaymentMethod::all();
        $shippingMethods = ShippingMethod::all();

        // Calculate totals
        $subTotal = $cart->cartDetails->sum(function ($detail) {
            return ($detail->price ?? 0) * $detail->quantity;
        });
        $shippingFee = 50000; // Fixed shipping fee
        $discountAmount = 0; // Placeholder for discount logic
        $totalAmount = $subTotal + $shippingFee - $discountAmount;

        return response()->json([
            'cart' => [
                'id' => $cart->id,
                'items' => $cart->cartDetails->map(function ($detail) {
                    return [
                        'id' => $detail->id,
                        'product_variant_id' => $detail->product_variant_id,
                        'quantity' => $detail->quantity,
                        'price' => $detail->price,
                        'subtotal' => ($detail->price ?? 0) * $detail->quantity,
                        'variant' => $detail->variant ? [
                            'id' => $detail->variant->id,
                            'sku' => $detail->variant->sku,
                            'size' => $detail->variant->size,
                            'color' => $detail->variant->color,
                            'price' => $detail->variant->price,
                            'product' => $detail->variant->product ? [
                                'id' => $detail->variant->product->id,
                                'name' => $detail->variant->product->name,
                                'slug' => $detail->variant->product->slug,
                                'images' => $detail->variant->product->images,
                            ] : null,
                        ] : null,
                    ];
                }),
                'total_items' => $cart->cartDetails->sum('quantity'),
            ],
            'payment_methods' => $paymentMethods,
            'shipping_methods' => $shippingMethods,
            'pricing' => [
                'sub_total' => $subTotal,
                'shipping_fee' => $shippingFee,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
            ],
        ]);
    }
}
