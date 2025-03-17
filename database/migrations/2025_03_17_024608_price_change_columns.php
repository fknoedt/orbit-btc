<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('daily_prices', function (Blueprint $table) {
            $table->double('price_change_1d')
                ->nullable()
                ->comment('Percentage change in closing price 1 day after this date');

            $table->double('price_change_3d')
                ->nullable()
                ->comment('Percentage change in closing price 3 days after this date');

            $table->double('price_change_5d')
                ->nullable()
                ->comment('Percentage change in closing price 5 days after this date');

            $table->double('price_change_10d')
                ->nullable()
                ->comment('Percentage change in closing price 10 days after this date');
        });
    }

    public function down()
    {
        Schema::table('daily_prices', function (Blueprint $table) {
            $table->dropColumn('price_change_1d');
            $table->dropColumn('price_change_3d');
            $table->dropColumn('price_change_5d');
            $table->dropColumn('price_change_10d');
        });
    }
};
