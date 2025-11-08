<?php

namespace Database\Factories;

use App\Enums\NotificationChannel;
use App\Models\EventType;
use App\Models\Member;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MemberCommunicationPreference>
 */
class MemberCommunicationPreferenceFactory extends Factory
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
            'organization_id' => Organization::factory(),
            'event_type_id' => null,
            'channel' => $this->faker->randomElement([NotificationChannel::Email, NotificationChannel::SMS]),
            'is_subscribed' => true,
            'unsubscribed_at' => null,
            'unsubscribe_token' => Str::random(64),
        ];
    }

    public function unsubscribed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_subscribed' => false,
            'unsubscribed_at' => now(),
        ]);
    }

    public function subscribed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_subscribed' => true,
            'unsubscribed_at' => null,
        ]);
    }

    public function forEventType(int $eventTypeId): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type_id' => $eventTypeId,
        ]);
    }

    public function email(): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => NotificationChannel::Email,
        ]);
    }

    public function sms(): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => NotificationChannel::SMS,
        ]);
    }

    public function all(): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => NotificationChannel::All,
        ]);
    }
}
