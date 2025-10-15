<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Payment;
use App\Models\Inventory;
use App\Models\ProductReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Date range for statistics (default: last 30 days)
        $dateFrom = $request->get('date_from', now()->subDays(30));
        $dateTo = $request->get('date_to', now());

        // Overall Statistics
        $stats = [
            'total_users' => User::count(),
            'new_users_this_month' => User::whereMonth('created_at', now()->month)->count(),
            'total_products' => Product::count(),
            'active_products' => Product::where('status', 'active')->count(),
            'total_orders' => PurchaseOrder::count(),
            'pending_orders' => PurchaseOrder::where('status', 'pending')->count(),
            'processing_orders' => PurchaseOrder::where('status', 'processing')->count(),
            'total_revenue' => Payment::where('status', 'completed')->sum('amount'),
            'revenue_this_month' => Payment::where('status', 'completed')
                ->whereMonth('created_at', now()->month)
                ->sum('amount'),
            'low_stock_items' => Inventory::whereColumn('quantity_on_hand', '<=', 'reorder_level')->count(),
            'out_of_stock_items' => Inventory::where('quantity_on_hand', '<=', 0)->count(),
            'pending_reviews' => ProductReview::where('status', 'pending')->count(),
        ];

        // Recent Orders
        $recentOrders = PurchaseOrder::with(['user', 'payment'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Top Selling Products (last 30 days)
        $topProducts = DB::table('purchase_order_details')
            ->join('product_variants', 'purchase_order_details.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->join('purchase_orders', 'purchase_order_details.order_id', '=', 'purchase_orders.id')
            ->where('purchase_orders.created_at', '>=', now()->subDays(30))
            ->select(
                'products.id',
                'products.name',
                DB::raw('SUM(purchase_order_details.quantity) as total_sold'),
                DB::raw('SUM(purchase_order_details.subtotal) as total_revenue')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_sold')
            ->limit(10)
            ->get();

        // Sales Chart Data (last 7 days)
        $salesChartData = Payment::where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(7))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Order Status Distribution
        $orderStatusDistribution = PurchaseOrder::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        // Low Stock Alerts
        $lowStockItems = Inventory::with(['productVariant.product', 'warehouse'])
            ->whereColumn('quantity_on_hand', '<=', 'reorder_level')
            ->orderBy('quantity_on_hand', 'asc')
            ->limit(10)
            ->get();

        // Recent User Registrations
        $recentUsers = User::with('role')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact(
            'stats',
            'recentOrders',
            'topProducts',
            'salesChartData',
            'orderStatusDistribution',
            'lowStockItems',
            'recentUsers'
        ));
    }

    /**
     * Get dashboard statistics API endpoint
     */
    public function getStatistics(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->subDays(30));
        $dateTo = $request->get('date_to', now());

        $stats = [
            'users' => [
                'total' => User::count(),
                'new' => User::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
                'active' => User::where('status', 'active')->count(),
            ],
            'products' => [
                'total' => Product::count(),
                'active' => Product::where('status', 'active')->count(),
                'inactive' => Product::where('status', 'inactive')->count(),
            ],
            'orders' => [
                'total' => PurchaseOrder::count(),
                'pending' => PurchaseOrder::where('status', 'pending')->count(),
                'processing' => PurchaseOrder::where('status', 'processing')->count(),
                'shipped' => PurchaseOrder::where('status', 'shipped')->count(),
                'delivered' => PurchaseOrder::where('status', 'delivered')->count(),
                'cancelled' => PurchaseOrder::where('status', 'cancelled')->count(),
            ],
            'revenue' => [
                'total' => Payment::where('status', 'completed')->sum('amount'),
                'period' => Payment::where('status', 'completed')
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->sum('amount'),
                'average_order_value' => Payment::where('status', 'completed')
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->avg('amount'),
            ],
            'inventory' => [
                'total_items' => Inventory::count(),
                'low_stock' => Inventory::whereColumn('quantity_on_hand', '<=', 'reorder_level')->count(),
                'out_of_stock' => Inventory::where('quantity_on_hand', '<=', 0)->count(),
                'total_quantity' => Inventory::sum('quantity_on_hand'),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
