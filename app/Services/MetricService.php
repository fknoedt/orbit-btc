<?php

namespace App\Services;

use App\Models\Metric;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

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
}
