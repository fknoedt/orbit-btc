<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metric_usual_moves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('metric_id')->constrained()->cascadeOnDelete();
            $table->foreignId('frequency_id')->constrained()->cascadeOnDelete();
            $table->decimal('usual_abs_change', 14, 6);
            $table->unsignedInteger('samples');
            $table->date('price_date');
            $table->timestamp('calculated_at');
            $table->unique(['metric_id', 'frequency_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metric_usual_moves');
    }
};
