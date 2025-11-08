<?php

namespace App\Livewire\Settings\Templates;

use App\Enums\NotificationChannel;
use App\Models\CommunicationTemplate;
use App\Models\EventType;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $sortBy = 'name';

    public string $sortDirection = 'asc';

    public string $search = '';

    public string $filterType = 'all';

    public ?int $filterEventType = null;

    #[Computed]
    public function templates()
    {
        $organizationIds = Auth::user()->organizations->pluck('id');

        return CommunicationTemplate::whereIn('organization_id', $organizationIds)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('subject', 'like', '%'.$this->search.'%')
                        ->orWhere('content', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->filterType !== 'all', function ($query) {
                $query->where('type', NotificationChannel::from($this->filterType));
            })
            ->when($this->filterEventType !== null, function ($query) {
                if ($this->filterEventType === 0) {
                    // Filter for templates with no event type (applies to all events)
                    $query->whereNull('event_type_id');
                } else {
                    $query->where('event_type_id', $this->filterEventType);
                }
            })
            ->with(['eventType'])
            ->tap(fn ($query) => $this->sortBy ? $query->orderBy($this->sortBy, $this->sortDirection) : $query)
            ->paginate(10);
    }

    #[Computed]
    public function eventTypes()
    {
        return EventType::whereIn('organization_id', Auth::user()->organizations->pluck('id'))
            ->orderBy('name')
            ->get();
    }

    public function render()
    {
        return view('livewire.settings.templates.index');
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

    public function updatedFilterType()
    {
        $this->resetPage();
    }

    public function updatedFilterEventType()
    {
        $this->resetPage();
    }

    public function delete($templateId)
    {
        $template = CommunicationTemplate::findOrFail($templateId);

        // Ensure user has access to this template
        if (! Auth::user()->organizations->contains($template->organization_id)) {
            abort(403, 'Unauthorized access to template.');
        }

        $template->delete();

        session()->flash('success', 'Template deleted successfully.');
        $this->dispatch('template-deleted');
    }
}
