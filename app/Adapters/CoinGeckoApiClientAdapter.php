<?php

namespace App\Adapters;

use App\Exceptions\AdapterException;
use App\Services\ExternalApiClientInterface;
use Carbon\Carbon;
use Codenixsv\CoinGeckoApi\CoinGeckoClient;

/**
 * BTC data starts on 2013-04-27
 * @see https://github.com/codenix-sv/coingecko-api
 */
class CoinGeckoApiClientAdapter extends BaseClientAdapter implements ExternalApiClientInterface
{
    private CoinGeckoClient $cgClient;
    private const ADAPTER_NAME = 'coingecko';
    private string $currency;
    private string $systemDateFormat;
    public const DATE_FORMAT = 'd-m-Y';

    protected static int $dataSourceId;

    public function __construct()
    {
        $this->cgClient = new \Codenixsv\CoinGeckoApi\CoinGeckoClient();
        $this->currency = config('btc.currency') ?? 'usd';
        $this->systemDateFormat = config('btc.date_format');
        self::$dataSourceId = config('data.data_source.coingecko_id');
    }

    /**
     * Get the current BTC price in the system's default currency
     * @throws \Exception
     */
    public function getCurrentBtcPrice(array $options = []): float
    {
        $data = $this->cgClient->simple()->getPrice('bitcoin', $this->currency);
        $price = $data['bitcoin'][$this->currency] ?? null;
        if (! $price) {
            throw new AdapterException(
                "price not found for `{$this->currency}` @ " . self::ADAPTER_NAME
            );
        }

        /**
         * TODO:
         *  * create  ->priceTemplate():array
         *  * each adapter will overwrite the template with what they have
         *  * template should match price DB table
         */
        return (float) $price;
    }

    /**
     * Get price [$date => $price] for the given date interval
     * @todo break it down in price, market-cap and total-volume?
     * @throws \Exception
     * @throws AdapterException
     */
    public function getBtcPriceInterval(Carbon $startDate, Carbon $endDate): array
    {
        $prices = [];

        if ($startDate->isSameDay($endDate)) {
            $endDate = $endDate->addDay();
        }

        $data = $this->cgClient->coins()->getMarketChartRange(
            'bitcoin',
            $this->currency,
            $startDate->getTimestamp(),
            $endDate->getTimestamp()
        );

        foreach ($data['prices'] as $price) {
            // $lastPriceOfDay = end($data['prices']);
            $timestampInSeconds = intval($price[0] / 1000);
            $date = Carbon::createFromTimestamp($timestampInSeconds)->format($this->systemDateFormat);
            if (! $date) {
                throw new AdapterException(__METHOD__ . ": Could not parse date from timestamp {$price[0]}");
            }
            $prices[$date] = $price[1];
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
        $btcData = [];
        throw new \BadMethodCallException('Method not implemented: ' . __METHOD__);
        foreach ($days as $day) {
            // standard input => adapter format
            $date = Carbon::createFromFormat($this->systemDateFormat, $day);
            $day = $date->format(self::DATE_FORMAT);
            $marketChart = $this->cgClient
                ->coins()
                ->getHistory('bitcoin', $day, ['localization' => false]);
            dd($marketChart);
            $marketData = $marketChart['market_data'] ?? null;
            if ($marketData) {
                $currentPrice = $marketData['current_price'] ?? null;
                if ($currentPrice) {
                    $price = $marketData[$this->currency] ?? null;
                    if (! $price) {
                        throw new \RuntimeException(
                            "price not found for `{$this->currency}` @ " . self::ADAPTER_NAME
                        );
                    }
                    $btcData[$startDate->format(self::DATE_FORMAT)]['price'] = $price;
                }
                $marketCap = $marketData['market_cap'] ?? null;
                if ($marketCap) {
                    $marketCapValue = $marketCap[$this->currency] ?? null;
                    if (! $marketCapValue) {
                        throw new \RuntimeException(
                            "market_cap not found for `{$this->currency}` @ " . self::ADAPTER_NAME
                        );
                    }
                    $btcData[$startDate->format(self::DATE_FORMAT)]['market_cap'] = $marketCapValue;
                }
                $totalVolume = $marketData['total_volume'] ?? null;
                if ($totalVolume) {
                    $totalVolumeValue = $totalVolume[$this->currency] ?? null;
                    if (! $totalVolumeValue) {
                        throw new \RuntimeException(
                            "total_volume not found for `{$this->currency}` @ " . self::ADAPTER_NAME
                        );
                    }
                    $btcData[$startDate->format(self::DATE_FORMAT)]['volume'] = [
                        'brl' => $totalVolume['brl'],
                        'eur' => $totalVolume['eur'],
                        'usd' => $totalVolume['usd'],
                    ];
                }
            }
            //->getMarketChartRange('bitcoin', 'usd', $startDate, $timestampTo);
        }

        dd($btcData);

        foreach ($marketChart as $info => $data) {
            dump($info, count($data));
        }

        return $btcData;
    }
}
