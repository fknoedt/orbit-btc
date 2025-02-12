<?php

return [
    /** Where technical errors or info should be sent to -- mind mailpit on port 8025 */
    'system_admin_email' => env('SYSTEM_ADMIN_EMAIL'),
    /** system-wide default date format, which should be passed to the FE and DB */
    'date_format' => env('DEFAULT_DATE_FORMAT', 'Y-m-d'),
    'datetime_format' => env('DEFAULT_DATETIME_FORMAT', 'Y-m-d H:i:s'),

    /** btc historical data */
    'first_available_date' => '2009-10-05',
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

    'currency' => env('DEFAULT_CURRENCY', 'usd'),

    'price_external_url' => env('PRICE_EXTERNAL_URL'),

    'apis' => [
        'coinmarketcap' => [
            'url' => env('COINMARKETCAP_URL'),
            'key' => env('COINMARKETCAP_KEY'),
        ]
    ]
];
