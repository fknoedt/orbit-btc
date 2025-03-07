<?php

namespace App\Models;

use App\Enum\Operators;
use App\Exceptions\UserModelException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserModelMetric extends Model
{
    protected $table = 'user_model_metrics';

    protected $guarded = ['id'];

    protected $casts = ['operator' => Operators::class];

    public function metric(): BelongsTo
    {
        return $this->belongsTo(Metric::class);
    }

    /**
     * Calculate and return current DailyPrice->$columnName oscillation percentage from the previous DailyPrice
     */
    public function dailyOscillation(DailyPrice $previousDay, DailyPrice $currentDay, string $columnName): ?float
    {
        if (
            (! $previousValue = $previousDay->{$columnName}) ||
            (! $currentValue = $currentDay->{$columnName})
        ) {
            throw new UserModelException(
                "TODO: maybe this can be expected, if tested before, and we can just return 0 or NULL"
            );
        }

        /**
         *  $metricDailyScore = weight x oscillation (if threshold is set and was not hit, then 0)
         *            $metricCurrentScore[$userModelMetric->id] = $metricDailyScore; (overwritten to be saved in the end with the last day)
         *            $userModelDailyScore += $metricDailyScore
         *   - operator && oscillation_threshold are now optional
         *       - when null (default): weight applied to modular variation of the day
         *       - when set: if oscillation was below threshold, no score for the metric/day
         */

        $oscillation = $currentValue / $previousValue;

        // when threshold is set, we just ignore any score for the day
        if (! empty($this->oscillation_threshold) && $oscillation < $this->oscillation_threshold) {
            return null;
        }

        return (1 - $oscillation) * 100;
    }
}
