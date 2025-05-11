<?php

use App\Models\DataSource;
use App\Models\Metric;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Define metrics with name, column_name, y_title, and description
    const array METRICS = [
        [
            'name' => 'ETF BTC Flow',
            'column_name' => 'etf_flow_btc',
            'y_title' => 'ETF Flow',
            'description' => 'The daily flows and aggregates of Bitcoin of the ETF, Blackrock (IBIT), The Grayscale Bitcoin Trust (GBTC), Fidelity (FBTC), Ark Invest /21Shares (ARKB), Bitwise (BITB), Franklin (EZBC), Investco / Galaxy (BTCO), Vaneck (HODL), Valkyrie (BRR) and Wisdomtree (BTCW).',
        ],
        [
            'name' => 'ETF BTC Total',
            'column_name' => 'etf_btc_total',
            'y_title' => 'ETF Total',
            'description' => 'The sum of bitcoin that are in ETFs.',
        ],
        [
            'name' => 'Miner Reserves',
            'column_name' => 'miner_reserves',
            'y_title' => 'Miner Res.',
            'description' => 'This metric indicates the reserve of BTC that miners have not yet sold.',
        ],
        [
            'name' => 'MVRV',
            'column_name' => 'mvrv',
            'y_title' => 'MVRV',
            'description' => 'Bitcoin Market Value Realized Value is the relationship between Market Value and Realized Value.',
        ],
        [
            'name' => 'NRPL USD',
            'column_name' => 'nrpl_usd',
            'y_title' => 'NRPL USD',
            'description' => 'Bitcoin NRPL (Net Realized Profit Loss). It measures the net dollar value of profit or loss realized by bitcoin holders over a given period.',
        ],
        [
            'name' => 'NUPL (Net Unrealized Profit Loss)',
            'column_name' => 'nupl',
            'y_title' => 'NUPL',
            'description' => 'Bitcoin Net Unrealized Profit Loss. It represents investor sentiment and shows what Bitcoin holders would gain or lose if they sold now.',
        ],
        [
            'name' => 'NVT Ratio',
            'column_name' => 'nvt_ratio',
            'y_title' => 'NVT Ratio',
            'description' => 'NVT Ratio is calculated by dividing the Network Value (market cap) by the USD volume transmitted through the blockchain daily. It\'s equivalent to dividing the Bitcoin token supply by the daily BTC value transmitted through the blockchain, making NVT technically an inverse of monetary velocity.',
        ],
        [
            'name' => 'Open Interest Futures',
            'column_name' => 'open_interest_futures',
            'y_title' => 'Open Futures',
            'description' => 'The total number of Bitcoin futures and options contracts in circulation on the market.',
        ],
        [
            'name' => 'Puell Multiple',
            'column_name' => 'puell_multiple',
            'y_title' => 'Puell Mult.',
            'description' => 'Bitcoin Puell Multiple is calculated by dividing the daily issuance value of bitcoins (in USD) by the 365-day moving average of the daily issuance value.',
        ],
        [
            'name' => 'Realized Cap',
            'column_name' => 'cap_real_usd',
            'y_title' => 'Real. Cap',
            'description' => 'Bitcoin Realized Capitalization is a metric that values each Bitcoin based on the price it was last moved or transacted, rather than its current market price. It calculates the total value of all Bitcoins in circulation by multiplying each coin\'s last transaction price by the number of coins, then summing these values. This provides a measure of the capital "realized" or invested in Bitcoin, reflecting the actual cost basis of holders and filtering out speculative market price fluctuations. It\'s often used to gauge market sentiment, investor behavior, and the economic weight of Bitcoin holdings.',
        ],
        [
            'name' => 'Reserve Risk',
            'column_name' => 'reserve_risk',
            'y_title' => 'Res. Risk',
            'description' => 'Bitcoin Reserve Risk. Current Price of Bitcoin / Cumulative Opportunity Cost (HODL Bank).',
        ],
        [
            'name' => 'True Market Mean',
            'column_name' => 'true_market_mean',
            'y_title' => 'True Mkt. Mean',
            'description' => 'Bitcoin True Market Mean Price (TMMP) is the ratio of realized market capitalization to active supply.',
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DataSource::create([
            'id' => config('data.data_source.bgeometrics_id'),
            'name' => 'BGeometrics',
            'description' => 'Bitcoin, the bank is coming',
            'uri' => 'https://bgeometrics.com/',
            'favicon' => 'bgeometrics.png'
        ]);

        // Add columns to the daily_prices table
        Schema::table('daily_prices', function (Blueprint $table) {
            $table->float('etf_flow_btc')->unsigned()->nullable();
            $table->float('etf_btc_total')->unsigned()->nullable();
            $table->float('miner_reserves')->unsigned()->nullable();
            $table->float('mvrv')->nullable();
            $table->float('nrpl_usd')->nullable();
            $table->float('nupl')->nullable();
            $table->float('nvt_ratio')->nullable();
            $table->float('open_interest_futures')->unsigned()->nullable();
            $table->float('puell_multiple')->nullable();
            $table->float('cap_real_usd')->unsigned()->nullable();
            $table->float('reserve_risk')->nullable();
            $table->float('true_market_mean')->nullable();
        });

        // Create one Metric record for each added column
        foreach (self::METRICS as $metric) {
            Metric::create([
                'data_source_id' => config('data.data_source.bgeometrics_id'),
                'name' => $metric['name'],
                'column_name' => $metric['column_name'],
                'description' => $metric['description'],
                'color' => sprintf('#%06x', rand(0, 0xFFFFFF)), // Random 6-digit hex color, e.g., #a1b2c3
                'y_title' => $metric['y_title'],
                'data_limited_at' => '2009-10-05',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Extract column names for deletion
        $columnNames = array_column(self::METRICS, 'column_name');

        // Delete Metric records associated with these columns
        Metric::where('data_source_id', config('data.data_source.bgeometrics_id'))
            ->whereIn('column_name', $columnNames)
            ->delete();

        // Drop the added columns
        Schema::table('daily_prices', function (Blueprint $table) use ($columnNames) {
            $table->dropColumn($columnNames);
        });
    }
};
