<?php

namespace Tests\Feature\Filament;

use App\Enums\TenantStatus;
use App\Filament\Admin\Resources\Tenants\Pages\ListTenants;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ListTenantsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_list_separates_tenants_inactive_for_three_months_into_archive(): void
    {
        $this->travelTo('2026-09-12 12:00:00');
        $this->seed(DatabaseSeeder::class);
        $owner = User::query()->role('owner')->firstOrFail();
        $room = Room::factory()->create();
        $activeTenant = Tenant::factory()->for($room)->create([
            'status' => TenantStatus::ACTIVE,
            'move_in_date' => '2026-08-01',
            'move_out_date' => null,
        ]);
        $recentlyInactiveTenant = Tenant::factory()->for($room)->create([
            'status' => TenantStatus::INACTIVE,
            'move_in_date' => '2026-01-01',
            'move_out_date' => '2026-06-13',
        ]);
        $archivedTenant = Tenant::factory()->for($room)->create([
            'status' => TenantStatus::INACTIVE,
            'move_in_date' => '2026-01-01',
            'move_out_date' => '2026-06-12',
        ]);

        Livewire::actingAs($owner)
            ->test(ListTenants::class)
            ->assertCanSeeTableRecords([$activeTenant, $recentlyInactiveTenant])
            ->assertCanNotSeeTableRecords([$archivedTenant])
            ->set('activeTab', 'archived')
            ->assertCanSeeTableRecords([$archivedTenant])
            ->assertCanNotSeeTableRecords([$activeTenant, $recentlyInactiveTenant]);
    }

    public function test_owner_can_reset_tenant_password_from_table_action(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::query()->role('owner')->firstOrFail();
        $user = User::factory()->create(['password' => 'old-password']);
        $user->assignRole('tenant');
        $tenant = Tenant::factory()->for($user)->create(['email' => $user->email]);

        Livewire::actingAs($owner)
            ->test(ListTenants::class)
            ->callTableAction('resetPassword', $tenant, ['password' => 'brand-new-secret-123'])
            ->assertHasNoTableActionErrors();

        $this->assertTrue(Hash::check('brand-new-secret-123', $user->refresh()->password));
    }
}
