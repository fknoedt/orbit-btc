<?php

use App\Models\DataSource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Output\ConsoleOutput;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $output = new ConsoleOutput();

        $output->writeln('Adding CoinMarketCap data...');

        DataSource::create([
            'id' => config('data.data_source.coinmarketcap_id'),
            'name' => 'CoinMarketCap',
            'description' => '"The Ultimate Cryptocurrency API From the Industry Authority"',
            'uri' => 'https://coinmarketcap.com/api/',
            'favicon' => 'images/cmc.ico'
        ]);

        $updates = [
            config('data.data_source.coinmarketcap_id') => 'images/cmc.ico',
            config('data.data_source.new_liberty_id') => 'images/new-liberty.ico',
            config('data.data_source.coindesk_id') => 'images/coindesk.ico',
        ];

        foreach ($updates as $dataSourceId => $favicon) {
            DB::table('data_sources')->where('id', $dataSourceId)->update(['favicon' => $favicon]);
        }

        $output->writeln('CMC data stored.');
    }

    public function down(): void
    {
        DataSource::destroy(config('data.data_source.coinmarketcap_id'));
    }
};
