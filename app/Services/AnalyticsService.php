<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\Product;
use App\Models\User;
use App\Models\Inventory;
use App\Models\ProductReview;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsService
{
    /**
     * Get comprehensive dashboard statistics
     */
    public function getDashboardStats(array $filters = []): array
    {
        $dateFrom = $filters['from_date'] ?? now()->startOfMonth();
        $dateTo = $filters['to_date'] ?? now()->endOfDay();

        return [
            'revenue' => $this->getRevenueStats($dateFrom, $dateTo),
            'orders' => $this->getOrderStats($dateFrom, $dateTo),
            'products' => $this->getProductStats(),
            'inventory' => $this->getInventoryStats(),
            'customers' => $this->getCustomerStats($dateFrom, $dateTo),
            'trends' => $this->getTrendData($dateFrom, $dateTo),
        ];
    }

    /**
     * Get revenue statistics
     */
    protected function getRevenueStats(Carbon $from, Carbon $to): array
    {
        $revenue = PurchaseOrder::whereBetween('created_at', [$from, $to])
                               ->whereNotIn('status', ['cancelled', 'refunded'])
                               ->selectRaw('
                                   SUM(total_amount) as total_revenue,
                                   SUM(sub_total) as gross_revenue,
                                   SUM(shipping_fee) as shipping_revenue,
                                   SUM(discount_amount) as total_discounts,
                                   AVG(total_amount) as average_order_value,
                                   COUNT(*) as order_count
                               ')
                               ->first();

        $previousPeriod = $this->getPreviousPeriodRevenue($from, $to);

        return [
            'total_revenue' => $revenue->total_revenue ?? 0,
            'gross_revenue' => $revenue->gross_revenue ?? 0,
            'shipping_revenue' => $revenue->shipping_revenue ?? 0,
            'total_discounts' => $revenue->total_discounts ?? 0,
            'average_order_value' => $revenue->average_order_value ?? 0,
            'order_count' => $revenue->order_count ?? 0,
            'growth_rate' => $this->calculateGrowthRate(
                $revenue->total_revenue ?? 0,
                $previousPeriod
            ),
        ];
    }

    /**
     * Get order statistics
     */
    protected function getOrderStats(Carbon $from, Carbon $to): array
    {
        $orders = PurchaseOrder::whereBetween('created_at', [$from, $to])
                             ->selectRaw('
                                 status,
                                 COUNT(*) as count,
                                 SUM(total_amount) as revenue
                             ')
                             ->groupBy('status')
                             ->get()
                             ->keyBy('status');

        $recentOrders = PurchaseOrder::with(['user:id,full_name', 'details'])
                                    ->latest()
                                    ->limit(10)
                                    ->get()
                                    ->map(function ($order) {
                                        return [
                                            'id' => $order->id,
                                            'order_code' => $order->order_code,
                                            'customer_name' => $order->user->full_name,
                                            'total_amount' => $order->total_amount,
                                            'status' => $order->status,
                                            'created_at' => $order->created_at,
                                        ];
                                    });

        return [
            'total_orders' => PurchaseOrder::whereBetween('created_at', [$from, $to])->count(),
            'pending_payment' => $orders->get('pending_payment')?->count ?? 0,
            'paid' => $orders->get('paid')?->count ?? 0,
            'processing' => $orders->get('processing')?->count ?? 0,
            'shipped' => $orders->get('shipped')?->count ?? 0,
            'delivered' => $orders->get('delivered')?->count ?? 0,
            'cancelled' => $orders->get('cancelled')?->count ?? 0,
            'recent_orders' => $recentOrders,
        ];
    }

    /**
     * Get product statistics
     */
    protected function getProductStats(): array
    {
        $products = Product::selectRaw('
                               status,
                               COUNT(*) as count
                           ')
                           ->groupBy('status')
                           ->get()
                           ->keyBy('status');

        $topSelling = $this->getTopSellingProducts(10);
        $lowStock = $this->getLowStockProducts(20);

        return [
            'total_products' => Product::count(),
            'active_products' => $products->get('active')?->count ?? 0,
            'inactive_products' => $products->get('inactive')?->count ?? 0,
            'draft_products' => $products->get('draft')?->count ?? 0,
            'top_selling' => $topSelling,
            'low_stock_count' => $lowStock->count(),
        ];
    }

    /**
     * Get inventory statistics
     */
    protected function getInventoryStats(): array
    {
        $inventory = Inventory::selectRaw('
                                   SUM(quantity_on_hand) as total_stock,
                                   SUM(quantity_reserved) as total_reserved,
                                   COUNT(*) as total_items
                               ')
                               ->first();

        $lowStockItems = $this->getLowStockProducts(50);

        return [
            'total_stock' => $inventory->total_stock ?? 0,
            'total_reserved' => $inventory->total_reserved ?? 0,
            'available_stock' => ($inventory->total_stock ?? 0) - ($inventory->total_reserved ?? 0),
            'total_items' => $inventory->total_items ?? 0,
            'low_stock_items' => $lowStockItems,
            'low_stock_count' => $lowStockItems->count(),
        ];
    }

    /**
     * Get customer statistics
     */
    protected function getCustomerStats(Carbon $from, Carbon $to): array
    {
        $newCustomers = User::whereBetween('created_at', [$from, $to])->count();
        $totalCustomers = User::count();
        $activeCustomers = User::whereHas('purchaseOrders', function ($q) use ($from, $to) {
            $q->whereBetween('created_at', [$from, $to]);
        })->count();

        return [
            'total_customers' => $totalCustomers,
            'new_customers' => $newCustomers,
            'active_customers' => $activeCustomers,
            'customer_growth_rate' => $totalCustomers > 0 
                ? round(($newCustomers / $totalCustomers) * 100, 2) 
                : 0,
        ];
    }

    /**
     * Get trend data for charts
     */
    protected function getTrendData(Carbon $from, Carbon $to): array
    {
        $dailyRevenue = PurchaseOrder::whereBetween('created_at', [$from, $to])
                                    ->whereNotIn('status', ['cancelled', 'refunded'])
                                    ->selectRaw('DATE(created_at) as date, SUM(total_amount) as revenue, COUNT(*) as orders')
                                    ->groupBy('date')
                                    ->orderBy('date')
                                    ->get();

        return [
            'daily_labels' => $dailyRevenue->pluck('date')->map(fn($d) => Carbon::parse($d)->format('M d'))->toArray(),
            'daily_revenue' => $dailyRevenue->pluck('revenue')->toArray(),
            'daily_orders' => $dailyRevenue->pluck('orders')->toArray(),
        ];
    }

    /**
     * Get top selling products
     */
    protected function getTopSellingProducts(int $limit = 10)
    {
        return Product::select('Products.*')
                     ->join('ProductVariants', 'Products.id', '=', 'ProductVariants.product_id')
                     ->join('PurchaseOrderDetails', 'ProductVariants.id', '=', 'PurchaseOrderDetails.product_variant_id')
                     ->join('PurchaseOrders', 'PurchaseOrderDetails.order_id', '=', 'PurchaseOrders.id')
                     ->where('PurchaseOrders.status', '!=', 'cancelled')
                     ->selectRaw('Products.*, SUM(PurchaseOrderDetails.quantity) as total_sold')
                     ->groupBy('Products.id')
                     ->orderBy('total_sold', 'desc')
                     ->limit($limit)
                     ->get()
                     ->map(function ($product) {
                         return [
                             'id' => $product->id,
                             'name' => $product->name,
                             'total_sold' => $product->total_sold,
                             'status' => $product->status,
                         ];
                     });
    }

    /**
     * Get low stock products
     */
    protected function getLowStockProducts(int $limit = 20)
    {
        return Product::select('Products.*')
                     ->join('ProductVariants', 'Products.id', '=', 'ProductVariants.product_id')
                     ->join('Inventories', 'ProductVariants.id', '=', 'Inventories.product_variant_id')
                     ->selectRaw('
                         Products.*,
                         ProductVariants.name as variant_name,
                         ProductVariants.sku,
                         SUM(Inventories.quantity_on_hand) as total_stock,
                         SUM(Inventories.quantity_reserved) as total_reserved
                     ')
                     ->groupBy('Products.id', 'ProductVariants.id')
                     ->havingRaw('(SUM(Inventories.quantity_on_hand) - SUM(Inventories.quantity_reserved)) <= 10')
                     ->orderByRaw('(SUM(Inventories.quantity_on_hand) - SUM(Inventories.quantity_reserved)) ASC')
                     ->limit($limit)
                     ->get()
                     ->map(function ($product) {
                         return [
                             'product_id' => $product->id,
                             'product_name' => $product->name,
                             'variant_name' => $product->variant_name,
                             'sku' => $product->sku,
                             'quantity' => $product->total_stock - $product->total_reserved,
                             'minimum_stock' => 10,
                         ];
                     });
    }

    /**
     * Calculate previous period revenue
     */
    protected function getPreviousPeriodRevenue(Carbon $from, Carbon $to): float
    {
        $periodLength = $from->diffInDays($to);
        $previousFrom = $from->copy()->subDays($periodLength);
        $previousTo = $from->copy()->subDay();

        return PurchaseOrder::whereBetween('created_at', [$previousFrom, $previousTo])
                           ->whereNotIn('status', ['cancelled', 'refunded'])
                           ->sum('total_amount') ?? 0;
    }

    /**
     * Calculate growth rate
     */
    protected function calculateGrowthRate(float $current, float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * Get sales by category
     */
    public function getSalesByCategory(Carbon $from, Carbon $to): array
    {
        return DB::table('Categories')
                ->join('Products', 'Categories.id', '=', 'Products.category_id')
                ->join('ProductVariants', 'Products.id', '=', 'ProductVariants.product_id')
                ->join('PurchaseOrderDetails', 'ProductVariants.id', '=', 'PurchaseOrderDetails.product_variant_id')
                ->join('PurchaseOrders', 'PurchaseOrderDetails.order_id', '=', 'PurchaseOrders.id')
                ->whereBetween('PurchaseOrders.created_at', [$from, $to])
                ->where('PurchaseOrders.status', '!=', 'cancelled')
                ->select('Categories.name as category_name')
                ->selectRaw('SUM(PurchaseOrderDetails.quantity) as total_quantity')
                ->selectRaw('SUM(PurchaseOrderDetails.quantity * PurchaseOrderDetails.price_at_purchase) as total_revenue')
                ->groupBy('Categories.id', 'Categories.name')
                ->orderBy('total_revenue', 'desc')
                ->get()
                ->toArray();
    }

    /**
     * Get customer lifetime value
     */
    public function getCustomerLifetimeValue(int $userId): array
    {
        $orders = PurchaseOrder::where('user_id', $userId)
                              ->whereNotIn('status', ['cancelled', 'refunded'])
                              ->selectRaw('
                                  COUNT(*) as total_orders,
                                  SUM(total_amount) as lifetime_value,
                                  AVG(total_amount) as average_order_value,
                                  MIN(created_at) as first_order_date,
                                  MAX(created_at) as last_order_date
                              ')
                              ->first();

        return [
            'total_orders' => $orders->total_orders ?? 0,
            'lifetime_value' => $orders->lifetime_value ?? 0,
            'average_order_value' => $orders->average_order_value ?? 0,
            'first_order_date' => $orders->first_order_date,
            'last_order_date' => $orders->last_order_date,
        ];
    }
}
