<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('user_model_metrics', function (Blueprint $table) {
            $table->boolean('oscillation_threshold_enabled')->default(false)->after('oscillation_threshold');
        });
    }

    public function down()
    {
        Schema::table('user_model_metrics', function (Blueprint $table) {
            $table->dropColumn('oscillation_threshold_enabled');
        });
    }
};
