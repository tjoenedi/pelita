<div>
    <div class="mb-6">
        <flux:heading size="xl">Create Communication Template</flux:heading>
        <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
            Create a new email or SMS template for event reminders.
        </flux:text>
    </div>

    <form wire:submit="save" class="space-y-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Form Section -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Template Name -->
                <flux:field>
                    <flux:label for="name">Template Name</flux:label>
                    <flux:input
                        type="text"
                        id="name"
                        wire:model.live="name"
                        placeholder="e.g., Sunday Service Reminder" />
                    <flux:description>
                        A descriptive name for this template.
                    </flux:description>
                    <flux:error name="name" />
                </flux:field>

                <!-- Type -->
                <flux:field>
                    <flux:label>Template Type</flux:label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <flux:radio
                                name="type"
                                value="email"
                                wire:model.live="type" />
                            <span>Email</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <flux:radio
                                name="type"
                                value="sms"
                                wire:model.live="type" />
                            <span>SMS</span>
                        </label>
                    </div>
                    <flux:error name="type" />
                </flux:field>

                <!-- Event Type -->
                <flux:field>
                    <flux:label for="event_type_id">Event Type (Optional)</flux:label>
                    <flux:select
                        id="event_type_id"
                        wire:model="event_type_id">
                        <option value="">All Events</option>
                        @foreach($this->eventTypes as $eventType)
                            <option value="{{ $eventType->id }}">{{ $eventType->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:description>
                        If specified, this template will only be used for this event type.
                    </flux:description>
                    <flux:error name="event_type_id" />
                </flux:field>

                <!-- Subject (Email Only) -->
                @if($type === 'email')
                    <flux:field>
                        <flux:label for="subject">Subject</flux:label>
                        <flux:input
                            type="text"
                            id="subject"
                            wire:model.live="subject"
                            placeholder="e.g., Reminder: {event_name} on {event_date}" />
                        <flux:description>
                            The email subject line. Use parameters like {event_name}, {event_date}.
                        </flux:description>
                        <flux:error name="subject" />
                    </flux:field>
                @endif

                <!-- Content -->
                <flux:field>
                    <flux:label for="content">Content</flux:label>
                    <flux:textarea
                        id="content"
                        wire:model.live="content"
                        rows="10"
                        placeholder="Hi {name},&#10;&#10;This is a reminder that you are scheduled for {event_name} as {position_name} on {event_date} at {event_time}.&#10;&#10;Thank you!&#10;{organization_name}" />
                    <flux:description>
                        The template content. Use the parameters shown on the right.
                        @if($type === 'sms')
                            <span class="font-semibold {{ $this->characterCount > 160 ? 'text-red-600 dark:text-red-400' : '' }}">
                                {{ $this->characterCount }} characters
                            </span>
                            @if($this->characterCount > 160)
                                (Warning: SMS messages over 160 characters may be split into multiple messages)
                            @endif
                        @endif
                    </flux:description>
                    <flux:error name="content" />
                </flux:field>

                <!-- Is Default -->
                <flux:field>
                    <flux:label for="is_default">Set as Default</flux:label>
                    <flux:checkbox
                        id="is_default"
                        wire:model="is_default" />
                    <flux:description>
                        If checked, this template will be the default for new events of this type.
                    </flux:description>
                    <flux:error name="is_default" />
                </flux:field>

                <!-- Append Unsubscribe Footer (Email Only) -->
                @if($type === 'email')
                    <flux:field>
                        <flux:label for="append_unsubscribe_footer">Append Unsubscribe Footer</flux:label>
                        <flux:checkbox
                            id="append_unsubscribe_footer"
                            wire:model="append_unsubscribe_footer" />
                        <flux:description>
                            Automatically add an unsubscribe link at the bottom of the email.
                        </flux:description>
                        <flux:error name="append_unsubscribe_footer" />
                    </flux:field>
                @endif

                <!-- Actions -->
                <div class="flex gap-3">
                    <flux:button type="submit" variant="primary">
                        Create Template
                    </flux:button>
                    <flux:button type="button" variant="ghost" wire:click="cancel">
                        Cancel
                    </flux:button>
                </div>
            </div>

            <!-- Sidebar Section -->
            <div class="space-y-6">
                <!-- Available Parameters -->
                <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4">
                    <h3 class="text-sm font-semibold mb-3 text-zinc-900 dark:text-zinc-100">Available Parameters</h3>
                    <dl class="space-y-2">
                        @foreach($availableParameters as $param => $description)
                            @if($type === 'sms' && $param === '{unsubscribe_link}')
                                @continue
                            @endif
                            <div>
                                <dt class="text-xs font-mono text-blue-600 dark:text-blue-400">{{ $param }}</dt>
                                <dd class="text-xs text-zinc-600 dark:text-zinc-400">{{ $description }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>

                <!-- Preview -->
                <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4">
                    <h3 class="text-sm font-semibold mb-3 text-zinc-900 dark:text-zinc-100">Preview</h3>
                    @if($type === 'email' && $subject)
                        <div class="mb-3">
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">Subject:</p>
                            <p class="text-sm text-zinc-900 dark:text-zinc-100 font-semibold">{{ $this->previewSubject }}</p>
                        </div>
                    @endif
                    @if($content)
                        <div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">Content:</p>
                            <div class="text-sm text-zinc-700 dark:text-zinc-300 whitespace-pre-wrap bg-white dark:bg-zinc-900 p-3 rounded border border-zinc-200 dark:border-zinc-700">{{ $this->previewContent }}</div>
                        </div>
                    @else
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 italic">Preview will appear here as you type...</p>
                    @endif
                </div>
            </div>
        </div>
    </form>
</div>
