<?php

use App\Models\DataSource;
use App\Models\Metric;
use App\Models\User;
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
        Schema::create('metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(DataSource::class)->constrained();
            $table->string('name')->unique();
            $table->string('column_name')->comment("daily_prices column which holds the metric's value");
            $table->string('widget_class')
                ->nullable()
                ->comment(
                    "Name of the widget's class, under namespace
App\\Filament\\Resources\\DashboardResource\\Widgets"
                );
            $table->timestamps();
        });

        $coinMarketCapId = config('data.data_source.coinmarketcap_id');
        $cryptoQuantId = config('data.data_source.cryptoquant_id');

        $metricsData = [
            [
                'data_source_id' => $coinMarketCapId,
                'name' => 'Closing Price',
                'column_name' => 'close',
                'widget_class' => 'BitcoinPriceWidget',
            ],
            [
                'data_source_id' => $coinMarketCapId,
                'name' => 'Total Volume',
                'column_name' => 'total_volume',
                'widget_class' => null,
            ],
            [
                'data_source_id' => $coinMarketCapId,
                'name' => 'Market Cap',
                'column_name' => 'market_cap',
                'widget_class' => null,
            ],
            [
                'data_source_id' => $coinMarketCapId,
                'name' => 'Average Fee',
                'column_name' => 'average_fee',
                'widget_class' => null,
            ],
            [
                'data_source_id' => $cryptoQuantId,
                'name' => 'Total Exchanges Reserve',
                'column_name' => 'exchanges_reserve',
                'widget_class' => null,
            ],
            [
                'data_source_id' => $coinMarketCapId,
                'name' => 'Fear & Greed',
                'column_name' => 'fear_and_greed',
                'widget_class' => null,
            ],
        ];

        foreach ($metricsData as $metric) {
            Metric::create($metric);
        }

        Schema::create('user_models', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained();
            $table->string('name')->unique();
            $table->text('description');
            $table->float('threshold')->comment(
                'When these score points are reached, alerts will be sent'
            )->nullable();
            $table->float('current_score')->comment(
                'Current points from every metric weighted against the latest available data'
            )->nullable();
            $table->string('email_to_notify')->nullable();
            $table->string('telegram_to_notify')->nullable();
            $table->boolean('is_paused')->default(false);
            $table->timestamps();
        });

        Schema::create('user_model_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_model_id')->constrained();
            $table->foreignId('metric_id')->constrained();
            $table->enum('operator', ['+', '-', '+-'])->default('+');
            $table
                ->float('oscillation_threshold')
                ->comment(
                    'Oscillation from one day to another beyond (depending on operator) this percentage
will score points that will be weighted based on `weight`'
                );
            $table->float('weight')->comment("How much this metric's score should affect your Model");
            $table->timestamp('created_at');
        });
    }
};
