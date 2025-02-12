<?php

namespace App\Adapters;

use App\Exceptions\AdapterException;
use App\Exceptions\ExternalApiException;
use App\Models\DailyPrice;
use App\Services\ExternalApiClientInterface;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * CMC Startup Plan trial from 1/24/25 to 2/24/25
 * @see https://github.com/vittominacori/coinmarketcap-php
 */
class CoinMarketCapApiClientAdapter extends BaseClientAdapter implements ExternalApiClientInterface
{
    private const ADAPTER_NAME = 'coinmarketcap';
    private const int CMC_BITCOIN_ID = 1;
    private string $key;
    private string $version = 'v2';
    private string $currency;
    private string $systemDateFormat;
    public const DATE_FORMAT = 'd-m-Y';
    private const int REQUEST_CACHE_TTL = 15;

    // static properties can be accessed by BaseClientAdapter methods
    protected static int $dataSourceId;
    protected static string $url;

    public function __construct()
    {
        $this->currency = strtoupper(config('btc.currency') ?? 'usd');
        $this->systemDateFormat = config('btc.date_format');
        self::$dataSourceId = config('data.data_source.coinmarketcap_id');
        self::$url = config('btc.apis.coinmarketcap.url');
        $this->key = config('btc.apis.coinmarketcap.key');
    }

    /**
     * @todo move part of this method to BaseClientAdapter?
     * @throws AdapterException
     * @throws ExternalApiException
     * @throws \Illuminate\Http\Client\ConnectionException
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function request(string $method, string $endpoint, array $args = []): array
    {
        // symbol is not unique and returns crap-coins if you filter by it
        $args['id'] = self::CMC_BITCOIN_ID;
        $url = 'https://' . self::$url . '/' . $this->version . '/' . $endpoint;

        $request = Http::withHeaders([
            'X-CMC_PRO_API_KEY' => $this->key,
            'Accept' => 'application/json',
        ]);

        if (! method_exists($request, $method)) {
            throw new AdapterException('Invalid request method: ' . $method);
        }

        $cacheKey = md5(
            __METHOD__ .
            $url .
            $method .
            implode('', $args) .
            implode('', array_keys($args))
        );

        if ($data = Cache::get($cacheKey)) {
            return $data;
        }

        /** @var Response $response */
        $response = $request->$method(
            $url,
            $args
        );

        $body = $response->getBody()->getContents();

        $this->logRequest(
            __METHOD__,
            $args,
            $response->getStatusCode(),
            $body,
            $method,
            $url,
            $response->transferStats->getTransferTime()
        );

        if ($response->failed()) {
            if ($response->clientError()) {
                throw new AdapterException(
                    sprintf(
                        "Error in %s request to %s: %s",
                        strtoupper($method),
                        $url,
                        $body
                    ),
                    $response->getStatusCode(),
                    $response->toException()
                );
            } else {
                throw new ExternalApiException(
                    $response->getBody()->getContents(),
                    $response->getStatusCode(),
                    $response->toException()
                );
            }
        }

        $data = json_decode($body, true);

        Cache::put($cacheKey, $data, self::REQUEST_CACHE_TTL);

