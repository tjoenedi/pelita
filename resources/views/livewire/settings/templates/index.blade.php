<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Communication Templates</flux:heading>
        <flux:button variant="primary" href="{{ route('settings.templates.create') }}">
            Create Template
        </flux:button>
    </div>

    @if (session()->has('success'))
        <flux:callout variant="success" class="mb-4">
            {{ session('success') }}
        </flux:callout>
    @endif

    <!-- Filters -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <!-- Search -->
        <flux:field>
            <flux:label for="search">Search Templates</flux:label>
            <flux:input
                type="text"
                id="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Search by name, subject, or content..." />
        </flux:field>

        <!-- Type Filter -->
        <flux:field>
            <flux:label for="filterType">Type</flux:label>
            <flux:select
                id="filterType"
                wire:model.live="filterType">
                <option value="all">All Types</option>
                <option value="email">Email</option>
                <option value="sms">SMS</option>
            </flux:select>
        </flux:field>

        <!-- Event Type Filter -->
        <flux:field>
            <flux:label for="filterEventType">Event Type</flux:label>
            <flux:select
                id="filterEventType"
                wire:model.live="filterEventType">
                <option value="">All Event Types</option>
                <option value="0">General (All Events)</option>
                @foreach($this->eventTypes as $eventType)
                    <option value="{{ $eventType->id }}">{{ $eventType->name }}</option>
                @endforeach
            </flux:select>
        </flux:field>
    </div>

    <!-- Templates Table -->
    <flux:table :paginate="$this->templates">
        <flux:table.columns>
            <flux:table.column
                sortable
                :sorted="$sortBy === 'name'"
                :direction="$sortBy === 'name' ? $sortDirection : null"
                wire:click="sort('name')">
                Name
            </flux:table.column>

            <flux:table.column>
                Type
            </flux:table.column>

            <flux:table.column>
                Event Type
            </flux:table.column>

            <flux:table.column>
                Default
            </flux:table.column>

            <flux:table.column align="end">
                Actions
            </flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($this->templates as $template)
                <flux:table.row :key="$template->id">
                    <flux:table.cell variant="strong">
                        {{ $template->name }}
                    </flux:table.cell>

                    <flux:table.cell>
                        @if($template->type === \App\Enums\NotificationChannel::Email)
                            <flux:badge color="blue" size="sm">Email</flux:badge>
                        @else
                            <flux:badge color="green" size="sm">SMS</flux:badge>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        @if($template->eventType)
                            {{ $template->eventType->name }}
                        @else
                            <span class="text-zinc-500 dark:text-zinc-400">All Events</span>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        @if($template->is_default)
                            <flux:badge color="zinc" size="sm">Default</flux:badge>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell align="end">
                        <div class="flex items-center justify-end gap-2">
                            <flux:button
                                variant="ghost"
                                size="xs"
                                href="{{ route('settings.templates.edit', $template) }}">
                                Edit
                            </flux:button>

                            <flux:button
                                variant="ghost"
                                size="xs"
                                wire:click="delete({{ $template->id }})"
                                wire:confirm="Are you sure you want to delete this template?"
                                class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200">
                                Delete
                            </flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" align="center">
                        @if($search)
                            No templates found matching "{{ $search }}".
                        @else
                            No templates found.
                            <flux:button
                                variant="ghost"
                                size="sm"
                                href="{{ route('settings.templates.create') }}">
                                Create your first template
                            </flux:button>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
