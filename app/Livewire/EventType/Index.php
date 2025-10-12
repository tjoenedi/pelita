<?php

namespace App\Livewire\EventType;

use App\Models\EventType;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public $search = '';

    #[Url]
    public $sortBy = 'created_at';

    #[Url]
    public $sortDirection = 'desc';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function sort($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    #[Computed]
    public function eventTypes()
    {
        // Use LIKE for SQLite compatibility (tests), ILIKE for PostgreSQL (production)
        $likeOperator = config('database.default') === 'sqlite' ? 'LIKE' : 'ILIKE';

        return EventType::with(['organization', 'positions', 'events'])
            ->whereIn('organization_id', Auth::user()->organizations->pluck('id'))
            ->when($this->search, function ($query) use ($likeOperator) {
                $query->where(function ($q) use ($likeOperator) {
                    $q->where('name', $likeOperator, '%' . $this->search . '%')
                        ->orWhere('description', $likeOperator, '%' . $this->search . '%');
                });
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(10);
    }

    public function deleteEventType($id)
    {
        $eventType = EventType::find($id);

        if ($eventType && in_array($eventType->organization_id, Auth::user()->organizations->pluck('id')->toArray())) {
            $eventType->delete();
            session()->flash('success', 'Event type deleted successfully.');
            $this->dispatch('event-type-deleted');
        }
    }

    public function render()
    {
        return view('livewire.event-type.index');
    }
}