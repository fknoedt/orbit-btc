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
            'id' => config('data.data_source.fmp_id'),
            'name' => 'Financial Modeling Prep (FMP)',
            'description' => 'We provide one of the most accurate financial data available on the market. You can get historical prices, fundamental data, insider transactions, and much more that goes back 30 years in history.',
            'uri' => 'https://financialmodelingprep.com/',
            'favicon' => 'images/fmp-32x32.png'
        ]);

        Schema::table('daily_prices', function (Blueprint $table) {
            $table->decimal('spy', 8, 2)->nullable();
            $table->decimal('gold', 8, 2)->nullable();
        });

        Metric::create([
            'data_source_id' => config('data.data_source.fmp_id'),
            'name' => 'SPY Index',
            'column_name' => 'spy',
            'description' => "The SPY data represents the daily price movements of the SPDR S&P 500 ETF Trust (ticker: SPY), an exchange-traded fund that tracks the performance of the S&P 500 Index, which comprises 500 large-cap U.S. stocks. SPY is widely used as a proxy for the broader stock market, capturing trends in investor risk appetite and economic conditions.",
            'color' => sprintf('#%06x', rand(0, 0xFFFFFF)), // Random 6-digit hex color, e.g., #a1b2c3
            'y_title' => 'SPY',
            'data_limited_at' => '2009-10-05',
            'suggested_frequency_id' => 1,
        ]);

        Metric::create([
            'data_source_id' => config('data.data_source.fmp_id'),
            'name' => 'Gold Price',
            'column_name' => 'gold',
            'description' => "The gold price data represents the daily price movements of gold, quoted in U.S. dollars (ticker: GCUSD), typically based on the spot price or futures contracts in the commodities market. Gold is considered a safe-haven asset, often rising in value during economic uncertainty, inflation, or geopolitical instability, as investors seek to preserve wealth. It serves as a hedge against currency devaluation and market volatility.",
            'color' => sprintf('#%06x', rand(0, 0xFFFFFF)), // Random 6-digit hex color, e.g., #a1b2c3
            'y_title' => 'Gold',
            'data_limited_at' => '2009-10-05',
            'suggested_frequency_id' => 1,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
