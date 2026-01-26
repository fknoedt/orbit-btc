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
        DB::update("
            UPDATE daily_prices
            SET
                price_change_1d = CASE WHEN price_change_1d = 0 THEN NULL ELSE price_change_1d END,
                price_change_3d = CASE WHEN price_change_3d = 0 THEN NULL ELSE price_change_3d END,
                price_change_5d = CASE WHEN price_change_5d = 0 THEN NULL ELSE price_change_5d END,
                price_change_10d = CASE WHEN price_change_10d = 0 THEN NULL ELSE price_change_10d END,
                price_change_14d = CASE WHEN price_change_14d = 0 THEN NULL ELSE price_change_14d END,
                price_change_30d = CASE WHEN price_change_30d = 0 THEN NULL ELSE price_change_30d END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
