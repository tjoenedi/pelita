<?php

namespace App\Livewire\Reports;

use App\Models\Event;
use App\Models\ReminderLog;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class ReminderLogs extends Component
{
    use WithPagination;

    public string $sortBy = 'scheduled_at';

    public string $sortDirection = 'desc';

    public string $search = '';

    public ?int $filterEvent = null;

    public array $filterStatus = [];

    public string $filterChannel = 'all';

    public ?string $filterDateFrom = null;

    public ?string $filterDateTo = null;

    public ?int $selectedLogId = null;

    public $showDetailModal = false;

    #[Computed]
    public function reminderLogs()
    {
        $organizationIds = Auth::user()->organizations->pluck('id');

        return ReminderLog::whereHas('event', function ($query) use ($organizationIds) {
            $query->whereIn('organization_id', $organizationIds);
        })
            ->with(['member', 'event', 'event.eventType'])
            ->when($this->search, function ($query) {
                $query->whereHas('member', function ($q) {
                    $q->where('first_name', 'like', '%'.$this->search.'%')
                        ->orWhere('last_name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->filterEvent, function ($query) {
                $query->where('event_id', $this->filterEvent);
            })
            ->when(count($this->filterStatus) > 0, function ($query) {
                $query->whereIn('status', $this->filterStatus);
            })
            ->when($this->filterChannel !== 'all', function ($query) {
                $query->where('channel', $this->filterChannel);
            })
            ->when($this->filterDateFrom, function ($query) {
                $query->whereDate('scheduled_at', '>=', $this->filterDateFrom);
            })
            ->when($this->filterDateTo, function ($query) {
                $query->whereDate('scheduled_at', '<=', $this->filterDateTo);
            })
            ->tap(fn ($query) => $this->sortBy ? $query->orderBy($this->sortBy, $this->sortDirection) : $query)
            ->paginate(15);
    }

    #[Computed]
    public function events()
    {
        return Event::whereIn('organization_id', Auth::user()->organizations->pluck('id'))
            ->orderBy('date', 'desc')
            ->get();
    }

    #[Computed]
    public function selectedLog()
    {
        if (! $this->selectedLogId) {
            return null;
        }

        return ReminderLog::with(['member', 'event', 'event.eventType'])
            ->find($this->selectedLogId);
    }

    public function render()
    {
        return view('livewire.reports.reminder-logs');
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

    public function updatedFilterEvent()
    {
        $this->resetPage();
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function updatedFilterChannel()
    {
        $this->resetPage();
    }

    public function updatedFilterDateFrom()
    {
        $this->resetPage();
    }

    public function updatedFilterDateTo()
    {
        $this->resetPage();
    }

    public function viewDetails($logId)
    {
        $this->selectedLogId = $logId;
        $this->showDetailModal = true;
    }

    public function closeModal()
    {
        $this->showDetailModal = false;
        $this->selectedLogId = null;
    }
}
