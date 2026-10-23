<?php

declare(strict_types=1);

return [
    'name' => 'Nexa Suite',
    'url' => '',
    'port' => 4411,
    'timezone' => 'UTC',
    'debug' => false,
    'database' => 'nexa.sqlite',
    'plans' => [
        'starter' => ['name' => 'Starter', 'activations' => 1, 'label' => 'Single install'],
        'pro' => ['name' => 'Pro', 'activations' => 3, 'label' => 'Up to 3 installs'],
        'business' => ['name' => 'Business', 'activations' => 10, 'label' => 'Up to 10 installs'],
        'lifetime' => ['name' => 'Lifetime', 'activations' => 25, 'label' => 'Lifetime access'],
    ],
];
