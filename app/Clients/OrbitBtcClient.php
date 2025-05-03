<?php

namespace App\Clients;

use App\Exceptions\AdapterException;
use App\Exceptions\ExternalApiException;
use App\Models\DailyPrice;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

/**
 * This is a client to consumer another Orbit instance's API
 */
class OrbitBtcClient extends BaseClient
{
    private ?string $authToken;


    public function __construct()
    {
        parent::__construct();
        self::$dataSourceId = config('data.data_source.orbit_btc_id');
        if (! $url = config('btc.apis.orbit_btc.url')) {
            throw new \RuntimeException('could not load config: btc.apis.orbit_btc.url');
        }
        self::$url = $url . '/api/';
        $this->authToken = config('btc.apis.orbit_btc.key');
        if (! $this->authToken) {
            throw new \RuntimeException('could not load config: btc.apis.orbit_btc.key');
        }
    }

    /**
     * @throws AdapterException
     * @throws ExternalApiException
     * @throws ConnectionException
     * @throws RequestException
     */
    public function updateRemotePrices(string $since, string $to): int
    {
        $data = DailyPrice::where('date', '>=', $since)
            ->where('date', '<=', $to)
            ->orderBy('date', 'asc')
            ->get()
            ->keyBy('date')
            ->toArray();

        $response = $this->request('post', 'update-daily-prices', ['daily_prices' => $data]);

        return $response['daily-prices-updated'];
    }

    /**
     * Override parent's method to inject authToken
     * @throws AdapterException
     * @throws ExternalApiException
     * @throws ConnectionException
     * @throws RequestException
     */
    public function request(string $method, string $endpoint, array $args = [], array $headers = []): array
    {
        $headers['Authorization'] = 'Bearer ' . $this->authToken;

        return parent::request($method, $endpoint, $args, $headers);
    }
}
