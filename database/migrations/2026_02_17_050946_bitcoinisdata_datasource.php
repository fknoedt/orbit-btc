<?php

use App\Models\DataSource;
use Illuminate\Database\Migrations\Migration;
use Symfony\Component\Console\Output\ConsoleOutput;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $output = new ConsoleOutput();

        $output->writeln('Adding BitcoinIsData data_source...');

        DataSource::create([
            'id' => config('data.data_source.bitcoinisdata_id'),
            'name' => 'Bitcoin is Data',
            'description' => 'Bitcoin Metrics, Simplified',
            'uri' => 'https://bitcoinisdata.com/',
            'favicon' => 'images/bitcoinisdata.ico'
        ]);

        $output->writeln('Done');
    }

    public function down(): void
    {
        DataSource::destroy(config('data.data_source.bitcoinisdata_id'));
    }
};
