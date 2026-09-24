<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'plate_number' => strtoupper(fake()->bothify('??? ####')),
            'make' => fake()->randomElement(['Toyota', 'Honda', 'Hyundai', 'Kia', 'Nissan']),
            'model' => fake()->word(),
            'year' => fake()->numberBetween(2005, 2026),
            'color' => fake()->safeColorName(),
            'vehicle_type' => 'sedan',
            'status' => 'active',
        ];
    }
}
