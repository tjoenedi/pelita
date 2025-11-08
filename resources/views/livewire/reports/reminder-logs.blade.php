<div>
    <flux:heading size="xl" class="mb-6">Reminder Logs</flux:heading>

    <!-- Filters -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <!-- Search -->
        <flux:field>
            <flux:label for="search">Search Member</flux:label>
            <flux:input
                type="text"
                id="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Search by name or email..." />
        </flux:field>

        <!-- Event Filter -->
        <flux:field>
            <flux:label for="filterEvent">Event</flux:label>
            <flux:select
                id="filterEvent"
                wire:model.live="filterEvent">
                <option value="">All Events</option>
                @foreach($this->events as $event)
                    <option value="{{ $event->id }}">{{ $event->name }} - {{ $event->date?->format('M d, Y') }}</option>
                @endforeach
            </flux:select>
        </flux:field>

        <!-- Channel Filter -->
        <flux:field>
            <flux:label for="filterChannel">Channel</flux:label>
            <flux:select
                id="filterChannel"
                wire:model.live="filterChannel">
                <option value="all">All Channels</option>
                <option value="email">Email</option>
                <option value="sms">SMS</option>
            </flux:select>
        </flux:field>

        <!-- Date From -->
        <flux:field>
            <flux:label for="filterDateFrom">From Date</flux:label>
            <flux:input
                type="date"
                id="filterDateFrom"
                wire:model.live="filterDateFrom" />
        </flux:field>

        <!-- Date To -->
        <flux:field>
            <flux:label for="filterDateTo">To Date</flux:label>
            <flux:input
                type="date"
                id="filterDateTo"
                wire:model.live="filterDateTo" />
        </flux:field>
    </div>

    <!-- Status Filter (Checkboxes) -->
    <div class="mb-6">
        <flux:label>Status</flux:label>
        <div class="flex gap-4 mt-2">
            @foreach(['scheduled', 'sent', 'failed', 'cancelled'] as $status)
                <label class="flex items-center gap-2 cursor-pointer">
                    <flux:checkbox
                        wire:model.live="filterStatus"
                        value="{{ $status }}" />
                    <span class="capitalize">{{ $status }}</span>
                </label>
            @endforeach
        </div>
    </div>

    <!-- Reminder Logs Table -->
    <flux:table :paginate="$this->reminderLogs">
        <flux:table.columns>
            <flux:table.column>
                Member
            </flux:table.column>

            <flux:table.column>
                Event
            </flux:table.column>

            <flux:table.column>
                Channel
            </flux:table.column>

            <flux:table.column
                sortable
                :sorted="$sortBy === 'scheduled_at'"
                :direction="$sortBy === 'scheduled_at' ? $sortDirection : null"
                wire:click="sort('scheduled_at')">
                Scheduled At
            </flux:table.column>

            <flux:table.column>
                Sent At
            </flux:table.column>

            <flux:table.column>
                Status
            </flux:table.column>

            <flux:table.column align="end">
                Actions
            </flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($this->reminderLogs as $log)
                <flux:table.row :key="$log->id">
                    <flux:table.cell variant="strong">
                        {{ $log->member->first_name }} {{ $log->member->last_name }}
                    </flux:table.cell>

                    <flux:table.cell>
                        <div>
                            <div class="font-medium">{{ $log->event->name }}</div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $log->event->date?->format('M d, Y') }}
                            </div>
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        @if($log->channel === 'email')
                            <flux:badge color="blue" size="sm">Email</flux:badge>
                        @else
                            <flux:badge color="green" size="sm">SMS</flux:badge>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $log->scheduled_at?->format('M d, Y g:i A') }}
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $log->sent_at?->format('M d, Y g:i A') ?? 'N/A' }}
                    </flux:table.cell>

                    <flux:table.cell>
                        @if($log->status === 'sent')
                            <flux:badge color="green" size="sm">Sent</flux:badge>
                        @elseif($log->status === 'scheduled')
                            <flux:badge color="blue" size="sm">Scheduled</flux:badge>
                        @elseif($log->status === 'failed')
                            <flux:badge color="red" size="sm">Failed</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">{{ ucfirst($log->status) }}</flux:badge>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell align="end">
                        <flux:button
                            variant="ghost"
                            size="xs"
                            wire:click="viewDetails({{ $log->id }})">
                            View Details
                        </flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" align="center">
                        No reminder logs found.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <!-- Detail Modal -->
    @if($showDetailModal && $this->selectedLog)
        <flux:modal wire:model="showDetailModal" class="max-w-2xl">
            <flux:modal.heading>Reminder Details</flux:modal.heading>

            <div class="space-y-4">
                <!-- Member Info -->
                <div>
                    <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Member</h3>
                    <p class="text-sm">{{ $this->selectedLog->member->first_name }} {{ $this->selectedLog->member->last_name }}</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $this->selectedLog->member->email }}</p>
                </div>

                <!-- Event Info -->
                <div>
                    <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Event</h3>
                    <p class="text-sm">{{ $this->selectedLog->event->name }}</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $this->selectedLog->event->date?->format('M d, Y') }}</p>
                </div>

                <!-- Channel & Status -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Channel</h3>
                        <p class="text-sm capitalize">{{ $this->selectedLog->channel }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Status</h3>
                        <p class="text-sm capitalize">{{ $this->selectedLog->status }}</p>
                    </div>
                </div>

                <!-- Scheduled & Sent Times -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Scheduled At</h3>
                        <p class="text-sm">{{ $this->selectedLog->scheduled_at?->format('M d, Y g:i A') }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Sent At</h3>
                        <p class="text-sm">{{ $this->selectedLog->sent_at?->format('M d, Y g:i A') ?? 'N/A' }}</p>
                    </div>
                </div>

                <!-- Template Snapshot -->
                @if($this->selectedLog->template_snapshot)
                    <div>
                        <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Template Content</h3>
                        @if($this->selectedLog->channel === 'email' && isset($this->selectedLog->template_snapshot['subject']))
                            <div class="mb-2">
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Subject:</p>
                                <p class="text-sm font-medium">{{ $this->selectedLog->template_snapshot['subject'] }}</p>
                            </div>
                        @endif
                        @if(isset($this->selectedLog->template_snapshot['content']))
                            <div class="bg-zinc-50 dark:bg-zinc-800 p-3 rounded text-sm whitespace-pre-wrap">{{ $this->selectedLog->template_snapshot['content'] }}</div>
                        @endif
                    </div>
                @endif

                <!-- Error Message -->
                @if($this->selectedLog->error_message)
                    <div>
                        <h3 class="text-sm font-semibold text-red-600 dark:text-red-400 mb-2">Error Message</h3>
                        <p class="text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 p-3 rounded">{{ $this->selectedLog->error_message }}</p>
                    </div>
                @endif
            </div>

            <flux:modal.actions>
                <flux:button variant="ghost" wire:click="closeModal">Close</flux:button>
            </flux:modal.actions>
        </flux:modal>
    @endif
</div>
