<?php

namespace App\Domains\Notifications\Services;

use App\Domains\Notifications\Contracts\EmailProviderInterface;
use App\Domains\Notifications\Contracts\TemplateRendererInterface;
use App\Domains\Notifications\DTOs\ReminderContext;
use App\Domains\SMS\Services\SMSService;
use App\Enums\NotificationChannel;
use App\Enums\ReminderStatus;
use App\Models\CommunicationTemplate;
use App\Models\Member;
use App\Models\MemberCommunicationPreference;
use App\Models\ReminderLog;
use Illuminate\Support\Facades\URL;

class NotificationService
{
    public function __construct(
        protected EmailProviderInterface $emailProvider,
        protected SMSService $smsService,
        protected TemplateRendererInterface $templateRenderer,
    ) {}

    public function sendReminder(
        Member $member,
        ReminderContext $context,
        CommunicationTemplate $template,
        NotificationChannel $channel,
        ReminderLog $log
    ): bool {
        if (! $this->checkMemberPreference($member, $context->event->event_type_id, $channel)) {
            $log->update([
                'status' => ReminderStatus::Cancelled,
                'failure_reason' => 'Member has unsubscribed from this type of notification',
            ]);

            return false;
        }

        $unsubscribeUrl = $this->generateUnsubscribeUrl($member, $context->event->event_type_id, $channel);
        $renderedContent = $this->templateRenderer->render($template->content, $context, $unsubscribeUrl);

        $success = match ($channel) {
            NotificationChannel::Email => $this->sendEmail($member, $template, $renderedContent),
            NotificationChannel::SMS => $this->sendSMS($member, $renderedContent),
            default => false,
        };

        if ($success) {
            $log->update([
                'status' => ReminderStatus::Sent,
                'sent_at' => now(),
            ]);
        } else {
            $failureReason = $channel === NotificationChannel::Email
                ? $this->emailProvider->getLastError()
                : 'SMS sending failed';

            $log->update([
                'status' => ReminderStatus::Failed,
                'failure_reason' => $failureReason,
            ]);
        }

        return $success;
    }

    protected function checkMemberPreference(Member $member, ?int $eventTypeId, NotificationChannel $channel): bool
    {
        $preference = MemberCommunicationPreference::where('member_id', $member->id)
            ->where('event_type_id', $eventTypeId)
            ->where(function ($query) use ($channel) {
                $query->where('channel', $channel)
                    ->orWhere('channel', NotificationChannel::All);
            })
            ->first();

        if ($preference && ! $preference->is_subscribed) {
            return false;
        }

        $globalPreference = MemberCommunicationPreference::where('member_id', $member->id)
            ->whereNull('event_type_id')
            ->where(function ($query) use ($channel) {
                $query->where('channel', $channel)
                    ->orWhere('channel', NotificationChannel::All);
            })
            ->first();

        if ($globalPreference && ! $globalPreference->is_subscribed) {
            return false;
        }

        return true;
    }

    protected function sendEmail(Member $member, CommunicationTemplate $template, string $content): bool
    {
        if (! $member->email) {
            return false;
        }

        return $this->emailProvider->send(
            $member->email,
            $template->subject ?? 'Event Reminder',
            $content
        );
    }

    protected function sendSMS(Member $member, string $content): bool
    {
        if (! $member->phone) {
            return false;
        }

        $result = $this->smsService->sendSMS($member->phone, $content);

        return ! isset($result['errorCode']);
    }

    protected function generateUnsubscribeUrl(Member $member, ?int $eventTypeId, NotificationChannel $channel): string
    {
        $preference = MemberCommunicationPreference::firstOrCreate(
            [
                'member_id' => $member->id,
                'organization_id' => $member->organization_id,
                'event_type_id' => $eventTypeId,
                'channel' => $channel,
            ],
            [
                'is_subscribed' => true,
            ]
        );

        return URL::to('/unsubscribe/'.$preference->unsubscribe_token);
    }
}
