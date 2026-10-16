<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('metric_usual_moves') && ! Schema::hasTable('leveling_moves')) {
            Schema::rename('metric_usual_moves', 'leveling_moves');
        }

        if (Schema::hasTable('leveling_moves') && Schema::hasColumn('leveling_moves', 'usual_abs_change')) {
            Schema::table('leveling_moves', function (Blueprint $table) {
                $table->renameColumn('usual_abs_change', 'abs_percent');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('leveling_moves') && Schema::hasColumn('leveling_moves', 'abs_percent')) {
            Schema::table('leveling_moves', function (Blueprint $table) {
                $table->renameColumn('abs_percent', 'usual_abs_change');
            });
        }

        if (Schema::hasTable('leveling_moves') && ! Schema::hasTable('metric_usual_moves')) {
            Schema::rename('leveling_moves', 'metric_usual_moves');
        }
    }
};
