<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartDetail;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderApiController extends Controller
{
    /**
     * Get user's orders
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = PurchaseOrder::where('user_id', $user->id)
            ->with([
                'orderDetails.variant.product.images',
                'payment.paymentMethod',
                'shipment'
            ])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->paginate($request->input('per_page', 15));

        return response()->json($orders);
    }

    /**
     * Get a single order
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $order = PurchaseOrder::where('user_id', $user->id)
            ->where('id', $id)
            ->with([
                'orderDetails.variant.product.images',
                'payment.paymentMethod',
                'shipment'
            ])
            ->firstOrFail();

        return response()->json($order);
    }

    /**
     * Create order from cart
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'shipping_recipient_name' => 'required|string|max:255',
            'shipping_recipient_phone' => 'required|string|max:20',
            'shipping_address' => 'required|string',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'shipping_method_id' => 'nullable|exists:shipping_methods,id',
            'notes' => 'nullable|string',
        ]);

        $user = $request->user();

        // Get cart
        $cart = Cart::where('user_id', $user->id)->firstOrFail();
        $cartDetails = CartDetail::where('cart_id', $cart->id)
            ->with('variant')
            ->get();

        if ($cartDetails->isEmpty()) {
            return response()->json([
                'message' => 'Cart is empty'
            ], 400);
        }

        // Start transaction
        DB::beginTransaction();

        try {
            // Check stock availability for all items
            foreach ($cartDetails as $detail) {
                if ($detail->variant->stock_quantity < $detail->quantity) {
                    throw new \Exception("Insufficient stock for {$detail->variant->sku}");
                }
            }

            // Calculate totals
            $subTotal = $cartDetails->sum(function ($detail) {
                return $detail->price * $detail->quantity;
            });

            $shippingFee = 50000; // Fixed shipping fee, can be calculated based on shipping method
            $discountAmount = 0; // Can be calculated based on coupons
            $totalAmount = $subTotal + $shippingFee - $discountAmount;

            // Generate order code
            $orderCode = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));

            // Create order
            $order = PurchaseOrder::create([
                'user_id' => $user->id,
                'order_code' => $orderCode,
                'status' => 'pending',
                'shipping_recipient_name' => $validated['shipping_recipient_name'],
                'shipping_recipient_phone' => $validated['shipping_recipient_phone'],
                'shipping_address' => $validated['shipping_address'],
                'sub_total' => $subTotal,
                'shipping_fee' => $shippingFee,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
            ]);

            // Create order details and update inventory
            foreach ($cartDetails as $detail) {
                PurchaseOrderDetail::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $detail->product_variant_id,
                    'quantity' => $detail->quantity,
                    'unit_price' => $detail->price,
                    'subtotal' => $detail->price * $detail->quantity,
                ]);

                // Update variant stock
                $variant = $detail->variant;
                $variant->stock_quantity -= $detail->quantity;
                $variant->save();

                // Create inventory transaction (if inventory tracking is enabled)
                // You can add inventory transaction logic here
            }

            // Create payment record
            Payment::create([
                'order_id' => $order->id,
                'payment_method_id' => $validated['payment_method_id'],
                'amount' => $totalAmount,
                'status' => 'pending',
            ]);

            // Create shipment record
            Shipment::create([
                'order_id' => $order->id,
                'shipping_method_id' => $validated['shipping_method_id'] ?? 1,
                'status' => 'pending',
            ]);

            // Clear cart
            CartDetail::where('cart_id', $cart->id)->delete();

            DB::commit();

            // Load relationships
            $order->load([
                'orderDetails.variant.product.images',
                'payment.paymentMethod',
                'shipment'
            ]);

            return response()->json($order, 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel order
     */
    public function cancel(Request $request, $id)
    {
        $user = $request->user();

        $order = PurchaseOrder::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        // Only allow cancellation of pending orders
        if (!in_array($order->status, ['pending', 'confirmed'])) {
            return response()->json([
                'message' => 'Cannot cancel order with current status'
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Restore stock
            foreach ($order->orderDetails as $detail) {
                $variant = $detail->variant;
                $variant->stock_quantity += $detail->quantity;
                $variant->save();
            }

            // Update order status
            $order->status = 'cancelled';
            $order->save();

            // Update payment status
            if ($order->payment) {
                $order->payment->status = 'cancelled';
                $order->payment->save();
            }

            DB::commit();

            $order->load([
                'orderDetails.variant.product.images',
                'payment.paymentMethod',
                'shipment'
            ]);

            return response()->json($order);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to cancel order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Track order by order code
     */
    public function track($orderCode)
    {
        $order = PurchaseOrder::where('order_code', $orderCode)
            ->with([
                'orderDetails.variant.product',
                'payment.paymentMethod',
                'shipment.shippingMethod'
            ])
            ->firstOrFail();

        return response()->json($order);
    }
}
