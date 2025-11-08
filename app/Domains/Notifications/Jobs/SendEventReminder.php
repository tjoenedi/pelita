<?php

namespace App\Domains\Notifications\Jobs;

use App\Domains\Notifications\DTOs\ReminderContext;
use App\Domains\Notifications\Services\NotificationService;
use App\Enums\NotificationChannel;
use App\Enums\ReminderStatus;
use App\Models\CommunicationTemplate;
use App\Models\Event;
use App\Models\Member;
use App\Models\Position;
use App\Models\ReminderLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendEventReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $logId,
        public int $memberId,
        public int $eventId,
        public ?int $positionId = null,
    ) {}

    public function handle(NotificationService $notificationService): void
    {
        $log = ReminderLog::find($this->logId);

        if (! $log || $log->status !== ReminderStatus::Scheduled) {
            Log::info('Skipping reminder: log not found or not scheduled', [
                'log_id' => $this->logId,
            ]);

            return;
        }

        $member = Member::find($this->memberId);
        $event = Event::with(['organization', 'eventType'])->find($this->eventId);
        $position = $this->positionId ? Position::find($this->positionId) : null;

        if (! $member || ! $event) {
            $log->update([
                'status' => ReminderStatus::Failed,
                'failure_reason' => 'Member or event not found',
            ]);

            return;
        }

        $context = new ReminderContext($member, $event, $position);

        $templateId = $log->template_snapshot['template_id'] ?? null;
        $channel = NotificationChannel::from($log->template_snapshot['channel']);

        $template = $templateId ? CommunicationTemplate::find($templateId) : null;

        if (! $template) {
            $log->update([
                'status' => ReminderStatus::Failed,
                'failure_reason' => 'Template not found',
            ]);

            return;
        }

        $notificationService->sendReminder($member, $context, $template, $channel, $log);
    }
}
