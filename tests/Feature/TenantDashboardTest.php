<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Announcement;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\TenantActivityNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantDashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_dashboard_displays_admin_managed_data(): void
    {
        Role::findOrCreate('tenant', 'web');
        $user = User::factory()->create();
        $tenant = Tenant::factory()->for($user)->create(['name' => 'Andi Pratama']);
        $user->assignRole('tenant');
        $invoice = Invoice::factory()->for($tenant)->create([
            'room_id' => $tenant->room_id,
            'period_month' => 9,
            'period_year' => 2026,
            'total_amount' => 1200000,
            'status' => InvoiceStatus::UNPAID,
        ]);
        $user->notify(new TenantActivityNotification('invoice', 'Tagihan September tersedia', 'Tagihan terbaru sudah diterbitkan.'));
        Announcement::factory()->create(['title' => 'Pemeliharaan Air']);

        $this->actingAs($user)->get(route('tenant.dashboard'))
            ->assertOk()
            ->assertSee('Selamat datang, Andi Pratama')
            ->assertSee('September 2026')
            ->assertSee('Rp 1.200.000')
            ->assertSee('Tagihan September tersedia')
            ->assertSee('Pemeliharaan Air')
            ->assertSee('Riwayat Bayar')
            ->assertSee('Lapor Perbaikan')
            ->assertSee(route('tenant.payments.create', $invoice), false);
    }

    public function test_dashboard_displays_empty_states_without_admin_content(): void
    {
        Role::findOrCreate('tenant', 'web');
        $user = User::factory()->create();
        Tenant::factory()->for($user)->create();
        $user->assignRole('tenant');

        $this->actingAs($user)->get(route('tenant.dashboard'))
            ->assertOk()
            ->assertSee('Belum ada tagihan')
            ->assertSee('Belum ada notifikasi.')
            ->assertSee('Belum ada pengumuman dari admin.');
    }
}
