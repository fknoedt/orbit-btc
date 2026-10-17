<?php

namespace App\Services;

use App\Models\DailyPrice;
use App\Models\Metric;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class MetricService
{
    protected ?Collection $allMetricsByKey = null;

    /**
     * Using cache, return all Metrics indexed by their IDs
     */
    public function getAllMetricsKeyById(bool $includeInactive = false): Collection
    {
        if ($includeInactive) {
            return Cache::remember('all-metrics-with-trash', (new Carbon())->endOfDay(), function () {
                return Metric::withTrashed()->get()->keyBy('id');
            });
        }

        return Cache::remember('all-metrics', (new Carbon())->endOfDay(), function () {
            return Metric::get()->keyBy('id');
        });
    }

    public function getAllMetricsKeyByColumnName(
        bool $includeInactive = false,
        bool $withDataSource = false
    ): array
    {
        $suffix = $withDataSource ? '-with-data-source' : '';
        $cacheKey = $includeInactive
            ? "all-metrics-with-trash{$suffix}"
            : "all-metrics{$suffix}";

        return Cache::remember(
            $cacheKey,
            now()->endOfDay(),
            function () use ($includeInactive, $withDataSource) {
                $query = Metric::query();

                if ($includeInactive) {
                    $query->withTrashed();
                }

                if ($withDataSource) {
                    $query->with('dataSource');
                }

                return $query
                    ->get()
                    ->keyBy('column_name')
                    ->toArray();
            }
        );
    }

    /**
     * This method fetches all metrics into a singleton to avoid multiple queries if called repeatedly
     */
    public function getMetric(int $id, bool $findOrFail = false): Metric
    {
        if (isset($this->allMetricsByKey[$id])) {
            return $this->allMetricsByKey[$id];
        }

        if (! $this->allMetricsByKey) {
            $this->allMetricsByKey = $this->getAllMetricsKeyById(true);
        }

        if ($findOrFail && ! isset($this->allMetricsByKey[$id])) {
            throw new \InvalidArgumentException("Metric with id {$id} not found");
        }

        return $this->allMetricsByKey[$id];
    }

    /**
     * Point each metric at the first day its daily_prices column has a value.
     * A column with no values keeps the date already stored.
     *
     * @return list<array{column: string, from: ?string, to: string}>
     */
    public function syncDataLimitedAt(): array
    {
        $columns = Schema::getColumnListing('daily_prices');
        $changes = [];

        foreach (Metric::withTrashed()->orderBy('id')->get() as $metric) {
            if (! in_array($metric->column_name, $columns, true)) {
                continue;
            }

            $firstValuedDay = DailyPrice::whereNotNull($metric->column_name)->min('date');
            if (! $firstValuedDay) {
                continue;
            }

            $firstValuedDay = substr((string) $firstValuedDay, 0, 10);
            $stored = $metric->data_limited_at
                ? substr((string) $metric->data_limited_at, 0, 10)
                : null;

            if ($stored === $firstValuedDay) {
                continue;
            }

            $metric->data_limited_at = $firstValuedDay;
            $metric->save();
            $changes[] = [
                'column' => $metric->column_name,
                'from' => $stored,
                'to' => $firstValuedDay,
            ];
        }

        if ($changes !== []) {
            foreach ([
                'all-metrics',
                'all-metrics-with-trash',
                'all-metrics-with-data-source',
                'all-metrics-with-trash-with-data-source',
            ] as $cacheKey) {
                Cache::forget($cacheKey);
            }
        }

        return $changes;
    }
}
