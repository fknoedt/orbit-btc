<?php

use App\Models\DataSource;
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
        DataSource::create([
            'id' => config('data.data_source.coinmetrics_id'),
            'name' => 'Coin Metrics',
            'description' => 'Coin Metrics is the leading provider of crypto financial intelligence, offering network data, market data, indexes and network risk solutions to the most prestigious institutions touching cryptoassets.',
            'uri' => 'https://api.coinmetrics.io/',
            'favicon' => 'images/coinmetrics.ico'
        ]);

        /*Schema::table('daily_prices', function (Blueprint $table) {
            $table->decimal('spy', 8, 2)->nullable();
            $table->decimal('gold', 8, 2)->nullable();
        });

        Metric::create([
            'data_source_id' => config('data.data_source.fmp_id'),
            'name' => 'Gold Price',
            'column_name' => 'gold',
            'description' => "The gold price data represents the daily price movements of gold, quoted in U.S. dollars (ticker: GCUSD), typically based on the spot price or futures contracts in the commodities market. Gold is considered a safe-haven asset, often rising in value during economic uncertainty, inflation, or geopolitical instability, as investors seek to preserve wealth. It serves as a hedge against currency devaluation and market volatility.",
            'color' => sprintf('#%06x', rand(0, 0xFFFFFF)), // Random 6-digit hex color, e.g., #a1b2c3
            'y_title' => 'Gold',
            'data_limited_at' => '2009-10-05',
            'suggested_frequency_id' => 1,
        ]);*/
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DataSource::where('id', config('data.data_source.coinmetrics_id'))->delete();
    }
};
