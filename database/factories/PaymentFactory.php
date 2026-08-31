<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
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
            'payment_number' => fake()->unique()->numerify('PAY-202608-####'),
            'amount' => 1000000,
            'payment_method' => PaymentMethod::BANK_TRANSFER,
            'paid_at' => now(),
            'status' => PaymentStatus::PENDING,
        ];
    }
}
