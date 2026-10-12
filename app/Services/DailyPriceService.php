<?php

namespace App\Services;

use App\Exceptions\TimeSeriesException;
use App\Math\LineDistance;
use App\Models\DailyPrice;
use App\Models\Metric;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class DailyPriceService
{
    /** Maximum number of days plotted when $reduce is true */
    protected const int NUMBER_OF_DAYS_IN_REDUCTION = 1000;

    protected static ?string $pollingInterval = null;

    /** Collection of DailyPrice indexed by date (warning: different date intervals will be mixed up) */
    protected Collection $dailyPricesByDate;

    public function __construct(Collection $dailyPricesByDate = null)
    {
        $this->dailyPricesByDate = $dailyPricesByDate ?? new Collection();
    }

    /**
     * Return array of DailyPrice arrays indexed by dates (useful for charts)
     * @param bool $reduce -- if true and number of days exceeds limit, ratio the days to match it
     */
    public function getDailyPriceByDays(
        Carbon $startDate,
        Carbon $endDate,
        bool   $reduce = false,
        bool   $shortDates = false
    ): array
    {
        if ($reduce && (int) $startDate->diffInDays($endDate, true) > self::NUMBER_OF_DAYS_IN_REDUCTION) {
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
                    return DailyPrice::whereIn('date', $days)->orderBy('date', 'asc')->get()->keyBy('date')->toArray();
                }
            );
        } else {
            $cacheKey = md5(__METHOD__ . $startDate->format('Y-m-d') . $endDate->format('Y-m-d'));
            $data = Cache::remember(
                $cacheKey,
                now()->endOfDay(),
                function () use ($startDate, $endDate) {
                    return DailyPrice::whereBetween('date', [$startDate, $endDate])->orderBy('date')->get()->keyBy('date')->toArray();
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
        bool $singleton = true
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

        $data = Cache::get($cacheKey);

        if ($data === null) {
            $query = DailyPrice::where('date', '>=', $startDate);

            if ($endDate) {
                $query->where('date', '<=', $endDate);
            }
            $data = $query->orderBy('date')->get()->keyBy('date');
            Cache::put($cacheKey, $data, (new Carbon())->endOfDay());

            // no need to overwrite it
            if (! empty($this->dailyPricesByDate) && count($this->dailyPricesByDate) === count($data)) {
                $singleton = false;
            }
        }

        // singleton can be empty even when data is retrieved from cache (first call)
        if ($singleton) {
            $this->dailyPricesByDate = $this->dailyPricesByDate->union($data);
        }

        return $data;
    }

    /**
     * Leverage $this->dailyPricesByDate, which is populated on $this->getAllDailyPricesByDate(), to fetch DailyPrices
     */
    public function getDailyPrice(string $date, bool $findOrFail = false, int $fallback = 0): ?DailyPrice
    {
        // first time called
        if ($this->dailyPricesByDate->isEmpty()) {
            throw new \BadMethodCallException(
                "You should call getAllDailyPricesByDate() to load initial data before calling getDailyPrice()"
            );
        }

        // If date exists, return it immediately
        if (isset($this->dailyPricesByDate[$date])) {
            return $this->dailyPricesByDate[$date];
        }

        // Handle fallback logic if price not found and fallback > 0
        if ($fallback > 0) {
            $currentDate = Carbon::parse($date);

            // Try previous days up to $fallback times
            for ($i = 1; $i <= $fallback; $i++) {
                $previousDate = $currentDate->copy()->subDay()->toDateString();

                if (isset($this->dailyPricesByDate[$previousDate])) {
                    return $this->dailyPricesByDate[$previousDate];
                }
            }
        }

        // If findOrFail is true and we still haven't found a price
        if ($findOrFail) {
            throw new \InvalidArgumentException("Daily Price not found for date {$date}");
        }

        // Return null if no price found and findOrFail is false
        return null;
    }

    public function getPatterMatchingTimeSeries(string $metric, Carbon $startDate, Carbon $endDate, int $limit = 3): array
    {
        if ($metric === 'close') {
            if (! $initialDailyPriceDate = config('btc.initial_pattern_search_date')) {
                throw new \RuntimeException('config btc.initial_pattern_search_date not set');
            }
        } else {
            $metricModel = Metric::where('column_name', $metric)->firstOrFail();
            if (! ($initialDailyPriceDate = $metricModel->data_limited_at)) {
                throw new \RuntimeException("Metric {$metric} with no `data_limited_at`");
            }
        }

        $initialDate = Carbon::parse($initialDailyPriceDate);
        $prices = $this->getAllDailyPricesKeyByDate($initialDate, Carbon::now(), false);

        $values = [];
        $dates = [];
        $queryStart = null;
        $queryEnd = null;
        $lastValue = null;
        $startKey = $startDate->format('Y-m-d');
        $endKey = $endDate->format('Y-m-d');

        foreach ($prices as $dailyPrice) {
            $value = $dailyPrice->$metric;
            // missing day: repeat the last known price
            if (!$value) {
                if ($lastValue) {
                    $value = $lastValue;
                } else {
                    $lastValue = $value;
                    continue;
                }
            }

            $date = $dailyPrice->date instanceof \DateTimeInterface
                ? $dailyPrice->date->format('Y-m-d')
                : substr((string) $dailyPrice->date, 0, 10);
            $values[] = (float) $value;
            $dates[] = $date;
            $lastValue = (float) $value;

            if ($date >= $startKey && $date <= $endKey) {
                $queryStart ??= count($values) - 1;
                $queryEnd = count($values) - 1;
            }
        }

        if ($queryStart === null || $queryEnd === null || $queryEnd <= $queryStart) {
            throw new \RuntimeException('Selected period has no comparable prices');
        }
        if ($values[$queryStart] == 0.0) {
            throw new \RuntimeException('Selected period starts at zero');
        }

        $query = [];
        for ($k = $queryStart; $k <= $queryEnd; $k++) {
            $query[] = ($values[$k] / $values[$queryStart]) - 1;
        }

        $line = new LineDistance();
        $windowSize = count($query);
        $matches = [];
        // One extra slot: the searched period itself is distance 0 and is removed at the end.
        $keptDistances = [];

        $lastDistance = null;
        $lastIndex = null;
        $quarantineUntil = null;
        $lastWindow = count($values) - $windowSize;
        for ($i = 0; $i <= $lastWindow; $i++) {
            if ($values[$i] == 0.0) {
                continue;
            }

            $seriesStartDate = Carbon::parse($dates[$i]);
            $inQuarantine = $quarantineUntil && $quarantineUntil > $seriesStartDate;
            $giveUpAt = $inQuarantine ? (float) $lastDistance : null;
            if (count($keptDistances) >= $limit + 1) {
                $cutoff = $keptDistances[$limit];
                $giveUpAt = $giveUpAt === null ? $cutoff : min($giveUpAt, $cutoff);
            }

            $distance = $line->distance($query, $values, $i, $giveUpAt);
            if ($distance === null) {
                continue;
            }

            // avoid hits too close to each other if they're smaller than the last one
            if ($inQuarantine) {
                if ($distance > $lastDistance) {
                    continue;
                }
                // during quarantine but distance is better: remove the item that started quarantine and start it again
                unset($matches[$lastIndex]);
                $this->forgetDistance($keptDistances, $lastDistance);
            }

            // make sure distances won't overlap
            $index = (string) $distance;
            while (isset($matches[$index])) {
                $index = $index . (!str_contains($index, '.') ? '.01' : '01');
            }

            // if there's more than one 1 (searched period) distance 0 series, we'll need to check searched period here
            $matches[$index] = ['start_date' => $seriesStartDate->format('Y-m-d'), 'distance' => $distance];
            $this->rememberDistance($keptDistances, $distance);

            $lastDistance = $distance;
            $lastIndex = $index;
            $quarantineUntil = $seriesStartDate->addDays($windowSize * 2);
        }

        // closest matches first
        ksort($matches);

        // expected: the same time window will have distance 0: remove it
        if (! isset($matches['0'])) {
            $debugInfo = [
                'metric' => $metric,
                'startDate' => $startDate->format('Y-m-d'),
                'endDate' => $endDate->format('Y-m-d'),
                'countMatches' => count($matches),
            ];
            report(
                new TimeSeriesException(
                    'No distance 0 when pattern matching: ' . json_encode($debugInfo)
                )
            );
        } else {
            // two 0 distance series?!?
            if (isset($matches['0.01'])) {
                $debugInfo = [
                    'metric' => $metric,
                    'startDate' => $startDate->format('Y-m-d'),
                    'endDate' => $endDate->format('Y-m-d'),
                    'countMatches' => count($matches),
                    'trashedMatch' => $matches['0'],
                ];
                report(
                    new TimeSeriesException('Two 0 distance series?!: ' . json_encode($debugInfo))
                );
            }
            unset($matches['0']);
        }

        return array_slice($matches, 0, $limit);
    }

    /**
     * @param array<int, float> $distances
     */
    private function rememberDistance(array &$distances, float $distance): void
    {
        $distances[] = $distance;
        sort($distances, SORT_NUMERIC);
    }

    /**
     * @param array<int, float> $distances
     */
    private function forgetDistance(array &$distances, float $distance): void
    {
        $index = array_search($distance, $distances, false);
        if ($index === false) {
            return;
        }

        array_splice($distances, (int) $index, 1);
    }
}
