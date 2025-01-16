<?php

namespace App\Services;

use App\Adapters\AdapterFactory;
use App\Exceptions\AdapterException;
use App\Models\DailyPrice;
use Carbon\Carbon;
use Illuminate\Console\OutputStyle;

class PriceHistoryService
{
    private array $persistedPrices = [];
    private string $systemDateFormat;
    private string $systemDatetimeFormat;
    private string $genesisDate;

    public function __construct()
    {
        $this->systemDateFormat = config('btc.date_format');
        $this->systemDatetimeFormat = config('btc.datetime_format');
        $this->genesisDate = config('btc.genesis_date');
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

    public function loadPersistedPrices(): void
    {
        $this->persistedPrices = $this->getPersistedPrices();
    }

    public function pricesMissing(Carbon $since = null): array
    {
        /*if (empty($this->persistedPrices)) {
            throw new \RuntimeException("Prices not loaded. Call `loadPersistedPrices()` first.");
        }*/

        $pricesMissing = [];
        $date = $since ?? Carbon::createFromFormat($this->systemDateFormat, $this->genesisDate);
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

    public function fillMissingPricesFromGenesis(OutputStyle $output, string $client = null): int
    {
        $genesis = Carbon::createFromFormat($this->systemDateFormat, $this->genesisDate);
        $output->writeln(
            'Filling princes since bitcoin genesis, which was ' . $genesis->diffForHumans() . ' 🎢 🚀'
        );

        return $this->fillMissingPrices(
            $genesis,
            $output,
            $client
        );
    }

    /**
     * Check every day with no price since the given date, fetch and create it
     */
    public function fillMissingPrices(Carbon $since, OutputStyle $output, string $client = null): int
    {
        $pricesCreated = 0;
        $prices = [];

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
        $dataSourceId = $clientAdapter->getDataSourceId();

        $apiPrices = $clientAdapter->getBtcPriceInterval($initialDate, $endDate);

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

        foreach ($apiPrices as $date => $price) {
            // price is not missing: skip
            if (! isset($pricesMissing[$date])) {
                continue;
            }

            $prices[] = [
                'date' => $date,
                'data_source_id' => $dataSourceId,
                'price' => $price,
            ];

            $output->writeln(
                sprintf(
                    '%s: %s',
                    $date,
                    $price
                )
            );

            $pricesCreated++;
            unset($pricesMissing[$date]);
        }

        if (! empty($pricesMissing)) {
            dump("One or more missing prices were not updated: " . json_encode($pricesMissing));
        }

        // insert all at once
        DailyPrice::insert($prices);

        return $pricesCreated;
    }
}
