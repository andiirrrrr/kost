<?php

namespace Database\Factories;

use App\Enums\TenantStatus;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\TenantRoomHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    public function configure(): static
    {
        return $this->afterCreating(function (Tenant $tenant): void {
            TenantRoomHistory::query()->firstOrCreate(
                ['tenant_id' => $tenant->id, 'starts_at' => $tenant->move_in_date->toDateString()],
                ['room_id' => $tenant->room_id, 'ends_at' => $tenant->move_out_date?->toDateString(), 'monthly_price' => (int) $tenant->monthly_price, 'due_day' => $tenant->move_in_date->day],
            );
        });
    }

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
