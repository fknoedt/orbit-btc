<?php

namespace App\Adapters;

use App\Services\ExternalApiClientInterface;

class AdapterFactory
{
    public const string DEFAULT_ADAPTER = 'CoinMarketCap';

    /** singleton */
    private static array $adapters;

    /**
     * Look for a class named "{$adapterName}ApiClientAdapter" and return its singleton instance
     */
    public static function getAdapter(string $adapterName = null): ExternalApiClientInterface
    {
        $adapterName = $adapterName ?? self::DEFAULT_ADAPTER;
        $adapterName = ucfirst($adapterName);

        if (! isset(self::$adapters[$adapterName])) {
            $adapterClassName = '\App\Adapters\\' . $adapterName . 'ApiClientAdapter';

            if (class_exists($adapterClassName)) {
                $adapter = new $adapterClassName();
            }

            if (!isset($adapter) || !$adapter instanceof ExternalApiClientInterface) {
                throw new \InvalidArgumentException(
                    "Invalid Adapter Class for {$adapterName}"
                );
            }

            self::$adapters[$adapterName] = $adapter;
        }

        return self::$adapters[$adapterName];
    }
}
