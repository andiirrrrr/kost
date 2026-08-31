<?php

namespace Tests\Feature\Filament;

use App\Filament\Admin\Pages\LandingPageSettings;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LandingPageSettingsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_owner_can_open_and_update_landing_page_settings(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::where('email', 'admin@kost.test')->sole();

        $this->actingAs($owner)
            ->get(LandingPageSettings::getUrl())
            ->assertOk()
            ->assertSee('Pengaturan Landing Page');

        Livewire::actingAs($owner)
            ->test(LandingPageSettings::class)
            ->set('data.business_name', 'Kost Terbaru')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Kost Terbaru', Setting::get('business_name'));
    }

    public function test_public_user_cannot_open_landing_page_settings(): void
    {
        $this->seed(DatabaseSeeder::class);
        $publicUser = User::factory()->create();
        $publicUser->assignRole('public');

        $this->actingAs($publicUser)
            ->get(LandingPageSettings::getUrl())
            ->assertForbidden();
    }
}
