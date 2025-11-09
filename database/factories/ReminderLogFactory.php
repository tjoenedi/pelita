<?php

namespace Database\Factories;

use App\Enums\NotificationChannel;
use App\Enums\ReminderStatus;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReminderLog>
 */
class ReminderLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'event_id' => Event::factory(),
            'event_type_id' => EventType::factory(),
            'channel' => $this->faker->randomElement([NotificationChannel::Email, NotificationChannel::SMS]),
            'scheduled_at' => $this->faker->dateTimeBetween('now', '+7 days'),
            'sent_at' => null,
            'status' => ReminderStatus::Scheduled,
            'failure_reason' => null,
            'template_snapshot' => [
                'template_id' => 1,
                'channel' => 'email',
            ],
        ];
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReminderStatus::Scheduled,
            'sent_at' => null,
            'failure_reason' => null,
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReminderStatus::Sent,
            'sent_at' => now(),
            'failure_reason' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReminderStatus::Failed,
            'sent_at' => null,
            'failure_reason' => $this->faker->sentence(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReminderStatus::Cancelled,
            'sent_at' => null,
            'failure_reason' => 'Event updated or rescheduled',
        ]);
    }

    public function email(): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => NotificationChannel::Email,
            'template_snapshot' => [
                'template_id' => 1,
                'channel' => 'email',
            ],
        ]);
    }

    public function sms(): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => NotificationChannel::SMS,
            'template_snapshot' => [
                'template_id' => 2,
                'channel' => 'sms',
            ],
        ]);
    }
}
