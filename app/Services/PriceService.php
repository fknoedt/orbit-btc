<?php

namespace App\Services;

use App\Models\DailyPrice;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Cache;

class PriceService
{
    /** If start to end is longer than this number of days AND $reduce is true, remove days */
    protected const int DATE_REDUCTION_THRESHOLD = 31;

    /**  */
    protected const int NUMBER_OF_DAYS_IN_REDUCTION = 100;

    protected static ?string $pollingInterval = null;

    /**
     * Return array of DailyPrice arrays indexed by dates (useful for charts)
     *
     */
    public function getClosePriceByDays(
        Carbon $startDate,
        Carbon $endDate,
        bool   $reduce = false,
        bool   $shortDates = false
    ): array
    {
        if ($reduce && $startDate->diffInDays($endDate) > self::DATE_REDUCTION_THRESHOLD) {
            $days = [];
            $period = CarbonPeriod::create($startDate, $endDate);
            $totalDays = $period->count();
            // 1st and last days are always included
            $daysHiatus = ($totalDays - 2) / (self::NUMBER_OF_DAYS_IN_REDUCTION - 2);

            $rowNumber = 0;
            foreach ($period as $date) {
                if ($rowNumber === 0 || $rowNumber === $totalDays || $daysHiatus < 1 || ($rowNumber % $daysHiatus == 0)) {
                    $days[] = $date;
                }
                $rowNumber++;
            }

            $cacheKey = md5(__METHOD__ . implode('_', $days));
            $data = Cache::remember(
                $cacheKey,
                now()->endOfDay(),
                function () use ($days) {
                    return DailyPrice::whereIn('date', $days)->get()->keyBy('date')->toArray();
                }
            );
        } else {
            $cacheKey = md5(__METHOD__ . $startDate->format('Y-m-d') . $endDate->format('Y-m-d'));
            $data = Cache::remember(
                $cacheKey,
                now()->endOfDay(),
                function () use ($startDate, $endDate) {
                    return DailyPrice::whereBetween('date', [$startDate, $endDate])->get()->keyBy('date')->toArray();
                }
            );
        }

        if ($shortDates) {
            $data = array_combine(
                array_map(fn ($date) => date('m/d', strtotime($date)), array_keys($data)),
                array_values($data)
            );
        }

        return $data;
    }
}
