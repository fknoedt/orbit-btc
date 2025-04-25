<?php

use App\Models\UserSignal;
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
        Schema::table('user_models', function (Blueprint $table) {
            $table->enum('time_horizon', [1, 3, 5, 10])->default(1)->nullable();
        });

        UserSignal::query()->update(['time_horizon' => 1]);
    }

    public function down(): void
    {
        Schema::table('user_models', function (Blueprint $table) {
            $table->dropColumn('time_horizon');
        });
    }
};
