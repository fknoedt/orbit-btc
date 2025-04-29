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
     * Using cache, return all Metrics indexed by their IDs. Params can be added ad-hoc.
     */
    public function getAllMetricsKeyById(): Collection
    {
        $cacheKey = __METHOD__;
        return Cache::remember($cacheKey, (new Carbon())->endOfDay(), function () {
            return Metric::get()->keyBy('id');
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
