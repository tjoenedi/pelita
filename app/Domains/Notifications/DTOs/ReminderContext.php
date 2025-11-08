<?php

namespace App\Domains\Notifications\DTOs;

use App\Models\Event;
use App\Models\Member;
use App\Models\Position;

class ReminderContext
{
    public function __construct(
        public readonly Member $member,
        public readonly Event $event,
        public readonly ?Position $position = null,
    ) {}

    public function toArray(): array
    {
        $eventTime = null;
        if ($this->event->start_time) {
            // start_time is stored as string (HH:MM:SS)
            $eventTime = \Carbon\Carbon::createFromFormat('H:i:s', $this->event->start_time)->format('g:i A');
        }

        return [
            'name' => $this->member->first_name.' '.$this->member->last_name,
            'event_name' => $this->event->name,
            'event_date' => $this->event->date?->format('F j, Y'),
            'event_time' => $eventTime,
            'position_name' => $this->position !== null ? $this->position->name : 'Member',
            'organization_name' => $this->event->organization->name,
        ];
    }
}
