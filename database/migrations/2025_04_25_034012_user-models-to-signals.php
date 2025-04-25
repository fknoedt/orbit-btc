<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Update user_model_metrics table (drop FK, rename column)
        Schema::table('user_model_metrics', function (Blueprint $table) {
            // Dynamically find and drop the foreign key constraint
            $this->dropForeignKey('user_model_metrics', 'user_model_id');
            // Rename the column
            $table->renameColumn('user_model_id', 'user_signal_id');
        });

        // Step 2: Update user_model_daily_scores table (drop FK and unique, rename column)
        Schema::table('user_model_daily_scores', function (Blueprint $table) {
            // Dynamically find and drop the foreign key constraint
            $this->dropForeignKey('user_model_daily_scores', 'user_model_id');
            // Drop the unique constraint
            $table->dropUnique(['date', 'user_model_id']);
            // Rename the column
            $table->renameColumn('user_model_id', 'user_signal_id');
        });

        // Step 3: Rename the tables
        Schema::rename('user_models', 'user_signals');
        Schema::rename('user_model_metrics', 'user_signal_metrics');
        Schema::rename('user_model_daily_scores', 'user_signal_daily_scores');

        // Step 4: Recreate foreign key constraints with new table names
        Schema::table('user_signal_metrics', function (Blueprint $table) {
            $table->foreign('user_signal_id')
                ->references('id')
                ->on('user_signals')
                ->onDelete('cascade')
                ->name('user_signal_metrics_user_signal_id_foreign');
        });

        Schema::table('user_signal_daily_scores', function (Blueprint $table) {
            $table->foreign('user_signal_id')
                ->references('id')
                ->on('user_signals')
                ->onDelete('cascade')
                ->onUpdate('cascade')
                ->name('user_signal_daily_scores_user_signal_id_foreign');
            $table->unique(['date', 'user_signal_id'], 'user_signal_daily_scores_date_user_signal_id_unique');
        });

        // Step 5: Rename the sequence for user_signal_daily_scores
        DB::statement('ALTER SEQUENCE daily_user_model_scores_id_seq RENAME TO daily_user_signal_scores_id_seq');

        // Step 6: Rename constraints on user_signals table
        DB::statement('ALTER TABLE user_signals RENAME CONSTRAINT user_models_name_unique TO user_signals_name_unique');
        DB::statement('ALTER TABLE user_signals RENAME CONSTRAINT user_models_buy_or_sell_check TO user_signals_buy_or_sell_check');
        DB::statement('ALTER TABLE user_signals RENAME CONSTRAINT user_models_time_horizon_check TO user_signals_time_horizon_check');

        // Step 7: Rename foreign key constraints on user_signal_metrics for other columns
        DB::statement('ALTER TABLE user_signal_metrics RENAME CONSTRAINT user_model_metrics_metric_id_foreign TO user_signal_metrics_metric_id_foreign');
        DB::statement('ALTER TABLE user_signal_metrics RENAME CONSTRAINT user_model_metrics_frequency_id_foreign TO user_signal_metrics_frequency_id_foreign');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Drop new constraints
        Schema::table('user_signal_daily_scores', function (Blueprint $table) {
            $table->dropForeign(['user_signal_id']);
            $table->dropUnique(['date', 'user_signal_id']);
        });

        Schema::table('user_signal_metrics', function (Blueprint $table) {
            $table->dropForeign(['user_signal_id']);
        });

        // Step 2: Rename tables back
        Schema::rename('user_signal_daily_scores', 'user_model_daily_scores');
        Schema::rename('user_signal_metrics', 'user_model_metrics');
        Schema::rename('user_signals', 'user_models');

        // Step 3: Update user_model_metrics table (rename column back, recreate FK)
        Schema::table('user_model_metrics', function (Blueprint $table) {
            $table->renameColumn('user_signal_id', 'user_model_id');
            $table->foreign('user_model_id')
                ->references('id')
                ->on('user_models')
                ->onDelete('cascade')
                ->name('user_model_metrics_user_model_id_foreign');
        });

        // Step 4: Update user_model_daily_scores table (rename column back, recreate constraints)
        Schema::table('user_model_daily_scores', function (Blueprint $table) {
            $table->renameColumn('user_signal_id', 'user_model_id');
            $table->foreign('user_model_id')
                ->references('id')
                ->on('user_models')
                ->onDelete('cascade')
                ->onUpdate('cascade')
                ->name('user_model_daily_scores_user_model_id_foreign');
            $table->unique(['date', 'user_model_id'], 'user_model_daily_scores_date_user_model_id_unique');
        });

        // Step 5: Rename sequence back
        DB::statement('ALTER SEQUENCE daily_user_signal_scores_id_seq RENAME TO daily_user_model_scores_id_seq');

        // Step 6: Rename constraints back
        DB::statement('ALTER TABLE user_models RENAME CONSTRAINT user_signals_name_unique TO user_models_name_unique');
        DB::statement('ALTER TABLE user_models RENAME CONSTRAINT user_signals_buy_or_sell_check TO user_models_buy_or_sell_check');
        DB::statement('ALTER TABLE user_models RENAME CONSTRAINT user_signals_time_horizon_check TO user_models_time_horizon_check');

        // Step 7: Rename foreign key constraints back
        DB::statement('ALTER TABLE user_model_metrics RENAME CONSTRAINT user_signal_metrics_metric_id_foreign TO user_model_metrics_metric_id_foreign');
        DB::statement('ALTER TABLE user_model_metrics RENAME CONSTRAINT user_signal_metrics_frequency_id_foreign TO user_model_metrics_frequency_id_foreign');
    }

    /**
     * Dynamically drop a foreign key constraint by table and column.
     *
     * @param string $table
     * @param string $column
     * @return void
     */
    private function dropForeignKey(string $table, string $column): void
    {
        $constraintName = DB::selectOne(
            "SELECT tc.constraint_name
             FROM information_schema.table_constraints tc
             JOIN information_schema.constraint_column_usage ccu
                 ON tc.constraint_name = ccu.constraint_name
                 AND tc.table_name = ccu.table_name
             WHERE tc.table_name = ?
                 AND tc.constraint_type = 'FOREIGN KEY'
                 AND ccu.column_name = ?",
            [$table, $column]
        )?->constraint_name;

        if ($constraintName) {
            Schema::table($table, function (Blueprint $table) use ($constraintName) {
                $table->dropForeign($constraintName);
            });
        } else {
            // Log or handle the case where the constraint is not found
            \Log::warning("Foreign key constraint for column {$column} on table {$table} not found.");
        }
    }
};
