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

    /** 30 days after the first close, 2009-10-05. */
    public const string START_OF_ROI_30D = '2009-11-04';

    /** 365 days after the first close, 2009-10-05. */
    public const string START_OF_ROI_1YR = '2010-10-05';

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

    /**
     * First day that can still receive an ROI value.
     * A null return whose earlier close is missing or zero is left alone.
     */
    public static function getLastEmptyRoiDay(): ?string
    {
        $rows = self::where(function ($query) {
            $query->where(function ($query) {
                $query->where('date', '>=', self::START_OF_ROI_30D)
                    ->whereNull('roi_30d');
            })->orWhere(function ($query) {
                $query->where('date', '>=', self::START_OF_ROI_1YR)
                    ->whereNull('roi_1yr');
            });
        })
            ->orderBy('date', 'asc')
            ->get(['date', 'close', 'roi_30d', 'roi_1yr']);

        if ($rows->isEmpty()) {
            return null;
        }

        $lookbacks = [];
        foreach ($rows as $row) {
            $date = substr((string) $row->date, 0, 10);
            if ($date >= self::START_OF_ROI_30D && $row->roi_30d === null) {
                $lookbacks[] = Carbon::parse($date)->subDays(30)->toDateString();
            }
            if ($date >= self::START_OF_ROI_1YR && $row->roi_1yr === null) {
                $lookbacks[] = Carbon::parse($date)->subDays(365)->toDateString();
            }
        }

        $closes = [];
        foreach (self::whereIn('date', array_unique($lookbacks))->pluck('close', 'date') as $date => $close) {
            $closes[substr((string) $date, 0, 10)] = $close;
        }

        foreach ($rows as $row) {
            $date = substr((string) $row->date, 0, 10);
            if (! is_numeric($row->close) || (float) $row->close == 0.0) {
                continue;
            }

            if ($date >= self::START_OF_ROI_30D && $row->roi_30d === null) {
                $previous = $closes[Carbon::parse($date)->subDays(30)->toDateString()] ?? null;
                if (is_numeric($previous) && (float) $previous != 0.0) {
                    return $date;
                }
            }

            if ($date >= self::START_OF_ROI_1YR && $row->roi_1yr === null) {
                $previous = $closes[Carbon::parse($date)->subDays(365)->toDateString()] ?? null;
                if (is_numeric($previous) && (float) $previous != 0.0) {
                    return $date;
                }
            }
        }

        return null;
    }

    /**
     * First day with a forward return that can be filled from a later close.
     * A horizon still in the future, or aimed at a missing day, does not count.
     */
    public static function getLastEmptyFuturePriceDay(): ?string
    {
        $today = Carbon::now()->startOfDay();
        $windowStart = $today->copy()->subDays(self::FUTURE_PRICE_MAX_DAYS_AGO)->toDateString();
        $horizons = [1, 3, 5, 10, 14, 30];
        $columns = ['date', 'close'];
        foreach ($horizons as $days) {
            $columns[] = 'price_change_' . $days . 'd';
        }

        $rows = self::where('date', '>=', $windowStart)
            ->orderBy('date', 'asc')
            ->get($columns);

        $closes = [];
        foreach ($rows as $row) {
            $closes[substr((string) $row->date, 0, 10)] = $row->close;
        }

        foreach ($rows as $row) {
            $date = substr((string) $row->date, 0, 10);
            if ($date >= $today->toDateString()) {
                continue;
            }

            foreach ($horizons as $days) {
                if ($row->{'price_change_' . $days . 'd'} !== null) {
                    continue;
                }

                $later = Carbon::parse($date)->addDays($days)->toDateString();
                if ($later > $today->toDateString()) {
                    continue;
                }

                $close = $closes[$later] ?? null;
                if (is_numeric($close) && (float) $close != 0.0) {
                    return $date;
                }
            }
        }

        return null;
    }
}
