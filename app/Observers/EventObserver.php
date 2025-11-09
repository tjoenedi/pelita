<?php

namespace App\Observers;

use App\Domains\Notifications\Jobs\ScheduleEventReminders;
use App\Enums\ReminderMode;
use App\Models\Event;
use App\Models\ReminderLog;

class EventObserver
{
    /**
     * Handle the Event "created" event.
     */
    public function created(Event $event): void
    {
        // Auto-schedule reminders if reminder_mode is 'auto'
        if ($event->reminder_mode === ReminderMode::Auto) {
            ScheduleEventReminders::dispatch($event->id);
        }
    }

    /**
     * Handle the Event "updated" event.
     */
    public function updated(Event $event): void
    {
        // If the event date/time changed or reminder settings changed, reschedule reminders
        if ($event->wasChanged(['date', 'start_time', 'reminder_mode', 'reminder_override'])) {
            // Cancel existing scheduled reminders
            ReminderLog::where('event_id', $event->id)
                ->where('status', 'scheduled')
                ->update(['status' => 'cancelled']);

            // Reschedule if auto mode is enabled
            if ($event->reminder_mode === ReminderMode::Auto) {
                ScheduleEventReminders::dispatch($event->id);
            }
        }
    }

    /**
     * Handle the Event "deleted" event.
     */
    public function deleted(Event $event): void
    {
        // Cancel all scheduled reminders when event is deleted
        ReminderLog::where('event_id', $event->id)
            ->where('status', 'scheduled')
            ->update(['status' => 'cancelled']);
    }
}
