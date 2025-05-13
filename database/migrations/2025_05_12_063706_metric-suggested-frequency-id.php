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
        // Add suggested_frequency_id column as a foreign key
        Schema::table('metrics', function (Blueprint $table) {
            $table->bigInteger('suggested_frequency_id')->unsigned()->nullable();
            $table->foreign('suggested_frequency_id')
                ->references('id')
                ->on('frequencies')
                ->onDelete('set null');
        });

        // Individual updates for each metric
        Metric::where('column_name', 'average_fee')->update(['suggested_frequency_id' => 1]);
        Metric::where('column_name', 'average_hashrate')->update(['suggested_frequency_id' => 5]);
        Metric::where('column_name', 'average_transaction_value')->update(['suggested_frequency_id' => 2]);
        Metric::where('column_name', 'block_size')->update(['suggested_frequency_id' => 4]);
        Metric::where('column_name', 'cap_real_usd')->update(['suggested_frequency_id' => 6]);
        Metric::where('column_name', 'close')->update(['suggested_frequency_id' => 1]);
        Metric::where('column_name', 'difficulty')->update(['suggested_frequency_id' => 5]);
        Metric::where('column_name', 'etf_btc_total')->update(['suggested_frequency_id' => 4]);
        Metric::where('column_name', 'etf_flow_btc')->update(['suggested_frequency_id' => 2]);
        Metric::where('column_name', 'exchanges_reserve')->update(['suggested_frequency_id' => 3]);
        Metric::where('column_name', 'exchanges_volume')->update(['suggested_frequency_id' => 1]);
        Metric::where('column_name', 'fear_and_greed')->update(['suggested_frequency_id' => 1]);
        Metric::where('column_name', 'large_transaction_count')->update(['suggested_frequency_id' => 2]);
        Metric::where('column_name', 'market_cap')->update(['suggested_frequency_id' => 1]);
        Metric::where('column_name', 'mayer_multiple')->update(['suggested_frequency_id' => 1]);
        Metric::where('column_name', 'miner_reserves')->update(['suggested_frequency_id' => 6]);
        Metric::where('column_name', 'mvrv')->update(['suggested_frequency_id' => 5]);
        Metric::where('column_name', 'new_addresses')->update(['suggested_frequency_id' => 4]);
        Metric::where('column_name', 'nrpl_usd')->update(['suggested_frequency_id' => 2]);
        Metric::where('column_name', 'nupl')->update(['suggested_frequency_id' => 5]);
        Metric::where('column_name', 'nvt_ratio')->update(['suggested_frequency_id' => 4]);
        Metric::where('column_name', 'open_interest_futures')->update(['suggested_frequency_id' => 2]);
        Metric::where('column_name', 'puell_multiple')->update(['suggested_frequency_id' => 5]);
        Metric::where('column_name', 'reserve_risk')->update(['suggested_frequency_id' => 6]);
        Metric::where('column_name', 'total_volume')->update(['suggested_frequency_id' => 1]);
        Metric::where('column_name', 'transaction_count')->update(['suggested_frequency_id' => 3]);
        Metric::where('column_name', 'true_market_mean')->update(['suggested_frequency_id' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('metrics', function (Blueprint $table) {
            $table->dropForeign(['suggested_frequency_id']);
            $table->dropColumn('suggested_frequency_id');
        });
    }
};
