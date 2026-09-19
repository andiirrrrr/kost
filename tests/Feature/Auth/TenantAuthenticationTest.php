<?php

namespace Tests\Feature\Auth;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantAuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_tenant_login_page_renders_the_portal_form(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Login Penghuni')
            ->assertSee('Kembali ke Beranda')
            ->assertSee('Akses tagihan, pembayaran, notifikasi, dan informasi kost Anda.')
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertDontSee('cdn.tailwindcss.com', false);
    }

    public function test_forgot_password_page_renders_whatsapp_reset_contact(): void
    {
        $this->get('/lupa-password')
            ->assertOk()
            ->assertSee('Lupa Password Penghuni')
            ->assertSee('Hubungi Pengelola via WhatsApp')
            ->assertSee('password default')
            ->assertDontSee('Kirim tautan reset');
    }

    public function test_tenant_can_login_and_session_is_regenerated(): void
    {
        $user = $this->tenantUser();
        $oldSessionId = session()->getId();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'secure-password',
        ]);

        $response->assertRedirect(route('tenant.dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($oldSessionId, session()->getId());
    }

    public function test_tenant_cannot_open_admin_panel(): void
    {
        $user = $this->tenantUser();

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_admin_is_redirected_to_admin_instead_of_tenant_dashboard(): void
    {
        config()->set('demo.owner_password', 'secure-owner-password');
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@kost.test')->sole();

        $response = $this->post('/login', ['email' => $admin->email, 'password' => 'secure-owner-password']);

        $response->assertRedirect('/admin');
    }

    public function test_user_without_tenant_role_is_not_authenticated_in_portal(): void
    {
        $user = User::factory()->create(['password' => 'secure-password']);

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'secure-password']);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_inactive_tenant_cannot_open_portal(): void
    {
        $user = $this->tenantUser(TenantStatus::INACTIVE);

        $this->actingAs($user)->get('/dashboard')->assertForbidden();
    }

    public function test_inactive_tenant_can_return_to_login_page_and_is_logged_out(): void
    {
        $user = $this->tenantUser(TenantStatus::INACTIVE);

        $this->actingAs($user)
            ->get('/login')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->get('/login')->assertOk()->assertSee('Login Penghuni');
    }

    public function test_tenant_with_move_out_date_cannot_open_portal_even_if_status_is_active(): void
    {
        $user = $this->tenantUser();
        $user->tenant->update(['move_out_date' => now()->toDateString()]);

        $this->actingAs($user)->get('/dashboard')->assertForbidden();
    }

    public function test_tenant_with_future_move_in_date_cannot_login(): void
    {
        $user = $this->tenantUser();
        $user->tenant->update(['move_in_date' => now()->addDay()->toDateString()]);

        $this->post('/login', ['email' => $user->email, 'password' => 'secure-password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    private function tenantUser(TenantStatus $status = TenantStatus::ACTIVE): User
    {
        Role::findOrCreate('tenant', 'web');
        $user = User::factory()->create([
            'password' => Hash::make('secure-password'),
        ]);
        Tenant::factory()->for($user)->create(['status' => $status]);
        $user->assignRole('tenant');

        return $user;
    }
}
