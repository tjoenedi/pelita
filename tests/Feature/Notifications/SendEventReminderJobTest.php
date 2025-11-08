<?php

use App\Domains\Notifications\Contracts\EmailProviderInterface;
use App\Domains\Notifications\Jobs\SendEventReminder;
use App\Domains\SMS\Services\SMSService;
use App\Enums\NotificationChannel;
use App\Enums\ReminderStatus;
use App\Models\CommunicationTemplate;
use App\Models\Event;
use App\Models\Member;
use App\Models\MemberCommunicationPreference;
use App\Models\Organization;
use App\Models\Position;
use App\Models\ReminderLog;

use function Pest\Laravel\mock;

beforeEach(function () {
    $this->emailProvider = mock(EmailProviderInterface::class);
    $this->smsService = mock(SMSService::class);
});

it('sends reminder to member successfully', function () {
    $organization = Organization::factory()->create();
    $member = Member::factory()->create([
        'email' => 'test@example.com',
        'organization_id' => $organization->id,
    ]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);
    $template = CommunicationTemplate::factory()->email()->create([
        'organization_id' => $organization->id,
    ]);

    $log = ReminderLog::factory()->scheduled()->email()->create([
        'member_id' => $member->id,
        'event_id' => $event->id,
        'template_snapshot' => [
            'template_id' => $template->id,
            'channel' => 'email',
        ],
    ]);

    $this->emailProvider->shouldReceive('send')
        ->once()
        ->andReturn(true);

    $job = new SendEventReminder($log->id, $member->id, $event->id);
    $job->handle(app(\App\Domains\Notifications\Services\NotificationService::class));

    $log->refresh();
    expect($log->status)->toBe(ReminderStatus::Sent)
        ->and($log->sent_at)->not->toBeNull();
});

it('skips sending when member has unsubscribed', function () {
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

    $log = ReminderLog::factory()->scheduled()->email()->create([
        'member_id' => $member->id,
        'event_id' => $event->id,
        'template_snapshot' => [
            'template_id' => $template->id,
            'channel' => 'email',
        ],
    ]);

    $this->emailProvider->shouldNotReceive('send');

    $job = new SendEventReminder($log->id, $member->id, $event->id);
    $job->handle(app(\App\Domains\Notifications\Services\NotificationService::class));

    $log->refresh();
    expect($log->status)->toBe(ReminderStatus::Cancelled)
        ->and($log->failure_reason)->toContain('unsubscribed');
});

it('updates log to failed when sending fails', function () {
    $organization = Organization::factory()->create();
    $member = Member::factory()->create([
        'email' => 'test@example.com',
        'organization_id' => $organization->id,
    ]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);
    $template = CommunicationTemplate::factory()->email()->create([
        'organization_id' => $organization->id,
    ]);

    $log = ReminderLog::factory()->scheduled()->email()->create([
        'member_id' => $member->id,
        'event_id' => $event->id,
        'template_snapshot' => [
            'template_id' => $template->id,
            'channel' => 'email',
        ],
    ]);

    $this->emailProvider->shouldReceive('send')
        ->once()
        ->andReturn(false);

    $this->emailProvider->shouldReceive('getLastError')
        ->once()
        ->andReturn('Connection timeout');

    $job = new SendEventReminder($log->id, $member->id, $event->id);
    $job->handle(app(\App\Domains\Notifications\Services\NotificationService::class));

    $log->refresh();
    expect($log->status)->toBe(ReminderStatus::Failed)
        ->and($log->failure_reason)->toBe('Connection timeout');
});

it('skips when log is not in scheduled status', function () {
    $organization = Organization::factory()->create();
    $member = Member::factory()->create(['organization_id' => $organization->id]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);

    $log = ReminderLog::factory()->sent()->create([
        'member_id' => $member->id,
        'event_id' => $event->id,
    ]);

    $this->emailProvider->shouldNotReceive('send');

    $job = new SendEventReminder($log->id, $member->id, $event->id);
    $job->handle(app(\App\Domains\Notifications\Services\NotificationService::class));
});

