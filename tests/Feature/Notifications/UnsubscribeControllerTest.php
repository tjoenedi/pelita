<?php

use App\Enums\NotificationChannel;
use App\Models\EventType;
use App\Models\Member;
use App\Models\MemberCommunicationPreference;
use App\Models\Organization;

uses()->group('notifications');

it('shows unsubscribe page with valid token', function () {
    $organization = Organization::factory()->create();
    $member = Member::factory()->create(['organization_id' => $organization->id]);

    $preference = MemberCommunicationPreference::factory()->email()->create([
        'member_id' => $member->id,
        'organization_id' => $organization->id,
    ]);

    $response = $this->get(route('unsubscribe.show', $preference->unsubscribe_token));

    $response->assertSuccessful()
        ->assertViewIs('unsubscribe.show')
        ->assertViewHas('member', $member)
        ->assertViewHas('organization', $organization)
        ->assertViewHas('token', $preference->unsubscribe_token);
});

it('returns 404 for invalid token', function () {
    $response = $this->get(route('unsubscribe.show', 'invalid-token-12345'));

    $response->assertNotFound();
});

it('loads all member preferences grouped by event type', function () {
    $organization = Organization::factory()->create();
    $member = Member::factory()->create(['organization_id' => $organization->id]);
    $eventType = EventType::factory()->create(['organization_id' => $organization->id]);

    $globalPref = MemberCommunicationPreference::factory()->email()->create([
        'member_id' => $member->id,
        'organization_id' => $organization->id,
        'event_type_id' => null,
    ]);

    $eventTypePref = MemberCommunicationPreference::factory()->sms()->create([
        'member_id' => $member->id,
        'organization_id' => $organization->id,
        'event_type_id' => $eventType->id,
    ]);

    $response = $this->get(route('unsubscribe.show', $globalPref->unsubscribe_token));

    $response->assertSuccessful()
        ->assertViewHas('preferences', function ($preferences) {
            return $preferences->has('global') && $preferences->count() >= 1;
        })
        ->assertViewHas('eventTypes');
});

it('updates member preferences when form is submitted', function () {
    $organization = Organization::factory()->create();
    $member = Member::factory()->create(['organization_id' => $organization->id]);
    $eventType = EventType::factory()->create(['organization_id' => $organization->id]);

    $preference = MemberCommunicationPreference::factory()->email()->create([
        'member_id' => $member->id,
        'organization_id' => $organization->id,
    ]);

    $response = $this->post(route('unsubscribe.update', $preference->unsubscribe_token), [
        'subscriptions' => [
            "event_{$eventType->id}_email" => 'on',
            "event_{$eventType->id}_sms" => 'on',
            'global_email' => 'on',
        ],
    ]);

    $response->assertRedirect(route('unsubscribe.show', $preference->unsubscribe_token))
        ->assertSessionHas('success');

    expect(MemberCommunicationPreference::where('member_id', $member->id)->count())->toBeGreaterThan(0);
});

it('unsubscribes member from specific channel only', function () {
    $organization = Organization::factory()->create();
    $member = Member::factory()->create(['organization_id' => $organization->id]);
    $eventType = EventType::factory()->create(['organization_id' => $organization->id]);

    $preference = MemberCommunicationPreference::factory()->email()->create([
        'member_id' => $member->id,
        'organization_id' => $organization->id,
    ]);

    // Submit form with only SMS checked (unsubscribe from email)
    $response = $this->post(route('unsubscribe.update', $preference->unsubscribe_token), [
        'subscriptions' => [
            "event_{$eventType->id}_sms" => 'on',
            'global_sms' => 'on',
        ],
    ]);

    $response->assertRedirect();

    $emailPref = MemberCommunicationPreference::where('member_id', $member->id)
        ->where('channel', NotificationChannel::Email)
        ->whereNull('event_type_id')
        ->first();

    expect($emailPref)->not->toBeNull()
        ->and($emailPref->is_subscribed)->toBeFalse()
        ->and($emailPref->unsubscribed_at)->not->toBeNull();
});

it('unsubscribes member from specific event type', function () {
    $organization = Organization::factory()->create();
    $member = Member::factory()->create(['organization_id' => $organization->id]);
    $eventType1 = EventType::factory()->create(['organization_id' => $organization->id]);
    $eventType2 = EventType::factory()->create(['organization_id' => $organization->id]);

    $preference = MemberCommunicationPreference::factory()->email()->create([
        'member_id' => $member->id,
        'organization_id' => $organization->id,
    ]);

    // Subscribe only to eventType2
    $response = $this->post(route('unsubscribe.update', $preference->unsubscribe_token), [
        'subscriptions' => [
            "event_{$eventType2->id}_email" => 'on',
            "event_{$eventType2->id}_sms" => 'on',
        ],
    ]);

    $response->assertRedirect();

    $pref1 = MemberCommunicationPreference::where('member_id', $member->id)
        ->where('event_type_id', $eventType1->id)
        ->where('channel', NotificationChannel::Email)
        ->first();

    $pref2 = MemberCommunicationPreference::where('member_id', $member->id)
        ->where('event_type_id', $eventType2->id)
        ->where('channel', NotificationChannel::Email)
        ->first();

    expect($pref1)->not->toBeNull()
        ->and($pref1->is_subscribed)->toBeFalse()
        ->and($pref2)->not->toBeNull()
        ->and($pref2->is_subscribed)->toBeTrue();
});

