<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventType;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organizations = Organization::all();

        if ($organizations->isEmpty()) {
            $organizations = Organization::factory(2)->create();
        }

        foreach ($organizations as $organization) {
            // Create event types for each organization
            $eventTypes = EventType::factory(5)->create([
                'organization_id' => $organization->id,
            ]);

            // Create events for each organization
            foreach ($eventTypes as $eventType) {
                Event::factory(rand(2, 5))->create([
                    'organization_id' => $organization->id,
                    'event_type_id' => $eventType->id,
                ]);
            }

            // Create some events without event types
            Event::factory(3)->create([
                'organization_id' => $organization->id,
                'event_type_id' => null,
            ]);
        }
    }
}
