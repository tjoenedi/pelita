<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\EventType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate existing default_positions to the pivot table
        EventType::withTrashed()->get()->each(function ($eventType) {
            if ($eventType->default_positions && is_array($eventType->default_positions)) {
                $positionsData = [];
                foreach ($eventType->default_positions as $index => $positionId) {
                    $positionsData[$positionId] = ['order' => $index];
                }
                $eventType->positions()->sync($positionsData);
            }
        });

        // After migration, we can optionally drop the default_positions column
        // But let's keep it for now as a backup
        // Schema::table('event_types', function (Blueprint $table) {
        //     $table->dropColumn('default_positions');
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // If we want to reverse, we'd need to restore the default_positions column data
        // For now, we'll just detach all positions
        EventType::withTrashed()->get()->each(function ($eventType) {
            $eventType->positions()->detach();
        });
    }
};