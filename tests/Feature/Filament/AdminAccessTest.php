<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_to_filament_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_admin_login_page_renders_the_custom_filament_form(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Login Pengelola')
            ->assertSee('Masuk ke dashboard pengelolaan kost.')
            ->assertSee('Kembali ke Beranda')
            ->assertSee('Login sebagai penghuni')
            ->assertSee('Koneksi login dilindungi')
            ->assertDontSee('cdn.tailwindcss.com', false);
    }

    public function test_user_without_application_role_cannot_open_admin_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_admin_can_open_admin_panel(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@kost.test')->sole();

        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_public_user_cannot_open_admin_panel(): void
    {
        $this->seed(DatabaseSeeder::class);
        $publicUser = User::factory()->create();
        $publicUser->assignRole('public');

        $this->actingAs($publicUser)->get('/admin')->assertForbidden();
    }
}
