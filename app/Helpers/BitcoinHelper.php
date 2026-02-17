<?php

namespace App\Helpers;

use InvalidArgumentException;

class BitcoinHelper
{
    public static function blockHeightToDate(int $height): string
    {
        // Genesis block timestamp (Unix epoch): 2009-01-03 18:15:05 UTC
        $genesisTimestamp = 1231006505;

        // Average block time in seconds (10 minutes)
        $averageBlockTime = 600;

        // Estimated timestamp
        $estimatedTimestamp = $genesisTimestamp + ($height * $averageBlockTime);

        return date('Y-m-d', $estimatedTimestamp);
    }

    public static function dateToBlockHeight(string $date): int
    {
        // Genesis block timestamp (Unix epoch): 2009-01-03 18:15:05 UTC
        $genesisTimestamp = 1231006505;

        // Average block time in seconds (10 minutes)
        $averageBlockTime = 600;

        if ($date < '2009-01-03') {
            throw new InvalidArgumentException("Date must be >= 2009-01-03");
        }

        // Convert date to midnight timestamp (start of day)
        $dateTimestamp = strtotime($date . ' 00:00:00 UTC');

        if ($dateTimestamp === false) {
            throw new InvalidArgumentException("Invalid date format");
        }

        // Estimated height (floor to get the last block before or on that day)
        $estimatedHeight = (int) floor(($dateTimestamp - $genesisTimestamp) / $averageBlockTime);

        // Ensure non-negative
        return max(0, $estimatedHeight);
    }
}
