<?php

namespace App\Services;

use App\Models\DailyPrice;
use App\Models\Frequency;
use App\Models\LevelingMove;
use App\Models\Metric;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class LevelingMoveService
{
    /** A shorter history is kept as the last saved percent, or left empty. */
    public const int MIN_SAMPLES = 30;

    /**
     * Median absolute percent change for every metric and interval.
     * Suggestions use it to level multipliers. Scoring does not.
     * The percent is the same move scoring uses: the value versus the value
     * that many calendar days earlier.
     *
     * @return array{updated: int, short: int, kept: int, skipped: int}
     */
    public function update(bool $dryRun = false): array
    {
        $updated = 0;
        $short = 0;
        $kept = 0;
        $skipped = 0;

        $frequencies = Frequency::query()->orderBy('number_of_days')->get();
        $metrics = Metric::withTrashed()
            ->whereNotNull('column_name')
            ->get(['id', 'column_name']);

        $priceColumns = array_flip(Schema::getColumnListing('daily_prices'));
        $columnsByMetric = [];

        foreach ($metrics as $metric) {
            $column = (string) $metric->column_name;
            if (! $this->columnIsSafe($column) || ! isset($priceColumns[$column])) {
                $skipped++;
                continue;
            }
            $columnsByMetric[$metric->id] = $column;
        }

        if ($columnsByMetric === [] || $frequencies->isEmpty()) {
            return compact('updated', 'short', 'kept', 'skipped');
        }

        $uniqueColumns = array_values(array_unique($columnsByMetric));
        $rows = DailyPrice::query()
            ->orderBy('date')
            ->get(array_merge(['date'], $uniqueColumns));

        $series = [];
        $lastPriceDate = null;
        foreach ($rows as $row) {
            $date = substr((string) $row->date, 0, 10);
            $lastPriceDate = $date;
            foreach ($uniqueColumns as $column) {
                $series[$column][$date] = $row->{$column};
            }
        }

        if ($lastPriceDate === null) {
            return compact('updated', 'short', 'kept', 'skipped');
        }

        $dates = array_keys($series[$uniqueColumns[0]]);
        $referenceDates = [];
        foreach ($frequencies as $frequency) {
            $days = (int) $frequency->number_of_days;
            if (isset($referenceDates[$days])) {
                continue;
            }
            $map = [];
            foreach ($dates as $date) {
                $map[$date] = date('Y-m-d', strtotime("-{$days} days", strtotime($date)));
            }
            $referenceDates[$days] = $map;
        }

        $existing = LevelingMove::query()->get()->keyBy(
            fn (LevelingMove $move) => $move->metric_id . '-' . $move->frequency_id
        );
        $medians = [];
        $writes = [];
        $now = Carbon::now()->toDateTimeString();

        foreach ($columnsByMetric as $metricId => $column) {
            foreach ($frequencies as $frequency) {
                $days = (int) $frequency->number_of_days;
                $cacheKey = $column . '|' . $days;
                if (! array_key_exists($cacheKey, $medians)) {
                    $medians[$cacheKey] = $this->medianAbsPercent($series[$column], $referenceDates[$days]);
                }

                $result = $medians[$cacheKey];
                $key = $metricId . '-' . $frequency->id;
                $canStore = $result !== null
                    && $result['samples'] >= self::MIN_SAMPLES
                    && $result['median'] > 0;

                if (! $canStore) {
                    if ($existing->has($key)) {
                        $kept++;
                    } else {
                        $short++;
                    }
                    continue;
                }

                $updated++;
                if ($dryRun) {
                    continue;
                }

                $writes[] = [
                    'metric_id' => $metricId,
                    'frequency_id' => $frequency->id,
                    'abs_percent' => $result['median'],
                    'samples' => $result['samples'],
                    'price_date' => $lastPriceDate,
                    'calculated_at' => $now,
                ];
            }
        }

        if ($writes !== []) {
            LevelingMove::query()->upsert(
                $writes,
                ['metric_id', 'frequency_id'],
                ['abs_percent', 'samples', 'price_date', 'calculated_at']
            );
        }

        return compact('updated', 'short', 'kept', 'skipped');
    }

    /**
     * A current value of 0 still counts. A missing day or a zero reference does not.
     *
     * @param  array<string, mixed>  $values  date => value
     * @param  array<string, string>  $referenceDates  date => reference date
     * @return array{median: float, samples: int}|null
     */
    private function medianAbsPercent(array $values, array $referenceDates): ?array
    {
        $samples = [];
        foreach ($referenceDates as $date => $referenceDate) {
            $current = $values[$date] ?? null;
            $reference = $values[$referenceDate] ?? null;
            if ($current === null || $current === '' || $reference === null || $reference === '' || (float) $reference == 0.0) {
                continue;
            }

            $samples[] = abs((1 - ((float) $current / (float) $reference)) * 100);
        }

        $median = $this->median($samples);
        if ($median === null) {
            return null;
        }

        return [
            'median' => $median,
            'samples' => count($samples),
        ];
    }

    /** The middle percent. An even count averages the two middle values. */
    public function median(array $values): ?float
    {
        $count = count($values);
        if ($count === 0) {
            return null;
        }

        sort($values, SORT_NUMERIC);
        $middle = intdiv($count, 2);
        if ($count % 2 === 1) {
            return (float) $values[$middle];
        }

        return ((float) $values[$middle - 1] + (float) $values[$middle]) / 2;
    }

    /** Allows only a plain column name, so a metric cannot point the query at another table. */
    private function columnIsSafe(string $column): bool
    {
        return (bool) preg_match('/^[a-z][a-z0-9_]*$/', $column);
    }
}
