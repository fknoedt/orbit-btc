<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataSource extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function getParsedFaviconAttribute(): ?string
    {
        if (empty($this->favicon)) {
            return null;
        }

        return str_starts_with($this->favicon, 'https') ? url($this->favicon) : asset($this->favicon);
    }
}
