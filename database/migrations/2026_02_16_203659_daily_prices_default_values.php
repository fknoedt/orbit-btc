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
        $columns = ['price_change_1d', 'price_change_3d', 'price_change_5d', 'price_change_10d', 'price_change_14d', 'price_change_30d'];

        foreach ($columns as $column) {
            DB::statement("ALTER TABLE daily_prices ALTER COLUMN {$column} DROP DEFAULT;");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columns = ['price_change_1d', 'price_change_3d', 'price_change_5d', 'price_change_10d', 'price_change_14d', 'price_change_30d'];

        foreach ($columns as $column) {
            DB::statement("ALTER TABLE daily_pricesALTER COLUMN {$column} SET DEFAULT 0;");
        }
    }
};
