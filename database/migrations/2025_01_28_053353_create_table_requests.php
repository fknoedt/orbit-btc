<?php

use App\Models\DataSource;
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
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(DataSource::class)->constrained();
            $table->string('url', 2048)->nullable();
            $table->string('class_method')->nullable();
            $table->string('http_method', 6)->default('GET');
            $table->text('args')->nullable();
            $table->integer('http_status_code')->default(200);
            $table->text('raw_response')->nullable();
            $table->boolean('cron');
            $table->float('elapsed_time')->nullable();
            $table->timestamps();
        });
    }
};
