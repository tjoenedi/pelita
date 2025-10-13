<?php

namespace App\Livewire\Event;

use App\Models\Event;
use App\Models\EventPosition;
use App\Models\EventPositionMember;
use App\Models\EventType;
use App\Models\Member;
use App\Models\Position;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Create extends Component
{
    // Form fields
    public $event_type_id = '';

    public $name = '';

    public $description = '';

    public $nameManuallySet = false;

    public $date = '';

    public $all_day = true;

    public $start_time = '';

    public $end_time = '';

    public $is_active = true;

    public $is_public = true;

    // Position management
    public $selectedPositions = [];

    public $positionAssignments = [];

    protected $rules = [
        'event_type_id' => 'nullable|exists:event_types,id',
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'date' => 'nullable|date',
        'all_day' => 'boolean',
        'start_time' => 'nullable|required_if:all_day,false|date_format:H:i',
        'end_time' => 'nullable|required_if:all_day,false|date_format:H:i|after:start_time',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
    ];

    protected $messages = [
        'start_time.required_if' => 'Start time is required when not an all-day event.',
        'end_time.required_if' => 'End time is required when not an all-day event.',
        'end_time.after' => 'End time must be after start time.',
    ];

    public function mount()
    {
        // Set default date to today
        $this->date = now()->format('Y-m-d');

        // Auto-select the first event type if available
        $eventTypes = $this->eventTypes();
        if ($eventTypes->count() > 0) {
            $this->event_type_id = $eventTypes->first()->id;
            $this->updatedEventTypeId($this->event_type_id);
        } else {
            // If no event types, set default name to "General"
            $this->name = 'General';
        }
    }

    #[Computed]
    public function eventTypes()
    {
        return EventType::whereIn('organization_id', Auth::user()->organizations->pluck('id'))
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function availablePositions()
    {
        return Position::whereIn('organization_id', Auth::user()->organizations->pluck('id'))
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function availableMembers()
    {
        return Member::whereIn('organization_id', Auth::user()->organizations->pluck('id'))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    public function updatedAllDay($value)
    {
        if ($value) {
            $this->start_time = '';
            $this->end_time = '';
        }
    }

    public function updatedName($value)
    {
        // Track that the name has been manually set if it's different from the event type name
        if ($value && $this->event_type_id) {
            $eventType = EventType::find($this->event_type_id);
            if ($eventType && $value !== $eventType->name) {
                $this->nameManuallySet = true;
            }
        } elseif ($value && $value !== 'General') {
            $this->nameManuallySet = true;
        }
    }

    public function updatedEventTypeId($value)
    {
        if ($value) {
            $eventType = EventType::find($value);
            if ($eventType) {
                // Only auto-populate the name if it hasn't been manually set
                if (! $this->nameManuallySet) {
                    $this->name = $eventType->name;
                }

                // Load positions from event type
                $this->selectedPositions = $eventType->positions()
                    ->orderBy('event_type_positions.order')
                    ->pluck('positions.id')
                    ->toArray();

                // Clear existing assignments since positions changed
                $this->positionAssignments = [];
            }
        } else {
            // If no event type selected, set name to General only if not manually set
            if (! $this->nameManuallySet) {
                $this->name = 'General';
            }
            // Clear positions if no event type selected
            $this->selectedPositions = [];
            $this->positionAssignments = [];
        }
    }

    public function save()
    {
        $this->validate();

        DB::transaction(function () {
            $data = [
                'event_type_id' => $this->event_type_id ?: null,
                'name' => $this->name,
                'description' => $this->description,
                'date' => $this->date ?: null,
                'all_day' => $this->all_day,
                'start_time' => ! $this->all_day ? $this->start_time : null,
                'end_time' => ! $this->all_day ? $this->end_time : null,
                'organization_id' => Auth::user()->organizations->first()->id ?? null,
                'is_active' => $this->is_active,
                'is_public' => $this->is_public,
            ];

            $event = Event::create($data);

            // Add position assignments
            $this->createPositionAssignments($event);
        });

        session()->flash('success', 'Event created successfully.');

        return redirect()->route('events.index');
    }

    protected function createPositionAssignments($event)
    {
        // Add positions
        $event->positions()->sync($this->selectedPositions);

        // Add member assignments for each position
        foreach ($this->selectedPositions as $positionId) {
            $eventPosition = EventPosition::where('event_id', $event->id)
                ->where('position_id', $positionId)
                ->first();

            if ($eventPosition && ! empty($this->positionAssignments[$positionId])) {
                EventPositionMember::create([
                    'event_position_id' => $eventPosition->id,
                    'member_id' => $this->positionAssignments[$positionId],
                    'event_id' => $event->id,
                ]);
            }
        }
    }

    public function cancel()
    {
        return redirect()->route('events.index');
    }

    public function render()
    {
        return view('livewire.event.create');
    }
}
