<div>
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <flux:heading size="xl">Event Types</flux:heading>
            <flux:text>Manage event type templates</flux:text>
        </div>
        <flux:button variant="primary" :href="route('event-types.create')" wire:navigate icon="plus">
            Create Event Type
        </flux:button>
    </div>

    <!-- Success Message -->
    @if (session()->has('success'))
        <flux:callout variant="success" dismissible class="mb-6">
            {{ session('success') }}
        </flux:callout>
    @endif

    <!-- Search Section -->
    <div class="mb-6">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Search event types..."
            icon="magnifying-glass"
            clearable
        />
    </div>

    <!-- Event Types Table -->
    <flux:table :paginate="$this->eventTypes">
        <flux:table.columns>
            <flux:table.column
                sortable
                :sorted="$sortBy === 'name'"
                :direction="$sortBy === 'name' ? $sortDirection : null"
                wire:click="sort('name')"
            >
                Event Type Name
            </flux:table.column>

            <flux:table.column>Description</flux:table.column>

            <flux:table.column>Required Positions</flux:table.column>

            <flux:table.column align="center">Active Events</flux:table.column>

            <flux:table.column
                sortable
                :sorted="$sortBy === 'created_at'"
                :direction="$sortBy === 'created_at' ? $sortDirection : null"
                wire:click="sort('created_at')"
            >
                Created At
            </flux:table.column>

            <flux:table.column align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($this->eventTypes as $eventType)
                <flux:table.row :key="$eventType->id">
                    <flux:table.cell variant="strong">
                        {{ $eventType->name }}
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ Str::limit($eventType->description, 50) ?: 'No description' }}
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="flex flex-wrap gap-1">
                            @forelse($eventType->positions as $position)
                                <flux:badge size="sm" color="blue">
                                    {{ $position->name }}
                                </flux:badge>
                            @empty
                                <flux:text size="sm" dim>
                                    No positions assigned
                                </flux:text>
                            @endforelse
                        </div>
                    </flux:table.cell>

                    <flux:table.cell align="center">
                        <flux:badge
                            size="sm"
                            :color="$eventType->events->count() > 0 ? 'green' : 'gray'"
                        >
                            {{ $eventType->events->count() }} {{ Str::plural('event', $eventType->events->count()) }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $eventType->created_at->format('M d, Y') }}
                    </flux:table.cell>

                    <flux:table.cell align="end">
                        <div class="flex items-center justify-end gap-1">
                            <flux:button
                                variant="ghost"
                                size="sm"
                                :href="route('event-types.edit', $eventType)"
                                wire:navigate
                                icon="pencil-square"
                                aria-label="Edit {{ $eventType->name }}"
                            />

                            <flux:button
                                wire:click="deleteEventType({{ $eventType->id }})"
                                wire:confirm="Are you sure you want to delete this event type? All associated events will lose their type association."
                                variant="ghost"
                                size="sm"
                                icon="trash"
                                class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                aria-label="Delete {{ $eventType->name }}"
                            />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center py-12">
                        <div class="flex flex-col items-center">
                            <flux:icon name="calendar" variant="outline" class="w-12 h-12 text-gray-400 mb-4"/>
                            <flux:heading size="lg" class="mb-2">No Event Types Found</flux:heading>
                            <flux:text class="mb-6">
                                @if($search)
                                    No event types match your search criteria.
                                @else
                                    Get started by creating your first event type template.
                                @endif
                            </flux:text>
                            @if(!$search)
                                <flux:button variant="primary" :href="route('event-types.create')" wire:navigate icon="plus">
                                    Create Event Type
                                </flux:button>
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>