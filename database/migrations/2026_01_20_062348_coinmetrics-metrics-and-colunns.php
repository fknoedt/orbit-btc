<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE metrics DROP CONSTRAINT metrics_name_unique;');
        Schema::table('metrics', function (Blueprint $table) {
            $table->unique('column_name');
        });

        // Add columns to daily_prices table (excluding irrelevant ones)
        Schema::table('daily_prices', function (Blueprint $table) {
            $table->double('adr_act_cnt')->nullable();
            $table->double('adr_bal_cnt')->nullable();
            $table->double('fee_tot_ntv')->nullable();
            $table->double('flow_in_ex_ntv')->nullable();
            $table->double('flow_in_ex_usd')->nullable();
            $table->double('flow_out_ex_ntv')->nullable();
            $table->double('flow_out_ex_usd')->nullable();
            $table->double('roi_1yr')->nullable();
            $table->double('roi_30d')->nullable();
            $table->double('reference_rate')->nullable();
            $table->double('sply_ex_ntv')->nullable();
            $table->double('sply_ex_usd')->nullable();
            $table->double('tx_cnt')->nullable();
            $table->double('volume_reported_spot_usd_1d')->nullable();
        });

        // Insert into metrics table (excluding irrelevant ones)
        DB::table('metrics')->insert([
            [
                'data_source_id' => config('data.data_source.coinmetrics_id'),
                'name' => 'Active Addresses Count',
                'column_name' => 'adr_act_cnt',
                'y_title' => 'Active Addresses',
                'color' => '#3498db',
                'description' => 'The sum count of unique addresses that were active in the network (as a recipient or originator) that day. High active addresses indicate strong network usage and user engagement, often correlating with bullish BTC price trends due to increased demand and adoption. Low counts may signal reduced interest, potentially leading to price stagnation or declines.',
                'suggested_frequency_id' => 1,
                'max_delayed_days' => 0,
            ],
            [
                'data_source_id' => config('data.data_source.coinmetrics_id'),
                'name' => 'Balance Addresses Count',
                'column_name' => 'adr_bal_cnt',
                'y_title' => 'Balance Addresses',
                'color' => '#e74c3c',
                'description' => 'The sum count of unique addresses holding a positive balance. This metric reflects the distribution of BTC holdings and can indicate growing adoption if increasing. A rising count often supports BTC price appreciation as more holders enter the market, while stagnation or drops might suggest consolidation or bearish sentiment.',
                'suggested_frequency_id' => 1,
                'max_delayed_days' => 0,
            ],
            [
                'data_source_id' => config('data.data_source.coinmetrics_id'),
                'name' => 'Total Fees Native',
                'column_name' => 'fee_tot_ntv',
                'y_title' => 'Total Fees BTC',
                'color' => '#d35400',
                'description' => 'The total fees paid to miners in native units (BTC). High fees indicate network congestion from increased transactions, often during bull runs, which can enhance miner profitability and indirectly support BTC price through improved security incentives.',
                'suggested_frequency_id' => 1,
                'max_delayed_days' => 0,
            ],
            [
                'data_source_id' => config('data.data_source.coinmetrics_id'),
                'name' => 'Exchange Inflows Native',
                'column_name' => 'flow_in_ex_ntv',
                'y_title' => 'Ex Inflows BTC',
                'color' => '#27ae60',
                'description' => 'The amount of BTC flowing into known exchange wallets daily. Spikes in inflows may signal selling pressure from holders, potentially leading to BTC price drops. Low inflows suggest holding behavior, often a bullish indicator for price stability or growth.',
                'suggested_frequency_id' => 1,
                'max_delayed_days' => 0,
            ],
            [
                'data_source_id' => config('data.data_source.coinmetrics_id'),
                'name' => 'Exchange Inflows USD',
                'column_name' => 'flow_in_ex_usd',
                'y_title' => 'Ex Inflows USD',
                'color' => '#2980b9',
                'description' => 'The USD value of BTC inflows to exchanges. Large USD inflows can precede sell-offs, exerting downward pressure on BTC price. Monitoring this helps anticipate market moves, as it reflects investor intent to liquidate positions.',
                'suggested_frequency_id' => 1,
                'max_delayed_days' => 0,
            ],
            [
                'data_source_id' => config('data.data_source.coinmetrics_id'),
                'name' => 'Exchange Outflows Native',
                'column_name' => 'flow_out_ex_ntv',
                'y_title' => 'Ex Outflows BTC',
                'color' => '#c0392b',
                'description' => 'The amount of BTC flowing out of exchange wallets. High outflows indicate withdrawals to personal storage, a bullish sign of long-term holding that reduces selling pressure and can support BTC price increases.',
                'suggested_frequency_id' => 1,
                'max_delayed_days' => 0,
            ],
            [
                'data_source_id' => config('data.data_source.coinmetrics_id'),
                'name' => 'Exchange Outflows USD',
                'column_name' => 'flow_out_ex_usd',
                'y_title' => 'Ex Outflows USD',
                'color' => '#16a085',
                'description' => 'The USD value of BTC outflows from exchanges. Significant outflows in USD terms suggest accumulation by large holders, potentially signaling confidence and upward BTC price momentum as supply on exchanges decreases.',
                'suggested_frequency_id' => 1,
                'max_delayed_days' => 0,
            ],
            [
                'data_source_id' => config('data.data_source.coinmetrics_id'),
                'name' => 'ROI 1 Year',
                'column_name' => 'roi_1yr',
                'y_title' => 'ROI 1 Year',
                'color' => '#e67e22',
                'description' => 'The return on investment over the trailing 1 year. Positive ROI signals strong performance, attracting investors and potentially driving BTC price higher. Negative values may deter participation, leading to price corrections.',
                'suggested_frequency_id' => 6,
                'max_delayed_days' => 0,
            ],
            [
                'data_source_id' => config('data.data_source.coinmetrics_id'),
                'name' => 'ROI 30 Days',
                'column_name' => 'roi_30d',
                'y_title' => 'ROI 30 Days',
                'color' => '#3498db',
                'description' => 'The return on investment over the trailing 30 days. Short-term ROI fluctuations can indicate momentum; positive trends often precede BTC price breakouts, while negative ones signal potential reversals.',
                'suggested_frequency_id' => 2,
                'max_delayed_days' => 0,
            ],
            [
                'data_source_id' => config('data.data_source.coinmetrics_id'),
                'name' => 'Reference Rate',
                'column_name' => 'reference_rate',
                'y_title' => 'Reference Rate',
                'color' => '#95a5a6',
                'description' => 'The aggregated reference rate for BTC in base currency. This robust price benchmark is used for settlements and can stabilize BTC price perceptions in institutional trading, influencing overall market confidence.',
                'suggested_frequency_id' => 1,
                'max_delayed_days' => 0,
            ],
            [
                'data_source_id' => config('data.data_source.coinmetrics_id'),
                'name' => 'Exchange Supply Native',
                'column_name' => 'sply_ex_ntv',
                'y_title' => 'Ex Supply BTC',
                'color' => '#27ae60',
                'description' => 'The sum of BTC held on known exchange addresses. Decreasing exchange supply reduces available liquidity for selling, often a bullish signal for BTC price as it implies long-term holding.',
                'suggested_frequency_id' => 1,
                'max_delayed_days' => 0,
            ],
            [
                'data_source_id' => config('data.data_source.coinmetrics_id'),
                'name' => 'Exchange Supply USD',
                'column_name' => 'sply_ex_usd',
                'y_title' => 'Ex Supply USD',
                'color' => '#f39c12',
                'description' => 'The USD value of BTC on exchanges. Low levels can indicate reduced selling pressure, supporting BTC price rallies, while spikes may precede dumps and price declines.',
                'suggested_frequency_id' => 1,
                'max_delayed_days' => 0,
            ],
            [
                'data_source_id' => config('data.data_source.coinmetrics_id'),
                'name' => 'Transaction Count',
                'column_name' => 'tx_cnt',
                'y_title' => 'Tx Count',
                'color' => '#3498db',
                'description' => 'The sum count of transactions that day. High transaction counts reflect network activity and demand, often aligning with BTC price increases during bull markets. Low counts may indicate bearish phases.',
                'suggested_frequency_id' => 1,
                'max_delayed_days' => 0,
            ],
            [
                'data_source_id' => config('data.data_source.coinmetrics_id'),
                'name' => 'Reported Spot Volume USD',
                'column_name' => 'volume_reported_spot_usd_1d',
                'y_title' => 'Spot Volume USD',
                'color' => '#9b59b6',
                'description' => 'The reported spot trading volume in USD over the day. High volume confirms price trends; during uptrends, it validates BTC price gains, while low volume in rallies may signal weakness and reversals.',
                'suggested_frequency_id' => 1,
                'max_delayed_days' => 0,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Delete inserted metrics first to avoid name conflicts if constraint is recreated
        DB::table('metrics')->whereIn('column_name', [
            'adr_act_cnt', 'adr_bal_cnt',
            'fee_tot_ntv', 'flow_in_ex_ntv','flow_in_ex_usd', 'flow_out_ex_ntv', 'flow_out_ex_usd',
            'roi_1yr', 'roi_30d', 'reference_rate', 'sply_ex_ntv',
            'sply_ex_usd', 'tx_cnt',
            'volume_reported_spot_usd_1d'
        ])->delete();

        // Reverse constraint changes
        Schema::table('metrics', function (Blueprint $table) {
            $table->dropUnique(['column_name']);
        });
        DB::statement('ALTER TABLE metrics ADD CONSTRAINT metrics_name_unique UNIQUE (name);');

        // Drop columns from daily_prices
        Schema::table('daily_prices', function (Blueprint $table) {
            $table->dropColumn([
                'adr_act_cnt', 'adr_bal_cnt',
                'fee_tot_ntv', 'flow_in_ex_ntv',
                'flow_in_ex_usd', 'flow_out_ex_ntv', 'flow_out_ex_usd', 'roi_1yr', 'roi_30d',
                'reference_rate', 'sply_ex_ntv',
                'sply_ex_usd', 'tx_cnt',
                'volume_reported_spot_usd_1d'
            ]);
        });
    }
};
