<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            // Iraqi mobile format: +964 7XXXXXXXXX (7 + 9 digits).
            'phone' => '+9647'.fake()->unique()->numerify('#########'),
            'phone_verified_at' => now(),
            'name' => fake()->name(),
            'status' => 'active',
            'locale' => 'ar',
            'timezone' => 'Asia/Baghdad',
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone_verified_at' => null,
        ]);
    }
}
