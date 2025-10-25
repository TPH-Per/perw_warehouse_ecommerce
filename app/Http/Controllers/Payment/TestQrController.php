<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;

class TestQrController extends Controller
{
    public function show(PurchaseOrder $order)
    {
        // Local-only safety: still safe if routes guarded by env
        $method = PaymentMethod::firstOrCreate(
            ['code' => 'test_qr'],
            ['name' => 'Test QR (Local)', 'is_active' => true]
        );

        Payment::updateOrCreate(
            ['order_id' => $order->id],
            [
                'payment_method_id' => $method->id,
                'amount' => $order->total_amount,
                'status' => 'pending',
                'transaction_code' => 'TESTQR-' . $order->order_code,
            ]
        );

        return view('payment.test-qr', compact('order'));
    }

    public function simulate(Request $request, PurchaseOrder $order)
    {
        $payment = $order->payment;
        if ($payment && $payment->status !== 'completed') {
            $payment->status = 'completed';
            $payment->save();
        }

        return redirect()->route('manager.sales.show', $order->id)
            ->with('success', 'Đã đánh dấu thanh toán thành công (mô phỏng).');
    }
}

