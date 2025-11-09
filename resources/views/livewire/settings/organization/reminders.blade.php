<div>
    <flux:heading size="xl" class="mb-6">Reminder Settings</flux:heading>

    <flux:text class="mb-8 text-zinc-600 dark:text-zinc-400">
        Configure default reminder settings for your organization. These settings will be used for all events unless overridden at the event type or event level.
    </flux:text>

    @if (session()->has('success'))
        <flux:callout variant="success" class="mb-6">
            {{ session('success') }}
        </flux:callout>
    @endif

    <form wire:submit="save" class="space-y-8">
        <!-- Enable Reminders Toggle -->
        <flux:field>
            <flux:label for="reminder_enabled">Enable Reminders</flux:label>
            <flux:switch
                id="reminder_enabled"
                wire:model.live="reminder_enabled"
                :checked="$reminder_enabled" />
            <flux:description>
                When enabled, event reminders will be sent to members automatically based on the settings below.
            </flux:description>
            <flux:error name="reminder_enabled" />
        </flux:field>

        <!-- Days Before Event -->
        <flux:field>
            <flux:label for="reminder_days_before">Days Before Event</flux:label>
            <flux:input
                type="number"
                id="reminder_days_before"
                wire:model.live="reminder_days_before"
                min="0"
                max="30"
                :disabled="!$reminder_enabled" />
            <flux:description>
                Number of days before the event to send reminders (0-30 days).
            </flux:description>
            <flux:error name="reminder_days_before" />
        </flux:field>

        <!-- Time of Day -->
        <flux:field>
            <flux:label for="reminder_time">Time of Day</flux:label>
            <flux:input
                type="time"
                id="reminder_time"
                wire:model.live="reminder_time"
                :disabled="!$reminder_enabled" />
            <flux:description>
                What time of day should reminders be sent? (Organization timezone)
            </flux:description>
            <flux:error name="reminder_time" />
        </flux:field>

        <!-- Timezone -->
        <flux:field>
            <flux:label for="timezone">Timezone</flux:label>
            <flux:select
                id="timezone"
                wire:model="timezone"
                :disabled="!$reminder_enabled">
                @foreach($availableTimezones as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </flux:select>
            <flux:description>
                Your organization's timezone for scheduling reminders.
            </flux:description>
            <flux:error name="timezone" />
        </flux:field>

        <!-- Actions -->
        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">
                Save Settings
            </flux:button>
        </div>
    </form>
</div>
