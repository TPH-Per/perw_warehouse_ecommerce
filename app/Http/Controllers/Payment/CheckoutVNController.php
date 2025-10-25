<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PurchaseOrder;
use App\Services\CheckoutVNService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckoutVNController extends Controller
{
    public function __construct(private CheckoutVNService $svc)
    {
    }

    public function create(Request $request, PurchaseOrder $order)
    {
        if ($order->payment && $order->payment->status === 'completed') {
            return back()->with('error', 'Đơn hàng đã thanh toán.');
        }

        // Ensure payment method exists
        $method = PaymentMethod::firstOrCreate(
            ['code' => 'checkoutvn'],
            ['name' => 'Checkout.vn', 'is_active' => true]
        );

        try {
            $res = $this->svc->createSession($order);
        } catch (\Throwable $e) {
            Log::error('CheckoutVN create failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Checkout.vn lỗi: ' . $e->getMessage());
        }

        // Create pending payment; store remote id/reference if provided
        Payment::updateOrCreate(
            ['order_id' => $order->id],
            [
                'payment_method_id' => $method->id,
                'amount' => $order->total_amount,
                'status' => 'pending',
                'transaction_code' => $res['remote_id'] ?? $res['reference'],
            ]
        );

        return redirect()->away($res['url']);
    }

    // Customer return; do not mark as completed without server IPN verification
    public function return(Request $request)
    {
        $message = 'Đã quay về từ Checkout.vn. Vui lòng chờ xác nhận thanh toán.';
        return view('payment.vnpay-result', ['success' => true, 'message' => $message, 'order' => null]);
    }

    // IPN/Webhook: requires provider signature docs to verify
    public function ipn(Request $request)
    {
        // TODO: Verify signature according to Checkout.vn docs (not provided).
        // Example (pseudo): $signature = $request->header('X-Checkout-Signature');
        // Compare HMAC(body, api_token). Only then update payment.

        $orderCode = $request->input('order_code') ?? $request->input('order_id');
        $status = $request->input('status');

        if (!$orderCode) {
            return response('BAD_REQUEST', 400);
        }

        $order = PurchaseOrder::where('order_code', $orderCode)->first();
        if (!$order) {
            return response('NOT_FOUND', 404);
        }

        $payment = $order->payment;
        if (!$payment) {
            return response('PAYMENT_NOT_FOUND', 404);
        }

        if (in_array(strtolower((string) $status), ['success', 'paid', 'completed'], true)) {
            if ($payment->status !== 'completed') {
                $payment->status = 'completed';
                $payment->save();
            }
            return response('OK', 200);
        }

        return response('IGNORED', 200);
    }
}

