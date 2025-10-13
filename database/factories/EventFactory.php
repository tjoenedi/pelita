<?php

namespace Database\Factories;

use App\Models\EventType;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $allDay = $this->faker->boolean(70);
        $date = $this->faker->dateTimeBetween('-1 month', '+3 months');

        $data = [
            'event_type_id' => EventType::factory(),
            'name' => $this->faker->randomElement([
                'Sunday Service',
                'Wednesday Bible Study',
                'Youth Fellowship',
                'Prayer Meeting',
                'Women\'s Ministry',
                'Men\'s Fellowship',
                'Choir Practice',
                'Church Anniversary',
                'Easter Celebration',
                'Christmas Service',
                'Baptism Service',
                'Communion Service',
            ]),
            'description' => $this->faker->optional()->paragraph(),
            'date' => $date,
            'all_day' => $allDay,
            'organization_id' => Organization::factory(),
            'is_active' => $this->faker->boolean(90),
            'is_public' => $this->faker->boolean(80),
        ];

        if (! $allDay) {
            $startHour = $this->faker->numberBetween(6, 20);
            $duration = $this->faker->numberBetween(1, 4);
            $data['start_time'] = sprintf('%02d:%02d:00', $startHour, $this->faker->randomElement([0, 15, 30, 45]));
            $data['end_time'] = sprintf('%02d:%02d:00', $startHour + $duration, $this->faker->randomElement([0, 15, 30, 45]));
        }

        return $data;
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => false,
        ]);
    }

    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => $this->faker->dateTimeBetween('now', '+2 months'),
        ]);
    }

    public function past(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => $this->faker->dateTimeBetween('-2 months', '-1 day'),
        ]);
    }
}
