<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Output\ConsoleOutput;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $output = new ConsoleOutput();

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignIdFor(Role::class)->constrained();
        });

        $output->writeln('Table roles created + users.role_id');

        DB::table('roles')->insert(
            [
                [
                    'id' => 1,
                    'name' => 'User',
                    'slug' => 'user',
                ],
                [
                    'id' => 2,
                    'name' => 'Admin',
                    'slug' => 'admin',
                ],
                [
                    'id' => 3,
                    'name' => 'Super Admin',
                    'slug' => 'super-admin',
                ],
            ]
        );

        DB::table('users')->insert(
            [
                'name' => 'Root User',
                'email' => 'root@eagle-btc.local',
                'password' => '$2y$12$yNUmpBl..U4cDfZ3gdEglu05lCkisp6rqr9z7xV2T7ov//naXdChC',
                'role_id' => 3,
            ]
        );

        $output->writeln('roles and root user created');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeignIdFor(Role::class);
        });
        Schema::dropIfExists('roles');
    }
};
