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
        Schema::table('user_model_metrics', function (Blueprint $table) {
            $table->dropForeign('user_model_metrics_user_model_id_foreign');
            $table->foreign('user_model_id')->references('id')->on('user_models')->onDelete('cascade');
        });
    }
};
