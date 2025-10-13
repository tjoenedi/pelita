<?php

use App\Livewire\Event\Create;
use App\Models\EventType;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Position;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create();
    $this->user->organizations()->attach($this->organization);
    $this->actingAs($this->user);
});

it('loads positions from event type when selected', function () {
    // Create positions
    $position1 = Position::factory()->create([
        'name' => 'Pastor',
        'organization_id' => $this->organization->id,
    ]);
    $position2 = Position::factory()->create([
        'name' => 'Worship Leader',
        'organization_id' => $this->organization->id,
    ]);

    // Create event type with positions
    $eventType = EventType::factory()->create([
        'name' => 'Sunday Service',
        'organization_id' => $this->organization->id,
    ]);
    $eventType->positions()->attach([
        $position1->id => ['order' => 0],
        $position2->id => ['order' => 1],
    ]);

    Livewire::test(Create::class)
        ->set('event_type_id', $eventType->id)
        ->assertSet('selectedPositions', [$position1->id, $position2->id]);
});

it('allows member assignment to positions from event type', function () {
    // Create members
    $member1 = Member::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'organization_id' => $this->organization->id,
    ]);
    $member2 = Member::factory()->create([
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'organization_id' => $this->organization->id,
    ]);

    // Create positions
    $position1 = Position::factory()->create([
        'name' => 'Pastor',
        'organization_id' => $this->organization->id,
    ]);
    $position2 = Position::factory()->create([
        'name' => 'Worship Leader',
        'organization_id' => $this->organization->id,
    ]);

    // Create event type with positions
    $eventType = EventType::factory()->create([
        'name' => 'Sunday Service',
        'organization_id' => $this->organization->id,
    ]);
    $eventType->positions()->attach([
        $position1->id => ['order' => 0],
        $position2->id => ['order' => 1],
    ]);

    Livewire::test(Create::class)
        ->set('event_type_id', $eventType->id)
        ->assertSet('selectedPositions', [$position1->id, $position2->id])
        ->set('positionAssignments.'.$position1->id, $member1->id)
        ->set('positionAssignments.'.$position2->id, $member2->id)
        ->set('name', 'Sunday Service - January 1')
        ->set('date', '2025-01-01')
        ->set('all_day', false)
        ->set('start_time', '09:00')
        ->set('end_time', '11:00')
        ->call('save')
        ->assertRedirect(route('events.index'));

    // Verify the event was created with correct assignments
    $this->assertDatabaseHas('events', [
        'name' => 'Sunday Service - January 1',
        'event_type_id' => $eventType->id,
        'organization_id' => $this->organization->id,
    ]);

    // Get the created event
    $event = \App\Models\Event::where('name', 'Sunday Service - January 1')->first();

    // First get the event positions
    $eventPosition1 = \App\Models\EventPosition::where('event_id', $event->id)
        ->where('position_id', $position1->id)
        ->first();
    $eventPosition2 = \App\Models\EventPosition::where('event_id', $event->id)
        ->where('position_id', $position2->id)
        ->first();

    // Verify position assignments in the schedule table
    $this->assertDatabaseHas('event_position_member', [
        'event_id' => $event->id,
        'event_position_id' => $eventPosition1->id,
        'member_id' => $member1->id,
    ]);

    $this->assertDatabaseHas('event_position_member', [
        'event_id' => $event->id,
        'event_position_id' => $eventPosition2->id,
        'member_id' => $member2->id,
    ]);
});

it('clears position assignments when event type is changed', function () {
    $member = Member::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    $position1 = Position::factory()->create([
        'name' => 'Pastor',
        'organization_id' => $this->organization->id,
    ]);
    $position2 = Position::factory()->create([
        'name' => 'Singer',
        'organization_id' => $this->organization->id,
    ]);

    $eventType1 = EventType::factory()->create([
        'name' => 'Sunday Service',
        'organization_id' => $this->organization->id,
    ]);
    $eventType1->positions()->attach([$position1->id => ['order' => 0]]);

    $eventType2 = EventType::factory()->create([
        'name' => 'Prayer Meeting',
        'organization_id' => $this->organization->id,
    ]);
    $eventType2->positions()->attach([$position2->id => ['order' => 0]]);

    Livewire::test(Create::class)
        ->set('event_type_id', $eventType1->id)
        ->assertSet('selectedPositions', [$position1->id])
        ->set('positionAssignments.'.$position1->id, [$member->id])
        ->assertSet('positionAssignments.'.$position1->id, [$member->id])
        ->set('event_type_id', $eventType2->id)
        ->assertSet('selectedPositions', [$position2->id])
        ->assertSet('positionAssignments', []); // Assignments should be cleared
});
