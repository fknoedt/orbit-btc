<?php

namespace App\Adapters;

use App\Clients\BaseClient;
use App\Exceptions\AdapterException;
use App\Exceptions\ExternalApiException;
use App\Models\DailyPrice;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

/**
 * CryptoCompare uses and serves Coindesk data
 * Using Min API for now (not the new Data API)
 * WARNING: cannot be used as default adapter as getDailyPriceInterval() is not implemented
 * @see https://developers.coindesk.com/documentation
 */
class CryptoCompareApiAdapter extends BaseClient implements ExternalApiAdapterInterface
{
    public const int MAX_LIMIT = 2000;
    private ?string $key;

    protected const array ENDPOINTS = [
        /*'tradingsignals/intotheblock/latest' => [
            'name' => 'Trading Signals',
            'description' => 'Powered by IntoTheBlock, an intelligence company that leverages machine learning and advanced statistics to extract intelligent signals for crypto-assets.',
            'result_fields' => [
                'time',
            ]
        ],*/
    ];

    public function __construct()
    {
        parent::__construct();
        self::$dataSourceId = config('data.data_source.coindesk_id');
        self::$currency = strtoupper(self::$currency);
        self::$url = config('btc.apis.cryptocompare.url') . '/data/';
        if (! $this->key = config('btc.apis.cryptocompare.key')) {
            throw new \RuntimeException('could not load config: btc.apis.cryptocompare.key');
        }
    }

    /**
     * @throws AdapterException
     * @throws ExternalApiException
     * @throws ConnectionException
     * @throws RequestException
     */
    public function request(string $method, string $endpoint, array $args = [], array $headers = []): array
    {
        // always enforce these
        $args['fsym'] = 'BTC';
        $args['tsym'] = 'USD';

        $headers['Authorization'] = 'Apikey ' . $this->key;

        return parent::request($method, $endpoint, $args, $headers);
    }

    /**
     * get open, high, low and close -- no market cap or volume =/
     * @see https://developers.coindesk.com/documentation/legacy/Price/multipleSymbolsFullPriceEndpoint
     * @throws AdapterException
     * @throws ExternalApiException
     * @throws ConnectionException
     * @throws RequestException
     */
    private function getSymbolFullData(array $options = []): array
    {
        $options['fsyms'] = 'BTC';
        $options['tsyms'] = 'USD';
        $response = $this->request('get', 'pricemultifull', $options);

        if (! $data = $response['RAW']['BTC']['USD'] ?? null) {
            throw new AdapterException(
                $this->getClientName() . ": malformed response: " . json_encode($response)
            );
        }

        return $data;
    }

    /**
     * get open, high, low and close -- no market cap or volume =/
     * @see https://developers.coindesk.com/documentation/legacy/Historical/dataHistoday
     * @throws AdapterException
     * @throws ExternalApiException
     * @throws ConnectionException
     * @throws RequestException
     */
    private function getDailyPair(array $options = []): array
    {
        $response = $this->request('get', 'v2/histoday', $options);

        if (! $data = $response['Data']['Data'] ?? null) {
            throw new AdapterException(
                $this->getClientName() . ": malformed response: " . json_encode($response)
            );
        }

        if (isset($options['limit']) && $options['limit'] === 1) {
            // when limiting by 1, CC still returns two days so we're gonna use the latest (current)
            $data = end($data);
        }

        return $data;
    }

    /**
     * Get the current BTC price in the system's default currency
     * @throws ExternalApiException
     * @throws AdapterException
     * @throws ConnectionException
     * @throws RequestException
     */
    public function getCurrentPrice(array $options = []): float
    {
        $dailyPair = $this->getSymbolFullData($options);

        return (float) $dailyPair['PRICE'];
    }

    /**
     * @throws ExternalApiException
     * @throws AdapterException
     * @throws ConnectionException
     * @throws RequestException
     */
    public function getCurrentPriceStats(array $options = []): array
    {
        $symbol = $this->getSymbolFullData($options);

        return [
            'price' => $symbol['PRICE'],
            'market_cap' => $symbol['MKTCAP'] ?? null,
            'percent_change_24h' => $symbol['CHANGEPCT24HOUR'] ?? null,
            'volume_24h' => $symbol['VOLUME24HOUR'] ?? null,
            'volume_change_24h' => $symbol['VOLUME24HOURTO'],
            'market_cap_dominance' => null,
        ];
    }

