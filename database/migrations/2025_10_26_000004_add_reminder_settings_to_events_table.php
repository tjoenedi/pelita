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
        Schema::table('events', function (Blueprint $table) {
            $table->enum('reminder_mode', ['auto', 'manual', 'disabled'])->default('auto')->after('is_public');
            $table->json('reminder_override')->nullable()->after('reminder_mode');
            $table->string('timezone')->nullable()->after('reminder_override');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'reminder_mode',
                'reminder_override',
                'timezone',
            ]);
        });
    }
};
