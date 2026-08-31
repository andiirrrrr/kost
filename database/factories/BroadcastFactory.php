<?php

namespace Database\Factories;

use App\Enums\BroadcastAudience;
use App\Enums\BroadcastStatus;
use App\Models\Broadcast;
use App\Models\User;
use App\Models\WhatsAppTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Broadcast>
 */
class BroadcastFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'whatsapp_template_id' => WhatsAppTemplate::factory(),
            'template_name' => fn (array $attributes): string => WhatsAppTemplate::findOrFail($attributes['whatsapp_template_id'])->template_name,
            'audience_type' => BroadcastAudience::ALL,
            'total_recipient' => 0,
            'total_sent' => 0,
            'total_failed' => 0,
            'status' => BroadcastStatus::DRAFT,
            'created_by' => User::factory(),
        ];
    }
}
