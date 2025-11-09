<?php

use App\Domains\Notifications\Jobs\SendEventReminder;
use App\Domains\Notifications\Services\ReminderScheduler;
use App\Enums\NotificationChannel;
use App\Enums\ReminderMode;
use App\Enums\ReminderStatus;
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
    $this->scheduler = app(ReminderScheduler::class);
    Queue::fake();
});

it('schedules reminders for event with auto mode', function () {
    $organization = Organization::factory()->create([
        'reminder_days_before' => 3,
        'reminder_time' => '15:00:00',
        'timezone' => 'America/New_York',
    ]);

    $eventType = EventType::factory()->create([
        'organization_id' => $organization->id,
        'reminder_enabled' => true,
    ]);

    $event = Event::factory()->create([
        'organization_id' => $organization->id,
        'event_type_id' => $eventType->id,
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
        'event_id' => $event->id,
    ]);

    CommunicationTemplate::factory()->email()->default()->create([
        'organization_id' => $organization->id,
        'event_type_id' => null,
    ]);

    $this->scheduler->scheduleRemindersForEvent($event->fresh(['positionSchedules.member', 'positionSchedules.eventPosition.position', 'organization', 'eventType']));

    expect(ReminderLog::where('event_id', $event->id)->count())->toBeGreaterThan(0);
});

it('does not schedule reminders when mode is disabled', function () {
    $organization = Organization::factory()->create();
    $event = Event::factory()->create([
        'organization_id' => $organization->id,
        'date' => now()->addDays(10)->format('Y-m-d'),
        'reminder_mode' => ReminderMode::Disabled,
    ]);

    $this->scheduler->scheduleRemindersForEvent($event);

    expect(ReminderLog::where('event_id', $event->id)->count())->toBe(0);
});

it('does not schedule reminders when mode is manual', function () {
    $organization = Organization::factory()->create();
    $event = Event::factory()->create([
        'organization_id' => $organization->id,
        'date' => now()->addDays(10)->format('Y-m-d'),
        'reminder_mode' => ReminderMode::Manual,
    ]);

    $this->scheduler->scheduleRemindersForEvent($event);

    expect(ReminderLog::where('event_id', $event->id)->count())->toBe(0);
});

it('cancels existing scheduled reminders when rescheduling', function () {
    $organization = Organization::factory()->create();
    $event = Event::factory()->create([
        'organization_id' => $organization->id,
        'date' => now()->addDays(10)->format('Y-m-d'),
    ]);

    $log = ReminderLog::factory()->scheduled()->create([
        'event_id' => $event->id,
        'member_id' => Member::factory()->create(['organization_id' => $organization->id])->id,
    ]);

    $this->scheduler->cancelExistingReminders($event);

    $log->refresh();
    expect($log->status)->toBe(ReminderStatus::Cancelled)
        ->and($log->failure_reason)->toBe('Event updated or rescheduled');
});

it('calculates reminder time correctly for future event', function () {
    $organization = Organization::factory()->create([
        'reminder_days_before' => 3,
        'reminder_time' => '15:00:00',
        'timezone' => 'America/New_York',
    ]);

    $eventDate = now()->addDays(10)->setTime(10, 0, 0);
    $event = Event::factory()->create([
        'organization_id' => $organization->id,
        'date' => $eventDate->format('Y-m-d'),
        'start_time' => '10:00:00',
        'timezone' => 'America/New_York',
    ]);

    $reflection = new ReflectionClass($this->scheduler);
    $method = $reflection->getMethod('calculateReminderTime');
    $method->setAccessible(true);

    $reminderTime = $method->invoke($this->scheduler, $event);

    expect($reminderTime)->not->toBeNull()
        ->and($reminderTime->format('H:i'))->toBe('20:00'); // 15:00 EST = 20:00 UTC
});

it('returns null when event has no date', function () {
    $event = Event::factory()->create(['date' => null]);

    $reflection = new ReflectionClass($this->scheduler);
    $method = $reflection->getMethod('calculateReminderTime');
    $method->setAccessible(true);

    $reminderTime = $method->invoke($this->scheduler, $event);

    expect($reminderTime)->toBeNull();
});

it('returns null when event type has reminders disabled', function () {
    $organization = Organization::factory()->create();
    $eventType = EventType::factory()->create([
        'organization_id' => $organization->id,
        'reminder_enabled' => false,
    ]);

    $event = Event::factory()->create([
        'organization_id' => $organization->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10)->format('Y-m-d'),
    ]);

    $reflection = new ReflectionClass($this->scheduler);
    $method = $reflection->getMethod('calculateReminderTime');
    $method->setAccessible(true);

    $reminderTime = $method->invoke($this->scheduler, $event);

    expect($reminderTime)->toBeNull();
});

it('triggers manual reminders immediately', function () {
    $organization = Organization::factory()->create();
    $event = Event::factory()->create([
        'organization_id' => $organization->id,
        'date' => now()->addDays(10)->format('Y-m-d'),
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
        'event_id' => $event->id,
    ]);

    CommunicationTemplate::factory()->email()->default()->create([
        'organization_id' => $organization->id,
    ]);

    $this->scheduler->triggerManualReminders($event->fresh(['positionSchedules.member', 'positionSchedules.eventPosition.position']));

    Queue::assertPushed(SendEventReminder::class);
});

it('uses organization default reminder settings', function () {
    $organization = Organization::factory()->create([
        'reminder_days_before' => 5,
        'reminder_time' => '09:00:00',
        'timezone' => 'America/Los_Angeles',
    ]);

    $event = Event::factory()->create([
        'organization_id' => $organization->id,
        'date' => now()->addDays(10)->format('Y-m-d'),
        'start_time' => '14:00:00',
        'timezone' => null,
    ]);

    $reflection = new ReflectionClass($this->scheduler);
    $method = $reflection->getMethod('calculateReminderTime');
    $method->setAccessible(true);

    $reminderTime = $method->invoke($this->scheduler, $event);

    expect($reminderTime)->not->toBeNull()
        ->and($reminderTime->format('H:i'))->toBe('17:00'); // 09:00 PST = 17:00 UTC
});

it('schedules both email and sms when templates exist', function () {
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
        'event_id' => $event->id,
    ]);

    $this->scheduler->scheduleRemindersForEvent($event->fresh(['positionSchedules.member', 'positionSchedules.eventPosition.position', 'organization', 'eventType']));

    $logs = ReminderLog::where('event_id', $event->id)
        ->where('member_id', $member->id)
        ->get();

    expect($logs->count())->toBe(2)
        ->and($logs->pluck('channel')->toArray())->toContain(NotificationChannel::Email)
        ->and($logs->pluck('channel')->toArray())->toContain(NotificationChannel::SMS);
});
