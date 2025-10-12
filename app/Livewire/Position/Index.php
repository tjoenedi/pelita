<?php

namespace App\Livewire\Position;

use App\Models\Position;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $sortBy = 'name';
    public $sortDirection = 'asc';

    protected $queryString = ['search', 'sortBy', 'sortDirection'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function sort($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function delete($id)
    {
        $position = Position::find($id);
        if ($position && in_array($position->organization_id, Auth::user()->organizations->pluck('id')->toArray())) {
            $position->delete();
            session()->flash('success', 'Position deleted successfully.');
            $this->dispatch('position-deleted');
        }
    }

    public function getPositionsProperty()
    {
        return Position::query()
            ->whereIn('organization_id', Auth::user()->organizations->pluck('id'))
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(10);
    }

    public function render()
    {
        return view('livewire.position.index');
    }
}