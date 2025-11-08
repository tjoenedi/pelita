<?php

namespace Database\Factories;

use App\Enums\NotificationChannel;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CommunicationTemplate>
 */
class CommunicationTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement([NotificationChannel::Email, NotificationChannel::SMS]);

        return [
            'organization_id' => Organization::factory(),
            'event_type_id' => null,
            'type' => $type,
            'name' => $this->faker->words(3, true),
            'subject' => $type === NotificationChannel::Email ? $this->faker->sentence() : null,
            'content' => $type === NotificationChannel::Email
                ? 'Hi {name}, reminder for {event_name} on {event_date} at {event_time}. {unsubscribe_link}'
                : 'Hi {name}, reminder: {event_name} on {event_date} at {event_time}',
            'is_default' => false,
            'append_unsubscribe_footer' => true,
        ];
    }

    public function email(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => NotificationChannel::Email,
            'subject' => $this->faker->sentence(),
            'content' => 'Hi {name}, reminder for {event_name} on {event_date} at {event_time}. {unsubscribe_link}',
        ]);
    }

    public function sms(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => NotificationChannel::SMS,
            'subject' => null,
            'content' => 'Hi {name}, reminder: {event_name} on {event_date} at {event_time}',
        ]);
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    public function forEventType(int $eventTypeId): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type_id' => $eventTypeId,
        ]);
    }
}
