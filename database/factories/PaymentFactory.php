<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'tenant_id' => fn (array $attributes): int => Invoice::findOrFail($attributes['invoice_id'])->tenant_id,
            'payment_number' => fn () => fake()->unique()->numerify('PAY-202608-9###'),
            'amount' => 1000000,
            'payment_method_id' => PaymentMethod::factory(),
            'payment_method' => fn (array $attributes): string => PaymentMethod::findOrFail($attributes['payment_method_id'])->code,
            'paid_at' => now(),
            'status' => PaymentStatus::PENDING,
        ];
    }
}
