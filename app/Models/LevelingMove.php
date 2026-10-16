<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LevelingMove extends Model
{
    protected $table = 'leveling_moves';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'abs_percent' => 'float',
        'price_date' => 'date',
        'calculated_at' => 'datetime',
    ];

    /** The metric this stored percent belongs to. */
    public function metric(): BelongsTo
    {
        return $this->belongsTo(Metric::class);
    }

    /** The day window this stored percent was measured over. */
    public function frequency(): BelongsTo
    {
        return $this->belongsTo(Frequency::class);
    }
}
