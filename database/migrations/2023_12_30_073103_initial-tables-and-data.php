<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\ConsoleOutput;
use App\Models\DataSource;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $output = new ConsoleOutput();

        $output->writeln('Creating Tables with initial data...');

        Schema::create('data_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->string('api_key')->nullable();
            $table->string('host')->nullable();
            $table->string('uri')->nullable();
            $table->string('favicon')->nullable();
            $table->timestamps();
        });

        DataSource::insert([
            [
                'id' => config('data.data_source.new_liberty_id'),
                'name' => 'New Liberty Standard',
                'description' => 'First Bitcoin Exchange',
                'uri' => 'https://web.archive.org/web/20100428024753/http://newlibertystandard.wetpaint.com/page/2009+Exchange+Rate',
                'favicon' => '/images/new-liberty.ico'
            ],
            [
                'id' => config('data.data_source.coindesk_id'),
                'name' => 'CoinDesk',
                'description' => 'Seems to have been discontinued a long time ago',
                'uri' => 'https://api.coindesk.com/v1/bpi/historical/close.json?start=2010-07-18&end=2022-05-17',
                'favicon' => 'https://www.coindesk.com/pf/resources/favicons/production/favicon.ico?d=319'
            ],
            [
                'id' => config('data.data_source.coingecko_id'),
                'name' => 'CoinGecko',
                'desciption' => 'Allegely the most reliable & comprehensive cryptocurrency data API for traders and developers',
                'uri' => 'https://www.coingecko.com/en/api',
                'favicon' => 'https://www.coingecko.com/favicon.ico'
            ]
        ]);

        Schema::create('daily_prices', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->integer('data_source_id');
            $table->float('price');
            $table->float('market_cap')->nullable();
            $table->float('total_volume')->nullable();
            $table->timestamps();
            $table
                ->foreign('data_source_id')
                ->references('id')
                ->on('data_sources')
                ->onDelete('restrict')
                ->onUpdate('cascade');
        });

        $output->writeln('Tables created. Seeding InitialDailyPrices...');

        // Call seeder
        Artisan::call('db:seed', [
            '--class' => 'InitialDailyPrices',
            '--force' => true // run on production
        ], $output);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_prices');
        Schema::dropIfExists('data_sources');
    }
};
