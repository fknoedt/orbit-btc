<?php

use App\Models\DataSource;
use Illuminate\Database\Migrations\Migration;
use Symfony\Component\Console\Output\ConsoleOutput;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DataSource::create([
            'id' => config('data.data_source.x_id'),
            'name' => 'X',
            'description' => 'Good O\'Tweeter',
            'uri' => 'https://cryptoquant.com/',
            'favicon' => 'images/cryptoquant.ico'
        ]);
    }

    public function down(): void
    {
        DataSource::destroy(config('data.data_source.cryptoquant_id'));
    }
};
