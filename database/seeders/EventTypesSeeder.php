<?php

namespace Database\Seeders;

use App\Models\EventType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(int $organizationId): void
    {
        // Get position IDs for this organization
        $positionIds = DB::table('positions')
            ->where('organization_id', $organizationId)
            ->pluck('id')
            ->toArray();

        // Create the event type
        $eventType = EventType::create([
            'name' => 'Sunday Service',
            'description' => 'Weekly Sunday worship service',
            'organization_id' => $organizationId,
        ]);

        // Attach positions to the event type with their order
        $positionsData = [];
        foreach ($positionIds as $index => $positionId) {
            $positionsData[$positionId] = ['order' => $index];
        }

        $eventType->positions()->attach($positionsData);
    }
}
