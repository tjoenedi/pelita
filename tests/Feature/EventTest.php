<?php

use App\Livewire\Event\Create;
use App\Livewire\Event\Edit;
use App\Livewire\Event\Index;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

test('authenticated users can view events index page', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $user->organizations()->attach($organization);

    $response = $this->actingAs($user)->get('/events/index');

    $response->assertSuccessful();
    $response->assertSeeLivewire(Index::class);
});

test('authenticated users can view create event page', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $user->organizations()->attach($organization);

    $response = $this->actingAs($user)->get('/events/create');

    $response->assertSuccessful();
    $response->assertSeeLivewire(Create::class);
});

test('authenticated users can create an event', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $user->organizations()->attach($organization);

    $eventType = EventType::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($user);

    Livewire::test(Create::class)
        ->set('name', 'Sunday Service')
        ->set('description', 'Weekly Sunday worship service')
        ->set('event_type_id', $eventType->id)
        ->set('date', '2025-12-25')
        ->set('all_day', false)
        ->set('start_time', '10:00')
        ->set('end_time', '12:00')
        ->set('is_active', true)
        ->set('is_public', true)
        ->call('save')
        ->assertRedirect(route('events.index'));

    $this->assertDatabaseHas('events', [
        'name' => 'Sunday Service',
        'organization_id' => $organization->id,
        'event_type_id' => $eventType->id,
    ]);
});

test('authenticated users can edit an event', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $user->organizations()->attach($organization);

    $event = Event::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Original Event Name',
    ]);

    $response = $this->actingAs($user)->get("/events/{$event->id}/edit");

    $response->assertSuccessful();
    $response->assertSeeLivewire(Edit::class);

    Livewire::test(Edit::class, ['event' => $event])
        ->set('name', 'Updated Event Name')
        ->call('save')
        ->assertRedirect(route('events.index'));

    $this->assertDatabaseHas('events', [
        'id' => $event->id,
        'name' => 'Updated Event Name',
    ]);
});

test('authenticated users can delete an event', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $user->organizations()->attach($organization);

    $event = Event::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('delete', $event->id)
        ->assertDispatched('event-deleted');

    $this->assertSoftDeleted('events', [
        'id' => $event->id,
    ]);
});

test('unauthenticated users cannot access events pages', function () {
    $this->get('/events/index')->assertRedirect('/login');
    $this->get('/events/create')->assertRedirect('/login');
});

test('events index shows only events from user organizations', function () {
    $user = User::factory()->create();
    $org1 = Organization::factory()->create();
    $org2 = Organization::factory()->create();

    $user->organizations()->attach($org1);

    $event1 = Event::factory()->create([
        'organization_id' => $org1->id,
        'name' => 'Event in User Org',
    ]);

    $event2 = Event::factory()->create([
        'organization_id' => $org2->id,
        'name' => 'Event in Other Org',
    ]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertSee('Event in User Org')
        ->assertDontSee('Event in Other Org');
});
