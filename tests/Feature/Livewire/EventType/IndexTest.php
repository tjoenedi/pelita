<?php

use App\Livewire\EventType\Index;
use App\Models\EventType;
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

it('can render event type index page', function () {
    $response = $this->get(route('event-types.index'));

    $response->assertStatus(200);
    $response->assertSeeLivewire(Index::class);
});

it('displays event types in the table', function () {
    $position1 = Position::factory()->create([
        'name' => 'Pastor',
        'organization_id' => $this->organization->id,
    ]);
    $position2 = Position::factory()->create([
        'name' => 'Worship Leader',
        'organization_id' => $this->organization->id,
    ]);

    $eventType = EventType::factory()->create([
        'name' => 'Sunday Service',
        'description' => 'Weekly worship service',
        'organization_id' => $this->organization->id,
    ]);

    $eventType->positions()->attach([
        $position1->id => ['order' => 0],
        $position2->id => ['order' => 1],
    ]);

    Livewire::test(Index::class)
        ->assertSee('Sunday Service')
        ->assertSee('Weekly worship service')
        ->assertSee('Pastor')
        ->assertSee('Worship Leader');
});

it('can search event types', function () {
    EventType::factory()->create([
        'name' => 'Sunday Service',
        'organization_id' => $this->organization->id,
    ]);
    EventType::factory()->create([
        'name' => 'Prayer Meeting',
        'organization_id' => $this->organization->id,
    ]);
    EventType::factory()->create([
        'name' => 'Youth Group',
        'organization_id' => $this->organization->id,
    ]);

    Livewire::test(Index::class)
        ->set('search', 'Sunday')
        ->assertSee('Sunday Service')
        ->assertDontSee('Prayer Meeting')
        ->assertDontSee('Youth Group');
});

it('can sort event types by name', function () {
    EventType::factory()->create([
        'name' => 'Zebra Meeting',
        'organization_id' => $this->organization->id,
    ]);
    EventType::factory()->create([
        'name' => 'Alpha Service',
        'organization_id' => $this->organization->id,
    ]);
    EventType::factory()->create([
        'name' => 'Beta Group',
        'organization_id' => $this->organization->id,
    ]);

    Livewire::test(Index::class)
        ->call('sort', 'name')
        ->assertSeeInOrder(['Alpha Service', 'Beta Group', 'Zebra Meeting']);
});

it('can delete an event type', function () {
    $eventType = EventType::factory()->create([
        'name' => 'Test Event Type',
        'organization_id' => $this->organization->id,
    ]);

    $this->assertDatabaseHas('event_types', ['id' => $eventType->id]);

    Livewire::test(Index::class)
        ->call('deleteEventType', $eventType->id)
        ->assertDispatched('event-type-deleted');

    $this->assertSoftDeleted('event_types', ['id' => $eventType->id]);
});

it('displays empty state when no event types exist', function () {
    Livewire::test(Index::class)
        ->assertSee('No Event Types Found')
        ->assertSee('Get started by creating your first event type template');
});

it('navigates to create page when create button is clicked', function () {
    $response = $this->get(route('event-types.index'));

    $response->assertSee(route('event-types.create'));
    $response->assertSee('Create Event Type');
});

it('navigates to edit page when edit button is clicked', function () {
    $eventType = EventType::factory()->create([
        'name' => 'Test Event',
        'organization_id' => $this->organization->id,
    ]);

    Livewire::test(Index::class)
        ->assertSee(route('event-types.edit', $eventType));
});

it('shows active events count for each event type', function () {
    $eventType = EventType::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    // Create some events for this event type
    \App\Models\Event::factory()->count(3)->create([
        'event_type_id' => $eventType->id,
        'organization_id' => $this->organization->id,
    ]);

    Livewire::test(Index::class)
        ->assertSee('3 events');
});
