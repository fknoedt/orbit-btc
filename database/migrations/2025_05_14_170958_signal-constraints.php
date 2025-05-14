<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Drop the existing time_horizon constraint
        DB::statement('ALTER TABLE user_signals DROP CONSTRAINT user_signals_time_horizon_check');

        // Add the modified time_horizon constraint with additional values
        DB::statement("ALTER TABLE user_signals ADD CONSTRAINT user_signals_time_horizon_check CHECK (time_horizon::text = ANY (ARRAY['1'::character varying, '3'::character varying, '5'::character varying, '10'::character varying, '15'::character varying, '30'::character varying]::text[]))");

        // Drop the existing buy_or_sell constraint
        DB::statement('ALTER TABLE user_signals DROP CONSTRAINT user_signals_buy_or_sell_check');

        // Add the modified buy_or_sell constraint with additional value
        DB::statement("ALTER TABLE user_signals ADD CONSTRAINT user_signals_buy_or_sell_check CHECK (buy_or_sell::text = ANY (ARRAY['buy'::character varying, 'sell'::character varying, 'info'::character varying]::text[]))");
    }

    public function down()
    {
        // Drop the modified time_horizon constraint
        DB::statement('ALTER TABLE user_signals DROP CONSTRAINT user_signals_time_horizon_check');

        // Restore the original time_horizon constraint
        DB::statement("ALTER TABLE user_signals ADD CONSTRAINT user_signals_time_horizon_check CHECK (time_horizon::text = ANY (ARRAY['1'::character varying, '3'::character varying, '5'::character varying, '10'::character varying]::text[]))");

        // Drop the modified buy_or_sell constraint
        DB::statement('ALTER TABLE user_signals DROP CONSTRAINT user_signals_buy_or_sell_check');

        // Restore the original buy_or_sell constraint
        DB::statement("ALTER TABLE user_signals ADD CONSTRAINT user_signals_buy_or_sell_check CHECK (buy_or_sell::text = ANY (ARRAY['buy'::character varying, 'sell'::character varying]::text[]))");
    }
};
