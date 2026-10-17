<?php

namespace App\Console;

use App\Clients\BgeometricsClient;
use App\Clients\CoinMetricsClient;
use App\Console\Commands\BgeometricsDailyStatsCommand;
use App\Console\Commands\CoinMarketCapDailyStatsCommand;
use App\Console\Commands\CoinmetricsDailyStatsCommand;
use App\Console\Commands\CryptoCompareDailyStatsCommand;
use App\Console\Commands\UsTreasuryCommand;
use App\Models\DailyPrice;
use App\Models\LevelingMove;
use App\Models\Metric;
use App\Models\UserSignalDailyScore;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class Kernel extends ConsoleKernel
{
    /** how many days in the past to look for missing daily_prices entries */
    private const int PRICE_SYNC_LAST_X_DAYS = 33;

    /** from when NULL CryptoQuant stats should be updated */
    private const string STATS_START_DATE = '2023-01-01';

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $logPath = storage_path() . '/logs/';
        $emailErrorsTo = config('btc.system_admin_email');

        // get prices (necessary for the next commands) -- EST TZ because prices on CMC are not updated on UTC time
        $initialDate = Carbon::now('America/New_York')->subDays(self::PRICE_SYNC_LAST_X_DAYS)->format('Y-m-d');

        $schedule->command('btc:populate-price-history --since=' . $initialDate)
            ->everyThirtyMinutes()->when($this->shouldUpdatePrices($initialDate))
            ->sendOutputTo($logPath . 'populate-price-history.log')
            ->emailOutputOnFailure($emailErrorsTo);

        $schedule->command('btc:coin-market-cap-daily-stats-command')
            ->everyThirtyMinutes()->when($this->shouldUpdateCmcStats())
            ->sendOutputTo($logPath . 'coin-market-cap-daily-stats-command.log')
            ->emailOutputOnFailure($emailErrorsTo);

        $schedule->command('btc:mempool-daily-stats')
            ->everyThirtyMinutes()->when($this->shouldUpdateHashrate())
            ->sendOutputTo($logPath . 'mempool-daily-stats.log')
            ->emailOutputOnFailure($emailErrorsTo);

        /* FMP discontinued their free endpoints
        $schedule->command('btc:fmp-daily-stats')
            ->everyThirtyMinutes()->when($this->shouldUpdateStats(array_keys(FmpClient::METRICS)))
            ->sendOutputTo($logPath)
            ->emailOutputOnFailure($emailErrorsTo);*/

        $schedule->command('btc:coinmetrics-daily-stats --force')
            ->everyThirtyMinutes()->when(fn () => $this->shouldUpdateCoinMetrics())
            ->sendOutputTo($logPath . 'coinmetrics-daily-stats.log')
            ->emailOutputOnFailure($emailErrorsTo);

        $schedule->command('btc:update-future-price-change')
            ->everyThirtyMinutes()->when($this->shouldUpdateFuturePriceChange())
            ->sendOutputTo($logPath . 'update-future-price-change.log')
            ->emailOutputOnFailure($emailErrorsTo);

        $schedule->command('btc:update-mayer-multiple')
            ->everyThirtyMinutes()->when($this->shouldUpdateMayerMultiple())
            ->sendOutputTo($logPath . 'update-mayer-multiple.log')
            ->emailOutputOnFailure($emailErrorsTo);

        $schedule->command('btc:update-rsi')
            ->everyThirtyMinutes()->when($this->shouldUpdateRsi())
            ->sendOutputTo($logPath . 'update-rsi.log')
            ->emailOutputOnFailure($emailErrorsTo);

        $schedule->command('btc:update-bollinger-bands')
            ->everyThirtyMinutes()->when($this->shouldUpdateBollingerBands())
            ->sendOutputTo($logPath . 'update-bollinger-bands.log')
            ->emailOutputOnFailure($emailErrorsTo);

        $schedule->command('btc:update-roi')
            ->everyThirtyMinutes()->when($this->shouldUpdateRoi())
            ->sendOutputTo($logPath . 'update-roi.log')
            ->emailOutputOnFailure($emailErrorsTo);

        // The Treasury file updates once a day. Retry hourly only while the tip is behind.
        $schedule->command('btc:update-us-treasury-stats')
            ->hourly()->when($this->shouldUpdateTbillOutstanding())
            ->sendOutputTo($logPath . 'update-tbill-outstanding.log')
            ->emailOutputOnFailure($emailErrorsTo);

        // CQ needs a high tier subscription
        /*
        $cqMetrics = array_keys(CurlCryptoQuantClient::METRICS_TO_ENDPOINT);
        $schedule->command(
            'btc:crypto-quant-daily-stats ' . implode(',', $cqMetrics) . ' --ignore-errors --from-file'
        )
            ->everyThirtyMinutes()->when($this->shouldUpdateStats($cqMetrics))
            ->sendOutputTo($logPath . 'crypto-quant-daily-stats.log')
            ->emailOutputOnFailure($emailErrorsTo);*/

        /* CryptoCompare's free key is past the 100 calls/month cap, and the
           columns it fills are deactivated. Those columns stay empty, so the
           every-30-minute job never rested.
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
            ->sendOutputTo($logPath . 'cryptocompare-daily-stats.log')
            ->emailOutputOnFailure($emailErrorsTo);*/

        // Four requests per hour. Rotate which stale endpoints run, or the
        // first chunk stays due forever and the later metrics never update.
        $bgDue = $this->bgeometricsEndpointsDue();
        $bgChunks = array_chunk($bgDue, BgeometricsClient::MAX_REQUESTS_PER_RUN, true);
        if ($bgChunks !== []) {
            $bgChunk = $bgChunks[now()->hour % count($bgChunks)];
            $schedule->command(
                'btc:bgeometrics-daily-stats',
                [implode(',', array_keys($bgChunk))]
            )
                ->hourly()
                ->sendOutputTo($logPath . 'bgeometrics-daily-stats.log')
                ->emailOutputOnFailure($emailErrorsTo);
        }

        // Before scores, so a metric with a new first day is usable the same morning.
        // 06:30 UTC is before the 07:15 monitoring report.
        $schedule->command('btc:update-data-limited-at')
            ->everyThirtyMinutes()->when(fn () => $this->shouldUpdateDataLimitedAt())
            ->sendOutputTo($logPath . 'update-data-limited-at.log')
            ->emailOutputOnFailure($emailErrorsTo);

        $schedule->command('btc:update-leveling-moves')
            ->everyThirtyMinutes()->when($this->shouldUpdateLevelingMoves())
            ->sendOutputTo($logPath . 'update-leveling-moves.log')
            ->emailOutputOnFailure($emailErrorsTo);

        $schedule->command('btc:update-all-user-signal-scores')
            ->everyThirtyMinutes()->when($this->shouldUpdateUserSignals())
            ->sendOutputTo($logPath . 'update-all-user-signal-scores.log')
            ->emailOutputOnFailure($emailErrorsTo);

        // 07:15 UTC is before 10:00 America/New_York, so the report still judges yesterday.
        $schedule->command('btc:daily-prices-monitoring --send-email')
            ->dailyAt('7:15')
            ->sendOutputTo($logPath . 'daily-prices-monitoring.log')
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

    /**
     * True when a daily feed's latest value is inside the window it fetches
     * and older than yesterday minus that metric's max_delayed_days.
     * Holes behind a tip that is still inside the allowed lag do not count.
     * A series that ended before the window is left for a manual backfill.
     */
    private function dailyColumnsAreBehind(array $columns, string $since): bool
    {
        $delays = Metric::withTrashed()
            ->whereIn('column_name', $columns)
            ->pluck('max_delayed_days', 'column_name');

        foreach ($columns as $column) {
            $latest = DailyPrice::whereNotNull($column)->max('date');
            if ($latest === null) {
                return true;
            }

            $latest = substr((string) $latest, 0, 10);
            $allowedThrough = Carbon::yesterday()
                ->subDays((int) ($delays[$column] ?? 0))
                ->toDateString();

            if ($latest >= $since && $latest < $allowedThrough) {
                return true;
            }
        }

        return false;
    }

    private function shouldUpdateCoinMetrics(): bool
    {
        $since = Carbon::now()->subDays(CoinmetricsDailyStatsCommand::SINCE_DAYS_AGO)->toDateString();
        if ($this->dailyColumnsAreBehind(array_values(CoinMetricsClient::METRIC_TO_COLUMN_NAME), $since)) {
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

    /** @return array<string, string> endpoint => daily_prices column */
    private function bgeometricsEndpointsDue(): array
    {
        $since = Carbon::now()->subDays(BgeometricsDailyStatsCommand::SINCE_DAYS_AGO)->toDateString();
        $endpoints = BgeometricsClient::getEndpointsAsColumnNames();
        $deactivated = Metric::onlyTrashed()
            ->whereIn('column_name', array_values($endpoints))
            ->pluck('column_name')
            ->all();

        $due = [];
        foreach ($endpoints as $endpoint => $column) {
            if (in_array($column, $deactivated, true)) {
                continue;
            }
            if ($this->dailyColumnsAreBehind([$column], $since)) {
                $due[$endpoint] = $column;
            }
        }

        if ($due !== []) {
            Log::info('Running BG stats update');
        } else {
            Log::info('Not necessary to run BG stats update');
        }

        return $due;
    }

    private function shouldUpdateCmcStats(): bool
    {
        $since = Carbon::now()->subDays(CoinMarketCapDailyStatsCommand::SYNC_UP_TO_X_PAST_DAYS)->toDateString();
        if ($this->dailyColumnsAreBehind(['fear_and_greed'], $since)) {
            Log::info('Running CMC stats update');

            return true;
        }

        Log::info('Not necessary to run CMC stats update');

        return false;
    }

    private function shouldUpdateHashrate(): bool
    {
        // MempoolClient::HASHRATE_TIME_PERIOD is 1 month.
        $since = Carbon::now()->subMonth()->toDateString();
        if ($this->dailyColumnsAreBehind(['average_hashrate'], $since)) {
            Log::info('Running Mempool stats update');

            return true;
        }

        Log::info('Not necessary to run Mempool stats update');

        return false;
    }

    /** True when a newer daily price exists than the percents already stored. */
    private function shouldUpdateLevelingMoves(): bool
    {
        if (! Schema::hasTable('leveling_moves')) {
            return false;
        }

        $lastDailyPrice = DailyPrice::max('date');
        if (! $lastDailyPrice) {
            Log::info('Not necessary to run leveling move update');

            return false;
        }

        $lastCovered = LevelingMove::max('price_date');
        if (! $lastCovered || $lastCovered < $lastDailyPrice) {
            Log::info('Running leveling move update');

            return true;
        }

        Log::info('Not necessary to run leveling move update');

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

    private function shouldUpdateTbillOutstanding(): bool
    {
        $since = Carbon::now()->subMonths(UsTreasuryCommand::SINCE_MONTHS_AGO)->toDateString();
        if ($this->dailyColumnsAreBehind(
            ['us_tbill_net_issuance', 'us_tbill_normalized_qe'],
            $since
        )) {
            Log::info('Running US T-bill Outstanding stats update');

            return true;
        }

        Log::info('Not necessary to run US T-bill Outstanding stats update');

        return false;
    }

    private function shouldUpdateDataLimitedAt(): bool
    {
        if (now()->format('H:i') === '06:30' || Metric::withTrashed()->whereNull('data_limited_at')->exists()) {
            Log::info('Running data_limited_at update');

            return true;
        }

        Log::info('Not necessary to run data_limited_at update');

        return false;
    }

    private function shouldUpdateRoi(): bool
    {
        if (DailyPrice::getLastEmptyRoiDay()) {
            Log::info('Running ROI stats update');
            return true;
        }

        Log::info('Not necessary to run ROI stats update');

        return false;
    }

    private function shouldUpdateBollingerBands(): bool
    {
        if (DailyPrice::getLastEmptyBollingerBandsDay()) {
            Log::info('Running Bollinger Bands stats update');
            return true;
        }

        Log::info('Not necessary to run Bollinger Bands stats update');

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
