<?php

use App\Enums\NotificationChannel;
use App\Enums\ReminderStatus;
use App\Livewire\Reports\ReminderLogs;
use App\Models\Event;
use App\Models\Member;
use App\Models\Organization;
use App\Models\ReminderLog;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->organization = Organization::factory()->create();
    $this->user->organizations()->attach($this->organization->id);
    actingAs($this->user);
});

it('renders successfully for authenticated user', function () {
    Livewire::test(ReminderLogs::class)
        ->assertSuccessful();
});

it('displays reminder logs for user organization', function () {
    $event = Event::factory()->create(['organization_id' => $this->organization->id]);
    $member = Member::factory()->create(['organization_id' => $this->organization->id]);

    $log = ReminderLog::factory()->create([
        'event_id' => $event->id,
        'member_id' => $member->id,
    ]);

    Livewire::test(ReminderLogs::class)
        ->assertSee($member->first_name)
        ->assertSee($event->name);
});

it('does not display logs from other organizations', function () {
    $otherOrg = Organization::factory()->create();
    $otherEvent = Event::factory()->create(['organization_id' => $otherOrg->id]);
    $otherMember = Member::factory()->create(['organization_id' => $otherOrg->id]);

    ReminderLog::factory()->create([
        'event_id' => $otherEvent->id,
        'member_id' => $otherMember->id,
    ]);

    $myEvent = Event::factory()->create(['organization_id' => $this->organization->id]);
    $myMember = Member::factory()->create(['organization_id' => $this->organization->id]);

    ReminderLog::factory()->create([
        'event_id' => $myEvent->id,
        'member_id' => $myMember->id,
    ]);

    Livewire::test(ReminderLogs::class)
        ->assertSee($myMember->first_name)
        ->assertDontSee($otherMember->first_name);
});

it('filters logs by search term on member name', function () {
    $event = Event::factory()->create(['organization_id' => $this->organization->id]);
    $member1 = Member::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'organization_id' => $this->organization->id,
    ]);
    $member2 = Member::factory()->create([
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'organization_id' => $this->organization->id,
    ]);

    ReminderLog::factory()->create(['event_id' => $event->id, 'member_id' => $member1->id]);
    ReminderLog::factory()->create(['event_id' => $event->id, 'member_id' => $member2->id]);

    Livewire::test(ReminderLogs::class)
        ->set('search', 'John')
        ->assertSee('John')
        ->assertDontSee('Jane');
});

it('filters logs by event', function () {
    $event1 = Event::factory()->create([
        'name' => 'Sunday Service',
        'organization_id' => $this->organization->id,
    ]);
    $event2 = Event::factory()->create([
        'name' => 'Bible Study',
        'organization_id' => $this->organization->id,
    ]);
    $member = Member::factory()->create(['organization_id' => $this->organization->id]);

    ReminderLog::factory()->create(['event_id' => $event1->id, 'member_id' => $member->id]);
    ReminderLog::factory()->create(['event_id' => $event2->id, 'member_id' => $member->id]);

    Livewire::test(ReminderLogs::class)
        ->set('filterEvent', $event1->id)
        ->assertSee('Sunday Service')
        ->assertDontSee('Bible Study');
});

it('filters logs by status', function () {
    $event = Event::factory()->create(['organization_id' => $this->organization->id]);
    $member = Member::factory()->create(['organization_id' => $this->organization->id]);

    $scheduled = ReminderLog::factory()->scheduled()->create([
        'event_id' => $event->id,
        'member_id' => $member->id,
    ]);
    $sent = ReminderLog::factory()->sent()->create([
        'event_id' => $event->id,
        'member_id' => $member->id,
    ]);

    $component = Livewire::test(ReminderLogs::class)
        ->set('filterStatus', [ReminderStatus::Scheduled->value]);

    $logs = $component->viewData('reminderLogs');

    expect($logs->contains($scheduled))->toBeTrue()
        ->and($logs->contains($sent))->toBeFalse();
});

it('filters logs by channel', function () {
    $event = Event::factory()->create(['organization_id' => $this->organization->id]);
    $member = Member::factory()->create(['organization_id' => $this->organization->id]);

    $emailLog = ReminderLog::factory()->email()->create([
        'event_id' => $event->id,
        'member_id' => $member->id,
    ]);
    $smsLog = ReminderLog::factory()->sms()->create([
        'event_id' => $event->id,
        'member_id' => $member->id,
    ]);

    $component = Livewire::test(ReminderLogs::class)
        ->set('filterChannel', NotificationChannel::Email->value);

    $logs = $component->viewData('reminderLogs');

    expect($logs->contains($emailLog))->toBeTrue()
        ->and($logs->contains($smsLog))->toBeFalse();
});

