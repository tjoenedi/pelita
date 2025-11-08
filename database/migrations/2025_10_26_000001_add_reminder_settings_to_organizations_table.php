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
        Schema::table('organizations', function (Blueprint $table) {
            $table->boolean('reminder_enabled')->default(true)->after('time_zone');
            $table->integer('reminder_days_before')->default(3)->after('reminder_enabled');
            $table->time('reminder_time')->default('15:00:00')->after('reminder_days_before');
            $table->string('timezone')->default('America/New_York')->after('reminder_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'reminder_enabled',
                'reminder_days_before',
                'reminder_time',
                'timezone',
            ]);
        });
    }
};
