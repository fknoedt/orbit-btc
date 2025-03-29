<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_model_daily_scores', function (Blueprint $table) {
            $table->float('conviction')->nullable();
            $table->float('stake')->nullable();
        });

        Schema::table('user_models', function (Blueprint $table) {
            $table->integer('total_simulated_trades')->nullable();
            $table->date('first_date_calculated')->nullable();
        });
    }
};
