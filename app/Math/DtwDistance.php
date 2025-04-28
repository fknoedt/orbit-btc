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
     * @return float DTW distance
     * @throws MathException If series are empty or have different lengths
     */
    function distance(array $series1, array $series2, ?int $window = null, bool $normalize = false): float
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

        // Initialize cost matrix with infinity
        $costMatrix = array_fill(0, $n + 1, array_fill(0, $n + 1, INF));

        // Set starting point
        $costMatrix[0][0] = 0.0;

        // Fill cost matrix
        for ($i = 1; $i <= $n; $i++) {
            // Apply Sakoe-Chiba window constraint
            $jStart = max(1, $window !== null ? $i - $window : 1);
            $jEnd = min($n, $window !== null ? $i + $window : $n);

            for ($j = $jStart; $j <= $jEnd; $j++) {
                // Local cost (squared difference, as in tslearn)
                $cost = pow($series1[$i - 1] - $series2[$j - 1], 2);

                // Minimum cost from previous steps
                $minCost = min(
                    $costMatrix[$i - 1][$j],     // Insertion
                    $costMatrix[$i][$j - 1],     // Deletion
                    $costMatrix[$i - 1][$j - 1]  // Match
                );

                // Cumulative cost
                $costMatrix[$i][$j] = $cost + $minCost;
            }
        }

        // Return DTW distance (square root of final cost, as in tslearn)
        return sqrt($costMatrix[$n][$n]);
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
