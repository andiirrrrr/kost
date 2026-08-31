<?php

namespace Tests\Feature\Filament;

use App\Filament\Admin\Pages\WhatsAppCenter;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class WhatsAppCenterTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_open_whatsapp_center_without_exposing_token(): void
    {
        config()->set('services.whatsapp.access_token', 'super-secret-token');
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@kost.test')->sole();

        $this->actingAs($admin)
            ->get(WhatsAppCenter::getUrl())
            ->assertOk()
            ->assertSee('Pusat WhatsApp')
            ->assertDontSee('super-secret-token');
    }

    public function test_public_user_cannot_open_whatsapp_center(): void
    {
        $this->seed(DatabaseSeeder::class);
        $publicUser = User::factory()->create();
        $publicUser->assignRole('public');

        $this->actingAs($publicUser)->get(WhatsAppCenter::getUrl())->assertForbidden();
    }
}
