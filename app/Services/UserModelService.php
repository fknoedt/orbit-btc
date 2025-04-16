<?php

namespace App\Services;

use App\Exceptions\UserModelException;
use App\Exceptions\UserModelFunctionalException;
use App\Models\Frequency;
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
        int               $userModelId = null,
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

        // don't eager load userModelMetrics.metric to use a pre-loaded hashmap
        $query = UserModel::with(['userModelMetrics', 'userModelMetrics.frequency'])
            ->where('is_paused', false)
            ->whereHas('userModelMetrics');

        if ($userModelId) {
            $query->where('id', $userModelId);
        } else {
            if ($userId) {
                $query->where('user_id', $userId);
            }
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

                        // clear all entries for the User Model being calculated
                        UserModelDailyScore::where('user_model_id', $userModel->id)->delete();

                        // get the earliest date when all metrics of this model had data
                        $userModelMetricsCappedAt = $userModel->getMetricsDataCappedAt();
                        // and set where the user model really starts
                        $userModel->data_limited_at =
                            $userModelMetricsCappedAt > $since->format('Y-m-d') ?
                                $userModelMetricsCappedAt : $since->format('Y-m-d');
                        $startDate = Carbon::parse($userModel->data_limited_at);
                        // when days fetched for reference end
                        $subDaysEndDate = $since->copy()->addDays(Frequency::MAX_FREQUENCY_IN_DAYS);

                        $userModelMetricsWarnings = [];
                        $userModelDaysCalculated = 0;

                        // iterate through every day of the time series and, on each day, go through every metric
                        for ($date = $startDate->copy(); $date->lte($now); $date->addDay()) {
                            $userModelDailyScore = 0;

                            $dailyPrice = $priceService->getDailyPrice($date->format('Y-m-d'));

                            // iterating through day fetched for past variation: skip
                            if ($date <= $subDaysEndDate) {
                                continue;
                            }

                            if (! $dailyPrice) {
                                // create one warning per metric
                                foreach ($userModel->userModelMetrics->pluck('id')->all() as $userModelMetricId) {
                                    $userModelMetricsWarnings[$userModelMetricId]['Missing Day(s)'] ??= 0;
                                    $userModelMetricsWarnings[$userModelMetricId]['Missing Day(s)']++;
                                }
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

                                // get the reference day (current day - frequency in days)
                                $referenceDate = $date->copy()->subDays($userModelMetric->frequency->number_of_days);
                                $numberOfAttempts = 0;
                                while (
                                    !$referenceDailyPrice =
                                    $priceService->getDailyPrice($referenceDate->format('Y-m-d'))
                                ) {
                                    // tried every day backwards until $since, when it changes to frontwards
                                    if ($referenceDate->lt($since)) {
                                        $referenceDate = $date
                                            ->copy()
                                            ->subDays($userModelMetric->frequency->number_of_days)
                                            ->addDay();
                                    } elseif($referenceDate->gt($date)) {
                                        $referenceDate->addDay();
                                    } else {
                                        $referenceDate->subDay();
                                    }
                                    $numberOfAttempts++;
                                    if ($numberOfAttempts > 10) {
                                        throw new UserModelException("Could not fetch \$referenceDailyPrice for {$date->format('Y-m-d')}");
                                    }
                                }

                                // calculate current metric score based on oscillation from the previous day
                                $dailyOscillation = $userModelMetric->dailyOscillation(
                                    $referenceDailyPrice,
                                    $dailyPrice,
                                    $metric->column_name
                                );

                                // ignore variations against what is expected
                                if (
                                    $userModelMetric->operator === '+' && $dailyOscillation < 0 ||
                                    $userModelMetric->operator === '-' && $dailyOscillation > 0
                                ) {
                                        continue;
                                }

                                $dailyOscillation = abs($dailyOscillation);

                                // ignore if variation is below threshold
                                if (isset($userModelMetric->threshold) && $userModelMetric->threshold > $dailyOscillation) {
                                    continue;
                                }

                                // apply weight and use absolute value to sum up to the score
                                $userModelMetricLastScore = $dailyOscillation * $userModelMetric->weight;

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
                            $referenceDailyPrice = $dailyPrice;
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