it('filters logs by date range', function () {
    $event = Event::factory()->create(['organization_id' => $this->organization->id]);
    $member = Member::factory()->create(['organization_id' => $this->organization->id]);

    $oldLog = ReminderLog::factory()->create([
        'event_id' => $event->id,
        'member_id' => $member->id,
        'scheduled_at' => now()->subDays(10),
    ]);
    $recentLog = ReminderLog::factory()->create([
        'event_id' => $event->id,
        'member_id' => $member->id,
        'scheduled_at' => now()->subDays(2),
    ]);

    $component = Livewire::test(ReminderLogs::class)
        ->set('filterDateFrom', now()->subDays(5)->format('Y-m-d'))
        ->set('filterDateTo', now()->format('Y-m-d'));

    $logs = $component->viewData('reminderLogs');

    expect($logs->contains($recentLog))->toBeTrue()
        ->and($logs->contains($oldLog))->toBeFalse();
});

it('sorts logs by column', function () {
    $event = Event::factory()->create(['organization_id' => $this->organization->id]);
    $member = Member::factory()->create(['organization_id' => $this->organization->id]);

    ReminderLog::factory()->count(3)->create([
        'event_id' => $event->id,
        'member_id' => $member->id,
    ]);

    Livewire::test(ReminderLogs::class)
        ->call('sort', 'status')
        ->assertSet('sortBy', 'status')
        ->assertSet('sortDirection', 'asc');
});

it('toggles sort direction when sorting same column', function () {
    Livewire::test(ReminderLogs::class)
        ->assertSet('sortDirection', 'desc')
        ->call('sort', 'scheduled_at')
        ->assertSet('sortDirection', 'asc')
        ->call('sort', 'scheduled_at')
        ->assertSet('sortDirection', 'desc');
});

it('resets page when search is updated', function () {
    Livewire::test(ReminderLogs::class)
        ->set('search', 'test')
        ->assertSet('search', 'test');
});

it('opens detail modal when viewing log details', function () {
    $event = Event::factory()->create(['organization_id' => $this->organization->id]);
    $member = Member::factory()->create(['organization_id' => $this->organization->id]);
    $log = ReminderLog::factory()->create([
        'event_id' => $event->id,
        'member_id' => $member->id,
    ]);

    Livewire::test(ReminderLogs::class)
        ->call('viewDetails', $log->id)
        ->assertSet('selectedLogId', $log->id)
        ->assertSet('showDetailModal', true);
});

it('closes detail modal', function () {
    $event = Event::factory()->create(['organization_id' => $this->organization->id]);
    $member = Member::factory()->create(['organization_id' => $this->organization->id]);
    $log = ReminderLog::factory()->create([
        'event_id' => $event->id,
        'member_id' => $member->id,
    ]);

    Livewire::test(ReminderLogs::class)
        ->call('viewDetails', $log->id)
        ->assertSet('showDetailModal', true)
        ->call('closeModal')
        ->assertSet('showDetailModal', false)
        ->assertSet('selectedLogId', null);
});

it('loads selected log with relationships', function () {
    $event = Event::factory()->create(['organization_id' => $this->organization->id]);
    $member = Member::factory()->create(['organization_id' => $this->organization->id]);
    $log = ReminderLog::factory()->create([
        'event_id' => $event->id,
        'member_id' => $member->id,
    ]);

    $component = Livewire::test(ReminderLogs::class)
        ->call('viewDetails', $log->id);

    $selectedLog = $component->viewData('selectedLog');

    expect($selectedLog)->not->toBeNull()
        ->and($selectedLog->id)->toBe($log->id)
        ->and($selectedLog->relationLoaded('member'))->toBeTrue()
        ->and($selectedLog->relationLoaded('event'))->toBeTrue();
});

it('paginates results', function () {
    $event = Event::factory()->create(['organization_id' => $this->organization->id]);
    $member = Member::factory()->create(['organization_id' => $this->organization->id]);

    ReminderLog::factory()->count(20)->create([
        'event_id' => $event->id,
        'member_id' => $member->id,
    ]);

    $component = Livewire::test(ReminderLogs::class);
    $logs = $component->viewData('reminderLogs');

    expect($logs->total())->toBe(20)
        ->and($logs->perPage())->toBe(15);
});

