<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Broadcast;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsAppLog;
use App\Models\WhatsAppTemplate;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_seeder_can_run_repeatedly_without_duplicate_records(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(2, User::count());
        $this->assertSame(10, Room::count());
        $this->assertSame(7, Tenant::count());
        $this->assertSame(5, Invoice::count());
        $this->assertSame(3, Payment::count());
        $this->assertSame(3, Expense::count());
        $this->assertSame(5, WhatsAppTemplate::count());
        $this->assertSame(1, Broadcast::count());
        $this->assertSame(3, WhatsAppLog::count());
        $this->assertSame(3, Announcement::count());
        $this->assertSame(2, DatabaseNotification::count());
        $this->assertSame(['owner', 'public', 'tenant'], Role::query()->orderBy('name')->pluck('name')->all());
        $this->assertSame(36, Permission::count());

        $this->assertTrue(User::where('email', 'admin@kost.test')->sole()->hasRole('owner'));
        $tenantUser = User::where('email', 'tenant@kost.test')->sole();
        $this->assertTrue($tenantUser->hasRole('tenant'));
        $this->assertSame('Budi Santoso', $tenantUser->tenant?->name);
    }

    public function test_seeder_migrates_legacy_management_roles_to_owner(): void
    {
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('staff', 'web');
        $legacyManager = User::factory()->create();
        $legacyManager->assignRole('staff');

        $this->seed(DatabaseSeeder::class);

        $this->assertTrue($legacyManager->fresh()->hasRole('owner'));
        $this->assertSame(['owner', 'public', 'tenant'], Role::query()->orderBy('name')->pluck('name')->all());
    }
}
