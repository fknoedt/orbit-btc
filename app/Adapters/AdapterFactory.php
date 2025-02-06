<?php

namespace App\Adapters;

use App\Services\ExternalApiClientInterface;

class AdapterFactory
{
    public const DEFAULT_ADAPTER = 'CoinMarketCap';

    /**
     * Look for a class named "{$adapterName}ApiClientAdapter" and return its instance
     * @return ExternalApiClientInterface
     */
    public static function getAdapter(string $adapterName = null): ExternalApiClientInterface
    {
        $adapterName = $adapterName ?? self::DEFAULT_ADAPTER;
        $adapterName = ucfirst($adapterName);
        $adapterClassName = '\App\Adapters\\' . $adapterName . 'ApiClientAdapter';

        if (class_exists($adapterClassName)) {
            $adapter = new $adapterClassName();
        }

        if (!isset($adapter) || !$adapter instanceof ExternalApiClientInterface) {
            throw new \InvalidArgumentException(
                "Invalid Adapter Class for {$adapterName}"
            );
        }

        return $adapter;
    }
}
