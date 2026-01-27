<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::update("UPDATE metrics SET max_delayed_days = 2 WHERE column_name IN ('adr_act_cnt', 'adr_bal_cnt', 'fee_tot_ntv', 'flow_in_ex_ntv', 'flow_in_ex_usd', 'flow_out_ex_ntv', 'flow_out_ex_usd', 'sply_ex_ntv', 'sply_ex_usd', 'tx_cnt')");
        DB::update("UPDATE metrics SET max_delayed_days = 1 WHERE column_name IN ('roi_1yr', 'roi_30d', 'volume_reported_spot_usd_1d')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
