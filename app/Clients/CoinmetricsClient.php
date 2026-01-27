<?php

namespace App\Clients;

use App\Exceptions\AdapterException;
use App\Exceptions\ExternalApiException;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class CoinmetricsClient extends BaseClient
{
    /**
     * CM's metric => metrics.column_name / daily_prices column name
     * @see https://coinmetrics.io/community-network-data/ (choose bitcoin to download CSV file)
     */
    public const array METRIC_TO_COLUMN_NAME = [
        'AdrActCnt' => 'adr_act_cnt',
        'AdrBalCnt' => 'adr_bal_cnt',
        'FeeTotNtv' => 'fee_tot_ntv',
        'FlowInExNtv' => 'flow_in_ex_ntv',
        'FlowInExUSD' => 'flow_in_ex_usd',
        'FlowOutExNtv' => 'flow_out_ex_ntv',
        'FlowOutExUSD' => 'flow_out_ex_usd',
        'ROI1yr' => 'roi_1yr',
        'ROI30d' => 'roi_30d',
        'ReferenceRate' => 'reference_rate',
        'SplyExNtv' => 'sply_ex_ntv',
        'SplyExUSD' => 'sply_ex_usd',
        'TxCnt' => 'tx_cnt',
        'volume_reported_spot_usd_1d' => 'volume_reported_spot_usd_1d',
    ];

    protected const int DEFAULT_DAYS_AGO = 30;

    const array METRICS = [
        'spy' => 'getHistoricalSpy',
        'gold' => 'getHistoricalGold',
    ];

    private string $version = 'v4';
    private static ?string $apikey;

    public function __construct()
    {
        parent::__construct();
        self::$dataSourceId = config('data.data_source.coinmetrics_id');
        if (! $url = config('btc.apis.coinmetrics.url')) {
            throw new \RuntimeException('could not load config: btc.apis.coinmetrics.url');
        }
        self::$url = $url . '/'. $this->version . '/';

        self::$apikey = config('btc.apis.coinmetrics.key');
    }

    public function request(string $method, string $endpoint, array $args = [], array $headers = []): array
    {
        if (self::$apikey) {
            $args['api_key'] = self::$apikey;
        }
        return parent::request($method, $endpoint, $args, $headers);
    }

    /**
     * Get any of Coin Metric's BTC metric
     * @see https://docs.coinmetrics.io/api/v4/#tag/Timeseries/operation/getTimeseriesAssetMetrics
     * @throws AdapterException
     * @throws ExternalApiException
     * @throws ConnectionException
     * @throws RequestException
     */
    public function getMetrics(array $metrics, string $from = null, string $to = null): array
    {
        $from = $from ?? Carbon::now()->subDays(self::DEFAULT_DAYS_AGO)->format('Y-m-d');
        $to = $to ?? Carbon::now()->format('Y-m-d');

        $args = [
            'assets' => 'btc',
            'metrics' => implode(',', $metrics),
            'start_time' => $from,
            'end_time' => $to,
            'frequency' => '1d',
        ];

        return $this->request(
            'get',
            'timeseries/asset-metrics',
            $args,
        );
    }
}
