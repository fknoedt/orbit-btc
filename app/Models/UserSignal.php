<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class UserSignal extends Model
{
    protected $guarded = ['id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function userSignalMetrics(): HasMany
    {
        return $this->hasMany(UserSignalMetric::class);
    }

    public function metrics(): HasManyThrough
    {
        return $this->hasManyThrough(Metric::class, UserSignalMetric::class);
    }

    public function dailyScores(): HasMany
    {
        return $this->hasMany(UserSignalDailyScore::class);
    }

    /**
     * Retrieve the furthest date when every metric had data available
     * Warning: avoid calling this method if you didn't eager load these relationships first
     */
    public function getMetricsDataCappedAt(): ?string
    {
        $cappedAt = null;
        foreach ($this->userSignalMetrics as $userSignalMetric) {
            if ($userSignalMetric->metrics) {
                foreach ($userSignalMetric->metrics as $metric) {
                    if (is_null($cappedAt) || $metric->data_limited_at > $cappedAt) {
                        $cappedAt = $metric->data_limited_at;
                    }
                }
            }
        }

        return $cappedAt;
    }
}
