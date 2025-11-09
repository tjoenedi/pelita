<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Member>
 */
class MemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'phone' => $this->faker->phoneNumber,
            'address' => $this->faker->address,
            'city' => $this->faker->city,
            'state_province' => $this->faker->stateAbbr,
            'zip' => $this->faker->postcode,
            'country' => 'USA',
            'birth_date' => $this->faker->dateTimeBetween('-50 years', '-18 years'),
            'birth_place' => $this->faker->city,
            'gender' => $this->faker->randomElement(['M', 'F']),
            'baptism_date' => $this->faker->dateTimeBetween('-30 years', 'now'),
            'marital_status' => $this->faker->randomElement(['Single', 'Married', 'Divorced', 'Widowed']),
            'email' => $this->faker->unique()->safeEmail,
            'profile_picture' => $this->faker->imageUrl(640, 480, 'people'),
            'organization_id' => Organization::factory(),
        ];
    }

    public function withOrganizationId(int $organizationId): self
    {
        return $this->state([
            'organization_id' => $organizationId,
        ]);
    }
}
