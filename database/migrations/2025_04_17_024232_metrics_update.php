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
        Metric::whereIn('column_name', ['average_hashrate', 'difficulty'])->update([
            'data_limited_at' => '2012-01-01',
        ]);
    }
};
