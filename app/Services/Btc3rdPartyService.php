<?php

namespace App\Services;

use App\Adapters\AdapterFactory;

class Btc3rdPartyService
{
    private ExternalApiClientInterface $adapter;

    public function __construct(string $adapter = null)
    {
        $this->adapter = AdapterFactory::getAdapter($adapter);
    }

    public function getCurrentPrice(): float
    {
        return $this->adapter->getCurrentPrice();
    }

    public function getCurrentPriceStats(): array
    {
        return $this->adapter->getCurrentPriceStats();
    }
}
