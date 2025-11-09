<?php

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
        Schema::table('event_types', function (Blueprint $table) {
            $table->boolean('reminder_enabled')->nullable()->after('description');
            $table->json('reminder_schedules')->nullable()->after('reminder_enabled');
            $table->foreignId('email_template_id')->nullable()->after('reminder_schedules')->constrained('communication_templates')->onDelete('set null');
            $table->foreignId('sms_template_id')->nullable()->after('email_template_id')->constrained('communication_templates')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_types', function (Blueprint $table) {
            $table->dropForeign(['email_template_id']);
            $table->dropForeign(['sms_template_id']);
            $table->dropColumn([
                'reminder_enabled',
                'reminder_schedules',
                'email_template_id',
                'sms_template_id',
            ]);
        });
    }
};
