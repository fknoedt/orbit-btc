<?php

namespace App\Clients;

class BlockchainComClient extends BaseClient
{
    private string $version = 'v1';

    protected const array ENDPOINTS = [
        'distribution/balance_bhutan_government' => [
            'name' => 'Bhutan Government Balance',
            'description' => 'The Bhutan Government Balance corresponds to the amount of BTC held in addresses controlled by Druk Holding and Investments (DHI), the investment arm of the Royal Government of Bhutan.',
        ]
    ];

    public function __construct()
    {
        parent::__construct();
        self::$dataSourceId = config('data.data_source.blockchain_com_id');
        if (! $url = config('btc.apis.glassnode.url')) {
            throw new \RuntimeException('could not load config: btc.apis.glassnode.url');
        }
        self::$url = $url . '/' . $this->version . '/metrics/';
    }

    public function getAllEndpoints(): void
    {
        $args = [
            'a' => 'BTC',
            's' => '1743482905',
            'c' => 'usd'
        ];
        foreach (self::ENDPOINTS as $path => $endpoint) {
            echo "Running {$endpoint['name']}:";
            dump($this->request('GET', $path, $args));
        }
        exit;
    }
}
