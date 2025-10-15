<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManagerDashboardController extends Controller
{
    public function index()
    {
        // Get inventory statistics
        $stats = [
            'total_inventory_items' => Inventory::count(),
            'low_stock_items' => Inventory::whereColumn('quantity_on_hand', '<=', 'reorder_level')->count(),
            'out_of_stock_items' => Inventory::where('quantity_on_hand', 0)->count(),
            'total_products' => Product::count(),
            'today_sales' => PurchaseOrder::whereDate('created_at', today())
                ->where('status', 'delivered')
                ->count(),
        ];

        // Get low stock items
        $lowStockItems = Inventory::with(['productVariant.product', 'warehouse'])
            ->whereColumn('quantity_on_hand', '<=', 'reorder_level')
            ->orderBy('quantity_on_hand', 'asc')
            ->limit(10)
            ->get();

        // Get recent direct sales (today)
        $recentSales = PurchaseOrder::with(['user', 'payment'])
            ->whereDate('created_at', today())
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('manager.dashboard', compact('stats', 'lowStockItems', 'recentSales'));
    }
}
