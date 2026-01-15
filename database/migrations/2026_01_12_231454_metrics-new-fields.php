<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('metrics', function (Blueprint $table) {
            $table->integer('max_delayed_days')->default(0);
            $table->boolean('up_to_date')->default(false);
        });

        DB::statement("UPDATE metrics set max_delayed_days = 1 where column_name = 'fear_and_greed';");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('metrics', function (Blueprint $table) {
            $table->dropColumn('max_delayed_days');
            $table->dropColumn('up_to_date');
        });
    }
};
