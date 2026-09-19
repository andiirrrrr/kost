<?php

namespace Tests\Feature\Filament;

use App\Filament\Admin\Pages\Profile;
use App\Models\Setting;
use App\Models\User;
use App\Services\LandingPageService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_owner_can_open_profile_page(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::where('email', config('demo.owner_email'))->sole();

        $this->actingAs($owner)
            ->get(Profile::getUrl())
            ->assertOk()
            ->assertSee('Profil Saya')
            ->assertSee('Alamat Email');
    }

    public function test_sidebar_includes_profile_navigation_item(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::where('email', config('demo.owner_email'))->sole();

        $this->actingAs($owner)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Profil Saya');
    }

    public function test_owner_can_update_email_and_name_with_current_password(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::where('email', config('demo.owner_email'))->sole();

        Livewire::actingAs($owner)
            ->test(Profile::class)
            ->set('data.name', 'Owner Baru')
            ->set('data.email', 'ownerbaru@example.test')
            ->set('data.currentPassword', 'password')
            ->call('save')
            ->assertHasNoErrors();

        $owner->refresh();
        $this->assertSame('Owner Baru', $owner->name);
        $this->assertSame('ownerbaru@example.test', $owner->email);
    }

    public function test_owner_can_update_password_with_current_password(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::where('email', config('demo.owner_email'))->sole();

        Livewire::actingAs($owner)
            ->test(Profile::class)
            ->set('data.password', 'newsecret1234')
            ->set('data.passwordConfirmation', 'newsecret1234')
            ->set('data.currentPassword', 'password')
            ->call('save')
            ->assertHasNoErrors();

        $owner->refresh();
        $this->assertTrue(Hash::check('newsecret1234', $owner->password));
    }

    public function test_owner_cannot_update_password_with_wrong_current_password(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::where('email', config('demo.owner_email'))->sole();

        Livewire::actingAs($owner)
            ->test(Profile::class)
            ->set('data.password', 'newsecret1234')
            ->set('data.passwordConfirmation', 'newsecret1234')
            ->set('data.currentPassword', 'wrong-password')
            ->call('save')
            ->assertHasErrors(['data.currentPassword']);
    }

    public function test_owner_can_update_phone_and_it_syncs_to_settings_and_landing_page(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::where('email', config('demo.owner_email'))->sole();

        Livewire::actingAs($owner)
            ->test(Profile::class)
            ->set('data.phone', '089988776655')
            ->call('save')
            ->assertHasNoErrors();

        $owner->refresh();
        $this->assertSame('089988776655', $owner->phone);
        $this->assertSame('089988776655', Setting::get('contact_phone'));

        $landingSettings = app(LandingPageService::class)->get();
        $this->assertSame('089988776655', $landingSettings['contact_phone']);
    }
}
