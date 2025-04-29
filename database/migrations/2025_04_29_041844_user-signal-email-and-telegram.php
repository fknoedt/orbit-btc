<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_signals', function (Blueprint $table) {
            // Remove email_to_notify and telegram_to_notify
            $table->dropColumn(['email_to_notify', 'telegram_to_notify']);
            // Add email_notification and last_notification_at
            $table->boolean('email_notification')->default(false);
            $table->timestamp('last_notification_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('user_signals', function (Blueprint $table) {
            // Restore email_to_notify and telegram_to_notify
            $table->string('email_to_notify')->nullable();
            $table->string('telegram_to_notify')->nullable();
            // Remove email_notification and last_notification_at
            $table->dropColumn(['email_notification', 'last_notification_at']);
        });
    }
};
