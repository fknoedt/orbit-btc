<?php

namespace App\Services;

use App\Exceptions\UserSignalException;
use App\Exceptions\UserSignalFunctionalException;
use App\Models\Frequency;
use App\Models\UserSignal;
use App\Models\UserSignalDailyScore;
use App\Models\UserSignalMetric;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UserSignalService
{
    /** How far back a user_signal will span */
    public const int MAX_DAYS_BACK = 1096;

    /** used to calculate max threshold */
    public const int MAX_OSCILLATION_PER_METRIC = 20;

    /** to calculate each day's signal_value, we need to simulate a trade weighted against the threshold */
    public const int TRADE_SIZE_IN_USD = 1000;

    protected int $totalDailyScoresCreated = 0;
    protected int $totalSimulatedTrades = 0;
    protected int $totalErrors = 0;

    /** [file:line => ['message' => $message, 'count' => $count]] */
    protected array $errors = [];

    public function getTotalDailyScoresCreated(): int
    {
        return $this->totalDailyScoresCreated;
    }

    public function getTotalSimulatedTrades(): int
    {
        return $this->totalSimulatedTrades;
    }

    public function getTotalErrors(): int
    {
        return $this->totalErrors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getStats(): array
    {
        return [
            'totalDailyScoresCreated' => $this->totalDailyScoresCreated,
            'totalSimulatedTrades' => $this->totalSimulatedTrades,
            'totalErrors' => $this->totalErrors,
            'errors' => $this->errors,
        ];
    }

    public function getMaxThreshold(int $userSignalId): int
    {
        $max = 0;

        $metrics = UserSignalMetric::where('user_signal_id', $userSignalId)->get();

        if (empty($metrics)) {
            return $max;
        }

        foreach ($metrics as $metric) {
            $max += $metric->weight * self::MAX_OSCILLATION_PER_METRIC;
        }

        return $max;
    }

    public function updateDailyScores(
        int               $userSignalId = null,
        int               $userId = null,
        Carbon            $since = null,
        MetricService     $metricService = null,
        DailyPriceService $priceService = null,
    ): array
    {
        if (! $metricService) {
            $metricService = new MetricService();
        }

        if (! $priceService) {
            $priceService = new DailyPriceService();
        }

        if (! $since) {
            $since = Carbon::now()->subDays(self::MAX_DAYS_BACK);
        }
        // always add maximum frequency (monthly) more days to allow oscillation calculation on the fly
        $since->subDays(Frequency::MAX_FREQUENCY_IN_DAYS);

        // pre-load all daily_prices to avoid repeated DB/cache access
        $priceService->getAllDailyPricesKeyByDate($since, Carbon::now(), true);

        // don't eager load userSignalMetrics.metric to use a pre-loaded hashmap
        $query = UserSignal::with(['userSignalMetrics', 'userSignalMetrics.frequency'])
            ->where('is_paused', false)
            ->whereHas('userSignalMetrics');

        if ($userSignalId) {
            $query->where('id', $userSignalId);
        } else {
            if ($userId) {
                $query->where('user_id', $userId);
            }
        }

        // calculate every Metric of every UserMetric and upsert all related tables
        foreach ($query->get() as $userSignal) {
            try {
                // if one UserSignal fail processing, report and save errors to this object and try every other UserSignal
                DB::transaction(
                    function () use ($userSignal, $since, $metricService, $priceService) {
                        $now = Carbon::now();
                        $totalSignalValue = 0;
                        $userSignalSimulatedTrades = 0;
                        $userSignalDailyScoresCreated = 0;
                        $warnings = [];

                        // clear all entries for the User Signal being calculated
                        UserSignalDailyScore::where('user_signal_id', $userSignal->id)->delete();

                        // get the earliest date when all metrics of this model had data
                        $userSignalMetricsCappedAt = $userSignal->getMetricsDataCappedAt();
                        // and set where the User Signal really starts
                        $userSignal->data_limited_at =
                            $userSignalMetricsCappedAt > $since->format('Y-m-d') ?
                                $userSignalMetricsCappedAt : $since->format('Y-m-d');
                        $startDate = Carbon::parse($userSignal->data_limited_at);
                        // when days fetched for reference end
                        $subDaysEndDate = $since->copy()->addDays(Frequency::MAX_FREQUENCY_IN_DAYS);

                        $userSignalMetricsWarnings = [];
                        $userSignalDaysCalculated = 0;

                        // iterate through every day of the time series and, on each day, go through every metric
                        for ($date = $startDate->copy(); $date->lte($now); $date->addDay()) {
                            $userSignalDailyScore = 0;

                            $dailyPrice = $priceService->getDailyPrice($date->format('Y-m-d'));

                            // iterating through day fetched for past variation: skip
                            if ($date <= $subDaysEndDate) {
                                continue;
                            }

                            if (! $dailyPrice) {
                                // create one warning per metric
                                foreach ($userSignal->userSignalMetrics->pluck('id')->all() as $userSignalMetricId) {
                                    $userSignalMetricsWarnings[$userSignalMetricId]['Missing Day(s)'] ??= 0;
                                    $userSignalMetricsWarnings[$userSignalMetricId]['Missing Day(s)']++;
                                }
                                continue;
                            }

                            if ($userSignalDaysCalculated === 1) {
                                $userSignal->first_date_calculated = $date->format('Y-m-d');
                            }

                            foreach ($userSignal->userSignalMetrics as $userSignalMetric) {
                                // retrieve from singleton to avoid queries
                                $metric = $metricService->getMetric($userSignalMetric->metric_id, true);
                                // metric has to be configured or whole model fails
                                if (empty($metric->data_limited_at)) {
                                    throw new UserSignalFunctionalException(
                                        sprintf(
                                            'Metric %s not ready/enabled to process',
                                            $metric->name
                                        )
                                    );
                                }

                                $currentMetricValue = $dailyPrice->{$metric->column_name};

                                if (! $currentMetricValue) {
                                    $warnings[$userSignalMetric->id] ??= []; // Initialize if not set
                                    $warnings[$userSignalMetric->id]['Day(s) missing value'] ??= 0; // Initialize if not set
                                    $warnings[$userSignalMetric->id]['Day(s) missing value']++;
                                    continue;
                                }

                                // get the reference day (current day - frequency in days)
                                $referenceDate = $date->copy()->subDays($userSignalMetric->frequency->number_of_days);
                                $numberOfAttempts = 0;
                                while (
                                    !$referenceDailyPrice =
                                    $priceService->getDailyPrice($referenceDate->format('Y-m-d'))
                                ) {
                                    // tried every day backwards until $since, when it changes to frontwards
                                    if ($referenceDate->lt($since)) {
                                        $referenceDate = $date
                                            ->copy()
                                            ->subDays($userSignalMetric->frequency->number_of_days)
                                            ->addDay();
                                    } elseif($referenceDate->gt($date)) {
                                        $referenceDate->addDay();
                                    } else {
                                        $referenceDate->subDay();
                                    }
                                    $numberOfAttempts++;
                                    if ($numberOfAttempts > 10) {
                                        throw new UserSignalException("Could not fetch \$referenceDailyPrice for {$date->format('Y-m-d')}");
                                    }
                                }

                                // calculate current metric score based on oscillation from the previous day
                                $dailyOscillation = $userSignalMetric->dailyOscillation(
                                    $referenceDailyPrice,
                                    $dailyPrice,
                                    $metric->column_name
                                );

                                // ignore variations against what is expected
                                if (
                                    $userSignalMetric->operator === '+' && $dailyOscillation < 0 ||
                                    $userSignalMetric->operator === '-' && $dailyOscillation > 0
                                ) {
                                        continue;
                                }

                                $dailyOscillation = abs($dailyOscillation);

                                // ignore if variation is below threshold
                                if (isset($userSignalMetric->threshold) && $userSignalMetric->threshold > $dailyOscillation) {
                                    continue;
                                }

                                // apply weight and use absolute value to sum up to the score
                                $userSignalMetricLastScore = $dailyOscillation * $userSignalMetric->weight;

                                // add points to User Signal grand score for the day
                                $userSignalDailyScore += $userSignalMetricLastScore;
                            }

                            // with (all metrics) daily score set, calculate signal_value
                            // @see https://x.com/i/grok/share/qc9u88jiSlSnW9liwos5Ogc3q
                            $conviction = null;
                            $tradeValue = null;
                            $dailySignalValue = null;
                            if ($userSignalDailyScore >= $userSignal->threshold) {
                                // TODO: user_signal.conviction_trade bool to make it proportional to how past threshold
                                if (! empty($userSignal->conviction_trade)) {
                                    // how strong - above the threshold - the model is today
                                    $tradeStrength =
                                        ($userSignalDailyScore - $userSignal->threshold) /
                                        $userSignal->threshold;
                                    // amount bought or sold depends on conviction (cap to full value)
                                    $conviction = min($tradeStrength, 1);
                                } else {
                                    $conviction = 1;
                                }
                                $tradeValue = $conviction * self::TRADE_SIZE_IN_USD;

                                // get the future price change
                                $futurePriceColumnName = 'price_change_' . $userSignal->time_horizon . 'd';
                                // and normalize it (maybe it should be stored like that in the first place?)
                                $futureTradeValueChange = ($dailyPrice->{$futurePriceColumnName} / 100);
                                // total gained or saved this day
                                $futureValueDelta = $tradeValue * $futureTradeValueChange;
                                // if signal was to sell, invert value (price going up is bad while down is good)
                                $dailySignalValue = ($userSignal->buy_or_sell === 'buy') ?
                                    $futureValueDelta : (-1 * $futureValueDelta);
                                // sum to the model's grand signal score
                                $totalSignalValue += $dailySignalValue;

                                $userSignalSimulatedTrades++;
                                $this->totalSimulatedTrades++;
                            }

                            $userSignal->last_score = $userSignalDailyScore;
                            $userSignal->last_date_calculated = $date->format('Y-m-d');
                            $userSignal->last_signal_value = $dailySignalValue;

                            // save day in user_signal_daily_scores
                            UserSignalDailyScore::create([
                                'date' => $date->format('Y-m-d'),
                                'user_signal_id' => $userSignal->id,
                                'score' => $userSignalDailyScore,
                                'signal_value' => $dailySignalValue,
                                'conviction' => $conviction,
                                'stake' => $tradeValue,
                            ]);

                            $userSignalDaysCalculated++;
                            $userSignalDailyScoresCreated++;
                            $this->totalDailyScoresCreated++;
                            $referenceDailyPrice = $dailyPrice;
                        }

                        // if not necessary to have detailed information on user_signal_metrics, remove this ASAP
                        /*foreach ($userSignalMetricsToUpdate as $userSignalMetricId => $data) {
                            if (! empty($warnings[$userSignalMetricId])) {
                                $data['warning'] = implode(' | ', array_map(
                                    function ($warning, $count) {
                                        return $count . ' ' . $warning;
                                    },
                                    array_keys($warnings[$userSignalMetricId]),
                                    $warnings[$userSignalMetricId]
                                ));

                            }
                            UserSignalMetric::where('id', $userSignalMetricId)->update($data);
                        }*/

                        $userSignal->total_signal_value = $totalSignalValue;
                        $userSignal->scores_last_updated_at = $now->format('Y-m-d H:i:s');
                        $userSignal->total_simulated_trades = $userSignalSimulatedTrades;

                        $userSignal->warning = !empty($warnings);

                        $userSignal->error = ($userSignalDailyScoresCreated === 0);

                        $userSignal->save();
                    }
                );
            } catch (\Throwable $e) {
                $fileLine = $e->getFile() . ':' . $e->getLine();
                // first occurrence of an error in the same file/line
                if (! isset($this->errors[$fileLine])) {
                    report($e);
                    $this->errors[$fileLine] = [
                        'message' => $e->getMessage(),
                        'count' => 0,
                    ];
                }
                $this->errors[$fileLine]['count']++;
                $this->totalErrors++;
            }
        }

        return $this->getStats();
    }

    public function getUserStats(int $userId): array
    {
        // Fetch UserSignal records for the given user_id with counts of dailyScores and userSignalMetrics
        $userSignals = UserSignal::where('user_id', $userId)
            ->withCount(['dailyScores', 'userSignalMetrics'])
            ->get();

        // Count the total UserSignal records
        $totalModels = $userSignals->count();

        // Sum the counts of dailyScores and userSignalMetrics across all UserSignal records
        $totalDailyScores = $userSignals->sum('daily_scores_count');
        $totalUserSignalMetrics = $userSignals->sum('user_signal_metrics_count');

        return [
            'total_signals' => $totalModels,
            'total_daily_scores' => $totalDailyScores,
            'total_metrics' => $totalUserSignalMetrics,
        ];
    }

    /**
     *
     */
    public function getUserTopSignal(int $userId)
    {
        return UserSignal::where('user_id', $userId)->whereNotNull('total_signal_value')->orderBy('total_signal_value', 'desc')->first();
    }
}
