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
        'first_name' => 'SearchTestAlpha',
        'last_name' => 'Doe',
        'organization_id' => $this->organization->id,
    ]);
    $member2 = Member::factory()->create([
        'first_name' => 'SearchTestBeta',
        'last_name' => 'Smith',
        'organization_id' => $this->organization->id,
    ]);

    ReminderLog::factory()->create(['event_id' => $event->id, 'member_id' => $member1->id]);
    ReminderLog::factory()->create(['event_id' => $event->id, 'member_id' => $member2->id]);

    Livewire::test(ReminderLogs::class)
        ->set('search', 'SearchTestAlpha')
        ->assertSee('SearchTestAlpha')
        ->assertDontSee('SearchTestBeta');
});

it('filters logs by event', function () {
    $event1 = Event::factory()->create([
        'name' => 'EventFilterTest1',
        'organization_id' => $this->organization->id,
    ]);
    $event2 = Event::factory()->create([
        'name' => 'EventFilterTest2',
        'organization_id' => $this->organization->id,
    ]);
    $member1 = Member::factory()->create([
        'first_name' => 'Event1User',
        'organization_id' => $this->organization->id,
    ]);
    $member2 = Member::factory()->create([
        'first_name' => 'Event2User',
        'organization_id' => $this->organization->id,
    ]);

    ReminderLog::factory()->create(['event_id' => $event1->id, 'member_id' => $member1->id]);
    ReminderLog::factory()->create(['event_id' => $event2->id, 'member_id' => $member2->id]);

    Livewire::test(ReminderLogs::class)
        ->set('filterEvent', $event1->id)
        ->assertSee('Event1User')
        ->assertDontSee('Event2User');
});

it('filters logs by status', function () {
    $event = Event::factory()->create(['organization_id' => $this->organization->id]);
    $member1 = Member::factory()->create([
        'first_name' => 'ScheduledUser',
        'organization_id' => $this->organization->id,
    ]);
    $member2 = Member::factory()->create([
        'first_name' => 'SentUser',
        'organization_id' => $this->organization->id,
    ]);

    ReminderLog::factory()->scheduled()->create([
        'event_id' => $event->id,
        'member_id' => $member1->id,
    ]);
    ReminderLog::factory()->sent()->create([
        'event_id' => $event->id,
        'member_id' => $member2->id,
    ]);

    Livewire::test(ReminderLogs::class)
        ->set('filterStatus', [ReminderStatus::Scheduled->value])
        ->assertSee('ScheduledUser')
        ->assertDontSee('SentUser');
});

it('filters logs by channel', function () {
    $event = Event::factory()->create(['organization_id' => $this->organization->id]);
    $member1 = Member::factory()->create([
        'first_name' => 'EmailUser',
        'organization_id' => $this->organization->id,
    ]);
    $member2 = Member::factory()->create([
        'first_name' => 'SMSUser',
        'organization_id' => $this->organization->id,
    ]);

    ReminderLog::factory()->email()->create([
        'event_id' => $event->id,
        'member_id' => $member1->id,
    ]);
    ReminderLog::factory()->sms()->create([
        'event_id' => $event->id,
        'member_id' => $member2->id,
    ]);

    Livewire::test(ReminderLogs::class)
        ->set('filterChannel', NotificationChannel::Email->value)
        ->assertSee('EmailUser')
        ->assertDontSee('SMSUser');
});

it('filters logs by date range', function () {
    $event = Event::factory()->create(['organization_id' => $this->organization->id]);
    $member1 = Member::factory()->create([
        'first_name' => 'OldUser',
        'organization_id' => $this->organization->id,
    ]);
    $member2 = Member::factory()->create([
        'first_name' => 'RecentUser',
        'organization_id' => $this->organization->id,
    ]);

    ReminderLog::factory()->create([
        'event_id' => $event->id,
        'member_id' => $member1->id,
        'scheduled_at' => now()->subDays(10),
    ]);
    ReminderLog::factory()->create([
        'event_id' => $event->id,
        'member_id' => $member2->id,
        'scheduled_at' => now()->subDays(2),
    ]);

    Livewire::test(ReminderLogs::class)
        ->set('filterDateFrom', now()->subDays(5)->format('Y-m-d'))
        ->set('filterDateTo', now()->format('Y-m-d'))
        ->assertSee('RecentUser')
        ->assertDontSee('OldUser');
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

    Livewire::test(ReminderLogs::class)
        ->call('viewDetails', $log->id)
        ->assertSet('selectedLogId', $log->id)
        ->assertSet('showDetailModal', true);
});

