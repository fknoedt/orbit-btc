<?php

namespace App\Services;

use App\Models\DailyPrice;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class PriceService
{
    /** If start to end is longer than this number of days AND $reduce is true, remove days */
    protected const int DATE_REDUCTION_THRESHOLD = 31;

    /**  */
    protected const int NUMBER_OF_DAYS_IN_REDUCTION = 100;

    protected static ?string $pollingInterval = null;

    /** Collection of DailyPrice indexed by date (warning: different date intervals will be mixed up) */
    protected Collection $dailyPricesByDate;

    public function __construct(Collection $dailyPricesByDate = null)
    {
        $this->dailyPricesByDate = $dailyPricesByDate ?? new Collection();
    }


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

    /**
     * This method fetches from cache all daily_price into a singleton to avoid multiple queries if called repeatedly
     * WARNING: don't use it if you don't want to allocate a lot of data in memory (should be used to repeated access)
     * @param bool $singleton will keep all DailyPrices allocated for further access
     */
    public function getAllDailyPricesKeyByDate(
        Carbon $startDate = null,
        Carbon $endDate = null,
        bool   $singleton = true
    ): Collection
    {
        if (! $startDate) {
            $startDate = Carbon::createFromFormat('Y-m-d', config('btc.first_cmc_available_date'));
        }
        $cacheKey = md5(
            __METHOD__ .
            $startDate->format('Y-m-d') .
            ($endDate ? $endDate->format('Y-m-d') : '')
        );
        $data = Cache::remember($cacheKey, (new Carbon())->endOfDay(), function () use ($startDate, $endDate) {
            $query = DailyPrice::query();
            if ($startDate) {
                $query->where('date', '>=', $startDate);
            }
            if ($endDate) {
                $query->where('date', '<=', $endDate);
            }
            return $query->orderBy('date')->get()->keyBy('date');
        });

        if ($singleton) {
            $this->dailyPricesByDate = $this->dailyPricesByDate->union($data);
        }

        return $data;
    }

    public function getDailyPrice(string $date, bool $findOrFail = false): ?DailyPrice
    {
        // first time called
        if ($this->dailyPricesByDate->isEmpty()) {
            throw new \BadMethodCallException(
                "You should call getAllDailyPricesByDate() to load initial data before calling getDailyPrice()"
            );
        }

        if ($findOrFail && ! isset($this->dailyPricesByDate[$date])) {
            throw new \InvalidArgumentException("Daily Price not found for date {$date}");
        }

        return $this->dailyPricesByDate[$date] ?? null;
    }
}
