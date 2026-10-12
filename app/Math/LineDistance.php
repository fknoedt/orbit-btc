<?php

namespace App\Math;

use App\Exceptions\MathException;

class LineDistance
{
    /**
     * Day-by-day distance between a percent-from-start line and a price window.
     * The price window is measured from its own first price, so a valley stays a valley.
     *
     * @param array $query Percent change from the selected window's first price
     * @param array $prices Candidate prices, same length as $query, read from $offset
     * @param float|null $giveUpAt Stop once the line is already farther than this distance
     * @return float|null Distance, or null when $giveUpAt was exceeded before the last day
     * @throws MathException If the series are empty or the window does not fit
     */
    function distance(array $query, array $prices, int $offset = 0, ?float $giveUpAt = null): ?float
    {
        $n = count($query);
        if ($n === 0 || $offset < 0 || $offset + $n > count($prices)) {
            throw new MathException('Series must be non-empty and have equal lengths');
        }

        $base = $prices[$offset];
        if ($base == 0.0) {
            throw new MathException('Cannot measure a window that starts at zero');
        }

        $giveUpSquared = $giveUpAt !== null ? $giveUpAt * $giveUpAt : null;
        $sum = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $level = ($prices[$offset + $i] / $base) - 1;
            $difference = $query[$i] - $level;
            $sum += $difference * $difference;

            // The slack keeps a line that merely ties the cap from being dropped by rounding.
            if ($giveUpSquared !== null && $i < $n - 1 && $sum > $giveUpSquared + 1e-10) {
                return null;
            }
        }

        return sqrt($sum);
    }
}
