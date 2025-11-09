<?php

namespace App\Livewire\Event;

use App\Domains\Notifications\Jobs\ScheduleEventReminders;
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

class Edit extends Component
{
    public Event $event;

    // Form fields
    public $event_type_id = '';

    public $name = '';

    public $description = '';

    public $date = '';

    public $all_day = true;

    public $start_time = '';

    public $end_time = '';

    public $is_active = true;

    public $is_public = true;

    // Reminder settings
    public $reminder_mode = 'auto';

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
        'reminder_mode' => 'required|in:auto,manual,disabled',
    ];

    protected $messages = [
        'start_time.required_if' => 'Start time is required when not an all-day event.',
        'end_time.required_if' => 'End time is required when not an all-day event.',
        'end_time.after' => 'End time must be after start time.',
    ];

    public function mount(Event $event)
    {
        $this->event = $event;
        $this->event_type_id = $event->event_type_id;
        $this->name = $event->name;
        $this->description = $event->description;
        $this->date = $event->date !== null ? $event->date->format('Y-m-d') : '';
        $this->all_day = $event->all_day;
        // Time fields are stored as strings in TIME format
        $this->start_time = $event->start_time ? substr($event->start_time, 0, 5) : '';
        $this->end_time = $event->end_time ? substr($event->end_time, 0, 5) : '';
        $this->is_active = $event->is_active;
        $this->is_public = $event->is_public;
        $this->reminder_mode = $event->reminder_mode ?? 'auto';

        // Load existing position assignments
        $this->loadPositionAssignments();
    }

    protected function loadPositionAssignments()
    {
        // If event has an EventType, load positions from it
        if ($this->event->event_type_id && $this->event->eventType) {
            /** @var EventType $eventType */
            $eventType = $this->event->eventType;
            $this->selectedPositions = $eventType->positions()
                ->orderBy('event_type_positions.order')
                ->pluck('positions.id')
                ->toArray();
        } else {
            // Otherwise load manually selected positions
            $this->selectedPositions = $this->event->positions->pluck('id')->toArray();
        }

        // Load member assignments
        foreach ($this->event->eventPositions as $eventPosition) {
            /** @var \App\Models\EventPosition $eventPosition */
            $assignedMember = $eventPosition->getAssignedMember();
            $this->positionAssignments[$eventPosition->position_id] = $assignedMember ? $assignedMember->id : '';
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

    public function updatedEventTypeId($value)
    {
        if ($value) {
            $eventType = EventType::find($value);
            if ($eventType) {
                // Auto-populate the name from event type
                $this->name = $eventType->name;

                // Load positions from event type
                $newPositions = $eventType->positions()
                    ->orderBy('event_type_positions.order')
                    ->pluck('positions.id')
                    ->toArray();

                // Keep assignments for positions that still exist
                $keepAssignments = array_intersect_key($this->positionAssignments, array_flip($newPositions));

                $this->selectedPositions = $newPositions;
                $this->positionAssignments = $keepAssignments;
            }
        } else {
            // If no event type selected, set name to General
            $this->name = 'General';
            // Allow manual position selection if no event type
            // Keep existing positions and assignments
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
                'is_active' => $this->is_active,
                'is_public' => $this->is_public,
                'reminder_mode' => $this->reminder_mode,
            ];

            $this->event->update($data);

            // Update position assignments
            $this->updatePositionAssignments();
        });

        session()->flash('success', 'Event updated successfully.');

        return redirect()->route('events.index');
    }

    protected function updatePositionAssignments()
    {
        // Sync positions
        $this->event->positions()->sync($this->selectedPositions);

        // Update member assignments for each position
        foreach ($this->selectedPositions as $positionId) {
            $eventPosition = EventPosition::where('event_id', $this->event->id)
                ->where('position_id', $positionId)
                ->first();

            if ($eventPosition) {
                // Remove existing schedule for this position
                EventPositionMember::where('event_position_id', $eventPosition->id)->delete();

                // Add new schedule if a member is assigned
                if (! empty($this->positionAssignments[$positionId])) {
                    EventPositionMember::create([
                        'event_position_id' => $eventPosition->id,
                        'member_id' => $this->positionAssignments[$positionId],
                        'event_id' => $this->event->id,
                    ]);
                }
            }
        }
    }

    public function addPosition()
    {
        if (! in_array('', $this->selectedPositions)) {
            $this->selectedPositions[] = '';
        }
    }

    public function removePosition($index)
    {
        unset($this->selectedPositions[$index]);
        $this->selectedPositions = array_values($this->selectedPositions);
    }

    public function sendRemindersNow()
    {
        // Dispatch the job to send reminders immediately (no delay)
        ScheduleEventReminders::dispatch($this->event->id);

        session()->flash('success', 'Reminders are being sent.');
    }

    #[Computed]
    public function scheduledRemindersCount()
    {
        return $this->event->reminderLogs()
            ->whereIn('status', ['scheduled', 'sent'])
            ->count();
    }

    public function cancel()
    {
        return redirect()->route('events.index');
    }

    public function render()
    {
        return view('livewire.event.edit');
    }
}
