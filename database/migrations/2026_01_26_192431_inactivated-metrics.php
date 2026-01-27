<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::update("UPDATE metrics SET deleted_at = CURRENT_TIMESTAMP WHERE column_name IN ('average_fee', 'average_transaction_value', 'block_size', 'exchanges_reserve', 'exchanges_volume', 'large_transaction_count', 'miner_reserves', 'new_addresses', 'transaction_count', 'spy', 'gold', 'm2')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