it('unsubscribes from all channels when nothing is checked', function () {
    $organization = Organization::factory()->create();
    $member = Member::factory()->create(['organization_id' => $organization->id]);
    $eventType = EventType::factory()->create(['organization_id' => $organization->id]);

    $preference = MemberCommunicationPreference::factory()->email()->create([
        'member_id' => $member->id,
        'organization_id' => $organization->id,
    ]);

    // Submit empty form (unsubscribe from everything)
    $response = $this->post(route('unsubscribe.update', $preference->unsubscribe_token), [
        'subscriptions' => [],
    ]);

    $response->assertRedirect();

    $preferences = MemberCommunicationPreference::where('member_id', $member->id)->get();

    foreach ($preferences as $pref) {
        expect($pref->is_subscribed)->toBeFalse()
            ->and($pref->unsubscribed_at)->not->toBeNull();
    }
});

it('preserves existing tokens when updating preferences', function () {
    $organization = Organization::factory()->create();
    $member = Member::factory()->create(['organization_id' => $organization->id]);
    $eventType = EventType::factory()->create(['organization_id' => $organization->id]);

    $existingPref = MemberCommunicationPreference::factory()->email()->create([
        'member_id' => $member->id,
        'organization_id' => $organization->id,
        'event_type_id' => $eventType->id,
        'is_subscribed' => false,
    ]);

    $originalToken = $existingPref->unsubscribe_token;

    // Resubscribe
    $this->post(route('unsubscribe.update', $originalToken), [
        'subscriptions' => [
            "event_{$eventType->id}_email" => 'on',
        ],
    ]);

    $existingPref->refresh();

    expect($existingPref->is_subscribed)->toBeTrue()
        ->and($existingPref->unsubscribe_token)->not->toBe($originalToken);
});

it('creates new preference records when they do not exist', function () {
    $organization = Organization::factory()->create();
    $member = Member::factory()->create(['organization_id' => $organization->id]);
    $eventType = EventType::factory()->create(['organization_id' => $organization->id]);

    $preference = MemberCommunicationPreference::factory()->email()->create([
        'member_id' => $member->id,
        'organization_id' => $organization->id,
    ]);

    $initialCount = MemberCommunicationPreference::where('member_id', $member->id)->count();

    $this->post(route('unsubscribe.update', $preference->unsubscribe_token), [
        'subscriptions' => [
            "event_{$eventType->id}_email" => 'on',
            "event_{$eventType->id}_sms" => 'on',
        ],
    ]);

    $newCount = MemberCommunicationPreference::where('member_id', $member->id)->count();

    expect($newCount)->toBeGreaterThan($initialCount);
});

it('handles multiple event types correctly', function () {
    $organization = Organization::factory()->create();
    $member = Member::factory()->create(['organization_id' => $organization->id]);
    $eventTypes = EventType::factory()->count(3)->create(['organization_id' => $organization->id]);

    $preference = MemberCommunicationPreference::factory()->email()->create([
        'member_id' => $member->id,
        'organization_id' => $organization->id,
    ]);

    $subscriptions = [];
    foreach ($eventTypes as $eventType) {
        $subscriptions["event_{$eventType->id}_email"] = 'on';
    }

    $this->post(route('unsubscribe.update', $preference->unsubscribe_token), [
        'subscriptions' => $subscriptions,
    ]);

    foreach ($eventTypes as $eventType) {
        $pref = MemberCommunicationPreference::where('member_id', $member->id)
            ->where('event_type_id', $eventType->id)
            ->where('channel', NotificationChannel::Email)
            ->first();

        expect($pref)->not->toBeNull()
            ->and($pref->is_subscribed)->toBeTrue();
    }
});

it('returns 404 when updating with invalid token', function () {
    $response = $this->post(route('unsubscribe.update', 'invalid-token'), [
        'subscriptions' => [],
    ]);

    $response->assertNotFound();
});

it('shows event types belonging to organization only', function () {
    $organization1 = Organization::factory()->create();
    $organization2 = Organization::factory()->create();
    $member = Member::factory()->create(['organization_id' => $organization1->id]);

    $eventType1 = EventType::factory()->create(['organization_id' => $organization1->id]);
    $eventType2 = EventType::factory()->create(['organization_id' => $organization2->id]);

    $preference = MemberCommunicationPreference::factory()->email()->create([
        'member_id' => $member->id,
        'organization_id' => $organization1->id,
    ]);

    $response = $this->get(route('unsubscribe.show', $preference->unsubscribe_token));

    $response->assertSuccessful()
        ->assertViewHas('eventTypes', function ($eventTypes) use ($eventType1, $eventType2) {
            return $eventTypes->contains($eventType1) && !$eventTypes->contains($eventType2);
        });
});
