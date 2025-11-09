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
        Schema::create('member_communication_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->foreignId('event_type_id')->nullable()->constrained('event_types')->onDelete('cascade');
            $table->enum('channel', ['email', 'sms', 'all']);
            $table->boolean('is_subscribed')->default(true);
            $table->timestamp('unsubscribed_at')->nullable();
            $table->string('unsubscribe_token')->unique();
            $table->timestamps();

            $table->unique(['member_id', 'organization_id', 'event_type_id', 'channel'], 'member_comm_prefs_unique');
            $table->index('unsubscribe_token');
            $table->index('member_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_communication_preferences');
    }
};
