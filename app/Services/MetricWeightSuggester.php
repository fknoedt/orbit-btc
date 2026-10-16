<?php

namespace App\Services;

class MetricWeightSuggester
{
    public const float MIN_WEIGHT = 0.1;

    public const float MAX_WEIGHT = 100;

    /**
     * Scale quieter metrics so a typical percent lands at about the same points.
     * The metric with the largest stored percent keeps its multiplier. Others rise to match it.
     *
     * @param  array<string, array{percent: ?float, weight: float}>  $rows
     * @return array<string, float>
     */
    public function desiredWeights(array $rows): array
    {
        $usable = [];
        foreach ($rows as $key => $row) {
            $percent = $row['percent'] ?? null;
            if ($percent === null || $percent <= 0) {
                continue;
            }
            $usable[$key] = [
                'percent' => (float) $percent,
                'weight' => (float) $row['weight'],
            ];
        }

        if (count($usable) < 2) {
            return [];
        }

        $loudestPercent = max(array_column($usable, 'percent'));
        $base = 1.0;
        foreach ($usable as $row) {
            if (abs($row['percent'] - $loudestPercent) < 0.00001) {
                $base = $row['weight'] > 0 ? $row['weight'] : 1.0;
                break;
            }
        }

        $desired = [];
        foreach ($usable as $key => $row) {
            $desired[$key] = $this->clamp($base * $loudestPercent / $row['percent']);
        }

        return $desired;
    }

    /** The multiplier that would level this percent with the loudest metric, before the 0.1 to 100 limit. */
    public function rawWeight(float $baseWeight, float $loudestPercent, float $percent): float
    {
        if ($percent <= 0) {
            return $baseWeight;
        }

        return $baseWeight * $loudestPercent / $percent;
    }

    /** Keeps a multiplier between 0.1 and 100, rounded to one decimal. */
    public function clamp(float $weight): float
    {
        return max(self::MIN_WEIGHT, min(self::MAX_WEIGHT, round($weight, 1)));
    }

    /** One decimal, with a trailing zero removed, so 1.0 reads as 1. */
    public function formatNumber(float $value): string
    {
        $text = rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.');

        return $text === '' ? '0' : $text;
    }

    /** The stored multiplier, without rounding it to one decimal. 1.34 stays 1.34. */
    public function formatExact(float $value): string
    {
        $text = rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');

        return $text === '' ? '0' : $text;
    }

    /** One decimal kept on purpose, so a suggestion reads 6.2 or 10.0. */
    public function formatDecimal(float $value): string
    {
        return number_format(round($value, 1), 1, '.', '');
    }

    /** The hint under a multiplier. Nothing when the number already levels, unless 100 is as high as it can go. */
    public function suggestionLine(float $suggested, bool $matches, bool $capped): ?string
    {
        if ($matches) {
            return $capped ? '100 is the highest this can go' : null;
        }

        return 'Try ' . $this->formatDecimal($suggested) . ' to level the metrics';
    }
}
