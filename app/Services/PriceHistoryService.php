<?php

namespace App\Services;

use App\Adapters\AdapterFactory;
use App\Exceptions\DailyPriceStatsException;
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
        } while ($date->format('Y-m-d') <= $currentDateFormatted);

        return $pricesMissing;
    }

    public function fillMissingPricesFromInitialDay(OutputStyle $output): int
    {
        $initialDay = Carbon::createFromFormat($this->systemDateFormat, $this->firstAvailableDate);
        $output->writeln(
            'Filling prices since bitcoin first available price (' . $initialDay->diffForHumans() . ') 🎢 🚀'
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
            'Filling prices since ' . $start
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

        if ($initialDate->diffInDays($endDate) === 0) {
            $initialDate->subDay();
        }

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

    public function updateRsi(string $since = null, int $period = 14): int
    {
        // Determine the starting date for RSI calculation
        if (! $since) {
            $since = DailyPrice::getLastEmptyRSIDay();

            if (! $since) {
                throw new DailyPriceStatsException("Could not fetch DailyPrices on " . __METHOD__);
            }
        }

        // Need at least period + 1 days to calculate price changes
        $startDate = Carbon::createFromFormat($this->systemDateFormat, $since)
            ->subDays($period + 1)
            ->format($this->systemDateFormat);

        // Fetch price data
        $prices = DailyPrice::where('date', '>=', $startDate)
            ->select(['date', 'close'])
            ->orderBy('date', 'asc')
            ->get()
            ->keyBy('date')
            ->toArray();

        // Ensure we have enough data
        if (count($prices) < $period + 1) {
            throw new DailyPriceStatsException("Not enough data to calculate RSI");
        }

        $fillStats = [];
        $gains = [];
        $losses = [];

        // Calculate price changes
        $dates = array_keys($prices);
        for ($i = 1; $i < count($dates); $i++) {
            $currentDate = $dates[$i];
            $prevDate = $dates[$i - 1];
            $change = $prices[$currentDate]['close'] - $prices[$prevDate]['close'];
            $gains[$currentDate] = $change > 0 ? $change : 0;
            $losses[$currentDate] = $change < 0 ? abs($change) : 0;
        }

        // Process RSI for each day after the initial period
        for ($i = $period; $i < count($dates); $i++) {
            $currentDate = $dates[$i];

            // Get the last $period price changes
            $periodGains = array_slice($gains, $i - $period, $period, true);
            $periodLosses = array_slice($losses, $i - $period, $period, true);

            // Calculate average gain and loss
            $avgGain = array_sum($periodGains) / $period;
            $avgLoss = array_sum($periodLosses) / $period;

            // Avoid division by zero
            if ($avgLoss == 0) {
                $rsi = $avgGain == 0 ? 50 : 100; // If no losses, RSI is 100; if no gains/losses, use neutral 50
            } else {
                $rs = $avgGain / $avgLoss;
                $rsi = 100 - (100 / (1 + $rs));
            }

            $fillStats[$currentDate]['rsi'] = round($rsi, 2); // Round to 2 decimal places
        }

        return (new DailyStatsService())->fillStats($fillStats);
    }

    public function updateFuturePriceChange(string $since = null, OutputStyle $output = null, bool $dryRun = false): int
    {
        if (! $output) {
            throw new \InvalidArgumentException('$output is necessary');
        }

        if (! $since) {
            $since = DailyPrice::getLastEmptyFuturePriceDay();
            // no empty days
            if (! $since) {
                return 0;
            }
        }

        // cache all days needed
        $daysUpdated = 0;
        $today = Carbon::now();
        $startDay = Carbon::parse($since);
        $priceService = new DailyPriceService();
        $priceService->getAllDailyPricesKeyByDate($startDay, $today, true);

        $currentDay = $startDay->copy();

        do {
            $dailyPrice = $priceService->getDailyPrice($currentDay->format('Y-m-d'));
            if (! $dailyPrice) {
                $output->writeln(
                    "Day not found: " . $currentDay->format('Y-m-d'),
                    $output::VERBOSITY_VERBOSE
                );
                $currentDay->addDay();
                continue;
            }

            if (
                empty($dailyPrice->price_change_1d) ||
                empty($dailyPrice->price_change_3d) ||
                empty($dailyPrice->price_change_5d) ||
                empty($dailyPrice->price_change_10d) ||
                empty($dailyPrice->price_change_14d) ||
                empty($dailyPrice->price_change_30d)
            ) {
                foreach ([1, 3, 5, 10, 14, 30] as $numberOfDays) {
                    $dailyPricePlusN = $priceService->getDailyPrice(
                        $currentDay->copy()->addDays($numberOfDays)->format('Y-m-d'),
                        false,
                        0
                    );

                    if (! $dailyPricePlusN) {
                        // future day
                        if ($currentDay->copy()->addDays($numberOfDays) > Carbon::now()) {
                            $value = null;
                        } else { // missing day
                            $value = 0;
                        }
                    } else {
                        $value = ($dailyPricePlusN->close - $dailyPrice->close) / $dailyPrice->close * 100;
                    }

                    $columnName = 'price_change_' . $numberOfDays . 'd';
                    $dailyPrice->{$columnName} = $value;
                }
                if (! $dryRun) {
                    $dailyPrice->save();
                    $action = 'saved';
                } else {
                    $action = 'not saved';
                }
                $output->writeln(
                    'DailyPrice ' . $dailyPrice->date . ' ' . $action,
                    $output::VERBOSITY_VERBOSE
                );
                $daysUpdated++;

            }

            $currentDay->addDay();
        } while ($currentDay <= $today);

        return $daysUpdated;
    }
}
