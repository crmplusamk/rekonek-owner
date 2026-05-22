<?php

return [
    'default_service' => env('CHAT_SERVICE', 'waha'),

    'services' => [
        'waha' => [
            'base_url' => env('CHAT_WAHA_BASE_URL'),
            'token' => env('CHAT_WAHA_TOKEN'),
            'timeout' => (int) env('CHAT_WAHA_TIMEOUT', 30),
            'webhook_base_url' => env('CHAT_WAHA_WEBHOOK_BASE_URL'),
        ],
    ],
];

