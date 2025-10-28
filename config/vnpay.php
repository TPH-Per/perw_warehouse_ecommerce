<?php

return [
    // Merchant credentials (from VNPAY portal)
    'tmn_code' => env('VNPAY_TMN_CODE', ''),
    'hash_secret' => env('VNPAY_HASH_SECRET', ''),

    // Gateway URL (sandbox by default)
    'vnp_url' => env('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),

    // Callback URLs
    // If not set, they default to APP_URL + given paths
    'return_url' => env('VNPAY_RETURN_URL', env('APP_URL') . '/payment/vnpay/return'),
    'ipn_url' => env('VNPAY_IPN_URL', env('APP_URL') . '/payment/vnpay/ipn'),
];

