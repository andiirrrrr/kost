<?php

namespace Database\Factories;

use App\Models\WhatsAppTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WhatsAppTemplate>
 */
class WhatsAppTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'template_name' => fake()->unique()->slug(2, '_'),
            'display_name' => fake()->words(3, true),
            'category' => 'utility',
            'language' => 'id',
            'content' => 'Halo {{nama}}, informasi untuk kamar {{kamar}}.',
            'variables' => ['nama', 'kamar'],
            'is_active' => true,
        ];
    }
}
