<?php

namespace App\Livewire\Member;

use App\Models\Member;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $sortBy = 'first_name';

    public string $sortDirection = 'asc';

    public string $search = '';

    #[Computed]
    public function members()
    {
        return Member::whereIn('organization_id', Auth::user()->organizations->pluck('id'))
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('first_name', 'like', '%'.$this->search.'%')
                        ->orWhere('last_name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%')
                        ->orWhere('phone', 'like', '%'.$this->search.'%');
                });
            })
            ->tap(fn ($query) => $this->sortBy ? $query->orderBy($this->sortBy, $this->sortDirection) : $query)
            ->paginate(10);
    }

    public function render()
    {
        return view('livewire.member.index');
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

    public function delete($memberId)
    {
        $member = Member::findOrFail($memberId);

        // Add authorization check if needed
        // $this->authorize('delete', $member);

        $member->delete();

        session()->flash('success', 'Member deleted successfully.');
        $this->dispatch('member-deleted');
    }
}
