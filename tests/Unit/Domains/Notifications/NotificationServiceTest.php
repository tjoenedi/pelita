<?php

use App\Domains\Notifications\Contracts\EmailProviderInterface;
use App\Domains\Notifications\DTOs\ReminderContext;
use App\Domains\Notifications\Services\NotificationService;
use App\Domains\SMS\Services\SMSService;
use App\Enums\NotificationChannel;
use App\Enums\ReminderStatus;
use App\Models\CommunicationTemplate;
use App\Models\Event;
use App\Models\Member;
use App\Models\MemberCommunicationPreference;
use App\Models\Organization;
use App\Models\ReminderLog;

use function Pest\Laravel\mock;

beforeEach(function () {
    $this->emailProvider = mock(EmailProviderInterface::class);
    $this->smsService = mock(SMSService::class);
    $this->service = app(NotificationService::class);
});

it('sends email reminder successfully', function () {
    $organization = Organization::factory()->create();
    $member = Member::factory()->create([
        'email' => 'test@example.com',
        'organization_id' => $organization->id,
    ]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);
    $template = CommunicationTemplate::factory()->email()->create([
        'subject' => 'Event Reminder',
        'content' => 'Hi {name}',
        'organization_id' => $organization->id,
    ]);

    $log = ReminderLog::factory()->scheduled()->create([
        'member_id' => $member->id,
        'event_id' => $event->id,
        'channel' => NotificationChannel::Email,
    ]);

    $context = new ReminderContext($member, $event);

    $this->emailProvider->shouldReceive('send')
        ->once()
        ->with('test@example.com', 'Event Reminder', \Mockery::type('string'))
        ->andReturn(true);

    $result = $this->service->sendReminder($member, $context, $template, NotificationChannel::Email, $log);

    expect($result)->toBeTrue();
    $log->refresh();
    expect($log->status)->toBe(ReminderStatus::Sent)
        ->and($log->sent_at)->not->toBeNull();
});

it('sends sms reminder successfully', function () {
    $organization = Organization::factory()->create();
    $member = Member::factory()->create([
        'phone' => '+1234567890',
        'organization_id' => $organization->id,
    ]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);
    $template = CommunicationTemplate::factory()->sms()->create([
        'content' => 'Hi {name}',
        'organization_id' => $organization->id,
    ]);

    $log = ReminderLog::factory()->scheduled()->create([
        'member_id' => $member->id,
        'event_id' => $event->id,
        'channel' => NotificationChannel::SMS,
    ]);

    $context = new ReminderContext($member, $event);

    $this->smsService->shouldReceive('sendSMS')
        ->once()
        ->with('+1234567890', \Mockery::type('string'))
        ->andReturn(['success' => true]);

    $result = $this->service->sendReminder($member, $context, $template, NotificationChannel::SMS, $log);

    expect($result)->toBeTrue();
    $log->refresh();
    expect($log->status)->toBe(ReminderStatus::Sent);
});

it('does not send when member has unsubscribed from channel', function () {
    $organization = Organization::factory()->create();
    $member = Member::factory()->create([
        'email' => 'test@example.com',
        'organization_id' => $organization->id,
    ]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);
    $template = CommunicationTemplate::factory()->email()->create([
        'organization_id' => $organization->id,
    ]);

    MemberCommunicationPreference::factory()->email()->unsubscribed()->create([
        'member_id' => $member->id,
        'organization_id' => $organization->id,
        'event_type_id' => $event->event_type_id,
    ]);

    $log = ReminderLog::factory()->scheduled()->create([
        'member_id' => $member->id,
        'event_id' => $event->id,
        'channel' => NotificationChannel::Email,
    ]);

    $context = new ReminderContext($member, $event);

    $this->emailProvider->shouldNotReceive('send');

    $result = $this->service->sendReminder($member, $context, $template, NotificationChannel::Email, $log);

    expect($result)->toBeFalse();
    $log->refresh();
    expect($log->status)->toBe(ReminderStatus::Cancelled)
        ->and($log->failure_reason)->toContain('unsubscribed');
});

it('does not send when member has globally unsubscribed from channel', function () {
    $organization = Organization::factory()->create();
    $member = Member::factory()->create([
        'email' => 'test@example.com',
        'organization_id' => $organization->id,
    ]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);
    $template = CommunicationTemplate::factory()->email()->create([
        'organization_id' => $organization->id,
    ]);

    MemberCommunicationPreference::factory()->email()->unsubscribed()->create([
        'member_id' => $member->id,
        'organization_id' => $organization->id,
        'event_type_id' => null,
    ]);

    $log = ReminderLog::factory()->scheduled()->create([
        'member_id' => $member->id,
        'event_id' => $event->id,
        'channel' => NotificationChannel::Email,
    ]);

    $context = new ReminderContext($member, $event);

    $this->emailProvider->shouldNotReceive('send');

    $result = $this->service->sendReminder($member, $context, $template, NotificationChannel::Email, $log);

    expect($result)->toBeFalse();
    $log->refresh();
    expect($log->status)->toBe(ReminderStatus::Cancelled);
});

