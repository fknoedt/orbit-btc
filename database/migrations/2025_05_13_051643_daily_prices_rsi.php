<?php

use App\Models\Metric;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('daily_prices', function (Blueprint $table) {
            $table->decimal('rsi', 8, 2)->nullable()->after('close');
        });

        Metric::create([
            'data_source_id' => config('data.data_source.orbit_btc_id'),
            'name' => 'RSI',
            'column_name' => 'rsi',
            'description' => "The Relative Strength Index (RSI) is a momentum indicator that evaluates Bitcoin's price changes over a 14-day period, ranging from 0 to 100. Values above 70 suggest overbought conditions, potentially signaling a price drop, while below 30 indicate oversold conditions, hinting at a rebound. Traders use RSI to spot trend reversals or confirm momentum in Bitcoin’s volatile market, though it’s best paired with other metrics.",
            'color' => sprintf('#%06x', rand(0, 0xFFFFFF)), // Random 6-digit hex color, e.g., #a1b2c3
            'y_title' => 'RSI',
            'data_limited_at' => '2012-01-01',
            'suggested_frequency_id' => 1,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_prices', function (Blueprint $table) {
            $table->dropColumn('rsi');
        });
    }
};
