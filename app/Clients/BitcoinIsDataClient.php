<?php

namespace App\Clients;

use App\Exceptions\AdapterException;
use App\Exceptions\ExternalApiException;
use App\Helpers\BitcoinHelper;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

/**
 * @todo figure out M2 over API - file to download was no longer available so I subscribed for 30 days on 2/17/26
 */
class BitcoinIsDataClient extends BaseClient
{
    /**
     * BIS offers a single endpoint where you can request multiple metrics at once
     * @see https://bitcoinisdata.com/data/
     */
    public const array METRIC_TO_COLUMN_NAME = [
        'gold_price' => 'gold',
        'global_m2' => 'm2',
    ];

    public const int DEFAULT_DAYS_AGO = 30;

    private static ?string $apikey;

    public function __construct()
    {
        parent::__construct();
        self::$dataSourceId = config('data.data_source.bitcoinisdata_id');
        if (! $url = config('btc.apis.bitcoinisdata.url')) {
            throw new \RuntimeException('could not load config: btc.apis.bitcoinisdata.url');
        }

        self::$url = $url . '/api/';

        self::$apikey = config('btc.apis.bitcoinisdata.key');
    }

    public function request(string $method, string $endpoint, array $args = [], array $headers = []): array
    {
        if (self::$apikey) {
            $args['api_key'] = self::$apikey;
        }

        return parent::request($method, $endpoint, $args, $headers);
    }

    /**
     * @see https://bitcoinisdata.com/api_page/
     * The default initial block (currently) is 800k
     * @throws AdapterException
     * @throws ExternalApiException
     * @throws ConnectionException
     * @throws RequestException
     */
    public function getData(array $metrics = null, string $from = null, string $to = null): array
    {
        $from = $from ?? Carbon::now()->subDays(self::DEFAULT_DAYS_AGO)->format('Y-m-d');
        $to = $to ?? Carbon::now()->format('Y-m-d');

        $metrics = $metrics ?? array_keys(self::METRIC_TO_COLUMN_NAME);

        $fromBlock = BitcoinHelper::dateToBlockHeight($from);
        $toBlock = BitcoinHelper::dateToBlockHeight($to);

        $args = [
            'format' => 'json',
            'columns' => implode(',', $metrics),
            'start_block' => $fromBlock,
            'end_block' => $toBlock,
        ];

        return $this->request(
            'get',
            'get_data',
            $args,
        );
    }
}
