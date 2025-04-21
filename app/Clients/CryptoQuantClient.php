<?php

namespace App\Clients;

use App\Exceptions\AdapterException;
use App\Exceptions\ExternalApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Symfony\Component\Mime\Exception\RuntimeException;

/**
 * CryptoQuant Open API
 * Advanced plan - $39/mo - doesn't grant API key =/ needs Professional, $109/mo, plan
 * Development was interrupted but the class will be here in case we start paying for it =)
 * @see CurlCryptoQuantClient -- different way of authentication for charts
 * @see https://cryptoquant.com/docs
 */
class CryptoQuantClient extends BaseClient
{
    private string $version = 'v1';

    public const array ENDPOINTS = [
        'exchange-flows/reserve' => [
            'name' => 'Exchanges Reserve',
            'description' => 'This endpoint returns the full historical on-chain balance of Bitcoin exchanges',
            'result_fields' => ['date', 'reserve', 'reserve_usd'],
        ],
        'exchange-flows/netflow' => [
            'name' => 'Exchanges Netflow',
            'description' => 'The difference between coins flowing into exchanges and flowing out of exchanges. Netflow usually helps us to figure out an increase of idle coins waiting to be traded in a certain time frame.',
            'result_fields' => ['date', 'netflow_total'],
        ],
        'exchange-flows/inflow' => [
            'name' => 'Exchanges Inflow',
            'description' => 'This endpoint returns the inflow of BTC into exchange wallets for as far back as we track. The average inflow is the average transaction value for transactions flowing into exchange wallets on a given day.',
            'result_fields' => ['date', 'inflow_total', 'inflow_top10', 'inflow_mean', 'inflow_mean_ma7'],
        ],
        'exchange-flows/outflow' => [
            'name' => 'Exchange Outflow',
            'description' => 'This endpoint returns the outflow of BTC into exchange wallets for as far back as we track. The average outflow is the average transaction value for transactions flowing into exchange wallets on a given day.',
            'result_fields' => ['date', 'outflow_total', 'outflow_top10', 'outflow_mean', 'outflow_mean_ma7'],
        ],
        'exchange-flows/transactions-count' => [
            'name' => 'Exchanges Transactions Count',
            'description' => 'This endpoint returns the number of transactions flowing in/out of Bitcoin exchanges.',
            'result_fields' => ['date', 'transactions_count_inflow', 'transactions_count_outflow'],
        ],
        'exchange-flows/addresses-count' => [
            'name' => 'Exchange Addresses Count',
            'description' => 'This endpoint returns the number of addresses involved in inflow/outflow transactions.',
            'result_fields' => ['date', 'addresses_count_inflow', 'addresses_count_outflow'],
        ],
        'exchange-flows/in-house-flow' => [
            'name' => 'Exchange In-House Flow',
            'description' => 'his endpoint returns the in-house flow of BTC within wallets of the same exchange for as far back as we track. The average in-house flow is the average transaction value for transactions flowing within wallets on a given day.',
            'result_fields' => ['date', 'flow_total', 'flow_mean', 'transactions_count_flow'],
        ],
        'exchange-flows/mpi' => [
            'name' => 'Miners\' Position Index (MPI)',
            'description' => 'MPI(Miners’ Position Index) is a z score of a specific period. The period range must be 2 days or more and if not, it will return an error. mpi is an index to understand miners’ behavior by examining the total outflow of miners. It highlights periods where the value of Bitcoin’s outflow by miners on a daily basis has historically been extremely high or low. MPI values above 2 indicate that most of the miners are selling Bitcoin. MPI values under 0 indicate that there is less selling pressure by miners.',
            'result_fields' => ['date', 'mpi'],
        ],
        'exchange-flows/exchange-shutdown-index' => [
            'name' => 'Exchange Shutdown Index',
            'description' => 'Stay Ahead of Exchange Hacks. See hacks as they happen by identifying sudden increases and become zero in exchange outflows and hedge against potential risk.',
            'result_fields' => ['date', 'is_shutdown'],
        ],
        'exchange-flows/fund-flow-ratio' => [
            'name' => 'Fund Flow Ratio',
            'description' => 'Fund Flow Ratio provides the amount of bitcoins that exchanges occupy among the bitcoins sent underlying the Bitcoin network. Knowing the amount of fund currently involved in trading can help you understand market volatility.',
            'result_fields' => ['date', 'fund_flow_ratio'],
        ],
        'flow-indicator/stablecoins-ratio' => [
            'name' => 'Stablecoins Ratio',
            'description' => 'BTC reserve divided by all stablecoins reserve held by an exchange. This usually indicates potential sell pressure. Supported exchanges are determined by the concurrent validity of both BTC and Stablecoins (for at least 1 token).',
            'result_fields' => ['date', 'stablecoins_ratio', 'stablecoins_ratio_usd'],
        ],
    ];

    public function __construct()
    {
        parent::__construct();
        self::$dataSourceId = config('data.data_source.cryptoquant_id');
        if (! $url = config('btc.apis.cryptoquant.url')) {
            throw new \RuntimeException('could not load config: data.data_source.cryptoquant_id');
        }
        self::$url = $url . '/' . $this->version . '/btc/';
    }

    public function getAllEndpoints(): void
    {
        $args = [
            'exchange' => 'all_exchange',
            'from' => '20250401T100000',
            'to' => '20250405T100000',
            'limit' => '10',
            'window' => 'day',
        ];
        foreach (self::ENDPOINTS as $path => $endpoint) {
            echo $endpoint['name'] . '</br>';
            $response = $this->request('GET', $path, $args);
            dump($response, $endpoint['result_fields']);
        }
    }
}
