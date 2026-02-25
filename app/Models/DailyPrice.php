<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyPrice extends Model
{
    use HasFactory;

    public const int FUTURE_PRICE_MAX_DAYS_AGO = 365;
    public const string START_OF_MAYER_MULTIPLE = '2012-01-01';
    public const string START_OF_RSI = '2012-01-01';
    public const string START_OF_BOLLINGER_BANDS = '2012-01-01';
    public const string START_OF_TBILL_OUTSTANDING = '2010-01-01';

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

    public static function getLastEmptyRsiDay(): ?string
    {
        return self::whereNull('rsi')
            ->where('date', '>=', self::START_OF_RSI)
            ->orderBy('date', 'asc')
            ->pluck('date')
            ->first();
    }


    public static function getLastEmptyBollingerBandsDay(): ?string
    {
        return self::where('date', '>=', self::START_OF_BOLLINGER_BANDS)
            ->where(function ($query) {
                return $query->whereNull('bb_upper')
                    ->orWhereNull('bb_middle')
                    ->orWhereNull('bb_lower');
            })
            ->orderBy('date', 'asc')
            ->pluck('date')
            ->first();
    }

    public static function getLastEmptyTbillOutstandingDay(): ?string
    {
        return self::where('date', '>=', self::START_OF_TBILL_OUTSTANDING)
            ->where(function ($query) {
                return $query->whereNull('us_tbill_net_issuance')
                    ->orWhereNull('us_tbill_normalized_qe');
            })
            ->orderBy('date', 'asc')
            ->pluck('date')
            ->first();
    }

    public static function getLastEmptyFuturePriceDay(): ?string
    {
        return self::where('date', '>=', Carbon::now()->subDays(self::FUTURE_PRICE_MAX_DAYS_AGO))
            ->where(function ($query) {
                return $query->whereNull('price_change_1d')
                    ->orWhereNull('price_change_3d')
                    ->orWhereNull('price_change_5d')
                    ->orWhereNull('price_change_10d')
                    ->orWhereNull('price_change_14d')
                    ->orWhereNull('price_change_30d');
            })
            ->orderBy('date', 'asc')
            ->pluck('date')
            ->first();
    }
}
