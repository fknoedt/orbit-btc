<?php

return [
    /** system-wide default date format, which should be passed to the FE and DB */
    'date_format' => env('DEFAULT_DATE_FORMAT', 'Y-m-d'),
    'datetime_format' => env('DEFAULT_DATETIME_FORMAT', 'Y-m-d H:i:s'),

    /** btc historical data */
    'genesis_date' => env('GENESIS_DATE', '2009-10-05'),
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
];
