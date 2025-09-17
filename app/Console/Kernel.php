<?php

namespace App\Console;

use App\Clients\BgeometricsClient;
use App\Clients\CurlCryptoQuantClient;
use App\Clients\FmpClient;
use App\Console\Commands\BgeometricsDailyStatsCommand;
use App\Console\Commands\CryptoCompareDailyStatsCommand;
use App\Models\DailyPrice;
use App\Models\UserSignalDailyScore;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /** how many days in the past to look for missing daily_prices entries */
    private const int PRICE_SYNC_LAST_X_DAYS = 30;

    /** since when NULL CruptoQuant stats should be updated */
    private const string STATS_START_DATE = '2023-01-01';

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $logPath = storage_path() . '/logs/schedule.log';
        $emailErrorsTo = config('btc.system_admin_email');

        // get prices (necessary for the next commands) -- EST TZ because prices on CMC are not updated on UTC time
        $initialDate = Carbon::now('America/New_York')->subDays(self::PRICE_SYNC_LAST_X_DAYS)->format('Y-m-d');

        $schedule->command('btc:populate-price-history --since=' . $initialDate)
            ->everyThirtyMinutes()->when($this->shouldUpdatePrices($initialDate))
            ->appendOutputTo($logPath)
            ->emailOutputOnFailure($emailErrorsTo);

        $schedule->command('btc:coin-market-cap-daily-stats-command')
            ->everyThirtyMinutes()->when($this->shouldUpdateCmcStats())
            ->appendOutputTo($logPath)
            ->emailOutputOnFailure($emailErrorsTo);

        $schedule->command('btc:mempool-daily-stats')
            ->everyThirtyMinutes()->when($this->shouldUpdateHashrate())
            ->appendOutputTo($logPath)
            ->emailOutputOnFailure($emailErrorsTo);

        $schedule->command('btc:fmp-daily-stats')
            ->everyThirtyMinutes()->when($this->shouldUpdateStats(array_keys(FmpClient::METRICS)))
            ->appendOutputTo($logPath)
            ->emailOutputOnFailure($emailErrorsTo);

        $schedule->command('btc:update-future-price-change')
            ->everyThirtyMinutes()->when($this->shouldUpdateFuturePriceChange())
            ->appendOutputTo($logPath)
            ->emailOutputOnFailure($emailErrorsTo);

        $schedule->command('btc:update-mayer-multiple')
            ->everyThirtyMinutes()->when($this->shouldUpdateMayerMultiple())
            ->appendOutputTo($logPath)
            ->emailOutputOnFailure($emailErrorsTo);

        $schedule->command('btc:update-rsi')
            ->everyThirtyMinutes()->when($this->shouldUpdateRsi())
            ->appendOutputTo($logPath)
            ->emailOutputOnFailure($emailErrorsTo);

        // CQ needs a high tier subscription
        /*
        $cqMetrics = array_keys(CurlCryptoQuantClient::METRICS_TO_ENDPOINT);
        $schedule->command(
            'btc:crypto-quant-daily-stats ' . implode(',', $cqMetrics) . ' --ignore-errors --from-file'
        )
            ->everyThirtyMinutes()->when($this->shouldUpdateStats($cqMetrics))
            ->appendOutputTo($logPath)
            ->emailOutputOnFailure($emailErrorsTo)
            ->onFailure(function (\Throwable $e) {
                \Log::error('Task:daily failed but ignored: ' . $e->getMessage());
            });*/

        $schedule->command(
            'btc:cryptocompare-daily-stats --ignore-errors'
        )
            ->everyThirtyMinutes()->when($this->shouldUpdateCryptoCompareStats([
                'transaction_count',
                'large_transaction_count',
                'average_transaction_value',
                'new_addresses',
                'block_size',
                'exchanges_volume'
            ]))
            ->appendOutputTo($logPath)
            ->emailOutputOnFailure($emailErrorsTo)
            ->onFailure(function (\Throwable $e) {
                \Log::error('Task:daily failed but ignored: ' . $e->getMessage());
            });

        $bgEndpoints = BgeometricsClient::getEndpointsAsColumnNames();

        // bgeometrics limits the free plan at 4 requests/hour
        foreach (array_chunk($bgEndpoints, BgeometricsClient::MAX_REQUESTS_PER_HOUR, true) as $endpoints) {
            if ($this->shouldUpdateBgeometricsStats(array_values($endpoints))) {
                $schedule->command(
                    'btc:bgeometrics-daily-stats',
                    [implode(',', array_keys($endpoints))]
                )
                    ->hourly()
                    ->appendOutputTo($logPath)
                    ->emailOutputOnFailure($emailErrorsTo)
                    ->onFailure(function (\Throwable $e) {
                        \Log::error('Task:daily failed but ignored: ' . $e->getMessage());
                    });
            }
        }

        $schedule->command('btc:update-all-user-signal-scores')
            ->everyThirtyMinutes()->when($this->shouldUpdateUserSignals())
            ->appendOutputTo($logPath)
            ->emailOutputOnFailure($emailErrorsTo);

        if (app()->environment('local')) {
            $schedule->command('backup:run')->daily()->at('03:00');
        }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    private function shouldUpdatePrices(string $initialDate): bool
    {
        if (DailyPrice::where('date', '>', $initialDate)->count() < self::PRICE_SYNC_LAST_X_DAYS) {
            Log::info('Running prices update');
            return true;
        }

        Log::info('Not necessary to run prices update');

        return false; // Skip execution
    }

    private function shouldUpdateStats(array $columnsToUpdate): bool
    {
        $pricesMissingStats = DailyPrice::where('date', '>', self::STATS_START_DATE)
            ->where('date', '<=', Carbon::now()->format('Y-m-d'))
            ->where(function ($q) use ($columnsToUpdate) {
                foreach ($columnsToUpdate as $column) {
                    $q->orWhereNull($column);
                }
            })->count();
        if ($pricesMissingStats) {
            Log::info('Running stats update');
            return true;
        }

        Log::info('Not necessary to run stats update');

        return false;
    }

    private function shouldUpdateCryptoCompareStats(array $columnsToUpdate): bool
    {
        $since = Carbon::now()->subDays(CryptoCompareDailyStatsCommand::FETCH_DAYS_AGO)->format('Y-m-d');
        $pricesMissingStats = DailyPrice::where('date', '>', $since)
            ->where('date', '<=', Carbon::now()->format('Y-m-d'))
            ->where(function ($q) use ($columnsToUpdate) {
                foreach ($columnsToUpdate as $column) {
                    $q->orWhereNull($column);
                }
            })->count();
        if ($pricesMissingStats) {
            Log::info('Running CC stats update');
            return true;
        }

        Log::info('Not necessary to run CC stats update');

        return false;
    }

    private function shouldUpdateBgeometricsStats(array $columnsToUpdate): bool
    {
        $since = Carbon::now()->subDays(BgeometricsDailyStatsCommand::SINCE_DAYS_AGO)->format('Y-m-d');
        $pricesMissingStats = DailyPrice::where('date', '>', $since)
            ->where('date', '<=', Carbon::now()->format('Y-m-d'))
            ->where(function ($q) use ($columnsToUpdate) {
                foreach ($columnsToUpdate as $column) {
                    $q->orWhereNull($column);
                }
            })->count();
        if ($pricesMissingStats) {
            Log::info('Running BG stats update');
            return true;
        }

        Log::info('Not necessary to run BG stats update');

        return false;
    }

    private function shouldUpdateCmcStats(): bool
    {
        if (DailyPrice::where('date', '>', self::STATS_START_DATE)->whereNull('fear_and_greed')->count()) {
            Log::info('Running CMC stats update');
            return true;
        }

        Log::info('Not necessary to run CMC stats update');

        return false;
    }

    private function shouldUpdateHashrate(): bool
    {
        if (DailyPrice::where('date', '>', self::STATS_START_DATE)->whereNull('average_hashrate')->count()) {
            Log::info('Running Mempool stats update');
            return true;
        }

        Log::info('Not necessary to run Mempool stats update');

        return false;
    }

    private function shouldUpdateUserSignals(): bool
    {
        $lastDailyPrice = DailyPrice::max('date');
        $lastUserSignalScore = UserSignalDailyScore::max('date');

        if ($lastDailyPrice > $lastUserSignalScore) {
            Log::info('Running UserSignals update all scores');
            return true;
        }

        Log::info('Not necessary to run UserSignals update all scores');

        return false;
    }

    private function shouldUpdateMayerMultiple(): bool
    {
        if (DailyPrice::getLastEmptyMayerMultipleDay()) {
            Log::info('Running Mayer Multiple stats update');
            return true;
        }

        Log::info('Not necessary to run Mayer Multiple stats update');

        return false;
    }

    private function shouldUpdateRsi(): bool
    {
        if (DailyPrice::getLastEmptyRsiDay()) {
            Log::info('Running RSI stats update');
            return true;
        }

        Log::info('Not necessary to run RSI stats update');

        return false;
    }

    private function shouldUpdateFuturePriceChange(): bool
    {
        if (DailyPrice::getLastEmptyFuturePriceDay()) {
            Log::info('Running Future Price Change update');
            return true;
        }

        Log::info('Not necessary to run Future Price Change update');

        return false;
    }
}
