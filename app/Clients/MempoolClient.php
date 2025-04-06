<?php

namespace App\Clients;


use App\Exceptions\AdapterException;
use App\Exceptions\DailyPriceStatsException;
use App\Exceptions\ExternalApiException;
use App\Services\DailyStatsService;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class MempoolClient extends BaseClient
{
    public const string HASHRATE_TIME_PERIOD = '1m';
    private string $version = 'v1';

    public function __construct()
    {
        parent::__construct();
        self::$dataSourceId = config('data.data_source.mempool_space_id');
        if (! $url = config('btc.apis.mempool_space.url')) {
            throw new \RuntimeException('could not load config: btc.apis.mempool_space.url');
        }
        self::$url = $url . '/api/'. $this->version . '/';
    }

    public function getHashrateTimePeriodDate(string $timePeriod = self::HASHRATE_TIME_PERIOD): string
    {
        // Extract the number and unit (m or y) using regex
        if (!preg_match('/^(\d+)([my])$/', $timePeriod, $matches)) {
            throw new \InvalidArgumentException("Invalid duration format. Use e.g., '1m', '3y'.");
        }

        $amount = (int) $matches[1]; // e.g., 1, 3, 6
        $unit = $matches[2];         // 'm' or 'y'

        // Start from now
        $date = Carbon::now();

        // Subtract based on unit
        if ($unit === 'm') {
            $date->subMonths($amount);
        } elseif ($unit === 'y') {
            $date->subYears($amount);
        }

        // Return formatted date
        return $date->format('Y-m-d');
    }

    /**
     * Get historical Hashrate and Difficulty
     * @see https://mempool.space/docs/api/rest#get-hashrate
     * @throws AdapterException
     * @throws ExternalApiException
     * @throws ConnectionException
     * @throws RequestException
     */
    public function getHistoricalHashrate(string $since = self::HASHRATE_TIME_PERIOD): array
    {
        return $this->request(
            'get',
            'mining/hashrate/' . $since,
        );
    }

    /**
     * @throws ExternalApiException
     * @throws AdapterException
     * @throws ConnectionException
     * @throws RequestException
     */
    public function getParsedHistoricalHashrate(string $since = self::HASHRATE_TIME_PERIOD): array
    {
        return $this->parseHistoricalHashrateResponse(
            $this->getHistoricalHashrate($since)
        );
    }

    /**
     * @throws DailyPriceStatsException
     */
    public function loadInitialDailyPricesData(): int
    {
        $service = new DailyStatsService();

        $hashrateAndDifficultyData = json_decode(
            file_get_contents(
                database_path() . DIRECTORY_SEPARATOR . 'raw-data' . DIRECTORY_SEPARATOR .
                'mempool-hashrate-historical.json'
            ),
            true
        );

        $parsedData = $this->parseHistoricalHashrateResponse($hashrateAndDifficultyData);

        return $service->fillStats($parsedData, true);
    }

    public function parseHistoricalHashrateResponse(array $data): array
    {
        $parsedData = [];
        // both arrays are returned together but there's a difficulty entry every 2 weeks (by protocol)
        foreach ($data['hashrates'] as $row) {
            $day = Carbon::createFromTimestamp($row['timestamp'])
                ->format('Y-m-d');
            if (! isset($parsedData[$day])) {
                $parsedData[$day] = [];
            }
            $parsedData[$day]['average_hashrate'] = $row['avgHashrate'];
        }
        foreach ($data['difficulty'] as $row) {
            $day = Carbon::createFromTimestamp($row['time'])
                ->format('Y-m-d');
            if (! isset($parsedData[$day])) {
                $parsedData[$day] = [];
            }
            $parsedData[$day]['difficulty'] = $row['difficulty'];
        }

        ksort($parsedData);

        $firstKey = array_key_first($parsedData);

        // we need it set in the first row -- see DailyStatsService->fillStats()
        if (! isset($parsedData[$firstKey]['difficulty'])) {
            $parsedData[$day]['difficulty'] = null;
        }

        return $parsedData;
    }
}