it('handles missing member gracefully', function () {
    $organization = Organization::factory()->create();
    $event = Event::factory()->create(['organization_id' => $organization->id]);

    $log = ReminderLog::factory()->scheduled()->create([
        'member_id' => 99999,
        'event_id' => $event->id,
    ]);

    $this->emailProvider->shouldNotReceive('send');

    $job = new SendEventReminder($log->id, 99999, $event->id);
    $job->handle(app(\App\Domains\Notifications\Services\NotificationService::class));

    $log->refresh();
    expect($log->status)->toBe(ReminderStatus::Failed)
        ->and($log->failure_reason)->toBe('Member or event not found');
});

it('handles missing event gracefully', function () {
    $organization = Organization::factory()->create();
    $member = Member::factory()->create(['organization_id' => $organization->id]);

    $log = ReminderLog::factory()->scheduled()->create([
        'member_id' => $member->id,
        'event_id' => 99999,
    ]);

    $this->emailProvider->shouldNotReceive('send');

    $job = new SendEventReminder($log->id, $member->id, 99999);
    $job->handle(app(\App\Domains\Notifications\Services\NotificationService::class));

    $log->refresh();
    expect($log->status)->toBe(ReminderStatus::Failed)
        ->and($log->failure_reason)->toBe('Member or event not found');
});

it('handles missing template gracefully', function () {
    $organization = Organization::factory()->create();
    $member = Member::factory()->create(['organization_id' => $organization->id]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);

    $log = ReminderLog::factory()->scheduled()->create([
        'member_id' => $member->id,
        'event_id' => $event->id,
        'template_snapshot' => [
            'template_id' => 99999,
            'channel' => 'email',
        ],
    ]);

    $this->emailProvider->shouldNotReceive('send');

    $job = new SendEventReminder($log->id, $member->id, $event->id);
    $job->handle(app(\App\Domains\Notifications\Services\NotificationService::class));

    $log->refresh();
    expect($log->status)->toBe(ReminderStatus::Failed)
        ->and($log->failure_reason)->toBe('Template not found');
});

it('handles missing log gracefully', function () {
    $this->emailProvider->shouldNotReceive('send');

    $job = new SendEventReminder(99999, 1, 1);
    $job->handle(app(\App\Domains\Notifications\Services\NotificationService::class));

    expect(true)->toBeTrue();
});

it('sends sms reminder successfully', function () {
    $organization = Organization::factory()->create();
    $member = Member::factory()->create([
        'phone' => '+1234567890',
        'organization_id' => $organization->id,
    ]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);
    $template = CommunicationTemplate::factory()->sms()->create([
        'organization_id' => $organization->id,
    ]);

    $log = ReminderLog::factory()->scheduled()->sms()->create([
        'member_id' => $member->id,
        'event_id' => $event->id,
        'template_snapshot' => [
            'template_id' => $template->id,
            'channel' => 'sms',
        ],
    ]);

    $this->smsService->shouldReceive('sendSMS')
        ->once()
        ->andReturn(['success' => true]);

    $job = new SendEventReminder($log->id, $member->id, $event->id);
    $job->handle(app(\App\Domains\Notifications\Services\NotificationService::class));

    $log->refresh();
    expect($log->status)->toBe(ReminderStatus::Sent);
});

it('includes position name in reminder context', function () {
    $organization = Organization::factory()->create();
    $member = Member::factory()->create([
        'email' => 'test@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'organization_id' => $organization->id,
    ]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);
    $position = Position::factory()->create([
        'name' => 'Worship Leader',
        'organization_id' => $organization->id,
    ]);
    $template = CommunicationTemplate::factory()->email()->create([
        'content' => 'Hi {name}, you are assigned as {position_name}',
        'organization_id' => $organization->id,
    ]);

    $log = ReminderLog::factory()->scheduled()->email()->create([
        'member_id' => $member->id,
        'event_id' => $event->id,
        'template_snapshot' => [
            'template_id' => $template->id,
            'channel' => 'email',
        ],
    ]);

    $this->emailProvider->shouldReceive('send')
        ->once()
        ->andReturnUsing(function ($email, $subject, $content) {
            expect($content)->toContain('John Doe')
                ->and($content)->toContain('Worship Leader');

            return true;
        });

    $job = new SendEventReminder($log->id, $member->id, $event->id, $position->id);
    $job->handle(app(\App\Domains\Notifications\Services\NotificationService::class));
});
