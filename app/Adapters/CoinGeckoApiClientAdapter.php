<?php

namespace App\Adapters;

use App\Services\ExternalApiClientInterface;
use Carbon\Carbon;
use Codenixsv\CoinGeckoApi\CoinGeckoClient;

/**
 * BTC data starts on 2013-04-27
 * @see https://github.com/codenix-sv/coingecko-api
 */
class CoinGeckoApiClientAdapter implements ExternalApiClientInterface
{
    private CoinGeckoClient $cgClient;
    private const ADAPTER_NAME = 'coingecko';
    private string $currency;

    public function __construct()
    {
        $this->cgClient = new \Codenixsv\CoinGeckoApi\CoinGeckoClient();
        $this->currency = config('btc.currency') ?? 'usd';
    }

    public function getCurrentBtcPrice(array $options = []): array
    {
        $data = $this->cgClient->simple()->getPrice('bitcoin', $this->currency);
        $price = $data['bitcoin'][$this->currency] ?? null;
        if (! $price) {
            throw new \RuntimeException(
                "price not found for `{$this->currency}` @ " . self::ADAPTER_NAME
            );
        }

        /**
         * TODO:
         *  * create BaseApiClientAdapter
         *  * create  ->priceTemplate():array
         *  * each adapter will overwrite the template with what they have
         *  * template should match price DB table
         *  * create methods to check, fetch and populate historic tables
         *  * create HistoricPriceService->populateAllDaysSince()
         */
        return ['btc/usd' => (float) $price];
    }

    public function getBtcPriceInterval(Carbon $startDate, Carbon $endDate): array
    {
        //$startDate = (new Carbon('2013-04-27'));
        //$timestampTo = (new Carbon('2013-05-01'));

        $dateFormat = config('btc.date_format');
        $btcData = [];

        while ($startDate->addDay() < $endDate) {
            $marketChart = $this->cgClient
                ->coins()
                ->getHistory('bitcoin', $startDate->format($dateFormat), ['localization' => false]);

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
                    $btcData[$startDate->format($dateFormat)]['price'] = $price;
                }
                $marketCap = $marketData['market_cap'] ?? null;
                if ($marketCap) {
                    $marketCapValue = $marketCap[$this->currency] ?? null;
                    if (! $marketCapValue) {
                        throw new \RuntimeException(
                            "market_cap not found for `{$this->currency}` @ " . self::ADAPTER_NAME
                        );
                    }
                    $btcData[$startDate->format($dateFormat)]['market_cap'] = $marketCapValue;
                }
                $totalVolume = $marketData['total_volume'] ?? null;
                if ($totalVolume) {
                    $totalVolumeValue = $totalVolume[$this->currency] ?? null;
                    if (! $totalVolumeValue) {
                        throw new \RuntimeException(
                            "total_volume not found for `{$this->currency}` @ " . self::ADAPTER_NAME
                        );
                    }
                    $btcData[$startDate->format($dateFormat)]['volume'] = [
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
    }

    public function getBtcPriceByDays(array $days): array
    {
        throw new \BadMethodCallException(__METHOD__ . ' is not implemented');
    }
}
