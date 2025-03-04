<?php

namespace App\Http\Controllers;

use App\Clients\BaseClient;
use App\Clients\SerpApiClient;
use App\Services\DailyStatsService;
use Carbon\Carbon;

class SandboxController
{
    public function index(): void
    {
        if (config('app.env') !== 'local') {
            throw new \BadMethodCallException('Sandbox needs to be run on the local environment');
        }

        // playground -- you probably don't need to commit anything in this file

        $googleTrendsData = json_decode(
            file_get_contents(
                database_path() . DIRECTORY_SEPARATOR . 'raw-data' . DIRECTORY_SEPARATOR .
                'google-trends-btc-data-2020-25.json'
            ),
            true
        );

        /**
         * Blocked: Google Trends only returns relative (to the search result) values and not absolute ones
         * This means that 100 will be the highest day in the series while all other values will be relative to it
         * It's necessary to anchor one day based on another Google API and calculate all absolute values but this will
         * require a more complex daily data feeding as it will always be necessary to get to the absolute value
         * @see https://x.com/i/grok/share/F3NCretFY2WR8HOZaWugGPuog
         */
        $service = new DailyStatsService();
        $parsedData = [];
        foreach ($googleTrendsData['data'] as $key => $day) {
            $date = Carbon::createFromTimestamp($day['time'])->format(BaseClient::$systemDateFormat);
            // $parsedData[$date]['google_trends'] = $value;
        }
        exit;
        $daysFilled = $service->fillStats($parsedData, true);

    }
}
