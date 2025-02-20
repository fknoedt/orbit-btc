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

        $output->writeln('Adding cryptoquant data...');

        DataSource::create([
            'id' => config('data.data_source.cryptoquant_id'),
            'name' => 'CryptoQuant',
            'description' => 'On-Chain Actionable Insights',
            'uri' => 'https://cryptoquant.com/',
            'favicon' => 'images/cryptoquant.ico'
        ]);

        $output->writeln('Done');
    }

    public function down(): void
    {
        DataSource::destroy(config('data.data_source.cryptoquant_id'));
    }
};
