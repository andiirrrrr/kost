<?php

namespace Database\Factories;

use App\Models\MaintenanceRequest;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceRequest>
 */
class MaintenanceRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'priority' => 'normal',
            'status' => 'reported',
            'cost' => 0,
        ];
    }
}
