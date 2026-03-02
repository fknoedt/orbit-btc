<?php

namespace App\Clients;

use App\Exceptions\AdapterException;
use App\Exceptions\ExternalApiException;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

/**
 * BGeometrics Free API -- bitcoin-data.com
 * @see https://charts.bgeometrics.com/bitcoin_api.html
 * @see https://bitcoin-data.com/api/swagger-ui/index.html
 */
class BgeometricsClient extends BaseClient
{
    private string $version = 'v1';

    /** For throttling -- @see https://bitcoin-data.com/bguser/pricing */
    public const int MAX_REQUESTS_PER_HOUR = 5;

    /** /v1/{endpoint} => result_field */
    public const array ENDPOINTS = [
        'etf-flow-btc' => 'etfFlow',
        'etf-btc-total' => 'etfBtcTotal',
        'miner-balances' => 'minerBalances',
        'mvrv' => 'mvrv',
        'nrpl-usd' => 'nrplUsd',
        'nupl' => 'nupl',
        'nvt-ratio' => 'nvtRatio',
        // disabled on 9/22/25 as it's coming back as null
        // 'open-interest-futures' => 'openInterestFutures',
        'puell-multiple' => 'puellMultiple',
        'cap-real-usd' => 'capRealUSD',
        'reserve-risk' => 'reserveRisk',
        'true-market-mean' => 'trueMarketMean',
        // @see https://charts.bgeometrics.com/m2_btc.html
        // went 404 around mid 2025
        // 'm2' => 1, // columns are 0 for date and 1 for value
    ];

    public function __construct()
    {
        parent::__construct();
        self::$dataSourceId = config('data.data_source.bgeometrics_id');
        if (! $url = config('btc.apis.bgeometrics.url')) {
            throw new \RuntimeException('could not load config: data.data_source.bgeometrics_id');
        }
        self::$url = $url . '/' . $this->version . '/';
    }

    public static function getEndpointsAsColumnNames(): array
    {
        $columnNames = [];
        foreach (array_keys(self::ENDPOINTS) as $endpoint) {
            $columnNames[$endpoint] = str_replace('-', '_', $endpoint);
        }

        return $columnNames;
    }

    public function getEndpointToColumnName(string $endpoint): string
    {
        if (! isset(self::ENDPOINTS[$endpoint])) {
            throw new \InvalidArgumentException("invalid endpoint {$endpoint}");
        }

        return str_replace('-', '_', $endpoint);
    }

    /**
     * @throws AdapterException
     * @throws ExternalApiException
     * @throws ConnectionException
     * @throws RequestException
     */
    public function getEndpoint(string $endpoint, array $params = [], bool $fromFile = false): array
    {
        if (! isset(self::ENDPOINTS[$endpoint])) {
            throw new \InvalidArgumentException("Invalid endpoint: {$endpoint}");
        }

        $data = [];
        $resultField = self::ENDPOINTS[$endpoint];
        $columnName = $this->getEndpointToColumnName($endpoint);
        if ($fromFile) {
            $response = json_decode(
                file_get_contents(
                    database_path() . DIRECTORY_SEPARATOR . 'local-data' . DIRECTORY_SEPARATOR .
                    "bg-{$endpoint}.json" // in .gitignore
                ),
                true
            );
        } else {
            $response = $this->request('get', $endpoint, $params);
        }

        foreach ($response as $entry) {
            $date = $entry['d'] ?? $entry['theDay'] ?? null;
            if (! $date) {
                if (isset($entry[0])) {
                    $date = Carbon::createFromTimestampMs($entry[0])->format('Y-m-d');
                } else {
                    throw new \RuntimeException("Invalid date on " . json_encode($entry));
                }
            }
            if (! array_key_exists($resultField, $entry)) {
                throw new \RuntimeException("Invalid result_field: {$resultField} - " . json_encode($entry));
            }
            $value = $entry[$resultField];
            // don't instantiate null value days
            if (is_null($value)) {
                continue;
            }
            $data[$date] = [
                $columnName => $value,
            ];
        }
        ksort($data);

        return $data;
    }
}
