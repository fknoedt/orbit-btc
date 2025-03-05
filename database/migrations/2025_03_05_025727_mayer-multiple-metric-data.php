<?php

use App\Models\Metric;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Metric::create([
            'data_source_id' => config('data.data_source.coinmarketcap_id'),
            'name' => 'Mayer Multiple',
            'column_name' => 'mayer_multiple',
        ]);
    }
};
