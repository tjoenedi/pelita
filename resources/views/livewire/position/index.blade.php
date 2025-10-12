<div>
    <!-- Success Message -->
    @if (session()->has('success'))
        <flux:callout variant="success" class="mb-4">
            {{ session('success') }}
        </flux:callout>
    @endif

    <!-- Search Bar -->
    <div class="mb-6">
        <flux:field class="max-w-md">
            <flux:label for="search">Search Positions</flux:label>
            <flux:input
                type="text"
                id="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Search by name or description..." />
        </flux:field>
    </div>

    <!-- Positions Table using Flux UI Pro Components -->
    <flux:table :paginate="$this->positions">
        <flux:table.columns>
            <flux:table.column
                sortable
                :sorted="$sortBy === 'name'"
                :direction="$sortBy === 'name' ? $sortDirection : null"
                wire:click="sort('name')">
                Name
            </flux:table.column>

            <flux:table.column
                sortable
                :sorted="$sortBy === 'description'"
                :direction="$sortBy === 'description' ? $sortDirection : null"
                wire:click="sort('description')">
                Description
            </flux:table.column>

            <flux:table.column align="end">
                Actions
            </flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($this->positions as $position)
                <flux:table.row :key="$position->id">
                    <flux:table.cell variant="strong">
                        {{ $position->name }}
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $position->description ?? 'N/A' }}
                    </flux:table.cell>

                    <flux:table.cell align="end">
                        <div class="flex items-center justify-end gap-2">
                            <flux:button
                                variant="ghost"
                                size="xs"
                                href="{{ route('positions.edit', $position) }}">
                                Edit
                            </flux:button>

                            <flux:button
                                variant="ghost"
                                size="xs"
                                wire:click="delete({{ $position->id }})"
                                wire:confirm="Are you sure you want to delete this position?"
                                class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200">
                                Delete
                            </flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="3" align="center">
                        @if($search)
                            No positions found matching "{{ $search }}".
                        @else
                            No positions found.
                            <flux:button
                                variant="ghost"
                                size="sm"
                                href="{{ route('positions.create') }}">
                                Add your first position
                            </flux:button>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>