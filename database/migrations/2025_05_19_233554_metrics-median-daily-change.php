<?php

use App\Models\User;
use App\Models\UserSignalDailyScore;
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
        Schema::table('metrics', function (Blueprint $table) {
            $table->decimal('median_change', 14, 2)->nullable();
            $table->decimal('high_changes', 14, 2)->nullable()->after('median_change');
            $table->decimal('low_changes', 14, 2)->nullable()->after('high_changes');
            $table->decimal('average_change', 14, 2)->nullable()->after('low_changes');
        });

        $user = new User();
        $user->mergeFillable(['id']);
        $user->fill([
            'id' => config('data.system_user_id'),
            'name' => 'Astronaka',
            'email' => 'astronaka@orbitbtc.space',
            'role_id' => config('data.role_id.super_admin'),
            'password' => Hash::make('password'), // not to login
        ]);
        $user->save();

        Schema::table('user_signal_daily_scores', function (Blueprint $table) {
            $table->boolean('quarantined')->nullable()->default(false);
        });

        UserSignalDailyScore::where('id', '>', 0)->update(['quarantined' => false]);

        Schema::table('user_signal_daily_scores', function (Blueprint $table) {
            $table->boolean('quarantined')->default(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
