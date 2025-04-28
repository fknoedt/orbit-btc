<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMetricAlert extends Model
{
    protected $guarded = [
        'id',
    ];

    /**
     * Get the user that owns the alert.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the metric associated with the alert.
     */
    public function metric(): BelongsTo
    {
        return $this->belongsTo(Metric::class);
    }

    /**
     * Get the frequency associated with the alert.
     */
    public function frequency(): BelongsTo
    {
        return $this->belongsTo(Frequency::class);
    }
}
