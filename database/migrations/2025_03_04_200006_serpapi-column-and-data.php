<?php

use App\Models\DataSource;
use App\Models\Request;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Symfony\Component\Console\Output\ConsoleOutput;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $output = new ConsoleOutput();

        $output->writeln('Creating column...');

        Schema::table('daily_prices', function (Blueprint $table): void {
           $table->integer('google_trend')->nullable();
        });

        $output->writeln('Ok');

        $output->writeln('Adding SerpAPI data...');

        DataSource::create([
            'id' => config('data.data_source.serpapi_id'),
            'name' => 'SerpAPI',
            'description' => 'Scrape Google and other search engines from our fast, easy, and complete API',
            'uri' => 'https://serpapi.com/',
            'favicon' => 'images/serpapi.ico'
        ]);

        $output->writeln('Done');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Request::where('data_source_id', '=', config('data.data_source.serpapi_id'))->delete();
        DataSource::destroy(config('data.data_source.serpapi_id'));
    }
};
