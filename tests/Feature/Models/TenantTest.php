<?php

namespace Tests\Feature\Models;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TenantTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_normalizes_indonesian_phone_number_for_whatsapp(): void
    {
        $tenant = Tenant::factory()->create(['phone' => '+62 812-3456-7890']);

        $this->assertSame('6281234567890', $tenant->phone);
    }
}
