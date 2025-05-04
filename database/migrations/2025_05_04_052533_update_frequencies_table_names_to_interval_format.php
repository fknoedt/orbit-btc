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
        // Update the 'name' column based on the 'days' column
        DB::table('frequencies')
            ->where('id', 1)
            ->update(['name' => '1-day interval']);

        DB::table('frequencies')
            ->where('id', 2)
            ->update(['name' => '3-day interval']);

        DB::table('frequencies')
            ->where('id', 3)
            ->update(['name' => '5-day interval']);

        DB::table('frequencies')
            ->where('id', 4)
            ->update(['name' => '7-day interval']);

        DB::table('frequencies')
            ->where('id', 5)
            ->update(['name' => '14-day interval']);

        DB::table('frequencies')
            ->where('id', 6)
            ->update(['name' => '30-day interval']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert the changes to the original names
        DB::table('frequencies')
            ->where('id', 1)
            ->update(['name' => 'Daily']);

        DB::table('frequencies')
            ->where('id', 2)
            ->update(['name' => 'Every 3 Days']);

        DB::table('frequencies')
            ->where('id', 3)
            ->update(['name' => 'Every 5 Days']);

        DB::table('frequencies')
            ->where('id', 4)
            ->update(['name' => 'Weekly']);

        DB::table('frequencies')
            ->where('id', 5)
            ->update(['name' => 'Biweekly']);

        DB::table('frequencies')
            ->where('id', 6)
            ->update(['name' => 'Monthly']);
    }
};
