<?php

use App\Models\Metric;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * ROI starts once the daily close has a full look-back. Close starts 2009-10-05.
     */
    public function up(): void
    {
        Metric::where('column_name', 'roi_30d')->update([
            'data_limited_at' => '2009-11-04',
        ]);
        Metric::where('column_name', 'roi_1yr')->update([
            'data_limited_at' => '2010-10-05',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Metric::whereIn('column_name', ['roi_30d', 'roi_1yr'])->update([
            'data_limited_at' => null,
        ]);
    }
};
