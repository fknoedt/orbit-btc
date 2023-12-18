<?php

namespace App\Http\Controllers;

use App\Services\ExternalApiClientInterface;
use Carbon\Carbon;

class AdapterController extends Controller
{
    private const DEFAULT_ADAPTER = 'CoinGecko';

    /**
     * @todo move to AdapterService?
     */
    private function getAdapter(string $adapterName = self::DEFAULT_ADAPTER): ExternalApiClientInterface
    {
        $adapterName = ucfirst($adapterName);
        $adapterClassName = '\App\Adapters\\' . $adapterName . 'ApiClientAdapter';

        if (class_exists($adapterClassName)) {
            $adapter = new $adapterClassName();
        }

        if (! isset($adapter) || ! $adapter instanceof ExternalApiClientInterface) {
            throw new \InvalidArgumentException(
                "Invalid Adapter Class for {$adapterName}"
            );
        }

        return $adapter;
    }

    public function getCurrentBtcPrice($externalApi = self::DEFAULT_ADAPTER)
    {
        $adapter = $this->getAdapter($externalApi);
        $currency = config('btc.currency');

        return response()->json(["btc/{$currency}" => $adapter->getCurrentBtcPrice()]);
    }

    public function getBtcPriceInterval(string $startDate, string $endDate, string $externalApi = self::DEFAULT_ADAPTER)
    {
        $adapter = $this->getAdapter($externalApi);

        $start = Carbon::createFromFormat(config('btc.date_format'), $startDate);
        $end = Carbon::createFromFormat(config('btc.date_format'), $endDate);

        return response()->json($adapter->getBtcPriceInterval($start, $end));
    }

    public function getBtcPriceByDays(string $days, ?string $externalApi = self::DEFAULT_ADAPTER)
    {
        throw new \BadMethodCallException(__METHOD__ . ' has not been implemented');

        $adapter = $this->getAdapter($externalApi);

        return response()->json($adapter->getBtcPriceByDays($days));
    }
}
