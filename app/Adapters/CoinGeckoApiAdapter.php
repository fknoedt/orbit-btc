<?php

namespace App\Adapters;

use App\Clients\BaseClient;
use App\Exceptions\AdapterException;
use App\Exceptions\ExternalApiException;
use App\Models\DailyPrice;
use BadMethodCallException;
use Carbon\Carbon;
use Codenixsv\CoinGeckoApi\CoinGeckoClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

/**
 * BTC data starts on 2013-04-27
 * @see https://github.com/codenix-sv/coingecko-api
 */
class CoinGeckoApiAdapter extends BaseClient implements ExternalApiAdapterInterface
{
    public const string DEFAULT_API_VERSION = 'v3';

    public const int MAX_PAST_DAYS = 365;

    private string $key;
    private string $version;

    public function __construct(string $apiVersion = self::DEFAULT_API_VERSION)
    {
        parent::__construct();
        $this->version = $apiVersion;
        self::$dataSourceId = config('data.data_source.coingecko_id');
        self::$url = config('btc.apis.coingecko.url') . '/api/'. $this->version . '/';
        $this->key = config('btc.apis.coingecko.key');
    }

    /**
     * @throws AdapterException
     * @throws ExternalApiException
     * @throws ConnectionException
     * @throws RequestException
     */
    public function request(string $method, string $endpoint, array $args = [], array $headers = []): array
    {
        $headers['x-cg-demo-api-key'] = $this->key;

        return parent::request($method, $endpoint, $args, $headers);
    }

    /**
     * Get the current BTC price in the system's default currency
     */
    public function getCurrentPrice(array $options = []): float
    {
        $response = $this->request('get', 'simple/price', [
            'ids' => 'bitcoin',
            'vs_currencies' => self::$currency
        ]);

        $price = $response['bitcoin'][self::$currency] ?? null;
        if (! $price) {
            throw new AdapterException(
                'price not found for `' . self::$currency . '` @ ' . $this->getClientName()
            );
        }

        return (float) $price;
    }

    public function getCurrentPriceStats(array $options = []): array
    {
        $response = $this->request('get', 'simple/price', [
            'ids' => 'bitcoin',
            'vs_currencies' => self::$currency,
            'include_market_cap' => 'true',
            'include_24hr_vol' => 'true',
            'include_24hr_change' => 'true',
        ]);

        $stats = $response['bitcoin'];

        return [
            'price' => $stats[self::$currency] ?? null,
            'percent_change_24h' => null, // TODO
            'volume_change_24h' => $stats['usd_24h_change'] ?? null,
            'market_cap_dominance' => null, // TODO
        ];
    }

    public function getCurrentDailyPrice(): DailyPrice
    {
        $response = $this->request('get', 'coins/bitcoin/market_chart', [
            'vs_currency' => self::$currency,
            'days' => 0,
            'interval' => 'daily',
        ]);

        $date =  Carbon::createFromTimestamp($response['prices'][0][0] / 1000)->format(self::$systemDateFormat);

        return new DailyPrice([
            'date' => $date,
            'close' => $response['prices'][0][1],
            'market_cap' => $response['market_caps'][0][1],
            'total_volume' => $response['total_volumes'][0][1]
        ]);
    }


    /**
     * Get price [$date => $price] for the given date interval
     * @throws \Exception
     * @throws AdapterException
     */
    public function getDailyPriceInterval(Carbon $startDate, Carbon $endDate): array
    {
        $prices = [];

        if ($startDate->isSameDay($endDate) && !$startDate->isToday()) {
            $endDate = $endDate->addDay();
        }

        if (Carbon::now()->diffInDays($startDate) > self::MAX_PAST_DAYS) {
            throw new AdapterException(
                sprintf(
                    '%s cannot retrieve dates older than %s: %s given',
                    __METHOD__,
                    self::MAX_PAST_DAYS,
                    $startDate->format('Y-m-d')
                )
            );
        }

        $response = $this->request('get', 'coins/bitcoin/market_chart/range', [
            'vs_currency' => self::$currency,
            'from' => $startDate->timestamp,
            'to' => $endDate->timestamp,
        ]);

        $length = count($response['prices']);

        // $i will correspond to the same position/day in all sub-arrays
        for ($i = 0; $i < $length; $i++) {
            $dailyPrice = new DailyPrice([
                'date' => Carbon::createFromTimestamp($response['prices'][$i][0] / 1000)->format(self::$systemDateFormat),
                'close' => $response['prices'][$i][1],
                'market_cap' => $response['market_caps'][$i][1],
                'total_volume' => $response['total_volumes'][$i][1]
            ]);

            $prices[$dailyPrice->date] = $dailyPrice;
        }

        if (empty($prices)) {
            throw new AdapterException(
                sprintf(
                    'No %s price found for interval (%s, %s) @ %s: %s',
                    self::$currency,
                    $startDate->format(self::$systemDateFormat),
                    $endDate->format(self::$systemDateFormat),
                    $this->getClientName(),
                    json_encode($response)
                )
            );
        }

        return $prices;
    }

    /**
     * @throws \Exception
     */
    public function getBtcPriceByDays(array $days): array
    {
        throw new \BadMethodCallException('Method not implemented: ' . __METHOD__);
    }
}
