<?php

namespace Tests\Feature\Filament;

use App\Filament\Admin\Resources\WhatsAppLogs\WhatsAppLogResource;
use App\Filament\Admin\Resources\WhatsAppTemplates\WhatsAppTemplateResource;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class WhatsAppResourcesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_open_template_and_log_resources(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@kost.test')->sole();

        $this->actingAs($admin)->get(WhatsAppTemplateResource::getUrl('index'))->assertOk();
        $this->actingAs($admin)->get(WhatsAppTemplateResource::getUrl('create'))->assertOk();
        $this->actingAs($admin)->get(WhatsAppLogResource::getUrl('index'))->assertOk();
    }

    public function test_public_user_cannot_open_whatsapp_resources(): void
    {
        $this->seed(DatabaseSeeder::class);
        $publicUser = User::factory()->create();
        $publicUser->assignRole('public');

        $this->actingAs($publicUser)->get(WhatsAppTemplateResource::getUrl('index'))->assertForbidden();
        $this->actingAs($publicUser)->get(WhatsAppLogResource::getUrl('index'))->assertForbidden();
    }
}
