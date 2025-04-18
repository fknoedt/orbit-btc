<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivityLog extends Model
{
    public const CREATED_AT = null;
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    // Relationship to User
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
