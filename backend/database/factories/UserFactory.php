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
            'phone' => '+9665'.fake()->unique()->numerify('########'),
            'phone_verified_at' => now(),
            'name' => fake()->name(),
            'status' => 'active',
            'locale' => 'ar',
            'timezone' => 'Asia/Riyadh',
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone_verified_at' => null,
        ]);
    }
}
