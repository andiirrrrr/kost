<?php

namespace Database\Factories;

use App\Enums\TenantStatus;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
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
            'name' => fake()->name(),
            'phone' => '628'.fake()->unique()->numerify('##########'),
            'email' => fake()->optional()->safeEmail(),
            'identity_number' => fake()->optional()->numerify('################'),
            'address' => fake()->optional()->address(),
            'emergency_contact' => fake()->optional()->phoneNumber(),
            'move_in_date' => fake()->date(),
            'monthly_price' => 1000000,
            'due_day' => 5,
            'status' => TenantStatus::ACTIVE,
        ];
    }
}
