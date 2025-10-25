<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PurchaseOrder;
use App\Services\VnpayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VnpayController extends Controller
{
    public function __construct(private VnpayService $vnpay)
    {
    }

    /**
     * Start a VNPAY payment and redirect to gateway.
     */
    public function create(Request $request, PurchaseOrder $order)
    {
        if ($order->payment && $order->payment->status === 'completed') {
            return back()->with('error', 'Đơn hàng đã thanh toán.');
        }

        try {
            // If ?qr=1 is provided, prefer QR flow
            $bankCode = $request->boolean('qr') ? 'VNPAYQR' : null;
            $result = $this->vnpay->createPaymentUrl($order, (string) $request->ip(), $bankCode);
        } catch (\Throwable $e) {
            return back()->with('error', 'Lỗi VNPAY: ' . $e->getMessage());
        }

        // Ensure payment method exists
        $method = PaymentMethod::firstOrCreate(
            ['code' => 'vnpay'],
            ['name' => 'VNPAY', 'is_active' => true]
        );

        // Create or update payment record as pending
        Payment::updateOrCreate(
            ['order_id' => $order->id],
            [
                'payment_method_id' => $method->id,
                'amount' => $order->total_amount,
                'status' => 'pending',
                'transaction_code' => $result['txn_ref'],
            ]
        );

        return redirect()->away($result['url']);
    }

    /**
     * Customer return URL handler.
     */
    public function return(Request $request)
    {
        $params = $request->all();
        $valid = $this->vnpay->verifySignature($params);

        $txnRef = $params['vnp_TxnRef'] ?? null;
        $responseCode = $params['vnp_ResponseCode'] ?? null;
        $txnStatus = $params['vnp_TransactionStatus'] ?? null;
        $amount = isset($params['vnp_Amount']) ? (int) $params['vnp_Amount'] : null;

        $payment = $txnRef ? Payment::where('transaction_code', $txnRef)->first() : null;
        if (!$payment) {
            Log::warning('VNPAY return: payment not found', ['txnRef' => $txnRef]);
        }

        $amountOk = $payment && $amount !== null && ((int) round($payment->amount * 100)) === $amount;
        $success = $valid && $amountOk && $responseCode === '00' && $txnStatus === '00';

        if ($payment) {
            $payment->status = $success ? 'completed' : 'failed';
            $payment->save();
        }

        $message = $success ? 'Thanh toán thành công.' : 'Thanh toán thất bại hoặc không hợp lệ.';
        $order = $payment?->order;

        return view('payment.vnpay-result', compact('success', 'message', 'order'));
    }

    /**
     * IPN handler (server-to-server from VNPAY). Should return a plain text/JSON.
     */
    public function ipn(Request $request)
    {
        $params = $request->all();
        $valid = $this->vnpay->verifySignature($params);

        if (!$valid) {
            return response('INVALID', 400);
        }

        $txnRef = $params['vnp_TxnRef'] ?? null;
        $responseCode = $params['vnp_ResponseCode'] ?? null;
        $txnStatus = $params['vnp_TransactionStatus'] ?? null;
        $amount = isset($params['vnp_Amount']) ? (int) $params['vnp_Amount'] : null;

        $payment = $txnRef ? Payment::where('transaction_code', $txnRef)->first() : null;
        if (!$payment) {
            return response('NOT_FOUND', 404);
        }

        $amountOk = $amount !== null && ((int) round($payment->amount * 100)) === $amount;
        $success = $amountOk && $responseCode === '00' && $txnStatus === '00';

        // Idempotent update
        if ($payment->status !== 'completed') {
            $payment->status = $success ? 'completed' : 'failed';
            $payment->save();
        }

        return response('OK', 200);
    }
}
