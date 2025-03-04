<?php

namespace App\Models;

use App\Enum\Operators;
use Illuminate\Database\Eloquent\Model;

class UserModelMetric extends Model
{
    const UPDATED_AT = null;

    protected $table = 'user_model_metrics';

    protected $guarded = ['id'];

    protected $casts = ['operator' => Operators::class];
}
