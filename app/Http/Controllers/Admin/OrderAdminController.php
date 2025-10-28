<?php

namespace App\Http\Controllers\Admin;

use App\Models\PurchaseOrder;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\ShippingMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderAdminController extends AdminController
{
    /**
     * Display a listing of orders
     */
    public function index(Request $request)
    {
        $query = PurchaseOrder::with(['user', 'payment', 'shipment']);

        // Search by order code or user
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_code', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%")
                  ->orWhereHas('user', function($uq) use ($search) {
                      $uq->where('full_name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Sort by latest first
        $query->orderBy('created_at', 'desc');

        $orders = $query->paginate(20);

        return view('admin.orders.index', compact('orders'));
    }

    /**
     * Display the specified order
     */
    public function show(PurchaseOrder $order)
    {
        $order->load([
            'user',
            'orderDetails.productVariant.product',
            'payment',
            'shipment'
        ]);

        return view('admin.orders.show', compact('order'));
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, PurchaseOrder $order)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
        ]);

        if ($validator->fails()) {
            return $this->errorRedirect($validator->errors()->first());
        }

        try {
            $order->update(['status' => $request->status]);

            // If order is cancelled, refund payment if applicable
            if ($request->status === 'cancelled' && $order->payment && $order->payment->status === 'completed') {
                $order->payment->update(['status' => 'refunded']);
            }

            // If order is delivered, mark payment as completed
            if ($request->status === 'delivered' && $order->payment && $order->payment->status !== 'completed') {
                $order->payment->update(['status' => 'completed']);
            }

            return redirect()->route('admin.orders.index')->with('success', 'Order status updated successfully!');
        } catch (\Exception $e) {
            return $this->errorRedirect('Failed to update order status: ' . $e->getMessage());
        }
    }

    /**
     * Process payment for order
     */
    public function processPayment(Request $request, PurchaseOrder $order)
    {
        $validator = Validator::make($request->all(), [
            'payment_method_id' => 'required|exists:payment_methods,id',
            'transaction_id' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), $validator->errors()->toArray());
        }

        try {
            DB::beginTransaction();

            $payment = Payment::create([
                'order_id' => $order->id,
                'payment_method_id' => $request->payment_method_id,
                'transaction_id' => $request->transaction_id,
                'amount' => $request->amount,
                'status' => 'completed',
                'payment_date' => now(),
            ]);

            $order->update(['status' => 'processing']);

            DB::commit();

            return $this->successResponse('Payment processed successfully!', ['payment' => $payment]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to process payment: ' . $e->getMessage());
        }
    }

    /**
     * Create shipment for order
     */
    public function createShipment(Request $request, PurchaseOrder $order)
    {
        $validator = Validator::make($request->all(), [
            'shipping_method_id' => 'required|exists:shipping_methods,id',
            'tracking_number' => 'required|string|max:255',
            'carrier' => 'nullable|string|max:100',
            'estimated_delivery' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), $validator->errors()->toArray());
        }

        try {
            DB::beginTransaction();

            $shipment = Shipment::create([
                'order_id' => $order->id,
                'shipping_method_id' => $request->shipping_method_id,
                'tracking_number' => $request->tracking_number,
                'carrier' => $request->carrier,
                'status' => 'pending',
                'shipped_at' => now(),
                'estimated_delivery' => $request->estimated_delivery,
            ]);

            $order->update(['status' => 'shipped']);

            DB::commit();

            return $this->successResponse('Shipment created successfully!', ['shipment' => $shipment]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to create shipment: ' . $e->getMessage());
        }
    }

    /**
     * Update shipment status
     */
    public function updateShipmentStatus(Request $request, Shipment $shipment)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,in_transit,delivered,failed',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), $validator->errors()->toArray());
        }

        try {
            DB::beginTransaction();

            $shipment->update(['status' => $request->status]);

            // Update order status if delivered
            if ($request->status === 'delivered') {
                $shipment->update(['delivered_at' => now()]);
                $shipment->order->update(['status' => 'delivered']);

                // Mark payment as completed for cash on delivery
                if ($shipment->order->payment && $shipment->order->payment->status !== 'completed') {
                    $shipment->order->payment->update(['status' => 'completed']);
                }
            }

            DB::commit();

            return $this->successResponse('Shipment status updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to update shipment status: ' . $e->getMessage());
        }
    }

    /**
     * Cancel order
     */
    public function cancel(Request $request, PurchaseOrder $order)
    {
        if (!in_array($order->status, ['pending', 'processing'])) {
            return $this->errorRedirect('Order cannot be cancelled in current status.');
        }

        $validator = Validator::make($request->all(), [
            'cancellation_reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->errorRedirect($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            $order->update([
                'status' => 'cancelled',
                'notes' => 'Cancellation reason: ' . $request->cancellation_reason,
            ]);

            // Refund payment if completed
            if ($order->payment && $order->payment->status === 'completed') {
                $order->payment->update(['status' => 'refunded']);
            }

            DB::commit();

            return redirect()->route('admin.orders.index')->with('success', 'Order cancelled successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorRedirect('Failed to cancel order: ' . $e->getMessage());
        }
    }

    /**
     * Get order statistics
     */
    public function statistics(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->subMonth());
        $dateTo = $request->get('date_to', now());

        $stats = [
            'total_orders' => PurchaseOrder::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'pending_orders' => PurchaseOrder::where('status', 'pending')->whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'processing_orders' => PurchaseOrder::where('status', 'processing')->whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'shipped_orders' => PurchaseOrder::where('status', 'shipped')->whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'delivered_orders' => PurchaseOrder::where('status', 'delivered')->whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'cancelled_orders' => PurchaseOrder::where('status', 'cancelled')->whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'total_revenue' => Payment::where('status', 'completed')
                ->whereHas('order', function($q) use ($dateFrom, $dateTo) {
                    $q->whereBetween('created_at', [$dateFrom, $dateTo]);
                })
                ->sum('amount'),
            'average_order_value' => Payment::where('status', 'completed')
                ->whereHas('order', function($q) use ($dateFrom, $dateTo) {
                    $q->whereBetween('created_at', [$dateFrom, $dateTo]);
                })
                ->avg('amount'),
        ];

        return $this->successResponse('Statistics retrieved successfully', $stats);
    }

    /**
     * Export orders to CSV
     */
    public function export(Request $request)
    {
        $query = PurchaseOrder::with(['user', 'payment', 'shipment']);

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="orders_' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function() use ($orders) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, ['Order ID', 'Order Code', 'User', 'Email', 'Total Amount', 'Status', 'Payment Status', 'Shipping Status', 'Order Date']);

            // CSV data
            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->id,
                    $order->order_code ?? 'N/A',
                    $order->user->full_name ?? 'N/A',
                    $order->user->email ?? 'N/A',
                    $order->total_amount ?? 0,
                    $order->status,
                    $order->payment ? $order->payment->status : 'N/A',
                    $order->shipment ? $order->shipment->status : 'N/A',
                    $order->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Bulk update order status
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_ids' => 'required|array',
            'order_ids.*' => 'exists:purchase_orders,id',
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), $validator->errors()->toArray());
        }

        try {
            // Update order statuses
            PurchaseOrder::whereIn('id', $request->order_ids)->update(['status' => $request->status]);

            // If orders are marked as delivered, also mark payments as completed
            if ($request->status === 'delivered') {
                Payment::whereIn('order_id', $request->order_ids)
                    ->where('status', '!=', 'completed')
                    ->update(['status' => 'completed']);
            }

            return $this->successResponse('Orders status updated successfully!');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update orders: ' . $e->getMessage());
        }
    }
}
