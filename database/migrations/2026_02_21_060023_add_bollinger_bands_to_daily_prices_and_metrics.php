<?php

use App\Models\Metric;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    const array METRICS = [
        [
            'name' => 'Lower Bollinger Bands',
            'column_name' => 'bb_lower',
            'y_title' => 'Lower BB',
            'description' => 'Lower volatility band (20-day SMA minus 2 std dev). When BTC price touches or breaks below this level, it signals potential oversold conditions and buying opportunities, often leading to price bounces as traders anticipate mean reversion.',
            'color' => '#FF6B6B',
        ],
        [
            'name' => 'Middle Bollinger Bands',
            'column_name' => 'bb_middle',
            'y_title' => 'Middle BB',
            'description' => '20-day simple moving average acting as dynamic support/resistance. BTC price tends to gravitate toward this level during consolidation. Sustained breaks above or below signal potential trend changes that can drive price momentum.',
            'color' => '#4ECDC4',
        ],
        [
            'name' => 'Upper Bollinger Bands',
            'column_name' => 'bb_upper',
            'y_title' => 'Upper BB',
            'description' => 'Upper volatility band (20-day SMA plus 2 std dev). When BTC price reaches or exceeds this level, it indicates potential overbought conditions and possible reversals, as traders may take profits expecting mean reversion or correction.',
            'color' => '#95E1D3',
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add Bollinger Bands columns to daily_prices table
        Schema::table('daily_prices', function (Blueprint $table) {
            $table->decimal('bb_upper', 15, 2)->nullable()->after('rsi');
            $table->decimal('bb_middle', 15, 2)->nullable()->after('bb_upper');
            $table->decimal('bb_lower', 15, 2)->nullable()->after('bb_middle');
        });

        // Insert metric records
        foreach (self::METRICS as $metric) {
            Metric::create([
                'data_source_id' => 7,
                'name' => $metric['name'],
                'column_name' => $metric['column_name'],
                'y_title' => $metric['y_title'],
                'description' => $metric['description'],
                'color' => $metric['color'],
                'data_limited_at' => '2012-01-01',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove Bollinger Bands columns from daily_prices table
        Schema::table('daily_prices', function (Blueprint $table) {
            $table->dropColumn(['bb_upper', 'bb_middle', 'bb_lower']);
        });

        // Remove metric records
        Metric::whereIn('column_name', ['bb_lower', 'bb_middle', 'bb_upper'])->delete();
    }
};
