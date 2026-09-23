<?php

declare(strict_types=1);

return [

    'base_url' => env(
        'LEADSCAPTAIN_BASE_URL',
        'https://api.leadscaptain.com',
    ),

    'api_key' => env(
        'LEADSCAPTAIN_API_KEY',
    ),

    'timeout' => (int) env(
        'LEADSCAPTAIN_TIMEOUT',
        30,
    ),

    'retry_times' => (int) env(
        'LEADSCAPTAIN_RETRY_TIMES',
        3,
    ),

    'retry_backoff' => [
        1000,
        5000,
        30000,
    ],

    'per_page' => (int) env(
        'LEADSCAPTAIN_PER_PAGE',
        100,
    ),

    'concurrency' => (int) env(
        'LEADSCAPTAIN_CONCURRENCY',
        10,
    ),

    'max_page' => (int) env(
        'LEADSCAPTAIN_MAX_PAGE',
        1000,
    ),

    'queue' => [
        'connection' => env(
            'LEADSCAPTAIN_QUEUE_CONNECTION',
            'redis',
        ),

        'queue' => env(
            'LEADSCAPTAIN_QUEUE',
            'leadscaptain',
        ),

        'concurrency' => (int) env(
            'LEADSCAPTAIN_CONCURRENCY',
            10,
        ),
    ],
];
