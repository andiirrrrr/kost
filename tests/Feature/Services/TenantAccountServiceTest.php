<?php

namespace Tests\Feature\Services;

use App\Models\Tenant;
use App\Services\TenantAccountService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantAccountServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_creates_auth_identity_and_links_it_to_tenant(): void
    {
        Role::findOrCreate('tenant', 'web');
        $tenant = Tenant::factory()->create(['email' => null]);

        $result = app(TenantAccountService::class)->create($tenant, 'tenant@example.test', 'temporary-secret-123');

        $this->assertTrue($result['user']->hasRole('tenant'));
        $this->assertSame($result['user']->id, $tenant->refresh()->user_id);
        $this->assertSame('tenant@example.test', $tenant->email);
    }

    public function test_rejects_duplicate_account_for_same_tenant(): void
    {
        Role::findOrCreate('tenant', 'web');
        $tenant = Tenant::factory()->create();
        app(TenantAccountService::class)->create($tenant, 'first@example.test');

        $this->expectException(InvalidArgumentException::class);

        app(TenantAccountService::class)->create($tenant->refresh(), 'second@example.test');
    }

    public function test_resets_password_with_custom_password(): void
    {
        Role::findOrCreate('tenant', 'web');
        $tenant = Tenant::factory()->create();
        $account = app(TenantAccountService::class)->create($tenant, 'reset-test@example.test', 'initial-password');

        $result = app(TenantAccountService::class)->resetPassword($tenant->refresh(), 'new-custom-secret-123');

        $this->assertSame('new-custom-secret-123', $result['password']);
        $this->assertTrue(Hash::check('new-custom-secret-123', $account['user']->refresh()->password));
    }

    public function test_resets_password_with_auto_generated_password(): void
    {
        Role::findOrCreate('tenant', 'web');
        $tenant = Tenant::factory()->create();
        $account = app(TenantAccountService::class)->create($tenant, 'auto-reset@example.test', 'initial-password');

        $result = app(TenantAccountService::class)->resetPassword($tenant->refresh());

        $this->assertNotEmpty($result['password']);
        $this->assertTrue(Hash::check($result['password'], $account['user']->refresh()->password));
    }

    public function test_rejects_reset_password_for_tenant_without_account(): void
    {
        $tenant = Tenant::factory()->create(['user_id' => null]);

        $this->expectException(InvalidArgumentException::class);
        app(TenantAccountService::class)->resetPassword($tenant);
    }
}
