<?php

namespace App\Livewire\EventType;

use App\Models\EventType;
use App\Models\Position;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Edit extends Component
{
    public EventType $eventType;

    // Form fields
    public $name = '';

    public $description = '';

    public $selectedPositions = [];

    public $positionOrder = [];

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
    ];

    public function mount(EventType $eventType)
    {
        $this->eventType = $eventType;
        $this->name = $eventType->name;
        $this->description = $eventType->description;

        // Load existing positions with their order
        $this->selectedPositions = $eventType->positions->pluck('id')->toArray();
        foreach ($eventType->positions as $position) {
            /** @var \App\Models\Position $position */
            $this->positionOrder[$position->id] = $position->pivot->order ?? 0;
        }

        // Sort positionOrder by value to maintain correct order
        asort($this->positionOrder);
    }

    #[Computed]
    public function availablePositions()
    {
        return Position::whereIn('organization_id', Auth::user()->organizations->pluck('id'))
            ->orderBy('name')
            ->get();
    }

    public function updatedSelectedPositions()
    {
        // Initialize order for newly selected positions
        foreach ($this->selectedPositions as $positionId) {
            if (! isset($this->positionOrder[$positionId])) {
                $this->positionOrder[$positionId] = count($this->positionOrder);
            }
        }

        // Remove order for deselected positions
        foreach (array_keys($this->positionOrder) as $positionId) {
            if (! in_array($positionId, $this->selectedPositions)) {
                unset($this->positionOrder[$positionId]);
            }
        }

        // Reindex orders to be sequential
        $index = 0;
        $newOrder = [];
        foreach ($this->positionOrder as $positionId => $order) {
            $newOrder[$positionId] = $index++;
        }
        $this->positionOrder = $newOrder;
    }

    public function movePositionUp($positionId)
    {
        $currentOrder = $this->positionOrder[$positionId];
        if ($currentOrder > 0) {
            // Find the position with the order above
            foreach ($this->positionOrder as $id => $order) {
                if ($order === $currentOrder - 1) {
                    $this->positionOrder[$id] = $currentOrder;
                    break;
                }
            }
            $this->positionOrder[$positionId] = $currentOrder - 1;
        }
    }

    public function movePositionDown($positionId)
    {
        $currentOrder = $this->positionOrder[$positionId];
        $maxOrder = count($this->positionOrder) - 1;
        if ($currentOrder < $maxOrder) {
            // Find the position with the order below
            foreach ($this->positionOrder as $id => $order) {
                if ($order === $currentOrder + 1) {
                    $this->positionOrder[$id] = $currentOrder;
                    break;
                }
            }
            $this->positionOrder[$positionId] = $currentOrder + 1;
        }
    }

    public function save()
    {
        $this->validate();

        DB::transaction(function () {
            $this->eventType->update([
                'name' => $this->name,
                'description' => $this->description,
            ]);

            // Sync positions with their order
            $positionData = [];
            foreach ($this->selectedPositions as $positionId) {
                $positionData[$positionId] = [
                    'order' => $this->positionOrder[$positionId] ?? 0,
                ];
            }
            $this->eventType->positions()->sync($positionData);
        });

        session()->flash('success', 'Event type updated successfully.');

        return redirect()->route('event-types.index');
    }

    public function cancel()
    {
        return redirect()->route('event-types.index');
    }

    public function render()
    {
        // Sort selected positions by their order
        $sortedPositions = collect($this->selectedPositions)
            ->sortBy(function ($positionId) {
                return $this->positionOrder[$positionId] ?? 999;
            })
            ->values()
            ->toArray();

        return view('livewire.event-type.edit', [
            'sortedSelectedPositions' => $sortedPositions,
        ]);
    }
}
