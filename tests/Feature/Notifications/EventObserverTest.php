<?php

use App\Domains\Notifications\Jobs\ScheduleEventReminders;
use App\Enums\ReminderMode;
use App\Enums\ReminderStatus;
use App\Models\Event;
use App\Models\Organization;
use App\Models\ReminderLog;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
});

it('schedules reminders when event is created with auto mode', function () {
    $organization = Organization::factory()->create();

    $event = Event::factory()->create([
        'organization_id' => $organization->id,
        'date' => now()->addDays(10)->format('Y-m-d'),
        'reminder_mode' => ReminderMode::Auto,
    ]);

    Queue::assertPushed(ScheduleEventReminders::class, function ($job) use ($event) {
        return $job->eventId === $event->id;
    });
});

it('does not schedule reminders when event is created with disabled mode', function () {
    $organization = Organization::factory()->create();

    Event::factory()->create([
        'organization_id' => $organization->id,
        'date' => now()->addDays(10)->format('Y-m-d'),
        'reminder_mode' => ReminderMode::Disabled,
    ]);

    Queue::assertNotPushed(ScheduleEventReminders::class);
});

it('does not schedule reminders when event is created with manual mode', function () {
    $organization = Organization::factory()->create();

    Event::factory()->create([
        'organization_id' => $organization->id,
        'date' => now()->addDays(10)->format('Y-m-d'),
        'reminder_mode' => ReminderMode::Manual,
    ]);

    Queue::assertNotPushed(ScheduleEventReminders::class);
});

it('reschedules reminders when event date is updated', function () {
    $organization = Organization::factory()->create();

    $event = Event::factory()->create([
        'organization_id' => $organization->id,
        'date' => now()->addDays(10)->format('Y-m-d'),
        'reminder_mode' => ReminderMode::Auto,
    ]);

    ReminderLog::factory()->scheduled()->create([
        'event_id' => $event->id,
    ]);

    Queue::fake();

    $event->update(['date' => now()->addDays(15)->format('Y-m-d')]);

    $log = ReminderLog::where('event_id', $event->id)->first();
    expect($log->status)->toBe(ReminderStatus::Cancelled);

    Queue::assertPushed(ScheduleEventReminders::class, function ($job) use ($event) {
        return $job->eventId === $event->id;
    });
});

it('reschedules reminders when event start time is updated', function () {
    $organization = Organization::factory()->create();

    $event = Event::factory()->create([
        'organization_id' => $organization->id,
        'date' => now()->addDays(10)->format('Y-m-d'),
        'start_time' => '10:00:00',
        'reminder_mode' => ReminderMode::Auto,
    ]);

    ReminderLog::factory()->scheduled()->create([
        'event_id' => $event->id,
    ]);

    Queue::fake();

    $event->update(['start_time' => '14:00:00']);

    $log = ReminderLog::where('event_id', $event->id)->first();
    expect($log->status)->toBe(ReminderStatus::Cancelled);

    Queue::assertPushed(ScheduleEventReminders::class);
});

it('cancels reminders when reminder mode changes to disabled', function () {
    $organization = Organization::factory()->create();

    $event = Event::factory()->create([
        'organization_id' => $organization->id,
        'date' => now()->addDays(10)->format('Y-m-d'),
        'reminder_mode' => ReminderMode::Auto,
    ]);

    ReminderLog::factory()->scheduled()->create([
        'event_id' => $event->id,
    ]);

    Queue::fake();

    $event->update(['reminder_mode' => ReminderMode::Disabled]);

    $log = ReminderLog::where('event_id', $event->id)->first();
    expect($log->status)->toBe(ReminderStatus::Cancelled);

    Queue::assertNotPushed(ScheduleEventReminders::class);
});

it('reschedules when reminder mode changes from manual to auto', function () {
    $organization = Organization::factory()->create();

    $event = Event::factory()->create([
        'organization_id' => $organization->id,
        'date' => now()->addDays(10)->format('Y-m-d'),
        'reminder_mode' => ReminderMode::Manual,
    ]);

    Queue::fake();

    $event->update(['reminder_mode' => ReminderMode::Auto]);

    Queue::assertPushed(ScheduleEventReminders::class, function ($job) use ($event) {
        return $job->eventId === $event->id;
    });
});

it('does not reschedule when non-relevant fields are updated', function () {
    $organization = Organization::factory()->create();

    $event = Event::factory()->create([
        'organization_id' => $organization->id,
        'date' => now()->addDays(10)->format('Y-m-d'),
        'reminder_mode' => ReminderMode::Auto,
        'name' => 'Original Name',
    ]);

    Queue::fake();

    $event->update(['name' => 'Updated Name']);

    Queue::assertNotPushed(ScheduleEventReminders::class);
});

it('cancels all scheduled reminders when event is deleted', function () {
    $organization = Organization::factory()->create();

    $event = Event::factory()->create([
        'organization_id' => $organization->id,
        'date' => now()->addDays(10)->format('Y-m-d'),
    ]);

    $log1 = ReminderLog::factory()->scheduled()->create(['event_id' => $event->id]);
    $log2 = ReminderLog::factory()->scheduled()->create(['event_id' => $event->id]);
    $log3 = ReminderLog::factory()->sent()->create(['event_id' => $event->id]);

    $event->delete();

    $log1->refresh();
    $log2->refresh();
    $log3->refresh();

    expect($log1->status)->toBe(ReminderStatus::Cancelled)
        ->and($log2->status)->toBe(ReminderStatus::Cancelled)
        ->and($log3->status)->toBe(ReminderStatus::Sent);
});

it('does not cancel already sent reminders when event is deleted', function () {
    $organization = Organization::factory()->create();

    $event = Event::factory()->create([
        'organization_id' => $organization->id,
        'date' => now()->addDays(10)->format('Y-m-d'),
    ]);

    $sentLog = ReminderLog::factory()->sent()->create(['event_id' => $event->id]);
    $failedLog = ReminderLog::factory()->failed()->create(['event_id' => $event->id]);

    $event->delete();

    $sentLog->refresh();
    $failedLog->refresh();

    expect($sentLog->status)->toBe(ReminderStatus::Sent)
        ->and($failedLog->status)->toBe(ReminderStatus::Failed);
});

it('cancels only scheduled reminders for the specific event', function () {
    $organization = Organization::factory()->create();

    $event1 = Event::factory()->create(['organization_id' => $organization->id]);
    $event2 = Event::factory()->create(['organization_id' => $organization->id]);

    $log1 = ReminderLog::factory()->scheduled()->create(['event_id' => $event1->id]);
    $log2 = ReminderLog::factory()->scheduled()->create(['event_id' => $event2->id]);

    $event1->delete();

    $log1->refresh();
    $log2->refresh();

    expect($log1->status)->toBe(ReminderStatus::Cancelled)
        ->and($log2->status)->toBe(ReminderStatus::Scheduled);
});

it('handles event update with reminder_override field change', function () {
    $organization = Organization::factory()->create();

    $event = Event::factory()->create([
        'organization_id' => $organization->id,
        'date' => now()->addDays(10)->format('Y-m-d'),
        'reminder_mode' => ReminderMode::Auto,
        'reminder_override' => null,
    ]);

    ReminderLog::factory()->scheduled()->create(['event_id' => $event->id]);

    Queue::fake();

    $event->update(['reminder_override' => '{"days_before": 5}']);

    $log = ReminderLog::where('event_id', $event->id)->first();
    expect($log->status)->toBe(ReminderStatus::Cancelled);

    Queue::assertPushed(ScheduleEventReminders::class);
});
