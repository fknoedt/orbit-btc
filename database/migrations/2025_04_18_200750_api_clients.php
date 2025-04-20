<?php

use App\Models\DataSource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('accepting_connections')->default(false);
            $table->string('client_key'); // Encrypted field for storing client key
            $table->char('api_version', 10)->nullable();
            $table->dateTime('last_request')->nullable();
            $table->ipAddress('last_ip')->nullable();
            $table->integer('total_requests')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        DataSource::create([
            'id' => config('data.data_source.orbit_btc_id'),
            'name' => 'Orbit BTC',
            'description' => 'Yours truly',
            'uri' => 'http://orbit-btc.test/',
            'favicon' => 'orbit-btc.ico'
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('api_clients');
        DataSource::destroy(config('data.data_source.orbit_btc_id'));
    }
};
