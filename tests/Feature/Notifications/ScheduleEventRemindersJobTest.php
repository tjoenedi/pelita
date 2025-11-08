<?php

use App\Domains\Notifications\Jobs\ScheduleEventReminders;
use App\Domains\Notifications\Jobs\SendEventReminder;
use App\Enums\ReminderMode;
use App\Models\CommunicationTemplate;
use App\Models\Event;
use App\Models\EventPosition;
use App\Models\EventPositionMember;
use App\Models\EventType;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Position;
use App\Models\ReminderLog;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
});

it('schedules reminders for all assigned members', function () {
    $organization = Organization::factory()->create([
        'reminder_days_before' => 3,
        'reminder_time' => '15:00:00',
    ]);

    $event = Event::factory()->create([
        'organization_id' => $organization->id,
        'date' => now()->addDays(10)->format('Y-m-d'),
        'reminder_mode' => ReminderMode::Auto,
    ]);

    $members = Member::factory()->count(3)->create(['organization_id' => $organization->id]);
    $position = Position::factory()->create(['organization_id' => $organization->id]);

    $eventPosition = EventPosition::factory()->create([
        'event_id' => $event->id,
        'position_id' => $position->id,
    ]);

    foreach ($members as $member) {
        EventPositionMember::factory()->create([
            'event_position_id' => $eventPosition->id,
            'member_id' => $member->id,
        ]);
    }

    CommunicationTemplate::factory()->email()->default()->create([
        'organization_id' => $organization->id,
    ]);

    $job = new ScheduleEventReminders($event->id);
    $job->handle(app(\App\Domains\Notifications\Services\ReminderScheduler::class));

    expect(ReminderLog::where('event_id', $event->id)->count())->toBe(3);
    Queue::assertPushed(SendEventReminder::class, 3);
});

it('does not schedule when reminder mode is disabled', function () {
    $organization = Organization::factory()->create();
    $event = Event::factory()->create([
        'organization_id' => $organization->id,
        'date' => now()->addDays(10)->format('Y-m-d'),
        'reminder_mode' => ReminderMode::Disabled,
    ]);

    $job = new ScheduleEventReminders($event->id);
    $job->handle(app(\App\Domains\Notifications\Services\ReminderScheduler::class));

    expect(ReminderLog::where('event_id', $event->id)->count())->toBe(0);
    Queue::assertNothingPushed();
});

it('does not schedule when reminder mode is manual', function () {
    $organization = Organization::factory()->create();
    $event = Event::factory()->create([
        'organization_id' => $organization->id,
        'date' => now()->addDays(10)->format('Y-m-d'),
        'reminder_mode' => ReminderMode::Manual,
    ]);

    $job = new ScheduleEventReminders($event->id);
    $job->handle(app(\App\Domains\Notifications\Services\ReminderScheduler::class));

    expect(ReminderLog::where('event_id', $event->id)->count())->toBe(0);
    Queue::assertNothingPushed();
});

it('handles non-existent event gracefully', function () {
    $job = new ScheduleEventReminders(99999);
    $job->handle(app(\App\Domains\Notifications\Services\ReminderScheduler::class));

    Queue::assertNothingPushed();
});

it('schedules reminders with correct delay', function () {
    $organization = Organization::factory()->create([
        'reminder_days_before' => 3,
        'reminder_time' => '15:00:00',
        'timezone' => 'America/New_York',
    ]);

    $event = Event::factory()->create([
        'organization_id' => $organization->id,
        'date' => now()->addDays(10)->format('Y-m-d'),
        'start_time' => '10:00:00',
        'reminder_mode' => ReminderMode::Auto,
    ]);

    $member = Member::factory()->create(['organization_id' => $organization->id]);
    $position = Position::factory()->create(['organization_id' => $organization->id]);

    $eventPosition = EventPosition::factory()->create([
        'event_id' => $event->id,
        'position_id' => $position->id,
    ]);

    EventPositionMember::factory()->create([
        'event_position_id' => $eventPosition->id,
        'member_id' => $member->id,
    ]);

    CommunicationTemplate::factory()->email()->default()->create([
        'organization_id' => $organization->id,
    ]);

    $job = new ScheduleEventReminders($event->id);
    $job->handle(app(\App\Domains\Notifications\Services\ReminderScheduler::class));

    $log = ReminderLog::where('event_id', $event->id)->first();
    expect($log->scheduled_at)->not->toBeNull()
        ->and($log->scheduled_at->isFuture())->toBeTrue();
});

it('schedules multiple channels when templates exist', function () {
    $organization = Organization::factory()->create();
    $eventType = EventType::factory()->create(['organization_id' => $organization->id]);

    $emailTemplate = CommunicationTemplate::factory()->email()->create([
        'organization_id' => $organization->id,
        'event_type_id' => $eventType->id,
    ]);

    $smsTemplate = CommunicationTemplate::factory()->sms()->create([
        'organization_id' => $organization->id,
        'event_type_id' => $eventType->id,
    ]);

    $eventType->update([
        'email_template_id' => $emailTemplate->id,
        'sms_template_id' => $smsTemplate->id,
    ]);

    $event = Event::factory()->create([
        'organization_id' => $organization->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10)->format('Y-m-d'),
        'reminder_mode' => ReminderMode::Auto,
    ]);

    $member = Member::factory()->create(['organization_id' => $organization->id]);
    $position = Position::factory()->create(['organization_id' => $organization->id]);

    $eventPosition = EventPosition::factory()->create([
        'event_id' => $event->id,
        'position_id' => $position->id,
    ]);

    EventPositionMember::factory()->create([
        'event_position_id' => $eventPosition->id,
        'member_id' => $member->id,
    ]);

    $job = new ScheduleEventReminders($event->id);
    $job->handle(app(\App\Domains\Notifications\Services\ReminderScheduler::class));

    expect(ReminderLog::where('event_id', $event->id)->count())->toBe(2);
    Queue::assertPushed(SendEventReminder::class, 2);
});

it('does not schedule reminders for events in the past', function () {
    $organization = Organization::factory()->create([
        'reminder_days_before' => 3,
        'reminder_time' => '15:00:00',
    ]);

    $event = Event::factory()->create([
        'organization_id' => $organization->id,
        'date' => now()->subDays(5)->format('Y-m-d'),
        'reminder_mode' => ReminderMode::Auto,
    ]);

    $member = Member::factory()->create(['organization_id' => $organization->id]);
    $position = Position::factory()->create(['organization_id' => $organization->id]);

    $eventPosition = EventPosition::factory()->create([
        'event_id' => $event->id,
        'position_id' => $position->id,
    ]);

    EventPositionMember::factory()->create([
        'event_position_id' => $eventPosition->id,
        'member_id' => $member->id,
    ]);

    CommunicationTemplate::factory()->email()->default()->create([
        'organization_id' => $organization->id,
    ]);

    $job = new ScheduleEventReminders($event->id);
    $job->handle(app(\App\Domains\Notifications\Services\ReminderScheduler::class));

    expect(ReminderLog::where('event_id', $event->id)->count())->toBe(0);
});