it('paginates results', function () {
    $event = Event::factory()->create(['organization_id' => $this->organization->id]);
    $member = Member::factory()->create([
        'first_name' => 'TestUser',
        'organization_id' => $this->organization->id,
    ]);

    ReminderLog::factory()->count(20)->create([
        'event_id' => $event->id,
        'member_id' => $member->id,
    ]);

    Livewire::test(ReminderLogs::class)
        ->assertSee('TestUser')
        ->assertSuccessful();
});

it('shows all channels when filter is set to all', function () {
    $event = Event::factory()->create(['organization_id' => $this->organization->id]);
    $member1 = Member::factory()->create([
        'first_name' => 'EmailUser',
        'organization_id' => $this->organization->id,
    ]);
    $member2 = Member::factory()->create([
        'first_name' => 'SMSUser',
        'organization_id' => $this->organization->id,
    ]);

    ReminderLog::factory()->email()->create([
        'event_id' => $event->id,
        'member_id' => $member1->id,
    ]);
    ReminderLog::factory()->sms()->create([
        'event_id' => $event->id,
        'member_id' => $member2->id,
    ]);

    Livewire::test(ReminderLogs::class)
        ->set('filterChannel', 'all')
        ->assertSee('EmailUser')
        ->assertSee('SMSUser');
});

it('loads events for filter dropdown', function () {
    Event::factory()->count(3)->create(['organization_id' => $this->organization->id]);

    Livewire::test(ReminderLogs::class)
        ->assertSuccessful();
});

it('only loads events from user organizations', function () {
    $otherOrg = Organization::factory()->create();
    Event::factory()->create(['organization_id' => $otherOrg->id]);
    Event::factory()->count(2)->create(['organization_id' => $this->organization->id]);

    Livewire::test(ReminderLogs::class)
        ->assertSuccessful();
});

it('filters by multiple statuses', function () {
    $event = Event::factory()->create(['organization_id' => $this->organization->id]);
    $member1 = Member::factory()->create([
        'first_name' => 'ScheduledUser',
        'organization_id' => $this->organization->id,
    ]);
    $member2 = Member::factory()->create([
        'first_name' => 'SentUser',
        'organization_id' => $this->organization->id,
    ]);
    $member3 = Member::factory()->create([
        'first_name' => 'FailedUser',
        'organization_id' => $this->organization->id,
    ]);

    ReminderLog::factory()->scheduled()->create([
        'event_id' => $event->id,
        'member_id' => $member1->id,
    ]);
    ReminderLog::factory()->sent()->create([
        'event_id' => $event->id,
        'member_id' => $member2->id,
    ]);
    ReminderLog::factory()->failed()->create([
        'event_id' => $event->id,
        'member_id' => $member3->id,
    ]);

    Livewire::test(ReminderLogs::class)
        ->set('filterStatus', [ReminderStatus::Scheduled->value, ReminderStatus::Sent->value])
        ->assertSee('ScheduledUser')
        ->assertSee('SentUser')
        ->assertDontSee('FailedUser');
});

it('handles empty search gracefully', function () {
    $event = Event::factory()->create(['organization_id' => $this->organization->id]);
    $member = Member::factory()->create([
        'first_name' => 'TestUser',
        'organization_id' => $this->organization->id,
    ]);

    ReminderLog::factory()->count(5)->create([
        'event_id' => $event->id,
        'member_id' => $member->id,
    ]);

    Livewire::test(ReminderLogs::class)
        ->set('search', '')
        ->assertSee('TestUser');
});

it('searches by member email', function () {
    $event = Event::factory()->create(['organization_id' => $this->organization->id]);
    $member = Member::factory()->create([
        'first_name' => 'UniqueUser',
        'email' => 'unique@example.com',
        'organization_id' => $this->organization->id,
    ]);
    $otherMember = Member::factory()->create([
        'first_name' => 'OtherUser',
        'email' => 'other@example.com',
        'organization_id' => $this->organization->id,
    ]);

    ReminderLog::factory()->create(['event_id' => $event->id, 'member_id' => $member->id]);
    ReminderLog::factory()->create(['event_id' => $event->id, 'member_id' => $otherMember->id]);

    Livewire::test(ReminderLogs::class)
        ->set('search', 'unique@example.com')
        ->assertSee('UniqueUser')
        ->assertDontSee('OtherUser');
});

it('returns null for selectedLog when no log is selected', function () {
    Livewire::test(ReminderLogs::class)
        ->assertSet('selectedLogId', null)
        ->assertSet('showDetailModal', false);
});
