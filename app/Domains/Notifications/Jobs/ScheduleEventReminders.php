<?php

namespace App\Domains\Notifications\Jobs;

use App\Domains\Notifications\Services\ReminderScheduler;
use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScheduleEventReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $eventId,
    ) {}

    public function handle(ReminderScheduler $scheduler): void
    {
        $event = Event::with(['organization', 'eventType', 'positionSchedules.member', 'positionSchedules.eventPosition.position'])->find($this->eventId);

        if (! $event) {
            return;
        }

        $scheduler->scheduleRemindersForEvent($event);
    }
}
