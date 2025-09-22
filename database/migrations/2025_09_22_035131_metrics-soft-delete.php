<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add the deleted_at column for soft deletes
        Schema::table('metrics', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Soft delete rows where column_name = 'open_interest_futures'
        DB::table('metrics')
            ->where('column_name', 'open_interest_futures')
            ->update(['deleted_at' => now()]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('metrics', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
