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

    public function getAllMetricsKeyByColumnName(bool $includeInactive = false): array
    {
        if ($includeInactive) {
            return Cache::remember('all-metrics-with-trash', (new Carbon())->endOfDay(), function () {
                return Metric::withTrashed()->get()->keyBy('column_name')->toArray();
            });
        }

        return Cache::remember('all-metrics', (new Carbon())->endOfDay(), function () {
            return Metric::get()->keyBy('column_name')->toArray();
        });
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
            $this->allMetricsByKey = $this->getAllMetricsKeyById();
        }

        if ($findOrFail && ! isset($this->allMetricsByKey[$id])) {
            throw new \InvalidArgumentException("Metric with id {$id} not found");
        }

        return $this->allMetricsByKey[$id];
    }
}
