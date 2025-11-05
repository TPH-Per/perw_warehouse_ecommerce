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
use App\Models\ProductVariant;
use App\Services\WarehouseAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

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
                'shipment',
                'warehouse',
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
                'shipment',
                'warehouse',
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
            // Optional because the DB may not have this column
            'shipping_province' => 'nullable|string|max:100',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'shipping_method_id' => 'nullable|exists:shipping_methods,id',
            'notes' => 'nullable|string',
            'items' => 'sometimes|array|min:1',
            'items.*.variant_id' => 'nullable|integer|exists:product_variants,id',
            'items.*.product_id' => 'nullable|integer|exists:products,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.price' => 'required_with:items|numeric|min:0',
        ]);

        $user = $request->user();
        $province = $validated['shipping_province'] ?? null;
        $warehouseId = $province ? WarehouseAssignmentService::getWarehouseIdByProvince($province) : null;

        $itemsPayload = collect($validated['items'] ?? [])->filter(function ($item) {
            return !empty($item['variant_id']) || !empty($item['product_id']);
        });

        if ($itemsPayload->isNotEmpty()) {
            return $this->createOrderFromPayload($request, $validated, $warehouseId, $itemsPayload);
        }

        // Get cart
        $cart = Cart::where('user_id', $user->id)->firstOrFail();
        $cartDetails = CartDetail::where('cart_id', $cart->id)
            ->with(['variant.inventories'])
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
                $available = $detail->variant->inventories->sum(function ($inv) {
                    return max(0, ($inv->quantity_on_hand ?? 0) - ($inv->quantity_reserved ?? 0));
                });
                if ($available < $detail->quantity) {
                    throw new \Exception("Insufficient stock for {$detail->variant->sku}. Available: {$available}, Requested: {$detail->quantity}");
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
            $orderData = [
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
            ];
            // Add optional fields only if present in schema
            if (!empty($validated['shipping_province']) && Schema::hasColumn('purchase_orders', 'shipping_province')) {
                $orderData['shipping_province'] = $validated['shipping_province'];
            }
            if ($warehouseId && Schema::hasColumn('purchase_orders', 'warehouse_id')) {
                $orderData['warehouse_id'] = $warehouseId;
            }

            $order = PurchaseOrder::create($orderData);

            // Create order details and update inventory
            foreach ($cartDetails as $detail) {
                PurchaseOrderDetail::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $detail->product_variant_id,
                    'quantity' => $detail->quantity,
                    'price_at_purchase' => $detail->price,
                    'subtotal' => $detail->price * $detail->quantity,
                ]);

                // Reserve/decrement inventory from first available records
                foreach ($detail->variant->inventories as $inv) {
                    $available = max(0, ($inv->quantity_on_hand ?? 0) - ($inv->quantity_reserved ?? 0));
                    if ($available <= 0) continue;
                    $use = min($available, $detail->quantity);
                    $inv->quantity_on_hand = ($inv->quantity_on_hand ?? 0) - $use;
                    $inv->save();
                    $detail->quantity -= $use;
                    if ($detail->quantity <= 0) break;
                }
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
                'shipment',
                'warehouse',
            ]);

            return response()->json($order, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order creation failed (cart flow)', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to create order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create order from direct payload items (frontend cart sync)
     */
    protected function createOrderFromPayload(Request $request, array $validated, ?int $warehouseId, $itemsPayload)
    {
        $user = $request->user();

        DB::beginTransaction();

        try {
            $details = [];
            $subTotal = 0;

            foreach ($itemsPayload as $item) {
                $variant = null;
                if (!empty($item['variant_id'])) {
                    $variant = ProductVariant::with('inventories')->find($item['variant_id']);
                }
                if (!$variant && !empty($item['product_id'])) {
                    $variant = ProductVariant::with('inventories')->where('product_id', $item['product_id'])->first();
                }

                if (!$variant) {
                    throw new \Exception('Không tìm thấy biến thể sản phẩm.');
                }

                $quantity = max(1, (int) ($item['quantity'] ?? 1));
                $price = isset($item['price']) ? (float) $item['price'] : (float) $variant->price;
                $lineTotal = $price * $quantity;

                if ($lineTotal <= 0) {
                    throw new \Exception('Giá trị sản phẩm không hợp lệ.');
                }

                $details[] = [
                    'variant' => $variant,
                    'quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => $lineTotal,
                ];

                $subTotal += $lineTotal;
            }

            if ($subTotal <= 0) {
                throw new \Exception('Giá trị đơn hàng không hợp lệ.');
            }

            $shippingFee = 50000;
            $discountAmount = 0;
            $totalAmount = $subTotal + $shippingFee - $discountAmount;
            $orderCode = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));

            $orderData = [
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
            ];
            if (!empty($validated['shipping_province']) && Schema::hasColumn('purchase_orders', 'shipping_province')) {
                $orderData['shipping_province'] = $validated['shipping_province'];
            }
            if ($warehouseId && Schema::hasColumn('purchase_orders', 'warehouse_id')) {
                $orderData['warehouse_id'] = $warehouseId;
            }

            $order = PurchaseOrder::create($orderData);

            foreach ($details as $detail) {
                PurchaseOrderDetail::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $detail['variant']->id,
                    'quantity' => $detail['quantity'],
                    'price_at_purchase' => $detail['price'],
                    'subtotal' => $detail['subtotal'],
                ]);

                $inventories = $detail['variant']->inventories ?? collect();
                $deductedQuantity = $detail['quantity'];

                foreach ($inventories as $inventory) {
                    if ($inventory->quantity_on_hand !== null) {
                        $inventory->quantity_on_hand = max(0, ($inventory->quantity_on_hand ?? 0) - $deductedQuantity);
                        $inventory->save();
                    }

                    InventoryTransaction::create([
                        'product_variant_id' => $detail['variant']->id,
                        'warehouse_id' => $inventory->warehouse_id,
                        'order_id' => $order->id,
                        'type' => 'outbound',
                        'quantity' => $deductedQuantity,
                        'notes' => 'Order created from API payload',
                    ]);

                    // Deduct from first matching inventory only
                    break;
                }
            }

            Payment::create([
                'order_id' => $order->id,
                'payment_method_id' => $validated['payment_method_id'],
                'amount' => $totalAmount,
                'status' => 'pending',
            ]);

            Shipment::create([
                'order_id' => $order->id,
                'shipping_method_id' => $validated['shipping_method_id'] ?? 1,
                'status' => 'pending',
            ]);

            DB::commit();

            $order->load([
                'orderDetails.variant.product.images',
                'payment.paymentMethod',
                'shipment',
                'warehouse',
            ]);

            return response()->json($order, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order creation failed (payload flow)', [
                'user_id' => $request->user()->id,
                'items' => $itemsPayload,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to create order: ' . $e->getMessage(),
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
            // Restore inventory back to variant inventories
            foreach ($order->orderDetails as $detail) {
                $variant = $detail->variant()->with('inventories')->first();
                if (!$variant) continue;
                $qty = $detail->quantity;
                // Put back into the first inventory record(s)
                foreach ($variant->inventories as $inv) {
                    $inv->quantity_on_hand = ($inv->quantity_on_hand ?? 0) + $qty;
                    $inv->save();
                    // all restored in first inventory for simplicity
                    break;
                }
            }

            // Update order status
            $order->status = 'cancelled';
            $order->save();

            // Update payment status (use allowed enum values)
            if ($order->payment) {
                // If an order is cancelled, consider payment "refunded"
                $order->payment->status = 'refunded';
                $order->payment->save();
            }

            DB::commit();

            $order->load([
                'orderDetails.variant.product.images',
                'payment.paymentMethod',
                'shipment',
                'warehouse',
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
                'shipment.shippingMethod',
                'warehouse',
            ])
            ->firstOrFail();

        return response()->json($order);
    }
}
