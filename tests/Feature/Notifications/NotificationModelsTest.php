<?php

use App\Enums\NotificationChannel;
use App\Enums\ReminderStatus;
use App\Models\CommunicationTemplate;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Member;
use App\Models\MemberCommunicationPreference;
use App\Models\Organization;
use App\Models\ReminderLog;

it('communication template belongs to organization', function () {
    $organization = Organization::factory()->create();
    $template = CommunicationTemplate::factory()->create([
        'organization_id' => $organization->id,
    ]);

    expect($template->organization)->toBeInstanceOf(Organization::class)
        ->and($template->organization->id)->toBe($organization->id);
});

it('communication template belongs to event type', function () {
    $eventType = EventType::factory()->create();
    $template = CommunicationTemplate::factory()->create([
        'event_type_id' => $eventType->id,
    ]);

    expect($template->eventType)->toBeInstanceOf(EventType::class)
        ->and($template->eventType->id)->toBe($eventType->id);
});

it('communication template can be default', function () {
    $template = CommunicationTemplate::factory()->default()->create();

    expect($template->is_default)->toBeTrue();
});

it('communication template casts type to notification channel enum', function () {
    $template = CommunicationTemplate::factory()->email()->create();

    expect($template->type)->toBeInstanceOf(NotificationChannel::class)
        ->and($template->type)->toBe(NotificationChannel::Email);
});

it('reminder log belongs to member', function () {
    $member = Member::factory()->create();
    $log = ReminderLog::factory()->create(['member_id' => $member->id]);

    expect($log->member)->toBeInstanceOf(Member::class)
        ->and($log->member->id)->toBe($member->id);
});

it('reminder log belongs to event', function () {
    $event = Event::factory()->create();
    $log = ReminderLog::factory()->create(['event_id' => $event->id]);

    expect($log->event)->toBeInstanceOf(Event::class)
        ->and($log->event->id)->toBe($event->id);
});

it('reminder log belongs to event type', function () {
    $eventType = EventType::factory()->create();
    $log = ReminderLog::factory()->create(['event_type_id' => $eventType->id]);

    expect($log->eventType)->toBeInstanceOf(EventType::class)
        ->and($log->eventType->id)->toBe($eventType->id);
});

it('reminder log casts channel to notification channel enum', function () {
    $log = ReminderLog::factory()->email()->create();

    expect($log->channel)->toBeInstanceOf(NotificationChannel::class)
        ->and($log->channel)->toBe(NotificationChannel::Email);
});

it('reminder log casts status to reminder status enum', function () {
    $log = ReminderLog::factory()->sent()->create();

    expect($log->status)->toBeInstanceOf(ReminderStatus::class)
        ->and($log->status)->toBe(ReminderStatus::Sent);
});

