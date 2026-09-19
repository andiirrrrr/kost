<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contract>
 */
class ContractFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'contract_number' => 'CTR-'.fake()->unique()->numerify('########'),
            'starts_at' => now()->startOfMonth(),
            'ends_at' => now()->addYear()->subDay(),
            'monthly_price' => 1000000,
            'deposit_amount' => 1000000,
            'deposit_status' => 'held',
            'status' => 'active',
        ];
    }
}
