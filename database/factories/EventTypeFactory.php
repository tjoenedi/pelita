<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EventType>
 */
class EventTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'Sunday Service',
                'Bible Study',
                'Prayer Meeting',
                'Youth Service',
                'Women\'s Fellowship',
                'Men\'s Fellowship',
                'Choir Practice',
                'Special Event',
                'Conference',
                'Workshop',
            ]),
            'description' => $this->faker->optional()->sentence(),
            'organization_id' => Organization::factory(),
        ];
    }
}