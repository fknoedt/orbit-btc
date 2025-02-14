<?php

namespace App\Clients;

use RuntimeException;

class MempoolClient extends BaseClient
{
    private string $version = 'v1';

    public function __construct()
    {
        parent::__construct();
        self::$dataSourceId = config('data.data_source.mempool_space_id');
        if (! $url = config('btc.apis.mempool_space.url')) {
            throw new RuntimeException('could not load config: btc.apis.mempool_space.url');
        }
        self::$url = $url . '/api/'. $this->version . '/';
    }


}
