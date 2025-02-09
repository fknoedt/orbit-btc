<?php

use App\Models\DailyPrice;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Output\ConsoleOutput;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $output = new ConsoleOutput();

        Schema::table('daily_prices', function (Blueprint $table) {
            $table->float('open')->nullable();
            $table->float('high')->nullable();
            $table->float('low')->nullable();
            $table->float('close')->nullable();
            $table->dateTime('time_high')->nullable();
            $table->dateTime('time_low')->nullable();
        });

        DailyPrice::whereNotNull('price')->update(['close' => DB::raw('price')]);

        Schema::table('daily_prices', function (Blueprint $table) {
            $table->dropColumn('price');
            $table->float('close')->nullable(false)->change();
        });

        $output->writeln('Running InitialDailyPrices');

        // Call seeder (this was moved here from initial-tables-and-data migration as all data is necessary to run it
        Artisan::call('db:seed', [
            '--class' => 'InitialDailyPrices',
            '--force' => true // run on production
        ], $output);
    }
};