    /**
     * Get the current BTC price in the system's default currency and return a model hydrated with this API's data
     * @throws \Exception
     */
    public function getCurrentDailyPrice(array $options = []): DailyPrice
    {
        $options['limit'] = 1;
        $dailyPair = $this->getDailyPair($options);

        return $this->dailyPairToDailyPrice(
            $dailyPair,
            date('Y-m-d')
        );
    }

    /**
     * On-chain daily data
     * This endpoint can take more than 24h to return the latest day (e.g. on 5/2 3AM UTC it still returned 4/30 data)
     * @see https://developers.coindesk.com/documentation/legacy/Blockchain/blockchainDay
     * @throws AdapterException
     * @throws ConnectionException
     * @throws ExternalApiException
     * @throws RequestException
     */
    public function getOnChainDailyHistory(int $daysAgo = self::MAX_LIMIT): array
    {
        if ($daysAgo > self::MAX_LIMIT) {
            throw new \BadMethodCallException('Pagination not implemented');
        }
        $response = $this->request(
            'get',
            'blockchain/histo/day',
            [
                'toTs' => Carbon::now()->addDay()->getTimestamp(),
                'limit' => $daysAgo
            ]
        );

        if (! $data = $response['Data']['Data'] ?? null) {
            throw new AdapterException(
                $this->getClientName() . ": malformed response: " . json_encode($response)
            );
        }

        return $data;
    }

    /**
     * Get total volume from the daily historical exchange data. The values are based on 00:00 GMT time.
     * We [Crypto Compare] store the data in BTC and we multiply by the BTC-tsym value.
     * @see https://developers.coindesk.com/documentation/legacy/Historical/dataExchangeHistoday
     * @throws AdapterException
     * @throws ConnectionException
     * @throws ExternalApiException
     * @throws RequestException
     */
    public function getExchangeVolume(int $daysAgo = self::MAX_LIMIT): array
    {
        if ($daysAgo > self::MAX_LIMIT) {
            throw new \BadMethodCallException('Pagination not implemented');
        }
        $response = $this->request(
            'get',
            'exchange/histoday',
            [
                // this endpoint returns partial data for the current day, which we don't want
                'toTs' => Carbon::yesterday('UTC')->timestamp,
                'limit' => $daysAgo
            ]
        );

        if (! $data = $response['Data'] ?? null) {
            throw new AdapterException(
                $this->getClientName() . ": malformed response: " . json_encode($response)
            );
        }

        return $data;
    }

    // TODO: get and persist exchanges volume + persist initial stats info (since 2009-10-05) and save everything

    /**
     * Get price [$date => $price] for the given date interval
     * @throws \Exception
     * @throws AdapterException
     */
    public function getDailyPriceInterval(Carbon $startDate, Carbon $endDate): array
    {
        $prices = [];

        if ($startDate->isSameDay($endDate)) {
            $endDate = $endDate->addDay();
        }

        // TODO: adapt to https://developers.coindesk.com/documentation/legacy/Historical/dataHistoday
        // it seems that it only offers limit + toTs to control date ranges and it will need pagination
        $args = [
            'toTs'
        ];

        $data = $this->request(
            'get',
            'data/v2/histoday',
        );

        throw new \BadMethodCallException(__METHOD__ . ' not implemented.');

        if (empty($prices)) {
            throw new AdapterException(
                sprintf(
                    'No %s price found for interval (%s, %s) @ %s: %s',
                    self::$currency,
                    $startDate->format(self::$systemDateFormat),
                    $endDate->format(self::$systemDateFormat),
                    $this->getClientName(),
                    json_encode($data)
                )
            );
        }

        return $prices;
    }

    /**
     * @
     * @throws \Exception
     */
    public function getBtcPriceByDays(array $days): array
    {
        throw new \BadMethodCallException('Method not implemented: ' . __METHOD__);
    }

    public function dailyPairToDailyPrice(array $dailyPair, string $date = null): DailyPrice
    {
        if (empty($dailyPair)) {
            throw new AdapterException($this->getClientName() . ": empty dailyPair");
        }

        $dailyPrice = new DailyPrice();

        $dailyPrice->data_source_id = config('data.data_source.coindesk_id');
        $dailyPrice->open = $dailyPair['open'];
        $dailyPrice->high = $dailyPair['high'];
        $dailyPrice->low = $dailyPair['low'];
        $dailyPrice->close = $dailyPair['close'];
        $dailyPrice->total_volume = $dailyPair['volumeto'];
        $dailyPrice->market_cap = $dailyPair['marketcap'] ?? null;
        $dailyPrice->date = $date ?? Carbon::createFromTimestamp($dailyPair['time'])->format(self::$systemDateFormat);

        return $dailyPrice;
    }
}
