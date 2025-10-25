<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CheckoutVNService
{
    /**
     * Create a checkout session at Checkout.vn and return [url, remote_id].
     * This is configurable via env/config to adapt without code changes.
     */
    public function createSession(PurchaseOrder $order): array
    {
        $baseUrl = (string) config('checkoutvn.base_url');
        $path = (string) config('checkoutvn.create_path');
        $apiKey = (string) config('checkoutvn.api_key');
        $apiToken = (string) config('checkoutvn.api_token');
        $returnUrl = (string) config('checkoutvn.return_url');
        $callbackUrl = (string) config('checkoutvn.callback_url');
        $headersCfg = (array) config('checkoutvn.headers');

        if (empty($baseUrl) || empty($apiKey) || empty($apiToken)) {
            throw new \InvalidArgumentException('Thiếu cấu hình Checkout.vn (base_url/api_key/api_token)');
        }

        // Amount in VND, integer
        $amount = (int) round((float) $order->total_amount);
        $reference = 'ORD' . $order->id . '-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6));

        // Default payload; align keys according to provider docs if different
        $payload = [
            'amount' => $amount,
            'currency' => 'VND',
            'order_code' => $order->order_code,
            'order_ref' => substr($reference, 0, 100),
            'description' => 'Thanh toan don hang ' . $order->order_code,
            'return_url' => $returnUrl,
            'callback_url' => $callbackUrl,
            'customer' => [
                'name' => $order->shipping_recipient_name,
                'phone' => $order->shipping_recipient_phone,
            ],
        ];

        $headers = [
            (string) ($headersCfg['api_key_header'] ?? 'X-Api-Key') => $apiKey,
        ];
        if (!empty($headersCfg['use_bearer'])) {
            $headers[(string) ($headersCfg['api_token_header'] ?? 'Authorization')] = 'Bearer ' . $apiToken;
        } else {
            $headers[(string) ($headersCfg['api_token_header'] ?? 'X-Api-Token')] = $apiToken;
        }

        $resp = Http::baseUrl($baseUrl)
            ->withHeaders($headers)
            ->asJson()
            ->post($path, $payload);

        if (!$resp->ok()) {
            throw new \RuntimeException('Checkout.vn API lỗi: HTTP ' . $resp->status() . ' - ' . $resp->body());
        }

        $json = $resp->json();
        $url = $json['checkout_url'] ?? $json['redirect_url'] ?? $json['url'] ?? null;
        $remoteId = $json['id'] ?? $json['order_id'] ?? $json['transaction_id'] ?? null;

        if (!$url) {
            throw new \RuntimeException('Checkout.vn API không trả về checkout URL hợp lệ');
        }

        return ['url' => $url, 'remote_id' => $remoteId, 'reference' => $reference];
    }
}

