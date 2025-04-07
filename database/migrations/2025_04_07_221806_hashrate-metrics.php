<?php

use App\Models\Metric;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Metric::create([
            'data_source_id' => config('data.data_source.mempool_space_id'),
            'name' => 'Average Hashrate',
            'column_name' => 'average_hashrate',
        ]);

        Metric::create([
            'data_source_id' => config('data.data_source.mempool_space_id'),
            'name' => 'Difficulty',
            'column_name' => 'difficulty',
        ]);
    }
};
