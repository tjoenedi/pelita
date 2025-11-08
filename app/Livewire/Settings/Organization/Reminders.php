<?php

namespace App\Livewire\Settings\Organization;

use App\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Reminders extends Component
{
    public $organization_id;

    public $reminder_enabled = true;

    public $reminder_days_before = 1;

    public $reminder_time = '09:00';

    public $timezone = 'America/New_York';

    public $availableTimezones = [
        'America/New_York' => 'Eastern Time (ET)',
        'America/Chicago' => 'Central Time (CT)',
        'America/Denver' => 'Mountain Time (MT)',
        'America/Los_Angeles' => 'Pacific Time (PT)',
        'America/Phoenix' => 'Arizona Time (MST)',
        'America/Anchorage' => 'Alaska Time (AKT)',
        'Pacific/Honolulu' => 'Hawaii Time (HST)',
    ];

    protected function rules(): array
    {
        return [
            'reminder_enabled' => 'boolean',
            'reminder_days_before' => 'required|integer|min:0|max:30',
            'reminder_time' => 'required|date_format:H:i',
            'timezone' => 'required|string|in:'.implode(',', array_keys($this->availableTimezones)),
        ];
    }

    protected $messages = [
        'reminder_days_before.required' => 'Days before event is required.',
        'reminder_days_before.min' => 'Days before must be at least 0.',
        'reminder_days_before.max' => 'Days before cannot exceed 30 days.',
        'reminder_time.required' => 'Time of day is required.',
        'reminder_time.date_format' => 'Time must be in HH:MM format.',
        'timezone.required' => 'Timezone is required.',
    ];

    public function mount()
    {
        /** @var \App\Models\Organization|null $organization */
        $organization = Auth::user()->organizations->first();

        if (! $organization) {
            abort(403, 'No organization found for user.');
        }

        $this->organization_id = $organization->id;

        // Load existing settings or use defaults (individual fields in migration)
        $this->reminder_enabled = $organization->reminder_enabled ?? true;
        $this->reminder_days_before = $organization->reminder_days_before ?? 1;
        $this->reminder_time = $organization->reminder_time ? substr($organization->reminder_time, 0, 5) : '09:00';
        $this->timezone = $organization->timezone ?? 'America/New_York';
    }

    public function save()
    {
        $this->validate();

        $organization = Organization::findOrFail($this->organization_id);

        // Ensure user has access to this organization
        if (! Auth::user()->organizations->contains($organization->id)) {
            abort(403, 'Unauthorized access to organization.');
        }

        $organization->update([
            'reminder_enabled' => $this->reminder_enabled,
            'reminder_days_before' => (int) $this->reminder_days_before,
            'reminder_time' => $this->reminder_time,
            'timezone' => $this->timezone,
        ]);

        session()->flash('success', 'Reminder settings updated successfully.');

        $this->dispatch('reminder-settings-updated');
    }

    public function render()
    {
        return view('livewire.settings.organization.reminders');
    }
}
