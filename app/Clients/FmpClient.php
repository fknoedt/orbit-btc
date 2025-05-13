<?php

namespace App\Clients;


use App\Exceptions\AdapterException;
use App\Exceptions\DailyPriceStatsException;
use App\Exceptions\ExternalApiException;
use App\Services\DailyStatsService;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class FmpClient extends BaseClient
{
    protected const int DEFAULT_SPY_FROM_DAYS_AGO = 15;
    protected const int DEFAULT_GOLD_FROM_DAYS_AGO = 15;

    const array METRICS = [
        'spy' => 'getHistoricalSpy',
        'gold' => 'getHistoricalGold',
    ];

    private string $version = 'v3';
    private static ?string $apikey;

    public function __construct()
    {
        parent::__construct();
        self::$dataSourceId = config('data.data_source.fmp_id');
        if (! $url = config('btc.apis.fmp.url')) {
            throw new \RuntimeException('could not load config: btc.apis.fmp.url');
        }
        self::$url = $url . '/api/'. $this->version . '/';

        if (! self::$apikey = config('btc.apis.fmp.key')) {
            throw new \RuntimeException('could not load config: btc.apis.fmp.key');
        }
    }

    public function request(string $method, string $endpoint, array $args = [], array $headers = []): array
    {
        $args['apikey'] = self::$apikey;
        return parent::request($method, $endpoint, $args, $headers);
    }


    /**
     * Get historical SPY index
     * @see https://site.financialmodelingprep.com/developer/docs#historical-price-charts
     * @throws AdapterException
     * @throws ExternalApiException
     * @throws ConnectionException
     * @throws RequestException
     */
    public function getHistoricalSpy(string $from = null, string $to = null): array
    {
        $from = $from ?? Carbon::now()->subDays(self::DEFAULT_SPY_FROM_DAYS_AGO)->format('Y-m-d');
        $to = $to ?? Carbon::now()->format('Y-m-d');
        $args = [
            'from' => $from,
            'to' => $to,
        ];
        return $this->request(
            'get',
            'historical-price-full/SPY/',
            $args,
        );
    }

    /**
     * Get historical GCUSD index
     * @see https://site.financialmodelingprep.com/developer/docs#commodities-historical-price-commodities
     * @throws AdapterException
     * @throws ExternalApiException
     * @throws ConnectionException
     * @throws RequestException
     */
    public function getHistoricalGold(string $from = null, string $to = null): array
    {
        $from = $from ?? Carbon::now()->subDays(self::DEFAULT_GOLD_FROM_DAYS_AGO)->format('Y-m-d');
        $to = $to ?? Carbon::now()->format('Y-m-d');
        $args = [
            'from' => $from,
            'to' => $to,
        ];
        return $this->request(
            'get',
            'historical-price-full/GCUSD/',
            $args,
        );
    }

    /**
     * Transform result from historical-price-full into array ready for DailyStatsService->fillStats
     * @param array $result
     * @param string $metric
     * @return array
     */
    public function parseHistoricalResult(array $result, string $metric): array
    {
        $data = [];

        foreach ($result['historical'] as $day) {
            $date = $day['date'];
            $data[$date][$metric] = $day['close'];
        }

        ksort($data);

        return $data;
    }
}
