<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Frequency extends Model
{
    public const UPDATED_AT = null;

    public const int MAX_FREQUENCY_IN_DAYS = 30;

    protected $guarded = [];
}
