<?php

/**
 * all-environments data related (usually DB IDs) constants to avoid 'magic numbers' around the codebase
 */

return [
    'data_source' => [
        'new_liberty_id' => 1,
        'coindesk_id' => 2,
        'coingecko_id' => 3,
        'coinmarketcap_id' => 4,
        'mempool_space_id' => 5,
        'cryptoquant_id' => 6,
        'orbit_btc_id' => 7,
        'glassnode_id' => 8,
        'blockchain_com_id' => 9,
        'bgeometrics_id' => 10,
    ],
    'role_id' => [
        'user' => 1,
        'admin' => 2,
        'super_admin' => 3,
    ]
];
