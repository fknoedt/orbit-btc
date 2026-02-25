<?php

namespace App\Clients;

use App\Exceptions\AdapterException;
use App\Exceptions\ExternalApiException;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class UsTreasuryClient extends BaseClient
{
    /**
     * Treasury metric => daily_prices column name
     */
    public const array METRIC_TO_COLUMN_NAME = [
        'transaction_today_amt' => 'us_tbill_net_issuance', // Updated for net issuance
    ];

    public const int DEFAULT_MONTHS_AGO = 24;

    public function __construct()
    {
        parent::__construct();
        self::$dataSourceId = config('data.data_source.us_treasury_id');
        if (! $url = config('btc.apis.us_treasury.url')) {
            throw new \RuntimeException('could not load config: btc.apis.us_treasury.url');
        }
        self::$url = $url . '/services/api/fiscal_service/';
    }

    /**
     * Get US Treasury T-Bill daily transactions from DTS (Daily Treasury Statement)
     * Net issuance is computed as issues - redemptions in the command
     * @see https://fiscaldata.treasury.gov/datasets/daily-treasury-statement/public-debt-transactions
     * @throws AdapterException
     * @throws ExternalApiException
     * @throws ConnectionException
     * @throws RequestException
     */
    public function getTBillDailyTransactions(string $from = null, string $to = null): array
    {
        $from = $from ?? Carbon::now()->subMonths(self::DEFAULT_MONTHS_AGO)->format('Y-m-d');
        $to = $to ?? Carbon::yesterday()->format('Y-m-d'); // Adjust for lag

        $args = [
            'fields' => 'record_date,transaction_type,transaction_today_amt',
            'filter' => "security_market:eq:Marketable,security_type:eq:Bills,record_date:gte:{$from},record_date:lte:{$to}",
            'sort' => 'record_date',
            'page[size]' => 10000,
        ];

        $response = $this->request(
            'get',
            'v1/accounting/dts/public_debt_transactions',
            $args,
        );

        // Handle pagination if needed (loop until no more pages)
        $allData = $response['data'] ?? [];
        $meta = $response['meta'] ?? [];
        $links = $response['links'] ?? [];
        $currentPage = 1;

        while (isset($links['next']) && $links['next']) {
            $currentPage++;
            $args['page[number]'] = $currentPage;
            $nextResponse = $this->request(
                'get',
                'v1/accounting/dts/public_debt_transactions',
                $args,
            );
            $allData = array_merge($allData, $nextResponse['data'] ?? []);
            $links = $nextResponse['links'] ?? [];
        }

        return ['data' => $allData, 'meta' => $meta];
    }
}
