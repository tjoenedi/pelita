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
            <flux:label for="search">Search Members</flux:label>
            <flux:input
                type="text"
                id="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Search by name, email, or phone..." />
        </flux:field>
    </div>

    <!-- Members Table using Flux UI Pro Components -->
    <flux:table :paginate="$this->members">
        <flux:table.columns>
            <flux:table.column
                sortable
                :sorted="$sortBy === 'first_name'"
                :direction="$sortBy === 'first_name' ? $sortDirection : null"
                wire:click="sort('first_name')">
                First Name
            </flux:table.column>

            <flux:table.column
                sortable
                :sorted="$sortBy === 'last_name'"
                :direction="$sortBy === 'last_name' ? $sortDirection : null"
                wire:click="sort('last_name')">
                Last Name
            </flux:table.column>

            <flux:table.column
                sortable
                :sorted="$sortBy === 'email'"
                :direction="$sortBy === 'email' ? $sortDirection : null"
                wire:click="sort('email')">
                Email
            </flux:table.column>

            <flux:table.column
                sortable
                :sorted="$sortBy === 'phone'"
                :direction="$sortBy === 'phone' ? $sortDirection : null"
                wire:click="sort('phone')">
                Phone
            </flux:table.column>

            <flux:table.column align="end">
                Actions
            </flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($this->members as $member)
                <flux:table.row :key="$member->id">
                    <flux:table.cell variant="strong">
                        {{ $member->first_name }}
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $member->last_name }}
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $member->email }}
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $member->phone ?? 'N/A' }}
                    </flux:table.cell>

                    <flux:table.cell align="end">
                        <div class="flex items-center justify-end gap-2">
                            <flux:button
                                variant="ghost"
                                size="xs"
                                href="{{ route('members.edit', $member) }}">
                                Edit
                            </flux:button>

                            <flux:button
                                variant="ghost"
                                size="xs"
                                wire:click="delete({{ $member->id }})"
                                wire:confirm="Are you sure you want to delete this member?"
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
                            No members found matching "{{ $search }}".
                        @else
                            No members found.
                            <flux:button
                                variant="ghost"
                                size="sm"
                                href="{{ route('members.create') }}">
                                Add your first member
                            </flux:button>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