it('shows all channels when filter is set to all', function () {
    $event = Event::factory()->create(['organization_id' => $this->organization->id]);
    $member = Member::factory()->create(['organization_id' => $this->organization->id]);

    $emailLog = ReminderLog::factory()->email()->create([
        'event_id' => $event->id,
        'member_id' => $member->id,
    ]);
    $smsLog = ReminderLog::factory()->sms()->create([
        'event_id' => $event->id,
        'member_id' => $member->id,
    ]);

    $component = Livewire::test(ReminderLogs::class)
        ->set('filterChannel', 'all');

    $logs = $component->viewData('reminderLogs');

    expect($logs->contains($emailLog))->toBeTrue()
        ->and($logs->contains($smsLog))->toBeTrue();
});

it('loads events for filter dropdown', function () {
    Event::factory()->count(3)->create(['organization_id' => $this->organization->id]);

    $component = Livewire::test(ReminderLogs::class);
    $events = $component->viewData('events');

    expect($events->count())->toBe(3);
});

it('only loads events from user organizations', function () {
    $otherOrg = Organization::factory()->create();
    Event::factory()->create(['organization_id' => $otherOrg->id]);
    Event::factory()->count(2)->create(['organization_id' => $this->organization->id]);

    $component = Livewire::test(ReminderLogs::class);
    $events = $component->viewData('events');

    expect($events->count())->toBe(2);
});

it('resets page when any filter is updated', function () {
    Livewire::test(ReminderLogs::class)
        ->call('updatedFilterEvent')
        ->call('updatedFilterStatus')
        ->call('updatedFilterChannel')
        ->call('updatedFilterDateFrom')
        ->call('updatedFilterDateTo')
        ->assertSuccessful();
});

it('filters by multiple statuses', function () {
    $event = Event::factory()->create(['organization_id' => $this->organization->id]);
    $member = Member::factory()->create(['organization_id' => $this->organization->id]);

    $scheduled = ReminderLog::factory()->scheduled()->create([
        'event_id' => $event->id,
        'member_id' => $member->id,
    ]);
    $sent = ReminderLog::factory()->sent()->create([
        'event_id' => $event->id,
        'member_id' => $member->id,
    ]);
    $failed = ReminderLog::factory()->failed()->create([
        'event_id' => $event->id,
        'member_id' => $member->id,
    ]);

    $component = Livewire::test(ReminderLogs::class)
        ->set('filterStatus', [ReminderStatus::Scheduled->value, ReminderStatus::Sent->value]);

    $logs = $component->viewData('reminderLogs');

    expect($logs->contains($scheduled))->toBeTrue()
        ->and($logs->contains($sent))->toBeTrue()
        ->and($logs->contains($failed))->toBeFalse();
});

it('handles empty search gracefully', function () {
    $event = Event::factory()->create(['organization_id' => $this->organization->id]);
    $member = Member::factory()->create(['organization_id' => $this->organization->id]);

    ReminderLog::factory()->count(5)->create([
        'event_id' => $event->id,
        'member_id' => $member->id,
    ]);

    $component = Livewire::test(ReminderLogs::class)
        ->set('search', '');

    $logs = $component->viewData('reminderLogs');

    expect($logs->count())->toBe(5);
});

it('searches by member email', function () {
    $event = Event::factory()->create(['organization_id' => $this->organization->id]);
    $member = Member::factory()->create([
        'email' => 'unique@example.com',
        'organization_id' => $this->organization->id,
    ]);
    $otherMember = Member::factory()->create([
        'email' => 'other@example.com',
        'organization_id' => $this->organization->id,
    ]);

    ReminderLog::factory()->create(['event_id' => $event->id, 'member_id' => $member->id]);
    ReminderLog::factory()->create(['event_id' => $event->id, 'member_id' => $otherMember->id]);

    Livewire::test(ReminderLogs::class)
        ->set('search', 'unique@example.com')
        ->assertSee('unique@example.com')
        ->assertDontSee('other@example.com');
});

it('returns null for selectedLog when no log is selected', function () {
    $component = Livewire::test(ReminderLogs::class);
    $selectedLog = $component->viewData('selectedLog');

    expect($selectedLog)->toBeNull();
});
