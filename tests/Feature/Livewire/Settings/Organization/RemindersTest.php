<?php

use App\Livewire\Settings\Organization\Reminders;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->organization = Organization::factory()->create([
        'reminder_enabled' => true,
        'reminder_days_before' => 3,
        'reminder_time' => '15:00:00',
        'timezone' => 'America/New_York',
    ]);

    $this->user->organizations()->attach($this->organization->id);
    actingAs($this->user);
});

it('renders successfully for authenticated user', function () {
    Livewire::test(Reminders::class)
        ->assertSuccessful();
});

it('loads existing organization reminder settings', function () {
    Livewire::test(Reminders::class)
        ->assertSet('reminder_enabled', true)
        ->assertSet('reminder_days_before', 3)
        ->assertSet('reminder_time', '15:00')
        ->assertSet('timezone', 'America/New_York');
});

it('saves reminder settings successfully', function () {
    Livewire::test(Reminders::class)
        ->set('reminder_enabled', true)
        ->set('reminder_days_before', 5)
        ->set('reminder_time', '09:00')
        ->set('timezone', 'America/Los_Angeles')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('reminder-settings-updated');

    $this->organization->refresh();

    expect($this->organization->reminder_enabled)->toBeTrue()
        ->and($this->organization->reminder_days_before)->toBe(5)
        ->and($this->organization->reminder_time)->toBe('09:00')
        ->and($this->organization->timezone)->toBe('America/Los_Angeles');
});

it('validates reminder_days_before is required', function () {
    Livewire::test(Reminders::class)
        ->set('reminder_days_before', null)
        ->call('save')
        ->assertHasErrors(['reminder_days_before' => 'required']);
});

it('validates reminder_days_before is integer', function () {
    Livewire::test(Reminders::class)
        ->set('reminder_days_before', 'not-a-number')
        ->call('save')
        ->assertHasErrors(['reminder_days_before']);
});

it('validates reminder_days_before minimum value', function () {
    Livewire::test(Reminders::class)
        ->set('reminder_days_before', -1)
        ->call('save')
        ->assertHasErrors(['reminder_days_before' => 'min']);
});

it('validates reminder_days_before maximum value', function () {
    Livewire::test(Reminders::class)
        ->set('reminder_days_before', 31)
        ->call('save')
        ->assertHasErrors(['reminder_days_before' => 'max']);
});

it('validates reminder_time is required', function () {
    Livewire::test(Reminders::class)
        ->set('reminder_time', null)
        ->call('save')
        ->assertHasErrors(['reminder_time' => 'required']);
});

it('validates reminder_time format', function () {
    Livewire::test(Reminders::class)
        ->set('reminder_time', 'invalid-time')
        ->call('save')
        ->assertHasErrors(['reminder_time' => 'date_format']);
});

it('validates timezone is required', function () {
    Livewire::test(Reminders::class)
        ->set('timezone', null)
        ->call('save')
        ->assertHasErrors(['timezone' => 'required']);
});

it('validates timezone is in available list', function () {
    Livewire::test(Reminders::class)
        ->set('timezone', 'Invalid/Timezone')
        ->call('save')
        ->assertHasErrors(['timezone' => 'in']);
});

it('accepts all available timezones', function () {
    $timezones = [
        'America/New_York',
        'America/Chicago',
        'America/Denver',
        'America/Los_Angeles',
        'America/Phoenix',
        'America/Anchorage',
        'Pacific/Honolulu',
    ];

    foreach ($timezones as $timezone) {
        Livewire::test(Reminders::class)
            ->set('timezone', $timezone)
            ->call('save')
            ->assertHasNoErrors(['timezone']);

        $this->organization->refresh();
        expect($this->organization->timezone)->toBe($timezone);
    }
});

it('dispatches event on successful save', function () {
    Livewire::test(Reminders::class)
        ->set('reminder_days_before', 7)
        ->call('save')
        ->assertDispatched('reminder-settings-updated');
});

it('can disable reminders', function () {
    Livewire::test(Reminders::class)
        ->set('reminder_enabled', false)
        ->call('save')
        ->assertHasNoErrors();

    $this->organization->refresh();
    expect($this->organization->reminder_enabled)->toBeFalse();
});

it('can enable reminders', function () {
    $this->organization->update(['reminder_enabled' => false]);

    Livewire::test(Reminders::class)
        ->set('reminder_enabled', true)
        ->call('save')
        ->assertHasNoErrors();

    $this->organization->refresh();
    expect($this->organization->reminder_enabled)->toBeTrue();
});

it('loads default values when organization has no settings', function () {
    $newOrg = Organization::factory()->create([
        'reminder_enabled' => null,
        'reminder_days_before' => null,
        'reminder_time' => null,
        'timezone' => null,
    ]);

    $this->user->organizations()->attach($newOrg->id);
    $this->user->organizations()->detach($this->organization->id);

    Livewire::test(Reminders::class)
        ->assertSet('reminder_enabled', true)
        ->assertSet('reminder_days_before', 1)
        ->assertSet('reminder_time', '09:00')
        ->assertSet('timezone', 'America/New_York');
});

it('prevents unauthorized access to other organizations', function () {
    $otherOrganization = Organization::factory()->create();

    Livewire::test(Reminders::class)
        ->set('organization_id', $otherOrganization->id)
        ->call('save')
        ->assertForbidden();
});

it('handles edge case with 0 days before', function () {
    Livewire::test(Reminders::class)
        ->set('reminder_days_before', 0)
        ->call('save')
        ->assertHasNoErrors();

    $this->organization->refresh();
    expect($this->organization->reminder_days_before)->toBe(0);
});

it('handles edge case with 30 days before', function () {
    Livewire::test(Reminders::class)
        ->set('reminder_days_before', 30)
        ->call('save')
        ->assertHasNoErrors();

    $this->organization->refresh();
    expect($this->organization->reminder_days_before)->toBe(30);
});

it('converts reminder_days_before to integer when saving', function () {
    Livewire::test(Reminders::class)
        ->set('reminder_days_before', '7')
        ->call('save')
        ->assertHasNoErrors();

    $this->organization->refresh();
    expect($this->organization->reminder_days_before)->toBeInt()
        ->and($this->organization->reminder_days_before)->toBe(7);
});

it('aborts when user has no organization', function () {
    $userWithoutOrg = User::factory()->create();
    actingAs($userWithoutOrg);

    Livewire::test(Reminders::class)
        ->assertForbidden();
});

it('truncates time to HH:MM format', function () {
    $this->organization->update(['reminder_time' => '15:30:45']);

    Livewire::test(Reminders::class)
        ->assertSet('reminder_time', '15:30');
});

it('handles multiple saves correctly', function () {
    $component = Livewire::test(Reminders::class);

    $component
        ->set('reminder_days_before', 5)
        ->call('save')
        ->assertHasNoErrors();

    $this->organization->refresh();
    expect($this->organization->reminder_days_before)->toBe(5);

    $component
        ->set('reminder_days_before', 10)
        ->call('save')
        ->assertHasNoErrors();

    $this->organization->refresh();
    expect($this->organization->reminder_days_before)->toBe(10);
});
