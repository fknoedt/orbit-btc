<?php

namespace Database\Seeders;

use App\Adapters\AdapterFactory;
use App\Models\DailyPrice;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class InitialDailyPrices extends Seeder
{
    /**
     * Prices from New Liberty Standard, the first Bitcoin exchange, on WebArchive
     * @see https://bitcoin.zorinaq.com/price/
     * @see https://web.archive.org/web/20100428024753/http://newlibertystandard.wetpaint.com/page/2009+Exchange+Rate
     * @see https://web.archive.org/web/20100301174241/http://newlibertystandard.wetpaint.com/page/Exchange+Rate
     * @var array
     */
    private array $dailyUsdToBtc = [
        '2010-03-02' => 182.74,
        '2010-03-01' => 185.29,
        '2010-02-28' => 188.75,
        '2010-02-27' => 193.49,
        '2010-02-26' => 195.92,
        '2010-02-25' => 198.84,
        '2010-02-24' => 201.01,
        '2010-02-23' => 206.20,
        '2010-02-22' => 212.49,
        '2010-02-21' => 214.52,
        '2010-02-20' => 219.33,
        '2010-02-19' => 226.50,
        '2010-02-18' => 232.18,
        '2010-02-17' => 234.68,
        '2010-02-16' => 237.90,
        '2010-02-15' => 241.12,
        '2010-02-14' => 246.77,
        '2010-02-13' => 249.71,
        '2010-02-12' => 255.72,
        '2010-02-11' => 259.97,
        '2010-02-10' => 259.28,
        '2010-02-09' => 262.98,
        '2010-02-08' => 263.52,
        '2010-02-07' => 267.73,
        '2010-02-06' => 272.31,
        '2010-02-05' => 273.38,
        '2010-02-04' => 280.09,
        '2010-02-03' => 286.05,
        '2010-02-02' => 297.29,
        '2010-02-01' => 303.33,
        '2010-01-31' => 303.19,
        '2010-01-30' => 308.57,
        '2010-01-29' => 306.81,
        '2010-01-28' => 304.77,
        '2010-01-27' => 313.94,
        '2010-01-26' => 297.08,
        '2010-01-25' => 307.31,
        '2010-01-24' => 307.50,
        '2010-01-23' => 311.42,
        '2010-01-22' => 328.06,
        '2010-01-21' => 345.62,
        '2010-01-20' => 342.54,
        '2010-01-19' => 365.52,
        '2010-01-18' => 339.45,
        '2010-01-17' => 357.14,
        '2010-01-16' => 353.26,
        '2010-01-15' => 1392.33,
        '2010-01-14' => 1392.33,
        '2010-01-13' => 1392.33,
        '2010-01-12' => 1392.33,
        '2010-01-11' => 1392.33,
        '2010-01-10' => 1392.33,
        '2010-01-09' => 1392.33,
        '2010-01-08' => 1392.33,
        '2010-01-07' => 1392.33,
        '2010-01-06' => 1392.33,
        '2010-01-05' => 1392.33,
        '2010-01-04' => 1392.33,
        '2010-01-03' => 1392.33,
        '2010-01-02' => 1392.33,
        '2010-01-01' => 1392.33,
        '2009-12-31' => 1451.83,
        '2009-12-30' => 1555.24,
        '2009-12-29' => 1578.77,
        '2009-12-28' => 1578.77,
        '2009-12-27' => 1578.77,
        '2009-12-26' => 1578.77,
        '2009-12-25' => 1578.77,
        '2009-12-24' => 1578.77,
        '2009-12-23' => 1578.77,
        '2009-12-22' => 1578.77,
        '2009-12-21' => 1594.63,
        '2009-12-20' => 1594.63,
        '2009-12-19' => 1586.70,
        '2009-12-18' => 1622.40,
        '2009-12-17' => 1630.33,
        '2009-12-16' => 1606.53,
        '2009-12-15' => 1626.37,
        '2009-12-14' => 1626.37,
        '2009-12-13' => 1618.43,
        '2009-12-12' => 1562.90,
        '2009-12-11' => 1503.40,
        '2009-12-10' => 1491.50,
        '2009-12-09' => 1455.80,
        '2009-12-08' => 1428.03,
        '2009-12-07' => 1392.33,
        '2009-12-06' => 1364.56,
        '2009-12-05' => 1336.80,
        '2009-12-04' => 1340.76,
        '2009-12-03' => 1297.13,
        '2009-12-02' => 1257.46,
        '2009-12-01' => 1233.66,
        '2009-11-30' => 1213.83,
        '2009-11-29' => 1205.89,
        '2009-11-28' => 1178.13,
        '2009-11-27' => 1162.26,
        '2009-11-26' => 1114.66,
        '2009-11-25' => 1110.69,
        '2009-11-24' => 1078.96,
        '2009-11-23' => 999.62,
        '2009-11-22' => 944.09,
        '2009-11-21' => 940.12,
        '2009-11-20' => 928.22,
        '2009-11-19' => 932.19,
        '2009-11-18' => 904.42,
        '2009-11-17' => 848.89,
        '2009-11-16' => 840.96,
        '2009-11-15' => 809.22,
        '2009-11-14' => 777.49,
        '2009-11-13' => 737.82,
        '2009-11-12' => 745.75,
        '2009-11-11' => 769.55,
        '2009-11-10' => 809.22,
        '2009-11-09' => 793.35,
        '2009-11-08' => 793.35,
        '2009-11-07' => 781.45,
        '2009-11-06' => 777.49,
        '2009-11-05' => 765.59,
        '2009-11-04' => 765.59,
        '2009-11-03' => 785.42,
        '2009-11-02' => 796.09,
        '2009-11-01' => 820.27,
        '2009-10-31' => 802.17,
        '2009-10-30' => 796.41,
        '2009-10-29' => 790.17,
        '2009-10-28' => 778.48,
        '2009-10-27' => 791.63,
        '2009-10-26' => 789.75,
        '2009-10-25' => 776.35,
        '2009-10-24' => 785.42,
        '2009-10-23' => 795.44,
        '2009-10-22' => 819.80,
        '2009-10-21' => 826.02,
        '2009-10-20' => 810.71,
        '2009-10-19' => 801.29,
        '2009-10-18' => 816.02,
        '2009-10-17' => 805.56,
        '2009-10-16' => 803.27,
        '2009-10-15' => 854.66,
        '2009-10-14' => 880.62,
        '2009-10-13' => 885.91,
        '2009-10-12' => 907.40,
        '2009-10-11' => 867.02,
        '2009-10-10' => 892.52,
        '2009-10-09' => 833.02,
        '2009-10-08' => 922.27,
        '2009-10-07' => 952.02,
        '2009-10-06' => 1130.53,
        '2009-10-05' => 1309.03
    ];

    private int $pricesPersisted = 0;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dateFormat = config('btc.date_format');

        $this->command->info(
            '== Seeding Initial Daily Prices (from 2009-10-05 to 2022-07-10) =='
        );

        // -- New Liberty -- //

        $this->command->info('Loading New Liberty data');
        ksort($this->dailyUsdToBtc);

        $minDate = null;
        foreach ($this->dailyUsdToBtc as $date => $value) {
            $this->persistPrice($date, (1 / $value), config('data.data_source.new_liberty_id'));
            $minDate = $minDate ?? $date;
        }

        $this->command->info(
            sprintf(
                '%s Prices, between %s and %s, created or updated from New Liberty hard-coded data',
                $this->pricesPersisted,
                $minDate,
                $date
            )
        );

        $newLibertyPricesPersisted = $this->pricesPersisted;

        // -- CoinDesk -- //

        $this->command->info('Loading CoinDesk data');

        $coindeskData = json_decode(
            file_get_contents(
                database_path() . DIRECTORY_SEPARATOR . 'raw-data' . DIRECTORY_SEPARATOR .
                'coindesk-daily-prices.json'
            ),
            true
        );

        $minDate = null;
        foreach ($coindeskData['bpi'] as $date => $value) {
            $this->persistPrice($date, $value, config('data.data_source.coindesk_id'));
            $minDate = $minDate ?? $date;
        }

        $coindeskPricesPersisted = $this->pricesPersisted - $newLibertyPricesPersisted;

        $this->command->info(
            sprintf(
                '%s Prices, between %s and %s, created or updated from CoinDesk hard-coded data',
                $coindeskPricesPersisted,
                $minDate,
                $date
            )
        );

        // -- CoinGecko -- //

        // TODO: change to CoinMarketCap

        $adapter = AdapterFactory::getAdapter();

        // don't addDay() as coingecko seems to exclude the starting day
        $start = Carbon::createFromFormat($dateFormat, config('btc.initial_data_last_day'));
        $end = new Carbon();

        $this->command->info(
            sprintf(
                'Fetching Prices from %s until today, %s, from CoinGecko API',
                $start->format($dateFormat),
                (new Carbon())->format($dateFormat)
            )
        );

        $prices = $adapter->getDailyPriceInterval($start, $end);

        foreach ($prices as $date => $price) {
            $this->persistPrice($date, $price, config('data.data_source.coingecko_id'));
        }

        $coingeckoPricesPersisted = $this->pricesPersisted - $coindeskPricesPersisted - $newLibertyPricesPersisted;

        $this->command->info(
            sprintf(
                '%s Prices, between %s and %s, created or updated from CoinGecko API',
                $coingeckoPricesPersisted,
                $minDate,
                $date
            )
        );

        $this->command->info($this->pricesPersisted . ' Prices updated or created');
        $this->command->info('-- DONE ✅ --');
    }

    private function persistPrice(string $date, float $value, int $dataSourceId): void
    {
        $this->command->info(
            sprintf(
                '%s: %s',
                $date,
                $value
            )
        );

        DailyPrice::updateOrCreate(
            ['date' => $date],
            [
                'date' => $date,
                'data_source_id' => $dataSourceId,
                'price' => $value,
            ]
        );

        $this->pricesPersisted++;
    }
}
