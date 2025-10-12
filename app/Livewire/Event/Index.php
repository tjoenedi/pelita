<?php

namespace App\Livewire\Event;

use App\Models\Event;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $sortBy = 'date';

    public string $sortDirection = 'desc';

    public string $search = '';

    public string $filterStatus = 'all'; // all, upcoming, past

    public string $filterType = 'all'; // all, [event_type_ids]

    #[Computed]
    public function events()
    {
        return Event::with(['eventType', 'organization', 'eventPositions.position', 'eventPositions.schedules.member'])
            ->whereIn('organization_id', Auth::user()->organizations->pluck('id'))
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterStatus !== 'all', function ($query) {
                if ($this->filterStatus === 'upcoming') {
                    $query->where('date', '>=', now()->toDateString());
                } elseif ($this->filterStatus === 'past') {
                    $query->where('date', '<', now()->toDateString());
                }
            })
            ->when($this->filterType !== 'all', function ($query) {
                $query->where('event_type_id', $this->filterType);
            })
            ->tap(fn ($query) => $this->sortBy ? $query->orderBy($this->sortBy, $this->sortDirection) : $query)
            ->paginate(10);
    }

    #[Computed]
    public function eventTypes()
    {
        return \App\Models\EventType::whereIn('organization_id', Auth::user()->organizations->pluck('id'))
            ->orderBy('name')
            ->get();
    }

    public function render()
    {
        return view('livewire.event.index');
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

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function updatedFilterType()
    {
        $this->resetPage();
    }

    public function delete($eventId)
    {
        $event = Event::findOrFail($eventId);

        // Add authorization check if needed
        // $this->authorize('delete', $event);

        $event->delete();

        session()->flash('success', 'Event deleted successfully.');
        $this->dispatch('event-deleted');
    }
}