it('reminder log casts dates correctly', function () {
    $log = ReminderLog::factory()->sent()->create();

    expect($log->scheduled_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
        ->and($log->sent_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

it('reminder log stores template snapshot as array', function () {
    $log = ReminderLog::factory()->create([
        'template_snapshot' => [
            'template_id' => 1,
            'channel' => 'email',
            'custom_field' => 'value',
        ],
    ]);

    expect($log->template_snapshot)->toBeArray()
        ->and($log->template_snapshot['template_id'])->toBe(1)
        ->and($log->template_snapshot['channel'])->toBe('email')
        ->and($log->template_snapshot['custom_field'])->toBe('value');
});

it('member communication preference belongs to member', function () {
    $member = Member::factory()->create();
    $preference = MemberCommunicationPreference::factory()->create([
        'member_id' => $member->id,
    ]);

    expect($preference->member)->toBeInstanceOf(Member::class)
        ->and($preference->member->id)->toBe($member->id);
});

it('member communication preference belongs to organization', function () {
    $organization = Organization::factory()->create();
    $preference = MemberCommunicationPreference::factory()->create([
        'organization_id' => $organization->id,
    ]);

    expect($preference->organization)->toBeInstanceOf(Organization::class)
        ->and($preference->organization->id)->toBe($organization->id);
});

it('member communication preference belongs to event type', function () {
    $eventType = EventType::factory()->create();
    $preference = MemberCommunicationPreference::factory()->forEventType($eventType->id)->create();

    expect($preference->eventType)->toBeInstanceOf(EventType::class)
        ->and($preference->eventType->id)->toBe($eventType->id);
});

it('member communication preference generates unsubscribe token on creation', function () {
    $preference = MemberCommunicationPreference::factory()->create([
        'unsubscribe_token' => null,
    ]);

    expect($preference->unsubscribe_token)->not->toBeNull()
        ->and(strlen($preference->unsubscribe_token))->toBe(64);
});

it('member communication preference can have custom unsubscribe token', function () {
    $customToken = 'custom-token-12345';
    $preference = MemberCommunicationPreference::factory()->create([
        'unsubscribe_token' => $customToken,
    ]);

    expect($preference->unsubscribe_token)->toBe($customToken);
});

it('member communication preference casts channel to enum', function () {
    $preference = MemberCommunicationPreference::factory()->email()->create();

    expect($preference->channel)->toBeInstanceOf(NotificationChannel::class)
        ->and($preference->channel)->toBe(NotificationChannel::Email);
});

it('member communication preference casts is_subscribed to boolean', function () {
    $preference = MemberCommunicationPreference::factory()->subscribed()->create();

    expect($preference->is_subscribed)->toBeTrue();

    $unsubscribed = MemberCommunicationPreference::factory()->unsubscribed()->create();
    expect($unsubscribed->is_subscribed)->toBeFalse();
});

it('member communication preference tracks unsubscribed_at timestamp', function () {
    $preference = MemberCommunicationPreference::factory()->unsubscribed()->create();

    expect($preference->unsubscribed_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

it('communication template soft deletes', function () {
    $template = CommunicationTemplate::factory()->create();
    $id = $template->id;

    $template->delete();

    expect(CommunicationTemplate::find($id))->toBeNull()
        ->and(CommunicationTemplate::withTrashed()->find($id))->not->toBeNull();
});

it('can query reminder logs by status', function () {
    ReminderLog::factory()->scheduled()->count(3)->create();
    ReminderLog::factory()->sent()->count(2)->create();
    ReminderLog::factory()->failed()->count(1)->create();

    $scheduled = ReminderLog::where('status', ReminderStatus::Scheduled)->count();
    $sent = ReminderLog::where('status', ReminderStatus::Sent)->count();
    $failed = ReminderLog::where('status', ReminderStatus::Failed)->count();

    expect($scheduled)->toBe(3)
        ->and($sent)->toBe(2)
        ->and($failed)->toBe(1);
});

it('can query reminder logs by channel', function () {
    ReminderLog::factory()->email()->count(4)->create();
    ReminderLog::factory()->sms()->count(2)->create();

    $email = ReminderLog::where('channel', NotificationChannel::Email)->count();
    $sms = ReminderLog::where('channel', NotificationChannel::SMS)->count();

    expect($email)->toBe(4)
        ->and($sms)->toBe(2);
});

it('can query communication templates by type', function () {
    CommunicationTemplate::factory()->email()->count(3)->create();
    CommunicationTemplate::factory()->sms()->count(2)->create();

    $email = CommunicationTemplate::where('type', NotificationChannel::Email)->count();
    $sms = CommunicationTemplate::where('type', NotificationChannel::SMS)->count();

    expect($email)->toBe(3)
        ->and($sms)->toBe(2);
});

it('can query default communication templates', function () {
    CommunicationTemplate::factory()->default()->count(2)->create();
    CommunicationTemplate::factory()->count(3)->create(['is_default' => false]);

    $defaults = CommunicationTemplate::where('is_default', true)->count();

    expect($defaults)->toBe(2);
});

it('can query member preferences by subscription status', function () {
    MemberCommunicationPreference::factory()->subscribed()->count(5)->create();
    MemberCommunicationPreference::factory()->unsubscribed()->count(3)->create();

    $subscribed = MemberCommunicationPreference::where('is_subscribed', true)->count();
    $unsubscribed = MemberCommunicationPreference::where('is_subscribed', false)->count();

    expect($subscribed)->toBe(5)
        ->and($unsubscribed)->toBe(3);
});
