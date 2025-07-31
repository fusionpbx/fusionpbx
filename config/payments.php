<?php

return [
    'offline' => [],
    'stripe' => [
        'key' => env('PAYMENT_STRIPE_KEY'),
        'secret' => env('PAYMENT_STRIPE_SECRET'),
    ],
];
