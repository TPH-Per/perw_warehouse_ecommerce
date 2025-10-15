<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManagerOrderController extends Controller
{
    /**
     * Display a listing of orders
     */
    public function index(Request $request)
    {
        $query = PurchaseOrder::with(['user', 'orderDetails.productVariant.product', 'payment', 'shipment'])
            ->whereNotNull('shipping_address'); // Only shipping orders (not direct sales)

        // Search by order code or customer
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_code', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%")
                  ->orWhere('shipping_recipient_name', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        // Filter by date
        if ($request->has('date_from') && $request->date_from != '') {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to != '') {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('manager.orders.index', compact('orders'));
    }

    /**
     * Display the specified order
     */
    public function show(PurchaseOrder $order)
    {
        $order->load(['user', 'orderDetails.productVariant.product', 'payment.paymentMethod', 'shipment']);

        return view('manager.orders.show', compact('order'));
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, PurchaseOrder $order)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled'
        ]);

        try {
            $order->update(['status' => $request->status]);

            // If order is being shipped, update shipment status
            if ($request->status == 'shipped' && $order->shipment) {
                $order->shipment->update(['status' => 'in_transit']);
            }

            // If order is delivered, update shipment status
            if ($request->status == 'delivered' && $order->shipment) {
                $order->shipment->update([
                    'status' => 'delivered',
                    'delivered_at' => now()
                ]);
            }

            return redirect()->route('manager.orders.show', $order->id)
                ->with('success', 'Order status updated successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update order status: ' . $e->getMessage());
        }
    }

    /**
     * Update shipment tracking
     */
    public function updateTracking(Request $request, PurchaseOrder $order)
    {
        $request->validate([
            'tracking_number' => 'required|string|max:255',
            'carrier' => 'nullable|string|max:255',
        ]);

        try {
            if ($order->shipment) {
                $order->shipment->update([
                    'tracking_number' => $request->tracking_number,
                    'carrier' => $request->carrier,
                ]);

                return redirect()->route('manager.orders.show', $order->id)
                    ->with('success', 'Tracking information updated successfully!');
            } else {
                return back()->with('error', 'No shipment found for this order.');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update tracking: ' . $e->getMessage());
        }
    }
}
