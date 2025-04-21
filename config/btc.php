<?php

return [
    /** Where technical errors or info should be sent to -- mind mailpit on port 8025 */
    'system_admin_email' => env('SYSTEM_ADMIN_EMAIL'),
    /** system-wide default date format, which should be passed to the FE and DB */
    'date_format' => env('DEFAULT_DATE_FORMAT', 'Y-m-d'),
    'datetime_format' => env('DEFAULT_DATETIME_FORMAT', 'Y-m-d H:i:s'),

    /** btc historical data */
    'first_available_date' => '2009-10-05',
    'initial_pattern_search_date' => '2011-01-01',
    'first_cmc_available_date' => '2010-07-14',

    'initial_missing_prices_datetime' => env(
        'INITIAL_MISSING_PRICES_DATETIME',
        '2010-03-03 00:00:00'
    ),
    'final_missing_prices_datetime' => env(
        'FINAL_MISSING_PRICES_DATETIME',
        '2010-07-17 23:59:59'
    ),
    /** last day for initial - seeder - data */
    'initial_data_last_day' => env('INITIAL_DATA_LAST_DAY', '2022-07-10'),
    'time_series_pattern_min_days' => env('TIME_SERIES_PATTERN_MIN_DAYS', 7),
    'time_series_pattern_max_days' => env('TIME_SERIES_PATTERN_MAX_DAYS', 90),

    'currency' => env('DEFAULT_CURRENCY', 'usd'),

    'price_external_url' => env('PRICE_EXTERNAL_URL'),

    'apis' => [
        'coinmarketcap' => [
            'url' => env('COINMARKETCAP_URL'),
            'key' => env('COINMARKETCAP_KEY'),
        ],
        'coingecko' => [
            'url' => env('COINGECKO_URL'),
            'key' => env('COINGECKO_KEY'),
        ],
        'mempool_space' => [
            'url' => env('MEMPOOL_URL'),
            'key' => env('MEMPOOL_KEY'),
        ],
        'cryptoquant' => [
            'url' => env('CRYPTOQUANT_URL'),
            'auth_token' => env('CRYPTOQUANT_ACCESS_TOKEN'),
        ],
        'orbit_btc' => [
            'url' => env('ORBIT_BTC_REMOTE_URL'),
            'key' => env('ORBIT_BTC_CLIENT_KEY'),
        ],
        'glassnode' => [
            'url' => env('GLASSNODE_URL'),
        ],
        'blockchain_com' => [
            'url' => env('BLOCKCHAIN_COM_URL'),
        ]
    ]
];
