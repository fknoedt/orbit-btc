<?php

namespace App\Adapters;

class AdapterFactory
{
    public const string DEFAULT_ADAPTER = 'CoinGecko';

    /** singleton */
    private static array $adapters;

    /**
     * Look for a class named "{$adapterName}ApiClientAdapter" and return its singleton instance
     */
    public static function getAdapter(string $adapterName = null): ExternalApiAdapterInterface
    {
        $adapterName = $adapterName ?? self::DEFAULT_ADAPTER;
        $adapterName = ucfirst($adapterName);

        if (! isset(self::$adapters[$adapterName])) {
            $adapterClassName = '\App\Adapters\\' . $adapterName . 'ApiAdapter';

            if (class_exists($adapterClassName)) {
                $adapter = new $adapterClassName();
            }

            if (!isset($adapter) || !$adapter instanceof ExternalApiAdapterInterface) {
                throw new \InvalidArgumentException(
                    "Invalid Adapter Class for {$adapterName}"
                );
            }

            self::$adapters[$adapterName] = $adapter;
        }

        return self::$adapters[$adapterName];
    }
}
