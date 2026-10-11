<?php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    public function definition(): array
    {
        return [
            'public_token' => Str::random(43),
            'serial_number' => 'ST-'.strtoupper(fake()->unique()->bothify('?-####-######')),
            'type' => 'qr',
            'status' => 'unassigned',
        ];
    }
}
