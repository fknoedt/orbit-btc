<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('UPDATE requests SET raw_response = LEFT(raw_response, 1000) WHERE LENGTH(raw_response) > 1000;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
