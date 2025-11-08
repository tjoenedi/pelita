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
        Schema::create('communication_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->foreignId('event_type_id')->nullable()->constrained('event_types')->onDelete('cascade');
            $table->enum('type', ['email', 'sms']);
            $table->string('name');
            $table->string('subject')->nullable();
            $table->text('content');
            $table->boolean('is_default')->default(false);
            $table->boolean('append_unsubscribe_footer')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'type']);
            $table->index('event_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communication_templates');
    }
};
