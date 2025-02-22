<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Output\ConsoleOutput;

return new class extends Migration
{
    public function up(): void
    {
        $output = new ConsoleOutput();

        Schema::table('daily_prices', function (Blueprint $table) {
            $table->integer('fear_and_greed')->nullable();
        });

        $output->writeln('daily_prices.fear_and_greed created');
    }
};
