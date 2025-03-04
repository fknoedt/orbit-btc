<?php

namespace App\Services;

use App\Adapters\AdapterFactory;
use App\Models\DailyPrice;
use Carbon\Carbon;
use Illuminate\Console\OutputStyle;

class PriceHistoryService
{
    private array $persistedPrices = [];
    private string $systemDateFormat;
    private string $systemDatetimeFormat;
    private string $firstAvailableDate;

    public function __construct()
    {
        $this->systemDateFormat = config('btc.date_format');
        $this->systemDatetimeFormat = config('btc.datetime_format');
        $this->firstAvailableDate = config('btc.first_available_date');
    }

    public function getPersistedPrices(array $params = []): array
    {
        $query = DailyPrice::query();

        foreach ($params as $param) {
            $query->where(
                $param['column'],
                $param['operator'],
                $param['value'],
            );
        }

        return $query->orderBy('date', 'asc')->get()->keyBy('date')->toArray();
    }

    public function loadPersistedPrices(): array
    {
        return $this->persistedPrices = $this->getPersistedPrices();
    }

    public function pricesMissing(Carbon $since = null): array
    {
        /*if (empty($this->persistedPrices)) {
            throw new \RuntimeException("Prices not loaded. Call `loadPersistedPrices()` first.");
        }*/

        $pricesMissing = [];
        $date = $since ?? Carbon::createFromFormat($this->systemDateFormat, $this->firstAvailableDate);
        $currentDateFormatted = date($this->systemDateFormat);
        $initialMissingPricesDate = Carbon::createFromFormat(
            $this->systemDatetimeFormat,
            config('btc.initial_missing_prices_datetime')
        );
        $finalMissingPricesDate = Carbon::createFromFormat(
            $this->systemDatetimeFormat,
            config('btc.final_missing_prices_datetime')
        );

        do {
            $dateFormatted = $date->format($this->systemDateFormat);
            if (
                // price is not persisted
                ! isset($this->persistedPrices[$dateFormatted])
                &&
                // and it's not a known missing price
                ! $date->betweenIncluded($initialMissingPricesDate, $finalMissingPricesDate)
            ) {
                $pricesMissing[$dateFormatted] = true;
            }
            $date = $date->addDay();
        } while ($date <= $currentDateFormatted);

        return $pricesMissing;
    }

    public function fillMissingPricesFromInitialDay(OutputStyle $output): int
    {
        $initialDay = Carbon::createFromFormat($this->systemDateFormat, $this->firstAvailableDate);
        $output->writeln(
            'Filling princes since bitcoin first available price (' . $initialDay->diffForHumans() . ') 🎢 🚀'
        );

        return $this->fillMissingPrices(
            $initialDay,
            $output
        );
    }

    public function fillMissingPricesSince(OutputStyle $output, string $start): int
    {
        $initialDay = Carbon::createFromFormat($this->systemDateFormat, $start);
        $output->writeln(
            'Filling princes since ' . $start
        );

        return $this->fillMissingPrices(
            $initialDay,
            $output
        );
    }

    /**
     * Check every day with no price since the given date, fetch and create it
     */
    public function fillMissingPrices(Carbon $since, OutputStyle $output, string $client = null): int
    {
        $pricesCreated = 0;

        // load all existing prices in the database
        $this->loadPersistedPrices();

        // check which days are missing
        $pricesMissing = $this->pricesMissing($since);

        if (empty($pricesMissing)) {
            return 0;
        }

        $initialDate = Carbon::createFromFormat($this->systemDateFormat, array_key_first($pricesMissing));
        $endDate = Carbon::createFromFormat($this->systemDateFormat, array_key_last($pricesMissing));

        $clientAdapter = AdapterFactory::getAdapter($client);

        $apiPrices = $clientAdapter->getDailyPriceInterval($initialDate, $endDate);

        if (empty($apiPrices)) {
            throw new \RuntimeException(
                sprintf(
                    'Could not fetch price interval (%s, %s) from %s',
                    $initialDate->format($this->systemDateFormat),
                    $initialDate->format($this->systemDateFormat),
                    $clientAdapter->getClientName()
                )
            );
        }

        $output->writeln(
            count($apiPrices) . ' daily price(s) fetched from ' . $clientAdapter->getClientName()
        );

        foreach ($apiPrices as $date => $dailyPrice) {
            // price is not missing: skip
            if (! isset($pricesMissing[$date])) {
                continue;
            }

            $dailyPrice->save();

            $output->writeln(
                sprintf(
                    '%s: %s',
                    $date,
                    $dailyPrice->close
                )
            );

            $pricesCreated++;
            unset($pricesMissing[$date]);
        }

        if (! empty($pricesMissing)) {
            $output->writeln("One or more missing prices were not updated: " . json_encode($pricesMissing));
        }

        return $pricesCreated;
    }

    /**
     * Mayer Multiple is the average of the last 200 days' price
     * @see https://charts.bitbo.io/mayer-multiple-bars/
     */
    public function updateMayerMultiple(string $since = null): int
    {
        if (! $since) {
            $since = DailyPrice::getLastEmptyMayerMultipleDay();

            // no empty days
            if (! $since) {
                return 0;
            }
        }

        $startDate = Carbon::createFromFormat($this->systemDateFormat, $since)
            ->subDays(200)->format($this->systemDateFormat);

        $prices = DailyPrice::where('date', '>=', $startDate)
            ->select(['date', 'close'])
            ->orderBy('date', 'asc')
            ->get()
            ->keyBy('date')
            ->toArray();

        $last200prices = array_slice($prices, 0, 200, true);
        $prices = array_slice($prices, 200);
        $fillStats = [];

        do {
            $nextDay = array_shift($prices);
            $average = array_sum(array_column($last200prices, 'close')) / 200;
            $mayerMultiple = $nextDay['close'] / $average;

            $fillStats[$nextDay['date']]['mayer_multiple'] = $mayerMultiple;

            array_shift($last200prices);
            $last200prices[$nextDay['date']] = $nextDay;
        } while (! empty($prices));

        return (new DailyStatsService())->fillStats($fillStats);
    }
}
