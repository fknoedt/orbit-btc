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
}
