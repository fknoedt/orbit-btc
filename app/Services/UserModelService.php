<?php

namespace App\Services;

use App\Exceptions\UserModelFunctionalException;
use App\Models\UserModel;
use App\Models\UserModelDailyScore;
use App\Models\UserModelMetric;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UserModelService
{
    /** How far back a user_model will span */
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

    public function getMaxThreshold(int $userModelId): int
    {
        $max = 0;

        $metrics = UserModelMetric::where('user_model_id', $userModelId)->get();

        if (empty($metrics)) {
            return $max;
        }

        foreach ($metrics as $metric) {
            $max += $metric->weight * self::MAX_OSCILLATION_PER_METRIC;
        }

        return $max;
    }

    public function updateDailyScores(
        int           $userModelId = null,
        int           $userId = null,
        Carbon        $since = null,
        MetricService $metricService = null,
        PriceService $priceService = null,
    ): array
    {
        if (! $metricService) {
            $metricService = new MetricService();
        }

        if (! $priceService) {
            $priceService = new PriceService();
        }

        if (! $since) {
            $since = Carbon::now()->subDays(self::MAX_DAYS_BACK);
        }
        // always add one more day to allow oscillation calculation from the very first day analysed
        $since->subDay();

        // pre-load all daily_prices to avoid repeated DB/cache access
        $priceService->getAllDailyPricesKeyByDate($since, Carbon::now(), true);

        // don't eager load userModelMetrics.metric to use a pre-loaded hashmap
        $query = UserModel::with(['userModelMetrics'])
            ->where('is_paused', false)
            ->whereHas('userModelMetrics');

        if ($userModelId) {
            $query->where('id', $userModelId);
        } else {
            if ($userId) {
                $query->where('user_id', $userId);
            }
            /*if ($lastScoreAt) {
                $query->where('last_score_at', '>=', $lastScoreAt->format('Y-m-d H:i:s'));
            }*/
        }

        // calculate every Metric of every UserMetric and upsert all related tables
        foreach ($query->get() as $userModel) {
            try {
                // if one UserModel fail processing, report and save errors to this object and try every other UserModel
                DB::transaction(
                    function () use ($userModel, $since, $metricService, $priceService) {
                        $now = Carbon::now();
                        $totalSignalValue = 0;
                        $userModelSimulatedTrades = 0;
                        $userModelDailyScoresCreated = 0;
                        $warnings = [];
                        // needed to compare oscillation between the current and previous day
                        $previousDailyPrice = null;

                        // clear all entries for the User Model being calculated
                        UserModelDailyScore::where('user_model_id', $userModel->id)->delete();

                        // get the earliest date when all metrics of this model had data
                        $userModelMetricsCappedAt = $userModel->getMetricsDataCappedAt();
                        // and set where the user model really starts
                        $userModel->data_limited_at =
                            $userModelMetricsCappedAt > $since->format('Y-m-d') ?
                                $userModelMetricsCappedAt : $since->format('Y-m-d');
                        $startDate = Carbon::parse($userModel->data_limited_at);

                        // TODO: use or remove when decision is made (see commented code below)
                        // each Metric should be saved only once, not per day
                        // $metricsToUpdate = [];
                        $userModelMetricsWarnings = [];
                        $userModelDaysCalculated = 0;

                        // iterate through every day of the time series and, on each day, go through every metric
                        for ($date = $startDate->copy(); $date->lte($now); $date->addDay()) {
                            $userModelDailyScore = 0;

                            $dailyPrice = $priceService->getDailyPrice($date->format('Y-m-d'));
                            if (! $dailyPrice) {
                                // create one warning per metric
                                foreach ($userModel->userModelMetrics->pluck('id')->all() as $userModelMetricId) {
                                    $userModelMetricsWarnings[$userModelMetricId]['Missing Day(s)'] ??= 0;
                                    $userModelMetricsWarnings[$userModelMetricId]['Missing Day(s)']++;
                                }
                                continue;
                            }

                            // first day: just save DailyPrice for next day's reference
                            if (! $previousDailyPrice) {
                                $previousDailyPrice = $dailyPrice;
                                continue;
                            }

                            if ($userModelDaysCalculated === 1) {
                                $userModel->first_date_calculated = $date->format('Y-m-d');
                            }

                            foreach ($userModel->userModelMetrics as $userModelMetric) {
                                // retrieve from singleton to avoid queries
                                $metric = $metricService->getMetric($userModelMetric->metric_id, true);
                                // metric has to be configured or whole model fails
                                if (empty($metric->data_limited_at)) {
                                    throw new UserModelFunctionalException(
                                        sprintf(
                                            'Metric %s not ready/enabled to process',
                                            $metric->name
                                        )
                                    );
                                }

                                $currentMetricValue = $dailyPrice->{$metric->column_name};

                                if (! $currentMetricValue) {
                                    $warnings[$userModelMetric->id] ??= []; // Initialize if not set
                                    $warnings[$userModelMetric->id]['Day(s) missing value'] ??= 0; // Initialize if not set
                                    $warnings[$userModelMetric->id]['Day(s) missing value']++;
                                    continue;
                                }

                                // calculate current metric score based on oscillation from the previous day
                                $dailyOscillation = $userModelMetric->dailyOscillation(
                                    $previousDailyPrice,
                                    $dailyPrice,
                                    $metric->column_name
                                );

                                // apply weight and use absolute value to sum up to the score
                                $userModelMetricLastScore = abs($dailyOscillation) * $userModelMetric->weight;

                                // add points to User Model grand score for the day
                                $userModelDailyScore += $userModelMetricLastScore;
                            }

                            // with (all metrics) daily score set, calculate signal_value
                            // @see https://x.com/i/grok/share/qc9u88jiSlSnW9liwos5Ogc3q
                            $conviction = null;
                            $tradeValue = null;
                            $dailySignalValue = null;
                            if ($userModelDailyScore >= $userModel->threshold) {
                                // TODO: user_model.conviction_trade bool to make it proportional to how past threshold
                                if (! empty($userModel->conviction_trade)) {
                                    // how strong - above the threshold - the model is today
                                    $tradeStrength =
                                        ($userModelDailyScore - $userModel->threshold) /
                                        $userModel->threshold;
                                    // amount bought or sold depends on conviction (cap to full value)
                                    $conviction = min($tradeStrength, 1);
                                } else {
                                    $conviction = 1;
                                }
                                $tradeValue = $conviction * self::TRADE_SIZE_IN_USD;

                                // get the future price change
                                $futurePriceColumnName = 'price_change_' . $userModel->time_horizon . 'd';
                                // and normalize it (maybe it should be stored like that in the first place?)
                                $futureTradeValueChange = ($dailyPrice->{$futurePriceColumnName} / 100);
                                // total gained or saved this day
                                $futureValueDelta = $tradeValue * $futureTradeValueChange;
                                // if signal was to sell, invert value (price going up is bad while down is good)
                                $dailySignalValue = ($userModel->buy_or_sell === 'buy') ?
                                    $futureValueDelta : (-1 * $futureValueDelta);
                                // sum to the model's grand signal score
                                $totalSignalValue += $dailySignalValue;

                                $userModelSimulatedTrades++;
                                $this->totalSimulatedTrades++;
                            }

                            $userModel->last_score = $userModelDailyScore;
                            $userModel->last_date_calculated = $date->format('Y-m-d');
                            $userModel->last_signal_value = $dailySignalValue;

                            // save day in user_model_daily_scores
                            UserModelDailyScore::create([
                                'date' => $date->format('Y-m-d'),
                                'user_model_id' => $userModel->id,
                                'score' => $userModelDailyScore,
                                'signal_value' => $dailySignalValue,
                                'conviction' => $conviction,
                                'stake' => $tradeValue,
                            ]);

                            $userModelDaysCalculated++;
                            $userModelDailyScoresCreated++;
                            $this->totalDailyScoresCreated++;
                            $previousDailyPrice = $dailyPrice;
                        }

                        // if not necessary to have detailed information on user_model_metrics, remove this ASAP
                        /*foreach ($userModelMetricsToUpdate as $userModelMetricId => $data) {
                            if (! empty($warnings[$userModelMetricId])) {
                                $data['warning'] = implode(' | ', array_map(
                                    function ($warning, $count) {
                                        return $count . ' ' . $warning;
                                    },
                                    array_keys($warnings[$userModelMetricId]),
                                    $warnings[$userModelMetricId]
                                ));

                            }
                            UserModelMetric::where('id', $userModelMetricId)->update($data);
                        }*/

                        $userModel->total_signal_value = $totalSignalValue;
                        $userModel->scores_last_updated_at = $now->format('Y-m-d H:i:s');
                        $userModel->total_simulated_trades = $userModelSimulatedTrades;

                        $userModel->warning = !empty($warnings);

                        $userModel->error = ($userModelDailyScoresCreated === 0);

                        $userModel->save();
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
        // Fetch UserModel records for the given user_id with counts of dailyScores and userModelMetrics
        $userModels = UserModel::where('user_id', $userId)
            ->withCount(['dailyScores', 'userModelMetrics'])
            ->get();

        // Count the total UserModel records
        $totalModels = $userModels->count();

        // Sum the counts of dailyScores and userModelMetrics across all UserModel records
        $totalDailyScores = $userModels->sum('daily_scores_count');
        $totalUserModelMetrics = $userModels->sum('user_model_metrics_count');

        return [
            'total_models' => $totalModels,
            'total_daily_scores' => $totalDailyScores,
            'total_metrics' => $totalUserModelMetrics,
        ];
    }

    /**
     *
     */
    public function getUserTopModel(int $userId)
    {
        return UserModel::where('user_id', $userId)->orderBy('total_signal_value', 'desc')->first();
    }
}
