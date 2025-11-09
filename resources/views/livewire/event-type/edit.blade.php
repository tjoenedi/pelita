<div>
    <form wire:submit="save" class="space-y-6">
        <!-- Success Message -->
        @if (session()->has('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">
                {{ session('success') }}
            </div>
        @endif

        <!-- Event Type Details Section -->
        <div class="bg-gray-50 dark:bg-zinc-700 px-4 py-3 rounded-lg">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Event Type Details</h3>
            <div class="space-y-4">
                <!-- Event Type Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Event Type Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           id="name"
                           wire:model="name"
                           class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400"
                           placeholder="e.g., Sunday Service, Youth Meeting">
                    @error('name')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Description
                    </label>
                    <textarea id="description"
                              wire:model="description"
                              rows="3"
                              class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400"
                              placeholder="Describe this event type template..."></textarea>
                    @error('description')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Required Positions Section -->
        <div class="bg-gray-50 dark:bg-zinc-700 px-4 py-3 rounded-lg">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Required Positions</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Select the positions that will be required for events of this type. You can reorder them after selection.
            </p>

            <!-- Position Selection -->
            <div class="space-y-2 mb-6">
                @foreach($this->availablePositions as $position)
                    <div class="flex items-center">
                        <input type="checkbox"
                               id="position_{{ $position->id }}"
                               wire:model.live="selectedPositions"
                               value="{{ $position->id }}"
                               class="mr-2 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                        <label for="position_{{ $position->id }}" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ $position->name }}
                            @if($position->description)
                                <span class="text-gray-500 dark:text-gray-400 ml-2">- {{ $position->description }}</span>
                            @endif
                        </label>
                    </div>
                @endforeach

                @if($this->availablePositions->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400 italic">
                        No positions available. Please create positions first.
                    </p>
                @endif
            </div>

            <!-- Selected Positions Order -->
            @if(count($selectedPositions) > 0)
                <div class="border-t border-gray-200 dark:border-gray-600 pt-4">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Position Order</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                        Reorder positions as they should appear in events.
                    </p>
                    <div class="space-y-2">
                        @foreach($sortedSelectedPositions as $index => $positionId)
                            @php
                                $position = $this->availablePositions->find($positionId);
                            @endphp
                            @if($position)
                                <div class="flex items-center justify-between p-2 bg-white dark:bg-zinc-800 rounded-md border border-gray-200 dark:border-gray-600">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">
                                        {{ $index + 1 }}. {{ $position->name }}
                                    </span>
                                    <div class="flex gap-1">
                                        @if($index > 0)
                                            <button type="button"
                                                    wire:click="movePositionUp({{ $positionId }})"
                                                    class="p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                                </svg>
                                            </button>
                                        @endif
                                        @if($index < count($sortedSelectedPositions) - 1)
                                            <button type="button"
                                                    wire:click="movePositionDown({{ $positionId }})"
                                                    class="p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Reminder Settings Section -->
        <div class="bg-gray-50 dark:bg-zinc-700 px-4 py-3 rounded-lg">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Reminder Settings</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Configure reminder defaults for this event type. If not enabled, organization defaults will be used.
            </p>

            <!-- Override Toggle -->
            <div class="mb-4">
                <label class="flex items-center">
                    <input type="checkbox"
                           wire:model.live="override_reminders"
                           class="mr-2 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Override organization defaults
                    </span>
                </label>
            </div>

            @if($override_reminders)
                <div class="space-y-4 pl-6 border-l-2 border-blue-500">
                    <!-- Days Before -->
                    <div>
                        <label for="reminder_days_before" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Days Before Event
                        </label>
                        <input type="number"
                               id="reminder_days_before"
                               wire:model="reminder_days_before"
                               min="0"
                               max="30"
                               class="w-full md:w-48 border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-gray-100">
                        @error('reminder_days_before')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Time of Day -->
                    <div>
                        <label for="reminder_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Time of Day
                        </label>
                        <input type="time"
                               id="reminder_time"
                               wire:model="reminder_time"
                               class="w-full md:w-48 border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-gray-100">
                        @error('reminder_time')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Email Template -->
                    <div>
                        <label for="email_template_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Email Template
                        </label>
                        <select id="email_template_id"
                                wire:model="email_template_id"
                                class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-gray-100">
                            <option value="">Use default</option>
                            @foreach($this->emailTemplates as $template)
                                <option value="{{ $template->id }}">{{ $template->name }}</option>
                            @endforeach
                        </select>
                        @error('email_template_id')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- SMS Template -->
                    <div>
                        <label for="sms_template_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            SMS Template
                        </label>
                        <select id="sms_template_id"
                                wire:model="sms_template_id"
                                class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-gray-100">
                            <option value="">Use default</option>
                            @foreach($this->smsTemplates as $template)
                                <option value="{{ $template->id }}">{{ $template->name }}</option>
                            @endforeach
                        </select>
                        @error('sms_template_id')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            @endif
        </div>

        <!-- Events Using This Type -->
        @if($eventType->events->count() > 0)
            <div class="bg-yellow-50 dark:bg-yellow-900/20 px-4 py-3 rounded-lg">
                <h4 class="text-sm font-medium text-yellow-800 dark:text-yellow-200 mb-2">Events Using This Type</h4>
                <p class="text-sm text-yellow-700 dark:text-yellow-300">
                    {{ $eventType->events->count() }} {{ Str::plural('event', $eventType->events->count()) }} are using this event type.
                    Changing positions here will affect the position requirements for all these events.
                </p>
            </div>
        @endif

        <!-- Action Buttons -->
        <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-600">
            <button type="button"
                    wire:click="cancel"
                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-zinc-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                Cancel
            </button>
            <button type="submit"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    class="px-4 py-2 bg-blue-600 dark:bg-blue-500 text-white rounded-md hover:bg-blue-700 dark:hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-50">
                <span wire:loading.remove wire:target="save">Update Event Type</span>
                <span wire:loading wire:target="save">Updating...</span>
            </button>
        </div>
    </form>
</div>