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
        Schema::table('daily_prices', function (Blueprint $table) {
            $table->double('price_change_1d')->nullable()->default(0)
                ->comment('Percentage change in closing price 1 day after this date')
                ->change();
            $table->double('price_change_3d')->nullable()->default(0)
                ->comment('Percentage change in closing price 3 day after this date')
                ->change();
            $table->double('price_change_5d')->nullable()->default(0)
                ->comment('Percentage change in closing price 5 day after this date')
                ->change();
            $table->double('price_change_10d')->nullable()->default(0)
                ->comment('Percentage change in closing price 10 day after this date')
                ->change();
        });
    }
};
