<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\TenantRoomHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
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
            'tenant_room_history_id' => fn (array $attributes): ?int => TenantRoomHistory::query()->where('tenant_id', $attributes['tenant_id'])->whereNull('ends_at')->value('id'),
            'room_id' => fn (array $attributes): int => Tenant::findOrFail($attributes['tenant_id'])->room_id,
            'invoice_number' => fn () => fake()->unique()->numerify('INV-202608-9###'),
            'period_month' => 8,
            'period_year' => 2026,
            'base_amount' => 1000000,
            'electricity_amount' => 0,
            'water_amount' => 0,
            'other_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 1000000,
            'due_date' => '2026-08-05',
            'status' => InvoiceStatus::UNPAID,
        ];
    }
}
