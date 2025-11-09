<div>
    <form wire:submit="save" class="space-y-6">
        <!-- Success Message -->
        @if (session()->has('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">
                {{ session('success') }}
            </div>
        @endif

        <!-- Event Details Section -->
        <div class="bg-gray-50 dark:bg-zinc-700 px-4 py-3 rounded-lg">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Event Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Event Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Event Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           id="name"
                           wire:model="name"
                           class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400"
                           placeholder="e.g., Sunday Service">
                    @error('name')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Event Type -->
                <div>
                    <label for="event_type_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Event Type
                    </label>
                    <select id="event_type_id"
                            wire:model.live="event_type_id"
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400">
                        @if($this->eventTypes->isEmpty())
                            <option value="">General</option>
                        @else
                            <option value="">General (No Type)</option>
                            @foreach($this->eventTypes as $eventType)
                                <option value="{{ $eventType->id }}">{{ $eventType->name }}</option>
                            @endforeach
                        @endif
                    </select>
                    @error('event_type_id')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Description -->
                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Description
                    </label>
                    <textarea id="description"
                              wire:model="description"
                              rows="3"
                              class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400"
                              placeholder="Provide a brief description of the event..."></textarea>
                    @error('description')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Date and Time Section -->
        <div class="bg-gray-50 dark:bg-zinc-700 px-4 py-3 rounded-lg">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Date and Time</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Event Date -->
                <div>
                    <label for="date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Event Date
                    </label>
                    <input type="date"
                           id="date"
                           wire:model="date"
                           class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400">
                    @error('date')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- All Day Event -->
                <div class="flex items-center">
                    <input type="checkbox"
                           id="all_day"
                           wire:model.live="all_day"
                           class="mr-2 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                    <label for="all_day" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        All Day Event
                    </label>
                </div>

                @if(!$all_day)
                    <!-- Start Time -->
                    <div>
                        <label for="start_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Start Time <span class="text-red-500">*</span>
                        </label>
                        <input type="time"
                               id="start_time"
                               wire:model="start_time"
                               class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400">
                        @error('start_time')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- End Time -->
                    <div>
                        <label for="end_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            End Time <span class="text-red-500">*</span>
                        </label>
                        <input type="time"
                               id="end_time"
                               wire:model="end_time"
                               class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400">
                        @error('end_time')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                @endif
            </div>
        </div>

        <!-- Position Assignments Section -->
        <div class="bg-gray-50 dark:bg-zinc-700 px-4 py-3 rounded-lg">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Position Assignments</h3>

            @if($event_type_id)
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Positions are defined by the selected event type. Assign members to each required position.
                </p>
                <div class="space-y-4">
                    @php
                        $eventTypePositions = $this->availablePositions->whereIn('id', $selectedPositions);
                    @endphp
                    @foreach($eventTypePositions as $position)
                        <div class="flex items-center gap-4">
                            <div class="flex items-center min-w-[200px]">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ $position->name }}
                                </span>
                            </div>

                            <div class="flex-1">
                                <select wire:model="positionAssignments.{{ $position->id }}"
                                        class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-gray-100">
                                    <option value="">Select Member</option>
                                    @foreach($this->availableMembers as $member)
                                        <option value="{{ $member->id }}">
                                            {{ $member->first_name }} {{ $member->last_name }}
                                            @if($member->email)
                                                ({{ $member->email }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endforeach

                    @if($eventTypePositions->isEmpty())
                        <p class="text-sm text-gray-500 dark:text-gray-400 italic">
                            This event type has no positions defined. Edit the event type to add positions.
                        </p>
                    @endif
                </div>
            @else
                <div class="space-y-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Select positions for this event or choose an event type to use predefined positions.
                    </p>
                    @foreach($this->availablePositions as $position)
                        <div class="flex items-center gap-4">
                            <div class="flex items-center min-w-[200px]">
                                <input type="checkbox"
                                       id="position_{{ $position->id }}"
                                       wire:model="selectedPositions"
                                       value="{{ $position->id }}"
                                       class="mr-2 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                                <label for="position_{{ $position->id }}" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ $position->name }}
                                </label>
                            </div>

                            @if(in_array($position->id, $selectedPositions))
                                <div class="flex-1">
                                    <select wire:model="positionAssignments.{{ $position->id }}"
                                            class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-gray-100">
                                        <option value="">Select Member</option>
                                        @foreach($this->availableMembers as $member)
                                            <option value="{{ $member->id }}">
                                                {{ $member->first_name }} {{ $member->last_name }}
                                                @if($member->email)
                                                    ({{ $member->email }})
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>
                    @endforeach

                    @if($this->availablePositions->isEmpty())
                        <p class="text-sm text-gray-500 dark:text-gray-400 italic">
                            No positions available. Please create positions first.
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <!-- Reminder Settings Section -->
        <div class="bg-gray-50 dark:bg-zinc-700 px-4 py-3 rounded-lg">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Reminder Settings</h3>
            <div class="space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Control how and when reminders are sent to members assigned to this event.
                </p>

                <div class="space-y-3">
                    <!-- Auto Mode -->
                    <div class="flex items-center">
                        <input type="radio"
                               id="reminder_mode_auto"
                               wire:model="reminder_mode"
                               value="auto"
                               class="mr-2 border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                        <label for="reminder_mode_auto" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Automatic - Schedule reminders based on organization settings
                        </label>
                    </div>

                    <!-- Manual Mode -->
                    <div class="flex items-center">
                        <input type="radio"
                               id="reminder_mode_manual"
                               wire:model="reminder_mode"
                               value="manual"
                               class="mr-2 border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                        <label for="reminder_mode_manual" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Manual - Reminders must be sent manually
                        </label>
                    </div>

                    <!-- Disabled Mode -->
                    <div class="flex items-center">
                        <input type="radio"
                               id="reminder_mode_disabled"
                               wire:model="reminder_mode"
                               value="disabled"
                               class="mr-2 border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                        <label for="reminder_mode_disabled" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Disabled - No reminders will be sent
                        </label>
                    </div>
                </div>

                @error('reminder_mode')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <!-- Visibility Settings Section -->
        <div class="bg-gray-50 dark:bg-zinc-700 px-4 py-3 rounded-lg">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Visibility Settings</h3>
            <div class="space-y-4">
                <!-- Is Active -->
                <div class="flex items-center">
                    <input type="checkbox"
                           id="is_active"
                           wire:model="is_active"
                           class="mr-2 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                    <label for="is_active" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Active (Event is visible and can be scheduled)
                    </label>
                </div>

                <!-- Is Public -->
                <div class="flex items-center">
                    <input type="checkbox"
                           id="is_public"
                           wire:model="is_public"
                           class="mr-2 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                    <label for="is_public" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Public (Event is visible to non-members)
                    </label>
                </div>
            </div>
        </div>

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
                <span wire:loading.remove wire:target="save">Create Event</span>
                <span wire:loading wire:target="save">Creating...</span>
            </button>
        </div>
    </form>
</div>
