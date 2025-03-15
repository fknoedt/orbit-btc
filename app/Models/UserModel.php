<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * 'Model' in this context is the formula, over multiple metrics, that a user can have
 * Not to confuse with Eloquent Models 👀
 */
class UserModel extends Model
{
    protected $guarded = ['id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function userModelMetrics(): HasMany
    {
        return $this->hasMany(UserModelMetric::class);
    }

    public function metrics(): HasManyThrough
    {
        return $this->hasManyThrough(Metric::class, UserModelMetric::class);
    }

    public function dailyScores(): HasMany
    {
        return $this->hasMany(UserModelDailyScore::class);
    }

    /**
     * Retrieve the furthest date when every metric had data available
     * Warning: avoid calling this method if you didn't eager load these relationships first
     */
    public function getMetricsDataCappedAt(): ?string
    {
        $cappedAt = null;
        foreach ($this->userModelMetrics as $userModelMetric) {
            if ($userModelMetric->metrics) {
                foreach ($userModelMetric->metrics as $metric) {
                    if (is_null($cappedAt) || $metric->data_limited_at > $cappedAt) {
                        $cappedAt = $metric->data_limited_at;
                    }
                }
            }
        }

        return $cappedAt;
    }
}
