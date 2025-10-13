<?php

namespace App\Livewire\Position;

use App\Models\Position;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Create extends Component
{
    public $name = '';

    public $description = '';

    public $organization_id;

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string|max:255',
        'organization_id' => 'required|exists:organizations,id',
    ];

    public function mount()
    {
        $this->organization_id = Auth::user()->organizations->first()->id ?? null;
    }

    public function save()
    {
        $this->validate();

        Position::create([
            'name' => $this->name,
            'description' => $this->description ?: null,
            'organization_id' => $this->organization_id,
        ]);

        session()->flash('success', 'Position created successfully.');

        return redirect()->route('positions.index');
    }

    public function cancel()
    {
        return redirect()->route('positions.index');
    }

    public function render()
    {
        return view('livewire.position.create');
    }
}
