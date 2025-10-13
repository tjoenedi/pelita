<?php

use App\Livewire\Event\Index;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

test('past events are automatically deactivated when loading index page', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $user->organizations()->attach($organization);

    // Create a past event that is still active
    $pastEvent = Event::factory()->create([
        'organization_id' => $organization->id,
        'date' => now()->subDays(2),
        'is_active' => true,
        'all_day' => true,
    ]);

    // Create a future event that is active
    $futureEvent = Event::factory()->create([
        'organization_id' => $organization->id,
        'date' => now()->addDays(2),
        'is_active' => true,
        'all_day' => true,
    ]);

    // Create today's event that hasn't ended yet (should remain active)
    $todayEvent = Event::factory()->create([
        'organization_id' => $organization->id,
        'date' => now(),
        'is_active' => true,
        'all_day' => true,
    ]);

    $this->actingAs($user);

    // Load the index page
    Livewire::test(Index::class);

    // Check that past event is now inactive
    $pastEvent->refresh();
    expect($pastEvent->is_active)->toBe(false);

    // Check that future event is still active
    $futureEvent->refresh();
    expect($futureEvent->is_active)->toBe(true);

    // Check that today's all-day event is still active
    $todayEvent->refresh();
    expect($todayEvent->is_active)->toBe(true);
});

test('timed events are deactivated based on end time', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $user->organizations()->attach($organization);

    $now = now();

    // Create a past timed event (ended yesterday)
    $pastTimedEvent = Event::factory()->create([
        'organization_id' => $organization->id,
        'date' => $now->copy()->subDay(),
        'all_day' => false,
        'start_time' => '10:00:00',
        'end_time' => '12:00:00',
        'is_active' => true,
    ]);

    // Create today's event that has already ended
    $endedTodayEvent = Event::factory()->create([
        'organization_id' => $organization->id,
        'date' => $now->copy(),
        'all_day' => false,
        'start_time' => $now->copy()->subHours(3)->format('H:i:s'),
        'end_time' => $now->copy()->subHour()->format('H:i:s'),
        'is_active' => true,
    ]);

    // Create today's event that hasn't ended yet
    $ongoingEvent = Event::factory()->create([
        'organization_id' => $organization->id,
        'date' => $now->copy(),
        'all_day' => false,
        'start_time' => $now->copy()->subHour()->format('H:i:s'),
        'end_time' => $now->copy()->addHour()->format('H:i:s'),
        'is_active' => true,
    ]);

    $this->actingAs($user);

    // Load the index page
    Livewire::test(Index::class);

    // Check that past timed event is now inactive
    $pastTimedEvent->refresh();
    expect($pastTimedEvent->is_active)->toBe(false);

    // Check that today's ended event is now inactive
    $endedTodayEvent->refresh();
    expect($endedTodayEvent->is_active)->toBe(false);

    // Check that ongoing event is still active
    $ongoingEvent->refresh();
    expect($ongoingEvent->is_active)->toBe(true);
});

test('already inactive events remain inactive', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $user->organizations()->attach($organization);

    // Create an inactive past event
    $inactivePastEvent = Event::factory()->create([
        'organization_id' => $organization->id,
        'date' => now()->subDays(2),
        'is_active' => false,
    ]);

    // Create an inactive future event (manually deactivated)
    $inactiveFutureEvent = Event::factory()->create([
        'organization_id' => $organization->id,
        'date' => now()->addDays(2),
        'is_active' => false,
    ]);

    $this->actingAs($user);

    // Load the index page
    Livewire::test(Index::class);

    // Both should remain inactive
    $inactivePastEvent->refresh();
    expect($inactivePastEvent->is_active)->toBe(false);

    $inactiveFutureEvent->refresh();
    expect($inactiveFutureEvent->is_active)->toBe(false);
});
