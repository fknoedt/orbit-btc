<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Metric extends Model
{
    protected $guarded = ['id'];

    public function dataSource(): BelongsTo
    {
        return $this->belongsTo(DataSource::class);
    }

    public function userMetricAlerts(): HasMany
    {
        return $this->hasMany(UserMetricAlert::class, 'metric_id');
    }
}
