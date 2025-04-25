<?php

use App\Models\Frequency;
use App\Models\UserSignalMetric;
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
        Schema::create('frequencies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('number_of_days');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::table('user_model_metrics', function (Blueprint $table) {
            $table->foreignId('frequency_id')
                ->nullable()
                ->constrained('frequencies')
                ->nullOnDelete();
        });

        $frequencies = [
            [
                'name' => 'Daily',
                'number_of_days' => 1,
            ],
            [
                'name' => 'Every 3 Days',
                'number_of_days' => 3,
            ],
            [
                'name' => 'Every 5 Days',
                'number_of_days' => 5,
            ],
            [
                'name' => 'Weekly',
                'number_of_days' => 7,
            ],
            [
                'name' => 'Biweekly',
                'number_of_days' => 14,
            ],
            [
                'name' => 'Monthly',
                'number_of_days' => 30,
            ],
        ];

        foreach ($frequencies as $frequency) {
            Frequency::create($frequency);
        }

        UserSignalMetric::where('id', '>', 0)->update(['frequency_id' => 1]);

        Schema::table('user_model_metrics', function (Blueprint $table) {
            $table->dropForeign(['frequency_id']);
        });

        Schema::table('user_model_metrics', function (Blueprint $table) {
            $table->unsignedBigInteger('frequency_id')
                ->nullable(false)
                ->change();
            $table->foreign('frequency_id')
                ->references('id')
                ->on('frequencies')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_model_metrics', function (Blueprint $table) {
            $table->dropForeign(['frequency_id']);
            $table->dropColumn('frequency_id');
        });

        Schema::dropIfExists('frequencies');
    }
};
