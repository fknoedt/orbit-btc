<?php

namespace App\Adapters;

use Illuminate\Support\Facades\Log;

class AdapterFactory
{
    public const string DEFAULT_ADAPTER = 'CoinGecko';

    /** singleton */
    public static array $adapters;

    /**
     * Look for a class named "{$adapterName}ApiClientAdapter" and return its singleton instance
     */
    public static function getAdapter(string $adapterName = null): ExternalApiAdapterInterface
    {
        $logMessage = "adapter: {$adapterName} => ";
        $adapterName = $adapterName ?? self::DEFAULT_ADAPTER;
        $adapterName = ucfirst($adapterName);

        Log::info($logMessage . $adapterName);

        if (! isset(self::$adapters[$adapterName])) {
            $adapterClassName = '\App\Adapters\\' . $adapterName . 'ApiAdapter';

            if (class_exists($adapterClassName)) {
                $adapter = new $adapterClassName();
                Log::info('instantiated adapter');
            }

            if (!isset($adapter) || !$adapter instanceof ExternalApiAdapterInterface) {
                throw new \InvalidArgumentException(
                    "Invalid Adapter Class for {$adapterName}"
                );
            }

            self::$adapters[$adapterName] = $adapter;
            Log::info("$adapterName saved with {$adapter->getClientName()}");
        } else {
            Log::info("adapter: {$adapterName} is already instantiated with " . self::$adapters[$adapterName]->getClientName());
        }

        return self::$adapters[$adapterName];
    }
}
