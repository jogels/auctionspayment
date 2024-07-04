<?php

return [
    'mid' => env('YUK_PAYMENT_MID'),
    'grant_type' => env('YUK_PAYMENT_GRANT_TYPE'),
    'client_id' => env('YUK_PAYMENT_CLIENT_ID'),
    'client_secret' => env('YUK_PAYMENT_CLIENT_SECRET'),
    'url' => env('YUK_PAYMENT_URL'),

    'is_production' => false,
];
