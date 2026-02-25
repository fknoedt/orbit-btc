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
        // Create US Treasury data source (unchanged)
        DataSource::create([
            'id' => 14,
            'name' => 'US Treasury',
            'uri' => 'https://fiscaldata.treasury.gov/',
            'favicon' => 'images/us-treasury.ico',
            'description' => 'Official source for US government financial data, including debt issuance and treasury operations',
        ]);

        // Add US T-Bill net issuance and normalized QE columns to daily_prices table
        Schema::table('daily_prices', function (Blueprint $table) {
            $table->decimal('us_tbill_net_issuance', 20, 2)->nullable()->after('bb_lower');
            $table->decimal('us_tbill_normalized_qe', 20, 2)->nullable()->after('us_tbill_net_issuance');
        });

        // Insert metric records
        Metric::create([
            'data_source_id' => 14,
            'name' => 'US T-Bill Net Issuance',
            'column_name' => 'us_tbill_net_issuance',
            'y_title' => 'Net Issuance ($M)',
            'description' => 'Daily net US Treasury bill issuance (issues minus redemptions) in millions from the Daily Treasury Statement (DTS). Aggregated monthly changes reflect Treasury QE operations, which inject liquidity and correlate with BTC prices (r = +0.80, leading by ~8 months).',
            'color' => '#FF9500',
            'data_limited_at' => '2009-03-01',
        ]);

        Metric::create([
            'data_source_id' => 14,
            'name' => 'US T-Bill Normalized QE',
            'column_name' => 'us_tbill_normalized_qe',
            'y_title' => 'Normalized 12-Mo Change',
            'description' => 'Normalized (z-score) 12-month rolling change in T-bill outstanding, derived from daily net issuance. Matches the Treasury QE line in the chart, showing liquidity trends.',
            'color' => '#0000FF',  // Blue like in chart
            'data_limited_at' => '2009-03-01',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove metric records first to avoid FK violation
        Metric::whereIn('column_name', ['us_tbill_net_issuance', 'us_tbill_normalized_qe'])->delete();

        // Remove data source after
        DataSource::where('id', 14)->delete();

        // Remove columns from daily_prices table
        Schema::table('daily_prices', function (Blueprint $table) {
            $table->dropColumn(['us_tbill_net_issuance', 'us_tbill_normalized_qe']);
        });
    }
};