        return $data;
    }

    /**
     * Uses CMC's endpoint with open, high, low, market cap and volume
     * @see https://coinmarketcap.com/api/documentation/v1/#operation/getV2CryptocurrencyOhlcvLatest
     * @throws AdapterException
     * @throws ExternalApiException
     * @throws \Illuminate\Http\Client\ConnectionException
     * @throws \Illuminate\Http\Client\RequestException
     */
    private function getBtcFullQuote(array $options = []): array
    {
        return $this->request('get', 'cryptocurrency/ohlcv/latest', $options);
    }

    /**
     * Uses CMC's endpoint with latest variations
     * @see https://coinmarketcap.com/api/documentation/v1/#operation/getV2CryptocurrencyQuotesLatest
     * @throws AdapterException
     * @throws ExternalApiException
     * @throws \Illuminate\Http\Client\ConnectionException
     * @throws \Illuminate\Http\Client\RequestException
     */
    private function getBtcQuote(array $options = []): array
    {
        return $this->request('get', 'cryptocurrency/quotes/latest', $options);
    }

    /**
     * Get the current BTC price in the system's default currency
     * @throws ExternalApiException
     */
    public function getCurrentPrice(array $options = []): float
    {
        $quote = $this->getBtcFullQuote($options);

        if (! $price = $quote['data'][self::CMC_BITCOIN_ID]['quote'][$this->currency]['close'] ?? null) {
            throw new ExternalApiException(
                "BTC price not found for `{$this->currency}` @ " . self::ADAPTER_NAME .
                ' -- ' . json_encode($quote)
            );
        }

        return (float) $price;
    }

    public function getCurrentPriceStats(array $options = []): array
    {
        $quote = $this->getBtcQuote($options);

        if (! $lastQuote = $quote['data'][self::CMC_BITCOIN_ID]['quote'][$this->currency] ?? null) {
            throw new ExternalApiException(
                "BTC price not found for `{$this->currency}` @ " . self::ADAPTER_NAME .
                ' -- ' . json_encode($quote)
            );
        }

        return $lastQuote;
    }

    /**
     * Get the current BTC price in the system's default currency and return a model hydrated with this API's data
     * @throws \Exception
     */
    public function getCurrentDailyPrice(array $options = []): DailyPrice
    {
        $quote = $this->getBtcFullQuote($options);

        if (empty($quote['data'][self::CMC_BITCOIN_ID]['quote'])) {
            throw new AdapterException("CMC: malformed quote response: " . json_encode($quote));
        }

        return $this->quoteToDailyPrice(
            $quote['data'][self::CMC_BITCOIN_ID]['quote'][$this->currency],
            date('Y-m-d')
        );
    }

    /**
     * Get price [$date => $price] for the given date interval
     * @warning this is a paid endpoint
     * @see https://coinmarketcap.com/api/documentation/v1/#operation/getV2CryptocurrencyOhlcvHistorical
     * @throws \Exception
     * @throws AdapterException
     */
    public function getDailyPriceInterval(Carbon $startDate, Carbon $endDate): array
    {
        $prices = [];

        if ($startDate->isSameDay($endDate)) {
            $endDate = $endDate->addDay();
        }

        $data = $this->request(
            'get',
            'cryptocurrency/ohlcv/historical',
            [
                'interval' => 'daily', // @todo use 24h as per the manual to get end of day rate (not working)
                'time_start' => $startDate->subDay()->format('Y-m-d') . 'T23:59:00.000Z',
                'time_end' => $endDate->format('Y-m-d') . 'T23:59:00.000Z',
            ]
        );

        foreach ($data['data']['quotes'] as $day) {
            $dailyPrice = $this->quoteToDailyPrice($day['quote'][$this->currency]);

            $dailyPrice->time_high = $day['time_high'];
            $dailyPrice->time_low = $day['time_low'];

            $prices[$dailyPrice->date] = $dailyPrice;
        }

        if (empty($prices)) {
            throw new AdapterException(
                sprintf(
                    'No %s price found for interval (%s, %s) @ %s: %s',
                    $this->currency,
                    $startDate->format($this->systemDateFormat),
                    $endDate->format($this->systemDateFormat),
                    self::ADAPTER_NAME,
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

    public function quoteToDailyPrice(array $quote, string $date = null): DailyPrice
    {
        if (empty($quote)) {
            throw new AdapterException("CMC: empty quote");
        }

        $dailyPrice = new DailyPrice();

        $dailyPrice->data_source_id = config('data.data_source.coinmarketcap_id');
        $dailyPrice->open = $quote['open'];
        $dailyPrice->high = $quote['high'];
        $dailyPrice->low = $quote['low'];
        $dailyPrice->close = $quote['close'];
        $dailyPrice->total_volume = $quote['volume'];
        $dailyPrice->market_cap = $quote['market_cap'] ?? null;
        $dailyPrice->date = $date ?? substr($quote['timestamp'], 0, 10);

        return $dailyPrice;
    }
}
