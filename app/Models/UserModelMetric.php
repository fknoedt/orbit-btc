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

    public function frequency(): BelongsTo
    {
        return $this->belongsTo(Frequency::class);
    }

    /**
     * Calculate and return current DailyPrice->$columnName oscillation percentage from the reference DailyPrice
     * (current day - frequency, in days)
     */
    public function dailyOscillation(DailyPrice $referenceDay, DailyPrice $currentDay, string $columnName): ?float
    {
        if (
            (! $referenceValue = $referenceDay->{$columnName}) ||
            (! $currentValue = $currentDay->{$columnName})
        ) {
            throw new UserModelException(
                "TODO: maybe this can be expected, if tested before, and we can just return 0 or NULL"
            );
        }

        $oscillation = $currentValue / $referenceValue;

        return (1 - $oscillation) * 100;
    }
}
