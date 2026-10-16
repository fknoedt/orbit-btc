<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_signal_metrics', function (Blueprint $table) {
            $table->dropColumn(['oscillation_threshold', 'oscillation_threshold_enabled']);
        });
    }

    public function down(): void
    {
        Schema::table('user_signal_metrics', function (Blueprint $table) {
            $table->float('oscillation_threshold')->nullable();
            $table->boolean('oscillation_threshold_enabled')->default(false);
        });
    }
};
