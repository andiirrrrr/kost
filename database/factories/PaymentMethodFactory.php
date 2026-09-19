<?php

namespace Database\Factories;

use App\Enums\PaymentMethodCategory;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'code' => fake()->unique()->slug(2),
            'category' => PaymentMethodCategory::BANK_TRANSFER,
            'icon' => 'payments',
            'instructions' => fake()->sentence(),
            'account_number' => fake()->numerify('##########'),
            'account_holder' => fake()->name(),
            'is_active' => true,
            'sort_order' => 1,
        ];
    }
}
