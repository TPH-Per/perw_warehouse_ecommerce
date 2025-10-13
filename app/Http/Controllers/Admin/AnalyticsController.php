<?php

namespace App\Http\Controllers\Admin;

use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AnalyticsController extends Controller
{
    protected $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->middleware(['auth:sanctum', 'admin']);
        $this->analyticsService = $analyticsService;
    }

    /**
     * Get dashboard statistics
     */
    public function dashboard(Request $request)
    {
        $filters = [
            'from_date' => $request->input('from_date', now()->startOfMonth()),
            'to_date' => $request->input('to_date', now()->endOfDay()),
        ];

        $stats = $this->analyticsService->getDashboardStats($filters);

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    /**
     * Get sales by category
     */
    public function salesByCategory(Request $request)
    {
        $from = $request->input('from_date', now()->startOfMonth());
        $to = $request->input('to_date', now()->endOfDay());

        $sales = $this->analyticsService->getSalesByCategory($from, $to);

        return response()->json([
            'success' => true,
            'sales_by_category' => $sales
        ]);
    }

    /**
     * Get customer lifetime value
     */
    public function customerLifetimeValue($userId)
    {
        $ltv = $this->analyticsService->getCustomerLifetimeValue($userId);

        return response()->json([
            'success' => true,
            'customer_lifetime_value' => $ltv
        ]);
    }
}
