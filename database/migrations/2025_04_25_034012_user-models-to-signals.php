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
            $constraintName = $this->findForeignKeyConstraint('user_model_metrics', 'user_model_id');
            if ($constraintName) {
                $table->dropForeign($constraintName);
            } else {
                \Log::warning("Foreign key constraint for column user_model_id on table user_model_metrics not found.");
            }
            $table->renameColumn('user_model_id', 'user_signal_id');
        });

        // Step 2: Update user_model_daily_scores table (drop FK and unique, rename column)
        Schema::table('user_model_daily_scores', function (Blueprint $table) {
            $constraintName = $this->findForeignKeyConstraint('user_model_daily_scores', 'user_model_id');
            if ($constraintName) {
                $table->dropForeign($constraintName);
            } else {
                \Log::warning("Foreign key constraint for column user_model_id on table user_model_daily_scores not found.");
            }
            $table->dropUnique(['date', 'user_model_id']);
            $table->renameColumn('user_model_id', 'user_signal_id');
        });

        // Step 3: Get the current sequence name for user_model_daily_scores.id
        $oldSequenceName = DB::selectOne("SELECT pg_get_serial_sequence('user_model_daily_scores', 'id') as sequence_name")->sequence_name;

        // Step 4: Rename the tables
        Schema::rename('user_models', 'user_signals');
        Schema::rename('user_model_metrics', 'user_signal_metrics');
        Schema::rename('user_model_daily_scores', 'user_signal_daily_scores');

        // Step 5: Rename the sequence and update the default value if it exists
        if ($oldSequenceName) {
            // Extract the base sequence name without schema
            $sequenceParts = explode('.', $oldSequenceName);
            $baseOldSequenceName = end($sequenceParts); // e.g., 'user_model_daily_scores_id_seq'
            $baseNewSequenceName = str_replace('user_model', 'user_signal', $baseOldSequenceName); // e.g., 'user_signal_daily_scores_id_seq'

            // Rename the sequence (new name without schema prefix)
            DB::statement("ALTER SEQUENCE {$oldSequenceName} RENAME TO {$baseNewSequenceName}");

            // Update the default value for the id column in the renamed table
            DB::statement("ALTER TABLE user_signal_daily_scores ALTER COLUMN id SET DEFAULT nextval('public.{$baseNewSequenceName}')");
        } else {
            \Log::warning("Sequence for user_model_daily_scores.id not found, skipping rename and default update.");
        }

        // Step 6: Recreate foreign key constraints with new table names
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

        // Step 7: Rename constraints on user_signals table
        DB::statement('ALTER TABLE user_signals RENAME CONSTRAINT user_models_name_unique TO user_signals_name_unique');
        DB::statement('ALTER TABLE user_signals RENAME CONSTRAINT user_models_buy_or_sell_check TO user_signals_buy_or_sell_check');
        DB::statement('ALTER TABLE user_signals RENAME CONSTRAINT user_models_time_horizon_check TO user_signals_time_horizon_check');

        // Step 8: Rename foreign key constraints on user_signal_metrics for other columns
        DB::statement('ALTER TABLE user_signal_metrics RENAME CONSTRAINT user_model_metrics_metric_id_foreign TO user_signal_metrics_metric_id_foreign');
        DB::statement('ALTER TABLE user_signal_metrics RENAME CONSTRAINT user_model_metrics_frequency_id_foreign TO user_signal_metrics_frequency_id_foreign');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Drop new constraints if they exist
        Schema::table('user_signal_daily_scores', function (Blueprint $table) {
            $constraintName = $this->findForeignKeyConstraint('user_signal_daily_scores', 'user_signal_id');
            if ($constraintName) {
                $table->dropForeign($constraintName);
            } else {
                \Log::warning("Foreign key constraint user_signal_daily_scores_user_signal_id_foreign on table user_signal_daily_scores not found, skipping drop.");
            }

            $uniqueConstraintName = 'user_signal_daily_scores_date_user_signal_id_unique';
            $uniqueExists = DB::selectOne(
                "SELECT constraint_name
                 FROM information_schema.table_constraints
                 WHERE table_name = ?
                 AND constraint_type = 'UNIQUE'
                 AND constraint_name = ?",
                ['user_signal_daily_scores', $uniqueConstraintName]
            );
            if ($uniqueExists) {
                $table->dropUnique($uniqueConstraintName);
            } else {
                \Log::warning("Unique constraint user_signal_daily_scores_date_user_signal_id_unique on table user_signal_daily_scores not found, skipping drop.");
            }
        });

        Schema::table('user_signal_metrics', function (Blueprint $table) {
            $constraintName = $this->findForeignKeyConstraint('user_signal_metrics', 'user_signal_id');
            if ($constraintName) {
                $table->dropForeign($constraintName);
            } else {
                \Log::warning("Foreign key constraint user_signal_metrics_user_signal_id_foreign on table user_signal_metrics not found, skipping drop.");
            }
        });

        // Step 2: Get the current sequence name for user_signal_daily_scores.id
        $currentSequenceName = DB::selectOne("SELECT pg_get_serial_sequence('user_signal_daily_scores', 'id') as sequence_name")->sequence_name;

        // Step 3: Rename tables back
        Schema::rename('user_signal_daily_scores', 'user_model_daily_scores');
        Schema::rename('user_signal_metrics', 'user_model_metrics');
        Schema::rename('user_signals', 'user_models');

        // Step 4: Rename the sequence back and update the default value if it exists
        if ($currentSequenceName) {
            // Extract the base sequence name without schema
            $sequenceParts = explode('.', $currentSequenceName);
            $baseCurrentSequenceName = end($sequenceParts); // e.g., 'user_signal_daily_scores_id_seq'
            $baseOriginalSequenceName = str_replace('user_signal', 'user_model', $baseCurrentSequenceName); // e.g., 'user_model_daily_scores_id_seq'

            // Rename the sequence back (new name without schema prefix)
            DB::statement("ALTER SEQUENCE {$currentSequenceName} RENAME TO {$baseOriginalSequenceName}");

            // Update the default value for the id column in the original table
            DB::statement("ALTER TABLE user_model_daily_scores ALTER COLUMN id SET DEFAULT nextval('public.{$baseOriginalSequenceName}')");
        } else {
            \Log::warning("Sequence for user_signal_daily_scores.id not found, skipping rename and default update.");
        }

        // Step 5: Update user_model_metrics table (rename column back, recreate FK)
        Schema::table('user_model_metrics', function (Blueprint $table) {
            $table->renameColumn('user_signal_id', 'user_model_id');
            $table->foreign('user_model_id')
                ->references('id')
                ->on('user_models')
                ->onDelete('cascade')
                ->name('user_model_metrics_user_model_id_foreign');
        });

        // Step 6: Update user_model_daily_scores table (rename column back, recreate constraints)
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

        // Step 7: Rename constraints back
        DB::statement('ALTER TABLE user_models RENAME CONSTRAINT user_signals_name_unique TO user_models_name_unique');
        DB::statement('ALTER TABLE user_models RENAME CONSTRAINT user_signals_buy_or_sell_check TO user_models_buy_or_sell_check');
        DB::statement('ALTER TABLE user_models RENAME CONSTRAINT user_signals_time_horizon_check TO user_models_time_horizon_check');

        // Step 8: Rename foreign key constraints back
        DB::statement('ALTER TABLE user_model_metrics RENAME CONSTRAINT user_signal_metrics_metric_id_foreign TO user_model_metrics_metric_id_foreign');
        DB::statement('ALTER TABLE user_model_metrics RENAME CONSTRAINT user_signal_metrics_frequency_id_foreign TO user_model_metrics_frequency_id_foreign');
    }

    /**
     * Find a foreign key constraint by table and column.
     *
     * @param string $table
     * @param string $column
     * @return string|null
     */
    private function findForeignKeyConstraint(string $table, string $column): ?string
    {
        $constraint = DB::selectOne(
            "SELECT tc.constraint_name
             FROM information_schema.table_constraints tc
             JOIN information_schema.constraint_column_usage ccu
                 ON tc.constraint_name = ccu.constraint_name
                 AND tc.table_name = ccu.table_name
             WHERE tc.table_name = ?
                 AND tc.constraint_type = 'FOREIGN KEY'
                 AND ccu.column_name = ?",
            [$table, $column]
        );

        return $constraint?->constraint_name;
    }
};