it('does not send when member has unsubscribed from all notifications', function () {
    $organization = Organization::factory()->create();
    $member = Member::factory()->create([
        'email' => 'test@example.com',
        'organization_id' => $organization->id,
    ]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);
    $template = CommunicationTemplate::factory()->email()->create([
        'organization_id' => $organization->id,
    ]);

    MemberCommunicationPreference::factory()->all()->unsubscribed()->create([
        'member_id' => $member->id,
        'organization_id' => $organization->id,
        'event_type_id' => $event->event_type_id,
    ]);

    $log = ReminderLog::factory()->scheduled()->create([
        'member_id' => $member->id,
        'event_id' => $event->id,
        'channel' => NotificationChannel::Email,
    ]);

    $context = new ReminderContext($member, $event);

    $this->emailProvider->shouldNotReceive('send');

    $result = $this->service->sendReminder($member, $context, $template, NotificationChannel::Email, $log);

    expect($result)->toBeFalse();
});

it('logs failure when email sending fails', function () {
    $organization = Organization::factory()->create();
    $member = Member::factory()->create([
        'email' => 'test@example.com',
        'organization_id' => $organization->id,
    ]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);
    $template = CommunicationTemplate::factory()->email()->create([
        'organization_id' => $organization->id,
    ]);

    $log = ReminderLog::factory()->scheduled()->create([
        'member_id' => $member->id,
        'event_id' => $event->id,
        'channel' => NotificationChannel::Email,
    ]);

    $context = new ReminderContext($member, $event);

    $this->emailProvider->shouldReceive('send')
        ->once()
        ->andReturn(false);

    $this->emailProvider->shouldReceive('getLastError')
        ->once()
        ->andReturn('SMTP connection failed');

    $result = $this->service->sendReminder($member, $context, $template, NotificationChannel::Email, $log);

    expect($result)->toBeFalse();
    $log->refresh();
    expect($log->status)->toBe(ReminderStatus::Failed)
        ->and($log->failure_reason)->toBe('SMTP connection failed');
});

it('logs failure when sms sending fails', function () {
    $organization = Organization::factory()->create();
    $member = Member::factory()->create([
        'phone' => '+1234567890',
        'organization_id' => $organization->id,
    ]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);
    $template = CommunicationTemplate::factory()->sms()->create([
        'organization_id' => $organization->id,
    ]);

    $log = ReminderLog::factory()->scheduled()->create([
        'member_id' => $member->id,
        'event_id' => $event->id,
        'channel' => NotificationChannel::SMS,
    ]);

    $context = new ReminderContext($member, $event);

    $this->smsService->shouldReceive('sendSMS')
        ->once()
        ->andReturn(['errorCode' => 'INVALID_NUMBER']);

    $result = $this->service->sendReminder($member, $context, $template, NotificationChannel::SMS, $log);

    expect($result)->toBeFalse();
    $log->refresh();
    expect($log->status)->toBe(ReminderStatus::Failed)
        ->and($log->failure_reason)->toBe('SMS sending failed');
});

it('does not send email when member has no email address', function () {
    $organization = Organization::factory()->create();
    $member = Member::factory()->create([
        'email' => null,
        'organization_id' => $organization->id,
    ]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);
    $template = CommunicationTemplate::factory()->email()->create([
        'organization_id' => $organization->id,
    ]);

    $log = ReminderLog::factory()->scheduled()->create([
        'member_id' => $member->id,
        'event_id' => $event->id,
        'channel' => NotificationChannel::Email,
    ]);

    $context = new ReminderContext($member, $event);

    $this->emailProvider->shouldNotReceive('send');
    $this->emailProvider->shouldReceive('getLastError')->andReturn(null);

    $result = $this->service->sendReminder($member, $context, $template, NotificationChannel::Email, $log);

    expect($result)->toBeFalse();
    $log->refresh();
    expect($log->status)->toBe(ReminderStatus::Failed);
});

it('does not send sms when member has no phone number', function () {
    $organization = Organization::factory()->create();
    $member = Member::factory()->create([
        'phone' => null,
        'organization_id' => $organization->id,
    ]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);
    $template = CommunicationTemplate::factory()->sms()->create([
        'organization_id' => $organization->id,
    ]);

    $log = ReminderLog::factory()->scheduled()->create([
        'member_id' => $member->id,
        'event_id' => $event->id,
        'channel' => NotificationChannel::SMS,
    ]);

    $context = new ReminderContext($member, $event);

    $this->smsService->shouldNotReceive('sendSMS');

    $result = $this->service->sendReminder($member, $context, $template, NotificationChannel::SMS, $log);

    expect($result)->toBeFalse();
    $log->refresh();
    expect($log->status)->toBe(ReminderStatus::Failed);
});

it('generates unsubscribe url and includes in template', function () {
    $organization = Organization::factory()->create();
    $member = Member::factory()->create([
        'email' => 'test@example.com',
        'organization_id' => $organization->id,
    ]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);
    $template = CommunicationTemplate::factory()->email()->create([
        'content' => 'Unsubscribe: {unsubscribe_link}',
        'organization_id' => $organization->id,
    ]);

    $log = ReminderLog::factory()->scheduled()->create([
        'member_id' => $member->id,
        'event_id' => $event->id,
        'channel' => NotificationChannel::Email,
    ]);

    $context = new ReminderContext($member, $event);

    $this->emailProvider->shouldReceive('send')
        ->once()
        ->andReturnUsing(function ($email, $subject, $content) {
            expect($content)->toContain('/unsubscribe/');

            return true;
        });

    $this->service->sendReminder($member, $context, $template, NotificationChannel::Email, $log);

    expect(MemberCommunicationPreference::where('member_id', $member->id)->exists())->toBeTrue();
});
