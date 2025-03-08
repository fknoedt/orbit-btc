<?php

use App\Models\Metric;
use App\Models\UserModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_models', function (Blueprint $table) {
            $table->dateTime('scores_last_updated_at')
                ->nullable()
                ->comment('Last time all Metrics were calculated and last_score was updated');
            $table->boolean('error')->nullable()->default(false);
            $table->boolean('warning')->nullable()->default(false);
            $table->enum('buy_or_sell', ['buy', 'sell'])->nullable()->default('sell');
            $table->renameColumn('current_score', 'last_score');
            $table->dateTime('data_limited_at')->nullable();
        });

        UserModel::where('id', '>', 0)->update(['error' => false, 'warning' => false, 'buy_or_sell' => 'sell']);

        // enforce constraints
        Schema::table('user_models', function (Blueprint $table) {
            $table->boolean('warning')->default(false)->change();
            $table->boolean('error')->default(false)->change();
            $table->enum('buy_or_sell', ['buy', 'sell'])->default('sell')->change();
        });

        Schema::table('metrics', function (Blueprint $table) {
            $table->date('data_limited_at')
                ->nullable()
                ->comment('No data available before this date');
        });

        Schema::table('user_model_metrics', function (Blueprint $table) {
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('scores_last_updated_at')->nullable();
            $table->boolean('data_capped')
                ->nullable()
                ->comment('This Metric does not have all the data for the last given time period');
            $table->string('error', 2048)->nullable();
            $table->string('warning', 2048)->nullable();
            $table->float('last_score')->nullable();
            // operator and oscillation threshold are optional
            $table->enum('operator', ['+', '-', '+-'])->default('+')->nullable()->change();
            $table
                ->float('oscillation_threshold')
                ->comment(
                    'Oscillation from one day to another beyond (depending on operator) this percentage
will score points that will be weighted based on `weight`'
                )->nullable()->change();
        });

        $updateMetrics = [
            'average_fee' => '2022-02-22',
            'exchanges_reserve' => '2022-02-22',
            'fear_and_greed' => '2018-02-01',
            'mayer_multiple' => '2012-01-01',
            'market_cap' => '2010-07-14',
            'total_volume' => '2012-01-01',
            'close' => '2009-10-05'
        ];

        foreach ($updateMetrics as $metricColumn => $dataLimitedAt) {
            Metric::where('column_name', $metricColumn)->update(['data_limited_at' => $dataLimitedAt]);
        }

        Schema::create('user_model_daily_scores', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignIdFor(UserModel::class);
            $table
                ->foreign('user_model_id')
                ->references('id')
                ->on('user_models')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->float('score');
        });

    }
};
