<?php

use App\Models\Metric;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Define metrics with name, column_name, y_title, and description
    const array METRICS = [
        [
            'name' => 'Transaction Count',
            'column_name' => 'transaction_count',
            'y_title' => 'Txs #',
            'description' => 'Daily number of valid bitcoin transactions. Shows network activity. May relate to price through increased usage or trading.',
        ],
        [
            'name' => 'Large Transaction Count',
            'column_name' => 'large_transaction_count',
            'y_title' => 'Large Txs #',
            'description' => 'Daily bitcoin transactions over $100,000. Tracks big players. May signal price moves from institutional buying or selling.',
        ],
        [
            'name' => 'Average Transaction Value',
            'column_name' => 'average_transaction_value',
            'y_title' => 'Avg Tx Value',
            'description' => 'Daily mean value of bitcoin transactions in BTC. Indicates transaction size. Could link to price via larger transfers or operational shifts.',
        ],
        [
            'name' => 'New Addresses',
            'column_name' => 'new_addresses',
            'y_title' => 'New Addresses',
            'description' => 'Daily count of new bitcoin addresses. Suggests user growth. May tie to price through rising demand, though not always accurate.',
        ],
        [
            'name' => 'Block Size',
            'column_name' => 'block_size',
            'y_title' => 'Block Size',
            'description' => 'Daily average size of bitcoin blocks in bytes. Reflects transaction volume. Could affect price via high usage or network congestion.',
        ],
        [
            'name' => 'Exchanges Volume',
            'column_name' => 'exchanges_volume',
            'y_title' => 'Exchanges Vol.',
            'description' => 'Total exchanges volume per day. Indicates movement in and out of exchanges.',
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add columns to the daily_prices table
        Schema::table('daily_prices', function (Blueprint $table) {
            $table->integer('transaction_count')->unsigned()->nullable();
            $table->integer('large_transaction_count')->unsigned()->nullable();
            $table->float('average_transaction_value')->nullable();
            $table->integer('new_addresses')->unsigned()->nullable();
            $table->float('block_size')->nullable();
            $table->float('exchanges_volume')->nullable();
        });

        // Create one Metric record for each added column
        foreach (self::METRICS as $metric) {
            Metric::create([
                'data_source_id' => config('data.data_source.coindesk_id'),
                'name' => $metric['name'],
                'column_name' => $metric['column_name'],
                'description' => $metric['description'],
                'color' => sprintf('#%06x', rand(0, 0xFFFFFF)), // Random 6-digit hex color, e.g., #a1b2c3
                'y_title' => $metric['y_title'],
                'data_limited_at' => '2009-10-05',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Extract column names for deletion
        $columnNames = array_column(self::METRICS, 'column_name');

        // Delete Metric records associated with these columns
        Metric::where('data_source_id', config('data.data_source.coindesk_id'))
            ->whereIn('column_name', $columnNames)
            ->delete();

        // Drop the added columns
        Schema::table('daily_prices', function (Blueprint $table) use ($columnNames) {
            $table->dropColumn($columnNames);
        });
    }
};
