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
            ->assertSee('Pengaturan Landing Page')
            ->assertSee('landing-page-settings-form', false)
            ->assertSee('landing-page-settings-actions', false)
            ->assertDontSee('Ikon Material');

        Livewire::actingAs($owner)
            ->test(LandingPageSettings::class)
            ->set('data.business_name', 'Kost Terbaru')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Kost Terbaru', Setting::get('business_name'));
        $advantages = json_decode((string) Setting::get('advantages'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('map', $advantages[0]['icon']);
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

    public function test_owner_can_save_maps_iframe(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::where('email', 'admin@kost.test')->sole();
        $iframe = '<iframe src="https://www.google.com/maps/embed?pb=!1m18" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>';

        Livewire::actingAs($owner)
            ->test(LandingPageSettings::class)
            ->set('data.maps_iframe', $iframe)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($iframe, Setting::get('maps_iframe'));
    }

    public function test_owner_cannot_save_invalid_maps_iframe(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::where('email', 'admin@kost.test')->sole();

        Livewire::actingAs($owner)
            ->test(LandingPageSettings::class)
            ->set('data.maps_iframe', 'bukan-url-dan-bukan-iframe')
            ->call('save')
            ->assertHasErrors(['data.maps_iframe']);
    }

    public function test_owner_can_save_landmarks_and_icons_are_resolved_automatically(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::where('email', 'admin@kost.test')->sole();

        Livewire::actingAs($owner)
            ->test(LandingPageSettings::class)
            ->set('data.landmarks', [
                ['label' => '5 menit ke halte busway transjakarta', 'icon' => 'auto'],
                ['label' => '10 menit ke area perkantoran sudirman', 'icon' => 'auto'],
                ['label' => '2 menit ke RS Medika', 'icon' => 'auto'],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $landmarks = json_decode((string) Setting::get('landmarks'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('directions_bus', $landmarks[0]['icon']);
        $this->assertSame('business', $landmarks[1]['icon']);
        $this->assertSame('local_hospital', $landmarks[2]['icon']);
    }
}
