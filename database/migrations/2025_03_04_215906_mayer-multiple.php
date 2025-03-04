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
            $table->float('mayer_multiple')->nullable();
        });

        $output->writeln('daily_prices.mayer_multiple created');
    }
};
