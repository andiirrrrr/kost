<?php

namespace Database\Factories;

use App\Enums\WhatsAppStatus;
use App\Models\Broadcast;
use App\Models\Tenant;
use App\Models\WhatsAppLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WhatsAppLog>
 */
class WhatsAppLogFactory extends Factory
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
            'broadcast_id' => Broadcast::factory(),
            'phone' => '628'.fake()->unique()->numerify('##########'),
            'template_name' => 'tagihan_bulanan',
            'parameters' => ['Nama', 'A1'],
            'status' => WhatsAppStatus::QUEUED,
        ];
    }
}
