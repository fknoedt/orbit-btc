<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('user_signals')->where('time_horizon', '15')->update(['time_horizon' => '10']);

        DB::statement('ALTER TABLE user_signals DROP CONSTRAINT user_signals_time_horizon_check');
        DB::statement("ALTER TABLE user_signals ADD CONSTRAINT user_signals_time_horizon_check CHECK (time_horizon::text = ANY (ARRAY['1'::character varying, '3'::character varying, '5'::character varying, '10'::character varying, '30'::character varying]::text[]))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE user_signals DROP CONSTRAINT user_signals_time_horizon_check');
        DB::statement("ALTER TABLE user_signals ADD CONSTRAINT user_signals_time_horizon_check CHECK (time_horizon::text = ANY (ARRAY['1'::character varying, '3'::character varying, '5'::character varying, '10'::character varying, '15'::character varying, '30'::character varying]::text[]))");
    }
};
