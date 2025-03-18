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
        Schema::table('user_model_daily_scores', function (Blueprint $table) {
            $table
                ->float('score')
                ->comment('Model Score in the day - compared against Threshold to buy or sell')->change();
            $table
                ->float('signal_value')
                ->comment('Model outcome in the day - depending on buy or sell action and future price')->nullable();
        });

        Schema::table('user_models', function (Blueprint $table) {
            $table->date('last_date_calculated')
                ->comment('Last day calculated (not when it was calculated). See last_score and last_signal_value.')
                ->nullable()
                ->after('threshold');
            $table->float('last_signal_value')
                ->comment('Last day outcome - depending on buy or sell action and future price')
                ->nullable()
                ->after('last_score');
            $table->float('total_signal_value')
                ->comment('Model total outcome - depending on buy or sell action and future price')
                ->nullable()
                ->after('last_signal_value');
        });

        Schema::table('user_model_metrics', function (Blueprint $table) {
            $table->dropColumn(['data_capped', 'error', 'warning', 'last_score']);
        });
    }
};
