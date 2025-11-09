<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventPosition;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EventPositionMember>
 */
class EventPositionMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_position_id' => EventPosition::factory(),
            'member_id' => Member::factory(),
            'event_id' => Event::factory(),
        ];
    }
}
