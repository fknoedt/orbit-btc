<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyPrice extends Model
{
    use HasFactory;

    public const string START_OF_MAYER_MULTIPLE = '2012-01-01';

    protected $guarded = ['id'];

    public function dataSource(): BelongsTo
    {
        return $this->belongsTo(DataSource::class);
    }

    public static function getLastEmptyMayerMultipleDay(): ?string
    {
        return self::whereNull('mayer_multiple')
            ->where('date', '>=', self::START_OF_MAYER_MULTIPLE)
            ->orderBy('date', 'asc')
            ->pluck('date')
            ->first();
    }

    public static function getLastEmptyFuturePriceDay(): ?string
    {
        // they're always updated together
        return self::whereNull('price_change_1d')
            ->where('date', '>=', config('btc.first_cmc_available_date'))
            ->orderBy('date', 'asc')
            ->pluck('date')
            ->first();
    }
}
