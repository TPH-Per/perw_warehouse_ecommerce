<?php

return [
    'base_url' => env('CHECKOUTVN_BASE_URL', 'https://api.checkout.vn'),
    'api_key' => env('CHECKOUTVN_API_KEY'),
    'api_token' => env('CHECKOUTVN_API_TOKEN'),
    // Endpoint path to create a checkout session (adjust per provider docs)
    'create_path' => env('CHECKOUTVN_CREATE_PATH', '/v1/checkout/sessions'),
    'return_url' => env('CHECKOUTVN_RETURN_URL', env('APP_URL') . '/payment/checkoutvn/return'),
    'callback_url' => env('CHECKOUTVN_CALLBACK_URL', env('APP_URL') . '/payment/checkoutvn/ipn'),
    // Header names can vary; override via env if provider uses different headers
    'headers' => [
        'api_key_header' => env('CHECKOUTVN_API_KEY_HEADER', 'X-Api-Key'),
        'api_token_header' => env('CHECKOUTVN_API_TOKEN_HEADER', 'Authorization'), // default Bearer {token}
        'use_bearer' => env('CHECKOUTVN_USE_BEARER', true),
    ],
];

