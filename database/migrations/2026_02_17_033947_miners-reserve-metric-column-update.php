<?php

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
        DB::update("UPDATE metrics SET column_name = 'miner_balances' WHERE column_name = 'miner_reserves'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
