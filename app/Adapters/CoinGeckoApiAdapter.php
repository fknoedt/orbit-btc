<?php

namespace App\Adapters;

use App\Clients\BaseClient;
use App\Exceptions\AdapterException;
use App\Models\DailyPrice;
use BadMethodCallException;
use Carbon\Carbon;
use Codenixsv\CoinGeckoApi\CoinGeckoClient;

/**
 * BTC data starts on 2013-04-27
 * @see https://github.com/codenix-sv/coingecko-api
 */
class CoinGeckoApiAdapter extends BaseClient implements ExternalApiAdapterInterface
{
    private CoinGeckoClient $cgClient;
    public const DATE_FORMAT = 'd-m-Y';

    protected static int $dataSourceId;

    public function __construct()
    {
        parent::__construct();
        $this->cgClient = new \Codenixsv\CoinGeckoApi\CoinGeckoClient();
        self::$dataSourceId = config('data.data_source.coingecko_id');
    }

    /**
     * Get the current BTC price in the system's default currency
     * @throws \Exception
     */
    public function getCurrentPrice(array $options = []): float
    {
        // TODO: try/catch, log request upon failure and try to add request ID to Sentry issue
        $data = $this->cgClient->simple()->getPrice('bitcoin', self::$currency);
        $price = $data['bitcoin'][self::$currency] ?? null;
        if (! $price) {
            throw new AdapterException(
                'price not found for `' . self::$currency . '` @ ' . $this->getClientName()
            );
        }

        $this->logRequest(__METHOD__, ['currency' => self::$currency], 200, json_encode($data));

        return (float) $price;
    }

    public function getCurrentPriceStats(array $options = []): array
    {
        throw new BadMethodCallException(__METHOD__ . ' not implemented');
    }

    public function getCurrentDailyPrice(): DailyPrice
    {
        throw new BadMethodCallException("TODO: Implement getCurrentDailyPrice() method.");
    }


    /**
     * Get price [$date => $price] for the given date interval
     * @todo break it down in price, market-cap and total-volume?
     * @throws \Exception
     * @throws AdapterException
     */
    public function getDailyPriceInterval(Carbon $startDate, Carbon $endDate): array
    {
        $prices = [];

        if ($startDate->isSameDay($endDate) && !$startDate->isToday()) {
            $endDate = $endDate->addDay();
        }

        $data = $this->cgClient->coins()->getMarketChartRange(
            'bitcoin',
            self::$currency,
            $startDate->getTimestamp(),
            $endDate->getTimestamp()
        );

        foreach ($data['prices'] as $price) {
            // $lastPriceOfDay = end($data['prices']);
            $timestampInSeconds = intval($price[0] / 1000);
            $date = Carbon::createFromTimestamp($timestampInSeconds)->format(self::$systemDateFormat);
            if (! $date) {
                throw new AdapterException(__METHOD__ . ": Could not parse date from timestamp {$price[0]}");
            }
            $prices[$date] = $price[1];
        }

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
     * @throws \Exception
     */
    public function getBtcPriceByDays(array $days): array
    {
        $btcData = [];
        throw new \BadMethodCallException('Method not implemented: ' . __METHOD__);
        foreach ($days as $day) {
            // standard input => adapter format
            $date = Carbon::createFromFormat(self::$systemDateFormat, $day);
            $day = $date->format(self::DATE_FORMAT);
            $marketChart = $this->cgClient
                ->coins()
                ->getHistory('bitcoin', $day, ['localization' => false]);
            dd($marketChart);
            $marketData = $marketChart['market_data'] ?? null;
            if ($marketData) {
                $currentPrice = $marketData['current_price'] ?? null;
                if ($currentPrice) {
                    $price = $marketData[self::$currency] ?? null;
                    if (! $price) {
                        throw new \RuntimeException(
                            'price not found for `' . self::$currency . '` @ ' . $this->getClientName()
                        );
                    }
                    $btcData[$startDate->format(self::DATE_FORMAT)]['price'] = $price;
                }
                $marketCap = $marketData['market_cap'] ?? null;
                if ($marketCap) {
                    $marketCapValue = $marketCap[self::$currency] ?? null;
                    if (! $marketCapValue) {
                        throw new \RuntimeException(
                            "market_cap not found for `" . self::$currency . "` @ " . $this->getClientName()
                        );
                    }
                    $btcData[$startDate->format(self::DATE_FORMAT)]['market_cap'] = $marketCapValue;
                }
                $totalVolume = $marketData['total_volume'] ?? null;
                if ($totalVolume) {
                    $totalVolumeValue = $totalVolume[self::$currency] ?? null;
                    if (! $totalVolumeValue) {
                        throw new \RuntimeException(
                            "total_volume not found for `" . self::$currency . "` @ " . $this->getClientName()
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
