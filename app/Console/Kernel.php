<?php

namespace App\Console;

use App\Clients\CurlCryptoQuantClient;
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
            ->everyMinute()->when($this->shouldUpdatePrices($initialDate))
            ->appendOutputTo($logPath)
            ->emailOutputOnFailure($emailErrorsTo);

        $schedule->command('btc:coin-market-cap-daily-stats-command')
            ->everyMinute()->when($this->shouldUpdateCmcStats())
            ->appendOutputTo($logPath)
            ->emailOutputOnFailure($emailErrorsTo);

        $schedule->command('btc:mempool-daily-stats')
            ->everyMinute()->when($this->shouldUpdateHashrate())
            ->appendOutputTo($logPath)
            ->emailOutputOnFailure($emailErrorsTo);

        $schedule->command('btc:update-future-price-change')
            ->everyMinute()->when($this->shouldUpdateFuturePriceChange())
            ->appendOutputTo($logPath)
            ->emailOutputOnFailure($emailErrorsTo);

        $schedule->command('btc:update-mayer-multiple')
            ->everyMinute()->when($this->shouldUpdateMayerMultiple())
            ->appendOutputTo($logPath)
            ->emailOutputOnFailure($emailErrorsTo);

        $cqMetrics = array_keys(CurlCryptoQuantClient::METRICS_TO_ENDPOINT);

        $schedule->command(
            'btc:crypto-quant-daily-stats ' . implode(',', $cqMetrics) . ' --ignore-errors'
        )
            ->everyMinute()->when($this->shouldUpdateCryptoQuantStats($cqMetrics))
            ->appendOutputTo($logPath)
            ->emailOutputOnFailure($emailErrorsTo)
            ->onFailure(function (\Throwable $e) {
                \Log::error('Task:daily failed but ignored: ' . $e->getMessage());
            });

        $schedule->command(
            'btc:cryptocompare-daily-stats --ignore-errors'
        )
            ->everyMinute()->when($this->shouldUpdateCryptoCompareStats([
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

        $schedule->command('btc:update-all-user-signal-scores')
            ->everyMinute()->when($this->shouldUpdateUserSignals())
            ->appendOutputTo($logPath)
            ->emailOutputOnFailure($emailErrorsTo);
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

        return false; // Skip execution
    }

    private function shouldUpdateCryptoQuantStats(array $columnsToUpdate): bool
    {
        $pricesMissingStats = DailyPrice::where('date', '>', self::STATS_START_DATE)
            ->where('date', '<', Carbon::now()->format('Y-m-d'))
            ->where(function ($q) use ($columnsToUpdate) {
                foreach ($columnsToUpdate as $column) {
                    $q->orWhereNull($column);
                }
            })->count();
        if ($pricesMissingStats) {
            Log::info('Running CQ stats update');
            return true;
        }

        return false;
    }

    private function shouldUpdateCryptoCompareStats(array $columnsToUpdate): bool
    {
        $since = Carbon::now()->subDays(CryptoCompareDailyStatsCommand::FETCH_DAYS_AGO)->format('Y-m-d');
        $pricesMissingStats = DailyPrice::where('date', '>', $since)
            ->where('date', '<', Carbon::now()->format('Y-m-d'))
            ->where(function ($q) use ($columnsToUpdate) {
                foreach ($columnsToUpdate as $column) {
                    $q->orWhereNull($column);
                }
            })->count();
        if ($pricesMissingStats) {
            Log::info('Running CC stats update');
            return true;
        }

        return false;
    }

    private function shouldUpdateCmcStats(): bool
    {
        if (DailyPrice::where('date', '>', self::STATS_START_DATE)->whereNull('fear_and_greed')->count()) {
            Log::info('Running CMC stats update');
            return true;
        }

        return false;
    }

    private function shouldUpdateHashrate(): bool
    {
        if (DailyPrice::where('date', '>', self::STATS_START_DATE)->whereNull('average_hashrate')->count()) {
            Log::info('Running Mempool stats update');
            return true;
        }

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

        return false;
    }

    private function shouldUpdateMayerMultiple(): bool
    {
        if (DailyPrice::getLastEmptyMayerMultipleDay()) {
            Log::info('Running Mayer Multiple stats update');
            return true;
        }

        return false;
    }

    private function shouldUpdateFuturePriceChange(): bool
    {
        if (DailyPrice::getLastEmptyFuturePriceDay()) {
            Log::info('Running Future Price Change update');
            return true;
        }

        return false;
    }
}
