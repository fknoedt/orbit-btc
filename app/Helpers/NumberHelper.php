<?php

namespace App\Helpers;

use Illuminate\Support\Number;

class NumberHelper
{
    public static function formatWithSuffix(float $number, int $precision = 2): string
    {
        $suffixes = [
            1_000_000_000_000_000_000_000 => 'Z', // Zetta
            1_000_000_000_000_000_000 => 'E',     // Exa
            1_000_000_000_000_000 => 'P',         // Peta
            1_000_000_000_000 => 'T',             // Tera
            1_000_000_000 => 'B',                 // Billion
            1_000_000 => 'M',                     // Million
        ];

        foreach ($suffixes as $threshold => $suffix) {
            if ($number >= $threshold) {
                $formatted = $number / $threshold;
                return Number::format($formatted, $precision) . $suffix;
            }
        }

        return Number::format($number, $precision);
    }

    /**
     * Return the number of digits, not considering decimals, the given float value has
     * examples: 3.7 => 1; 9.0 => 1; 19.344 => 2; 421.5 => 3; 152555.13 => 6 and so on
     */
    public static function getFloatMagnitude(float $number): int
    {
        $intValue = (int) abs($number);
        $stringValue = (string) $intValue;

        return strlen($stringValue);
    }
}
