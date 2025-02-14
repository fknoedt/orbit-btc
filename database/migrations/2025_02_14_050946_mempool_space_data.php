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

        $output->writeln('Adding mempool.space data...');

        DataSource::create([
            'id' => config('data.data_source.mempool_space_id'),
            'name' => 'Mempool',
            'description' => 'Bitcoin Explorer',
            'uri' => 'https://mempool.space/',
            'favicon' => 'images/mempool.ico'
        ]);

        $output->writeln('Done');
    }

    public function down(): void
    {
        DataSource::destroy(config('data.data_source.mempool_space_id'));
    }
};
