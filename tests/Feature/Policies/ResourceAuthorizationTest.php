<?php

namespace Tests\Feature\Policies;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ResourceAuthorizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_has_full_resource_access(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@kost.test')->sole();

        $this->assertTrue($admin->can('delete', Room::factory()->create()));
        $this->assertTrue($admin->can('delete', Tenant::factory()->create()));
        $this->assertTrue($admin->can('delete', Invoice::factory()->create()));
        $this->assertTrue($admin->can('verify', Payment::factory()->create()));
    }

    public function test_public_user_has_no_management_permissions(): void
    {
        $this->seed(DatabaseSeeder::class);
        $publicUser = User::factory()->create();
        $publicUser->assignRole('public');
        $invoice = Invoice::factory()->create();

        $this->assertFalse($publicUser->can('viewAny', Room::class));
        $this->assertFalse($publicUser->can('viewAny', Tenant::class));
        $this->assertFalse($publicUser->can('viewAny', Invoice::class));
        $this->assertFalse($publicUser->can('recordPayment', $invoice));
        $this->assertFalse($publicUser->can('generate', Invoice::class));
    }
}
