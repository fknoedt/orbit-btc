<?php

namespace App\Services;

use App\Adapters\AdapterFactory;
use App\Adapters\ExternalApiAdapterInterface;

class Btc3rdPartyService
{
    private ExternalApiAdapterInterface $adapter;

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

    public function getInfo(): array
    {
        return [
            'name' => $this->adapter->getClientName(),
            'url' => $this->adapter->getUrl(),
            'data_source_id' => $this->adapter->getDataSourceId(),
        ];
    }
}
