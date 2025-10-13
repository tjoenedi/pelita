<?php

namespace App\Livewire\Position;

use App\Models\Position;
use Livewire\Component;

class Edit extends Component
{
    public Position $position;

    public $name = '';

    public $description = '';

    public $organization_id;

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string|max:255',
        'organization_id' => 'required|exists:organizations,id',
    ];

    public function mount(Position $position)
    {
        $this->position = $position;
        $this->name = $position->name;
        $this->description = $position->description;
        $this->organization_id = $position->organization_id;
    }

    public function save()
    {
        $this->validate();

        $this->position->update([
            'name' => $this->name,
            'description' => $this->description ?: null,
            'organization_id' => $this->organization_id,
        ]);

        session()->flash('success', 'Position updated successfully.');

        return redirect()->route('positions.index');
    }

    public function cancel()
    {
        return redirect()->route('positions.index');
    }

    public function render()
    {
        return view('livewire.position.edit');
    }
}
