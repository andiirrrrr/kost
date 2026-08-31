<?php

namespace Database\Factories;

use App\Enums\RoomStatus;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'room_number' => fake()->unique()->bothify('?#'),
            'room_name' => fake()->optional()->words(2, true),
            'type' => fake()->randomElement(['standard', 'premium']),
            'monthly_price' => fake()->numberBetween(700000, 2000000),
            'status' => RoomStatus::AVAILABLE,
            'description' => fake()->optional()->sentence(),
        ];
    }
}
