<?php

namespace App\Services;

use App\Exceptions\DailyPriceStatsException;
use App\Models\DailyPrice;

class DailyStatsService
{
    public function fillStats(array $data, bool $force): int
    {
        $fillData = [];
        $columnsToUpdate = [];
        foreach ($data as $day => $values) {
            if (empty($columnsToUpdate)) {
                $columnsToUpdate = array_keys($values);
            }
            $fillData[$day] = $values;
        }

        // fetch all daily_princes in the given interval..
        $start = array_key_first($fillData);
        $end = array_key_last($fillData);

        $query = DailyPrice::where('date', '>=', $start)->where('date', '<=', $end);

        // ...and, when not forcing, restrict by records with any of the columns to be updated as null
        if (!$force) {
            $query->where(function ($query) use ($columnsToUpdate) {
                foreach ($columnsToUpdate as $column) {
                    $query->orWhereNull($column);
                }
            });
        }

        $pricesSaved = 0;
        foreach ($query->get() as $dailyPrice) {
            if (empty($fillData[$dailyPrice->date])) {
                throw new DailyPriceStatsException(
                    "Missing \$fillingData[{$dailyPrice->date}] when filling daily_prices"
                );
            }
            foreach ($columnsToUpdate as $column) {
                // not forcing and column is not null: don't update
                if (! $force && ! empty($dailyPrice->{$column})) {
                    continue;
                }
                $dailyPrice->{$column} = $fillData[$dailyPrice->date][$column];
            }
            $dailyPrice->save();
            $pricesSaved++;
        }

        return $pricesSaved;
    }
}
