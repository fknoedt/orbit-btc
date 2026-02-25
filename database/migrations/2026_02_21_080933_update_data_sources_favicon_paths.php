<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $favicons = [
            'orbit-btc.ico',
            'glassnode.ico',
            'blockchain-com-32x32.png',
            'bgeometrics.png',
        ];

        DB::table('data_sources')
            ->whereIn('favicon', $favicons)
            ->update([
                'favicon' => DB::raw("CONCAT('images/', favicon)")
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $favicons = [
            'images/orbit-btc.ico',
            'images/glassnode.ico',
            'images/blockchain-com-32x32.png',
            'images/bgeometrics.png',
        ];

        DB::table('data_sources')
            ->whereIn('favicon', $favicons)
            ->update([
                'favicon' => DB::raw("REPLACE(favicon, 'images/', '')")
            ]);
    }
};
