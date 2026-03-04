<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::update("UPDATE metrics SET max_delayed_days = 1 WHERE column_name IN ('mvrv', 'nrpl-usd', 'nupl', 'puell-multiple')");

        DB::update("UPDATE metrics SET max_delayed_days = 2 WHERE column_name IN ('etf-flow-btc', 'etf-btc-total')");

        DB::update("UPDATE metrics SET max_delayed_days = 3 WHERE column_name IN ('cap-real-usd')");

        DB::update("UPDATE metrics SET max_delayed_days = 12 WHERE column_name IN ('miner-balances')");

        DB::update("UPDATE metrics SET max_delayed_days = 13 WHERE column_name IN ('nvt-ratio', 'reserve-risk', 'true-market-mean')");

        DB::update("UPDATE metrics SET name = 'Bitcoin Price' WHERE column_name = 'close'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
