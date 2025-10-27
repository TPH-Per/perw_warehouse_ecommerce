<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;

class CashOnDeliveryController extends Controller
{
    /**
     * Process cash on delivery payment
     */
    public function process(Request $request, PurchaseOrder $order)
    {
        if ($order->payment && $order->payment->status === 'completed') {
            return back()->with('error', 'Đơn hàng đã thanh toán.');
        }

        // Ensure payment method exists
        $method = PaymentMethod::firstOrCreate(
            ['code' => 'cod'],
            ['name' => 'Thanh toán khi nhận hàng', 'is_active' => true]
        );

        try {
            // Create pending payment for cash on delivery
            Payment::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'payment_method_id' => $method->id,
                    'amount' => $order->total_amount,
                    'status' => 'pending', // Will be marked as completed when delivered
                    'transaction_code' => 'COD-' . $order->order_code,
                ]
            );

            // Update order status to processing
            $order->update(['status' => 'processing']);

            return redirect()->route('admin.orders.show', $order)->with('success', 'Đã xác nhận phương thức thanh toán khi nhận hàng!');
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi khi xử lý thanh toán: ' . $e->getMessage());
        }
    }
}
