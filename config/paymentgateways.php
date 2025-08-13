<?php

return [
    'offline' => [
        'class' => App\Services\Payments\OfflineGateway::class,
        'default_charge' => 10,
    ],
    'stripe' => [
        'class' => App\Services\Payments\StripeGateway::class,
        'default_charge' => 10,
    ],
];
