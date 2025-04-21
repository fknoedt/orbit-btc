<?php

use App\Models\DataSource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DataSource::create([
            'id' => config('data.data_source.glassnode_id'),
            'name' => 'Glassnode',
            'description' => 'World leading onchain & financial metrics, charts, data & insights for #Bitcoin & digital assets',
            'uri' => 'https://glassnode.com/',
            'favicon' => 'glassnode.ico'
        ]);

        DataSource::create([
            'id' => config('data.data_source.blockchain_com_id'),
            'name' => 'Blockchain.com',
            'description' => 'Buy Bitcoin, Ethereum, and other cryptocurrencies on a platform trusted by millions',
            'uri' => 'https://blockchain.com/',
            'favicon' => 'blockchain-com-32x32.png'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
