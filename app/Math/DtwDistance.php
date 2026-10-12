<?php

namespace App\Math;

use App\Exceptions\MathException;

class DtwDistance
{


    /**
     * Compute Dynamic Time Warping (DTW) distance between two equal-length time series.
     *
     * @param array $series1 First time series (array of numbers)
     * @param array $series2 Second time series (array of numbers)
     * @param int|null $window Optional Sakoe-Chiba window size (null for no constraint)
     * @param bool $normalize Whether to apply z-score normalization
     * @param float|null $giveUpAt Stop and return null once every alignment so far is already worse than this distance
     * @return float|null DTW distance, or null when $giveUpAt was exceeded
     * @throws MathException If series are empty or have different lengths
     */
    function distance(array $series1, array $series2, ?int $window = null, bool $normalize = false, ?float $giveUpAt = null): ?float
    {
        // Validate input
        $n = count($series1);
        if ($n === 0 || $n !== count($series2)) {
            throw new MathException('Series must be non-empty and have equal lengths');
        }

        // Normalize if requested
        if ($normalize) {
            $series1 = $this->normalizeSeries($series1);
            $series2 = $this->normalizeSeries($series2);
        }

        // Only the previous row is needed. The same two rows are reused for every
        // step. Costs never go down, so a row that is already worse than $giveUpAt
        // cannot produce a better final distance.
        $previous = array_fill(0, $n + 1, INF);
        $current = array_fill(0, $n + 1, INF);
        $previous[0] = 0.0;

        for ($i = 1; $i <= $n; $i++) {
            $jStart = max(1, $window !== null ? $i - $window : 1);
            $jEnd = min($n, $window !== null ? $i + $window : $n);

            if ($window !== null) {
                for ($k = 0; $k <= $n; $k++) {
                    $current[$k] = INF;
                }
            } else {
                $current[0] = INF;
            }

            $rowMin = INF;
            for ($j = $jStart; $j <= $jEnd; $j++) {
                $difference = $series1[$i - 1] - $series2[$j - 1];
                $current[$j] = ($difference * $difference) + min(
                    $previous[$j],
                    $current[$j - 1],
                    $previous[$j - 1]
                );

                if ($current[$j] < $rowMin) {
                    $rowMin = $current[$j];
                }
            }

            if ($giveUpAt !== null && $rowMin < INF && sqrt($rowMin) > $giveUpAt) {
                return null;
            }

            [$previous, $current] = [$current, $previous];
        }

        return sqrt($previous[$n]);
    }

    /**
     * Normalize a time series using z-score normalization.
     *
     * @param array $series Time series (array of numbers)
     * @return array Normalized series
     */
    function normalizeSeries(array $series): array
    {
        $n = count($series);
        if ($n === 0) {
            return [];
        }

        // Compute mean
        $mean = array_sum($series) / $n;

        // Compute standard deviation
        $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $series)) / $n;
        $stdDev = sqrt($variance);

        // Avoid division by zero
        if ($stdDev == 0) {
            return array_fill(0, $n, 0.0);
        }

        // Normalize
        return array_map(fn($x) => ($x - $mean) / $stdDev, $series);
    }
}
