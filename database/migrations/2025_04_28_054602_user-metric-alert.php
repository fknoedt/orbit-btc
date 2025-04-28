<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Check if the operator enum type exists in PostgreSQL, create if not
        $enumExists = DB::select("SELECT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'operator_type')")[0]->exists;
        if (!$enumExists) {
            DB::statement("CREATE TYPE operator_type AS ENUM ('+', '-', '+-')");
        }

        Schema::create('user_metric_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('metric_id')->constrained()->onDelete('cascade');
            $table->foreignId('frequency_id')->constrained()->onDelete('cascade');
            $table->decimal('threshold', 15, 2);
            $table->enum('operator', ['+', '-', '+-'])->default('+'); // Using the existing or new enum
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_metric_alerts');
        // Only drop the enum type if no other tables are using it
        $enumInUse = DB::select("SELECT EXISTS (SELECT 1 FROM information_schema.columns WHERE data_type = 'USER-DEFINED' AND udt_name = 'operator_type')")[0]->exists;
        if (!$enumInUse) {
            DB::statement("DROP TYPE IF EXISTS operator_type");
        }
    }
};
