<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\Cart;
use App\Models\CartDetail;
use App\Models\Inventory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class OrderService
{
    /**
     * Get user's order history with filters
     */
    public function getUserOrders(int $userId, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = PurchaseOrder::where('user_id', $userId)
                             ->with(['details.productVariant.product', 'shipment', 'payments']);

        // Filter by status
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Date range filter
        if (isset($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        // Sort options
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Get order by ID for specific user
     */
    public function getUserOrder(int $userId, int $orderId): PurchaseOrder
    {
        return PurchaseOrder::where('id', $orderId)
                           ->where('user_id', $userId)
                           ->with([
                               'details.productVariant.product',
                               'shipment.shippingMethod',
                               'payments.paymentMethod'
                           ])
                           ->firstOrFail();
    }

    /**
     * Create order from cart
     */
    public function createOrderFromCart(int $userId, array $orderData): array
    {
        return DB::transaction(function () use ($userId, $orderData) {
            $cart = Cart::where('user_id', $userId)->firstOrFail();
            
            $cartItems = CartDetail::where('cart_id', $cart->id)
                                  ->with('productVariant.product')
                                  ->get();

            if ($cartItems->isEmpty()) {
                throw new \Exception('Cart is empty. Cannot create order with empty cart.');
            }

            // Validate and calculate order totals
            $orderCalculation = $this->validateAndCalculateOrder($cartItems);

            // Calculate shipping fee
            $shippingFee = $this->calculateShippingFee(
                $orderCalculation['sub_total'], 
                $orderData['shipping_address'] ?? null
            );

            $totalAmount = $orderCalculation['sub_total'] + $shippingFee;

            // Create order
            $order = PurchaseOrder::create([
                'user_id' => $userId,
                'order_code' => $this->generateOrderCode(),
                'status' => 'pending_payment',
                'shipping_recipient_name' => $orderData['shipping_recipient_name'],
                'shipping_recipient_phone' => $orderData['shipping_recipient_phone'],
                'shipping_address' => $orderData['shipping_address'],
                'sub_total' => $orderCalculation['sub_total'],
                'shipping_fee' => $shippingFee,
                'discount_amount' => $orderData['discount_amount'] ?? 0,
                'total_amount' => $totalAmount,
                'notes' => $orderData['notes'] ?? null,
            ]);

            // Create order details
            foreach ($orderCalculation['order_items'] as $item) {
                PurchaseOrderDetail::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $item['product_variant_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $item['line_total'],
                ]);
            }

            // Clear cart after successful order creation
            CartDetail::where('cart_id', $cart->id)->delete();

            return [
                'order' => $order->load(['details.productVariant.product']),
                'message' => 'Order created successfully.'
            ];
        });
    }

    /**
     * Cancel an order
     */
    public function cancelOrder(int $userId, int $orderId): PurchaseOrder
    {
        return DB::transaction(function () use ($userId, $orderId) {
            $order = PurchaseOrder::where('id', $orderId)
                                 ->where('user_id', $userId)
                                 ->firstOrFail();

            if (!$this->canCancelOrder($order)) {
                throw new \Exception('Cannot cancel order. Only orders with pending payment status can be cancelled.');
            }

            $order->update(['status' => 'cancelled']);

            return $order;
        });
    }

    /**
     * Update order status (admin function)
     */
    public function updateOrderStatus(int $orderId, string $status): PurchaseOrder
    {
        return DB::transaction(function () use ($orderId, $status) {
            $order = PurchaseOrder::findOrFail($orderId);
            
            $validStatuses = ['pending_payment', 'paid', 'processing', 'shipped', 'delivered', 'cancelled'];
            
            if (!in_array($status, $validStatuses)) {
                throw new \Exception('Invalid order status.');
            }

            $order->update(['status' => $status]);

            return $order;
        });
    }

    /**
     * Get order statistics for user
     */
    public function getUserOrderStatistics(int $userId): array
    {
        $stats = PurchaseOrder::where('user_id', $userId)
                             ->selectRaw('
                                 status,
                                 COUNT(*) as count,
                                 SUM(total_amount) as total_amount
                             ')
                             ->groupBy('status')
                             ->get()
                             ->keyBy('status');

        $totalOrders = PurchaseOrder::where('user_id', $userId)->count();
        $totalSpent = PurchaseOrder::where('user_id', $userId)
                                 ->whereNotIn('status', ['cancelled'])
                                 ->sum('total_amount');

        $recentOrders = PurchaseOrder::where('user_id', $userId)
                                   ->with('details.productVariant.product')
                                   ->orderBy('created_at', 'desc')
                                   ->limit(5)
                                   ->get();

        return [
            'total_orders' => $totalOrders,
            'total_spent' => $totalSpent,
            'by_status' => $stats,
            'recent_orders' => $recentOrders,
        ];
    }

    /**
     * Get all orders (admin function)
     */
    public function getAllOrders(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PurchaseOrder::with(['user:id,full_name,email', 'details.productVariant.product']);

        // Filter by status
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by user
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Date range filter
        if (isset($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        // Search by order code
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('order_code', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('full_name', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Sort options
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Get order analytics (admin function)
     */
    public function getOrderAnalytics(array $filters = []): array
    {
        $query = PurchaseOrder::query();

        // Apply date filters
        if (isset($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        $totalOrders = $query->count();
        $totalRevenue = $query->whereNotIn('status', ['cancelled'])->sum('total_amount');
        $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        $statusBreakdown = PurchaseOrder::selectRaw('status, COUNT(*) as count')
                                       ->groupBy('status')
                                       ->get()
                                       ->keyBy('status');

        $monthlyStats = PurchaseOrder::selectRaw('
                                      YEAR(created_at) as year,
                                      MONTH(created_at) as month,
                                      COUNT(*) as order_count,
                                      SUM(total_amount) as revenue
                                  ')
                                  ->whereNotIn('status', ['cancelled'])
                                  ->groupBy('year', 'month')
                                  ->orderBy('year', 'desc')
                                  ->orderBy('month', 'desc')
                                  ->limit(12)
                                  ->get();

        return [
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'average_order_value' => round($averageOrderValue, 2),
            'status_breakdown' => $statusBreakdown,
            'monthly_statistics' => $monthlyStats,
        ];
    }

    /**
     * Validate cart items and calculate order totals
     */
    private function validateAndCalculateOrder($cartItems): array
    {
        $subTotal = 0;
        $orderItems = [];

        foreach ($cartItems as $item) {
            $variant = $item->productVariant;
            
            // Check product status
            if ($variant->product->status !== 'active') {
                throw new \Exception("Product '{$variant->product->name}' is no longer available.");
            }

            // Check stock
            $availableStock = Inventory::where('product_variant_id', $variant->id)
                                     ->sum('quantity_on_hand');
            
            if ($item->quantity > $availableStock) {
                throw new \Exception("Insufficient stock for product '{$variant->product->name}'. Available: {$availableStock}");
            }

            $lineTotal = $variant->price * $item->quantity;
            $subTotal += $lineTotal;

            $orderItems[] = [
                'product_variant_id' => $variant->id,
                'quantity' => $item->quantity,
                'unit_price' => $variant->price,
                'line_total' => $lineTotal,
            ];
        }

        return [
            'sub_total' => $subTotal,
            'order_items' => $orderItems,
        ];
    }

    /**
     * Generate unique order code
     */
    private function generateOrderCode(): string
    {
        $prefix = 'ORD';
        $timestamp = now()->format('YmdHis');
        $random = str_pad(mt_rand(0, 999), 3, '0', STR_PAD_LEFT);
        
        $orderCode = $prefix . $timestamp . $random;
        
        // Ensure uniqueness
        while (PurchaseOrder::where('order_code', $orderCode)->exists()) {
            $random = str_pad(mt_rand(0, 999), 3, '0', STR_PAD_LEFT);
            $orderCode = $prefix . $timestamp . $random;
        }
        
        return $orderCode;
    }

    /**
     * Calculate shipping fee
     */
    private function calculateShippingFee(float $subTotal, ?string $shippingAddress = null): float
    {
        // Basic shipping calculation
        // Free shipping for orders above 500,000 VND
        if ($subTotal >= 500000) {
            return 0;
        }
        
        // Standard shipping fee
        $standardFee = 30000;
        
        // Additional logic can be added here for different regions, weight, etc.
        // For now, return standard fee
        return $standardFee;
    }

    /**
     * Check if order can be cancelled
     */
    private function canCancelOrder(PurchaseOrder $order): bool
    {
        return in_array($order->status, ['pending_payment']);
    }

    /**
     * Get order by order code
     */
    public function getOrderByCode(string $orderCode): PurchaseOrder
    {
        return PurchaseOrder::where('order_code', $orderCode)
                           ->with([
                               'details.productVariant.product',
                               'shipment.shippingMethod',
                               'payments.paymentMethod',
                               'user:id,full_name,email'
                           ])
                           ->firstOrFail();
    }

    /**
     * Process order payment
     */
    public function processPayment(int $orderId, array $paymentData): array
    {
        return DB::transaction(function () use ($orderId, $paymentData) {
            $order = PurchaseOrder::findOrFail($orderId);
            
            if ($order->status !== 'pending_payment') {
                throw new \Exception('Order is not in pending payment status.');
            }

            // Here you would integrate with payment gateway
            // For now, we'll simulate successful payment
            
            $order->update(['status' => 'paid']);

            return [
                'order' => $order,
                'payment_status' => 'success',
                'message' => 'Payment processed successfully.'
            ];
        });
    }
}