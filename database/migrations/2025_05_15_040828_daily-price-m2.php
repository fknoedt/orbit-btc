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
            $table->decimal('price_change_14d', 8, 2)->nullable()->after('price_change_10d');
            $table->decimal('price_change_30d', 8, 2)->nullable()->after('price_change_14d');
            $table->decimal('m2', 14, 2)->nullable();
        });

        Metric::create([
            'data_source_id' => config('data.data_source.bgeometrics_id'),
            'name' => 'Global M2',
            'column_name' => 'm2',
            'description' => 'Global M2 is the aggregate money supply, including cash, checking deposits, and easily convertible near-money (like savings accounts), from major central banks worldwide, such as the Federal Reserve, ECB, Bank of Japan, and others. It reflects global liquidity and economic activity. BGeometrics’ data, tracking M2 from 21 central banks, shows a correlation with Bitcoin’s price, suggesting that increases in global M2, indicating higher liquidity, often align with Bitcoin price surges, as investors seek assets to hedge against potential inflation.',
            'color' => sprintf('#%06x', rand(0, 0xFFFFFF)), // Random 6-digit hex color, e.g., #a1b2c3
            'y_title' => 'M2',
            'data_limited_at' => '2011-01-01',
            'suggested_frequency_id' => 6,
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
