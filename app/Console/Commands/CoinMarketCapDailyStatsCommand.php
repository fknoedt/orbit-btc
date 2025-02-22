<?php

namespace App\Console\Commands;

use App\Adapters\CoinMarketCapApiAdapter;
use App\Exceptions\DailyPriceStatsException;
use App\Services\DailyStatsService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CoinMarketCapDailyStatsCommand extends Command
{
    protected const string CMC_API_VERSION = 'v3';

    protected const int SYNC_UP_TO_X_PAST_DAYS = 60;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'btc:coin-market-cap-daily-stats-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     * @throws DailyPriceStatsException
     */
    public function handle()
    {
        $client = new CoinMarketCapApiAdapter(self::CMC_API_VERSION);

        $this->info(
            'Fetching Fear and Greed data from CoinMarketCap (last ' . self::SYNC_UP_TO_X_PAST_DAYS . ' days)'
        );

        // fetch info for the last X days and try to update each of them
        $fearAndGreedData = $client->getMarketSentiment(1, self::SYNC_UP_TO_X_PAST_DAYS);

        $service = new DailyStatsService();
        $parsedData = [];
        foreach ($fearAndGreedData['data'] as $day) {
            $datetime = Carbon::createFromTimestamp($day['timestamp'])->format('Y-m-d');
            $parsedData[$datetime]['fear_and_greed'] = $day['value']; // has to match column name
        }
        ksort($parsedData);
        $daysFilled = $service->fillStats($parsedData);

        $this->info("{$daysFilled} daily_prices filled with fear_and_greed info");
    }
}
