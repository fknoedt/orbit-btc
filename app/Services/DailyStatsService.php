<?php

namespace App\Services;

use App\Exceptions\DailyPriceStatsException;
use App\Models\DailyPrice;

class DailyStatsService
{
    /**
     * Receives $data dataset in the following format (has to be ordered by date ASC):
     * [$date][$columnName] => $value -- where $date is in Y-m-d format and $columnName matches daily_prices.column_name
     * $value will be set for each of the daily_prices matching the given data set (daily_prices.date x $date)
     * @param bool $force will update records even when $columnName is not null
     * @throws DailyPriceStatsException
     */
    public function fillStats(array $data, bool $force = false): int
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
                // column might not be set when records are heterogeneous (WARNING: first row needs all columns)
                if (isset($fillData[$dailyPrice->date][$column])) {
                    $dailyPrice->{$column} = $fillData[$dailyPrice->date][$column];
                }
            }
            $dailyPrice->save();
            $pricesSaved++;
        }

        return $pricesSaved;
    }

    /**
     * Fill intermittent null values based on the last (ordered chronologically asc) value found
     * Created for `difficulty` (every two weeks)
     * @param string $column
     * @param string|null $since
     * @return int
     */
    public function fillForward(string $column, string $since = null): int
    {
        $totalUpdates = 0;
        $query = DailyPrice::query();

        if ($since) {
            $query->where('date', '>=', $since);
        }

        $lastValue = null;
        foreach ($query->orderBy('date', 'asc')->get() as $dailyPrice) {
            $currentValue = $dailyPrice->{$column};
            if ($currentValue !== $lastValue && ! is_null($currentValue)) {
                $lastValue = $currentValue;
            }
            if (is_null($currentValue) && ! is_null($lastValue)) {
                $dailyPrice->{$column} = $lastValue;
                $dailyPrice->save();
                $totalUpdates++;
            }
        }

        return $totalUpdates;
    }
}
