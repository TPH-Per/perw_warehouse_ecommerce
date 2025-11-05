<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ManagerDashboardController extends Controller
{
    public function index()
    {
        // Filter by user's assigned warehouse if they are a warehouse-specific manager
        $user = Auth::user();
        $warehouseId = null;

        if ($user && $user->role->name === 'manager' && $user->warehouse_id) {
            $warehouseId = $user->warehouse_id;
        }

        // Get inventory statistics
        $inventoryQuery = Inventory::query();
        $productQuery = Product::query();
        $orderQuery = PurchaseOrder::query();

        if ($warehouseId) {
            $inventoryQuery->where('warehouse_id', $warehouseId);
            // Filter products that have inventory in this warehouse
            $productQuery->whereHas('variants.inventories', function($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            });
            $orderQuery->whereHas('orderDetails.productVariant.inventories', function($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            });
        }

        $stats = [
            'total_inventory_items' => $inventoryQuery->count(),
            'low_stock_items' => $inventoryQuery->clone()->whereColumn('quantity_on_hand', '<=', 'reorder_level')->count(),
            'out_of_stock_items' => $inventoryQuery->clone()->where('quantity_on_hand', 0)->count(),
            'total_products' => $productQuery->count(),
            'today_sales' => $orderQuery->whereDate('created_at', today())
                ->where('status', 'delivered')
                ->count(),
        ];

        // Get low stock items
        $lowStockQuery = Inventory::with(['productVariant.product', 'warehouse'])
            ->whereColumn('quantity_on_hand', '<=', 'reorder_level')
            ->orderBy('quantity_on_hand', 'asc')
            ->limit(10);

        if ($warehouseId) {
            $lowStockQuery->where('warehouse_id', $warehouseId);
        }

        $lowStockItems = $lowStockQuery->get();

        // Get recent direct sales (today)
        $recentSalesQuery = PurchaseOrder::with(['user', 'payment'])
            ->whereDate('created_at', today())
            ->orderBy('created_at', 'desc')
            ->limit(10);

        if ($warehouseId) {
            $recentSalesQuery->whereHas('orderDetails.productVariant.inventories', function($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            });
        }

        $recentSales = $recentSalesQuery->get();

        return view('manager.dashboard', compact('stats', 'lowStockItems', 'recentSales'));
    }
}
