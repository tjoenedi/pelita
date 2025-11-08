<?php

namespace App\Domains\Notifications\Services;

use App\Domains\Notifications\Jobs\SendEventReminder;
use App\Enums\NotificationChannel;
use App\Enums\ReminderMode;
use App\Enums\ReminderStatus;
use App\Models\Event;
use App\Models\ReminderLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ReminderScheduler
{
    public function scheduleRemindersForEvent(Event $event): void
    {
        if ($event->reminder_mode === null || $event->reminder_mode === ReminderMode::Disabled) {
            return;
        }

        if ($event->reminder_mode === ReminderMode::Manual) {
            return;
        }

        $this->cancelExistingReminders($event);

        $scheduledTime = $this->calculateReminderTime($event);

        if (! $scheduledTime || $scheduledTime->isPast()) {
            Log::info("Skipping reminder scheduling for event {$event->id}: scheduled time is in the past");

            return;
        }

        $members = $this->getScheduledMembers($event);

        foreach ($members as $memberData) {
            $this->scheduleReminderForMember($event, $memberData, $scheduledTime);
        }
    }

    public function triggerManualReminders(Event $event): void
    {
        $members = $this->getScheduledMembers($event);

        foreach ($members as $memberData) {
            $this->scheduleReminderForMember($event, $memberData, now(), true);
        }
    }

    public function cancelExistingReminders(Event $event): void
    {
        ReminderLog::where('event_id', $event->id)
            ->where('status', ReminderStatus::Scheduled)
            ->update([
                'status' => ReminderStatus::Cancelled,
                'failure_reason' => 'Event updated or rescheduled',
            ]);
    }

    protected function calculateReminderTime(Event $event): ?Carbon
    {
        if (! $event->date) {
            return null;
        }

        $organization = $event->organization;
        $eventType = $event->eventType;

        $daysBefore = $organization->reminder_days_before ?? 3;
        $time = $organization->reminder_time ?? '15:00:00';
        $timezone = $event->timezone ?? $organization->timezone ?? 'America/New_York';

        if ($eventType && ! $eventType->reminder_enabled) {
            return null;
        }

        $eventDate = Carbon::parse($event->date)->setTimezone($timezone);

        if ($event->start_time) {
            $eventDate->setTimeFromTimeString($event->start_time);
        } else {
            $eventDate->setTime(9, 0);
        }

        $reminderDateTime = $eventDate->copy()->subDays($daysBefore);

        [$hours, $minutes] = explode(':', $time);
        $reminderDateTime->setTime((int) $hours, (int) $minutes);

        return $reminderDateTime->setTimezone('UTC');
    }

    protected function getScheduledMembers(Event $event): array
    {
        $members = [];

        $schedules = $event->positionSchedules()->with(['member', 'eventPosition.position'])->get();

        foreach ($schedules as $schedule) {
            if ($schedule->member) {
                $members[$schedule->member_id] = [
                    'member' => $schedule->member,
                    'position' => $schedule->eventPosition->position ?? null,
                ];
            }
        }

        return $members;
    }

    protected function scheduleReminderForMember(Event $event, array $memberData, Carbon $scheduledTime, bool $sendNow = false): void
    {
        $member = $memberData['member'];
        $position = $memberData['position'];

        $channels = $this->getChannelsToSend($event);

        foreach ($channels as $channel) {
            $log = ReminderLog::create([
                'member_id' => $member->id,
                'event_id' => $event->id,
                'event_type_id' => $event->event_type_id,
                'channel' => $channel,
                'scheduled_at' => $scheduledTime,
                'status' => ReminderStatus::Scheduled,
                'template_snapshot' => $this->getTemplateSnapshot($event, $channel),
            ]);

            $job = new SendEventReminder($log->id, $member->id, $event->id, $position?->id);

            if ($sendNow) {
                dispatch($job);
            } else {
                dispatch($job)->delay($scheduledTime);
            }
        }
    }

    protected function getChannelsToSend(Event $event): array
    {
        $channels = [];

        $eventType = $event->eventType;

        if ($eventType?->email_template_id) {
            $channels[] = NotificationChannel::Email;
        }

        if ($eventType?->sms_template_id) {
            $channels[] = NotificationChannel::SMS;
        }

        if (empty($channels)) {
            $templates = $event->organization->communicationTemplates()
                ->whereNull('event_type_id')
                ->where('is_default', true)
                ->get();

            foreach ($templates as $template) {
                $channels[] = $template->type;
            }
        }

        return array_unique($channels);
    }

    protected function getTemplateSnapshot(Event $event, NotificationChannel $channel): array
    {
        $eventType = $event->eventType;

        $templateId = match ($channel) {
            NotificationChannel::Email => $eventType?->email_template_id,
            NotificationChannel::SMS => $eventType?->sms_template_id,
            default => null,
        };

        if (! $templateId) {
            $template = $event->organization->communicationTemplates()
                ->whereNull('event_type_id')
                ->where('type', $channel)
                ->where('is_default', true)
                ->first();

            $templateId = $template?->id;
        }

        return [
            'template_id' => $templateId,
            'channel' => $channel->value,
        ];
    }
}
