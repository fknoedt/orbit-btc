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
}
