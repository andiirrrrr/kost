<?php

namespace Tests\Feature\Filament;

use App\Enums\PaymentStatus;
use App\Filament\Admin\Pages\DataTools;
use App\Filament\Admin\Pages\Onboarding;
use App\Filament\Admin\Resources\MaintenanceRequests\Pages\ListMaintenanceRequests;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OperationalFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_open_operational_admin_pages(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::query()->role('owner')->firstOrFail();
        $this->actingAs($owner);

        Livewire::test(ListMaintenanceRequests::class)->assertSuccessful();
        Livewire::test(Onboarding::class)->assertSuccessful();
        Livewire::test(DataTools::class)->assertSuccessful();
    }

    public function test_tenant_can_report_maintenance_and_print_verified_receipt(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('tenant');
        $tenant = Tenant::factory()->for($user)->create();

        $this->actingAs($user)->post(route('tenant.maintenance.store'), ['title' => 'Keran bocor', 'description' => 'Air terus menetes di kamar mandi.', 'priority' => 'normal'])->assertRedirect();
        $this->assertDatabaseHas('maintenance_requests', ['tenant_id' => $tenant->id, 'title' => 'Keran bocor']);

        $payment = Payment::factory()->create(['tenant_id' => $tenant->id, 'status' => PaymentStatus::VERIFIED]);
        $this->actingAs($user)->get(route('payments.receipt', $payment))->assertOk()->assertSee($payment->payment_number);
    }
}
