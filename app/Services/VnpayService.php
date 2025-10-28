<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use Illuminate\Support\Str;

class VnpayService
{
    /**
     * Build a VNPAY payment URL for the given order.
     * Returns [url, txn_ref].
     */
    public function createPaymentUrl(PurchaseOrder $order, string $ipAddress, ?string $bankCode = null, ?\DateTimeInterface $expireAt = null): array
    {
        $vnpUrl = (string) config('vnpay.vnp_url');
        $returnUrl = (string) config('vnpay.return_url');
        $tmnCode = (string) config('vnpay.tmn_code');
        $hashSecret = (string) config('vnpay.hash_secret');

        if (empty($tmnCode) || empty($hashSecret) || empty($vnpUrl)) {
            throw new \InvalidArgumentException('Thiếu cấu hình VNPAY (TMN code/Hash secret/URL).');
        }

        // Ensure absolute return URL
        if (!preg_match('/^https?:\/\//i', $returnUrl)) {
            $returnUrl = url('/payment/vnpay/return');
        }

        // Normalize IP (avoid IPv6 loopback)
        if (strpos($ipAddress, ':') !== false) {
            $ipAddress = '127.0.0.1';
        }

        // Unique reference for this transaction (max 100 chars)
        $txnRef = 'ORD' . $order->id . '-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6));
        $txnRef = substr($txnRef, 0, 100);

        // VNPAY expects amount in VND * 100, but VND has no fractional unit.
        // Round to whole VND first to avoid values like 36000.06.
        $amountVnp = (int) round((float) $order->total_amount) * 100;
        $createDate = now('Asia/Ho_Chi_Minh');
        $expireDate = $expireAt ? \Carbon\Carbon::instance((new \DateTimeImmutable())->setTimestamp($expireAt->getTimestamp())) : $createDate->copy()->addMinutes(15);

        $inputData = [
            'vnp_Version' => '2.1.0',
            'vnp_TmnCode' => $tmnCode,
            'vnp_Amount' => $amountVnp,
            'vnp_Command' => 'pay',
            'vnp_CreateDate' => $createDate->format('YmdHis'),
            'vnp_CurrCode' => 'VND',
            'vnp_IpAddr' => $ipAddress,
            'vnp_Locale' => 'vn',
            'vnp_OrderInfo' => 'Thanh toan don hang ' . $order->order_code,
            'vnp_OrderType' => 'other',
            'vnp_ReturnUrl' => $returnUrl,
            'vnp_TxnRef' => $txnRef,
            'vnp_ExpireDate' => $expireDate->format('YmdHis'),
        ];

        if (!empty($bankCode)) {
            $inputData['vnp_BankCode'] = $bankCode;
        }

        ksort($inputData);

        // Per VNPAY sample, urlencode keys and values when hashing and building query
        $pairs = [];
        foreach ($inputData as $key => $value) {
            $pairs[] = urlencode($key) . '=' . urlencode((string) $value);
        }
        $hashData = implode('&', $pairs);
        $query = implode('&', $pairs);
        $secureHash = hash_hmac('sha512', $hashData, $hashSecret);
        $paymentUrl = $vnpUrl . '?' . $query . '&vnp_SecureHash=' . $secureHash;

        return [
            'url' => $paymentUrl,
            'txn_ref' => $txnRef,
        ];
    }

    /**
     * Verify VNPAY return/IPN signature.
     */
    public function verifySignature(array $params): bool
    {
        $receivedHash = $params['vnp_SecureHash'] ?? '';
        if (!$receivedHash) {
            return false;
        }

        unset($params['vnp_SecureHash'], $params['vnp_SecureHashType']);
        ksort($params);

        $pairs = [];
        foreach ($params as $key => $value) {
            $pairs[] = urlencode($key) . '=' . urlencode((string) $value);
        }
        $hashData = implode('&', $pairs);
        $calculated = hash_hmac('sha512', $hashData, (string) config('vnpay.hash_secret'));
        return hash_equals($calculated, $receivedHash);
    }
}
