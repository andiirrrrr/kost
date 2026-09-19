<?php

namespace Tests\Feature\Services;

use App\Enums\InvoiceStatus;
use App\Enums\TenantStatus;
use App\Models\Invoice;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\Tenant;
use App\Models\TenantRoomHistory;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_calculates_invoice_total_on_the_backend(): void
    {
        $total = InvoiceService::calculateTotal([
            'base_amount' => 1000000,
            'electricity_amount' => 150000,
            'water_amount' => 50000,
            'other_amount' => 25000,
            'discount_amount' => 100000,
            'total_amount' => 1,
        ]);

        $this->assertSame(1125000, $total);
    }

    public function test_generates_invoice_from_server_side_tenant_data_and_clamps_due_date(): void
    {
        $tenant = Tenant::factory()->create([
            'monthly_price' => 1250000,
            'move_in_date' => '2026-01-31',
            'due_day' => 31,
            'status' => TenantStatus::ACTIVE,
        ]);
        Tenant::factory()->create(['move_in_date' => '2026-01-01', 'status' => TenantStatus::INACTIVE]);

        $result = app(InvoiceService::class)->generateMonthlyInvoices(2, 2026);

        $this->assertSame(1, $result['created']);
        $this->assertSame(0, $result['skipped']);
        $invoice = Invoice::whereBelongsTo($tenant)->sole();
        $this->assertSame(1250000, $invoice->total_amount);
        $this->assertSame('2026-02-28', $invoice->due_date->toDateString());
        $this->assertSame(InvoiceStatus::UNPAID, $invoice->status);
    }

    public function test_skips_duplicate_invoice_for_the_same_tenant_and_period(): void
    {
        $tenant = Tenant::factory()->create(['move_in_date' => '2026-01-05']);
        Invoice::factory()->for($tenant)->create([
            'room_id' => $tenant->room_id,
            'period_month' => 8,
            'period_year' => 2026,
        ]);

        $result = app(InvoiceService::class)->generateMonthlyInvoices(8, 2026);

        $this->assertSame(0, $result['created']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame(1, Invoice::whereBelongsTo($tenant)->count());
    }

    public function test_monthly_invoice_uses_room_category_price(): void
    {
        $category = RoomCategory::factory()->create(['base_monthly_price' => 1750000]);
        $room = Room::factory()->for($category, 'roomCategory')->create(['monthly_price' => 1600000]);
        $tenant = Tenant::factory()->for($room)->create([
            'monthly_price' => 1250000,
            'move_in_date' => '2026-01-01',
        ]);

        $result = app(InvoiceService::class)->generateMonthlyInvoices(2, 2026);

        $this->assertSame(1, $result['created']);
        $invoice = Invoice::query()->whereBelongsTo($tenant)->sole();
        $this->assertSame(1750000, $invoice->base_amount);
        $this->assertSame(1750000, $invoice->total_amount);
    }

    public function test_does_not_generate_invoice_before_tenant_move_in_period(): void
    {
        $existingTenant = Tenant::factory()->create(['move_in_date' => '2026-08-20']);
        $futureTenant = Tenant::factory()->create(['move_in_date' => '2026-09-01']);

        $result = app(InvoiceService::class)->generateMonthlyInvoices(8, 2026);

        $this->assertSame(1, $result['created']);
        $this->assertTrue(Invoice::whereBelongsTo($existingTenant)->exists());
        $this->assertFalse(Invoice::whereBelongsTo($futureTenant)->exists());
    }

    public function test_does_not_generate_invoice_after_tenant_has_moved_out(): void
    {
        $movedOutTenant = Tenant::factory()->create(['move_in_date' => '2026-01-01', 'move_out_date' => '2026-07-31', 'status' => TenantStatus::ACTIVE]);

        $result = app(InvoiceService::class)->generateMonthlyInvoices(8, 2026);

        $this->assertSame(0, $result['created']);
        $this->assertFalse(Invoice::whereBelongsTo($movedOutTenant)->exists());
    }

    public function test_marks_only_past_unpaid_invoices_as_overdue(): void
    {
        $this->travelTo('2026-08-30 12:00:00');
        $pastDue = Invoice::factory()->create(['due_date' => '2026-08-29']);
        $futureDue = Invoice::factory()->create(['due_date' => '2026-08-31']);
        $paid = Invoice::factory()->create(['due_date' => '2026-08-01', 'status' => InvoiceStatus::PAID]);

        $updated = app(InvoiceService::class)->updateOverdueStatus();

        $this->assertSame(1, $updated);
        $this->assertSame(InvoiceStatus::OVERDUE, $pastDue->refresh()->status);
        $this->assertSame(InvoiceStatus::UNPAID, $futureDue->refresh()->status);
        $this->assertSame(InvoiceStatus::PAID, $paid->refresh()->status);
    }

    public function test_synchronizes_unpaid_invoice_while_preserving_manual_costs_and_room_history(): void
    {
        $category = RoomCategory::factory()->create(['base_monthly_price' => 1500000]);
        $historicalRoom = Room::factory()->for($category, 'roomCategory')->create();
        $currentRoom = Room::factory()->create();
        $tenant = Tenant::factory()->for($currentRoom)->create(['move_in_date' => '2026-01-17', 'due_day' => 5]);
        TenantRoomHistory::query()->whereBelongsTo($tenant)->delete();
        $history = TenantRoomHistory::query()->create([
            'tenant_id' => $tenant->id,
            'room_id' => $historicalRoom->id,
            'starts_at' => '2026-01-17',
            'ends_at' => '2026-09-30',
            'monthly_price' => 1500000,
            'due_day' => 17,
        ]);
        $invoice = Invoice::factory()->for($tenant)->for($historicalRoom)->create([
            'tenant_room_history_id' => $history->id,
            'period_month' => 9,
            'period_year' => 2026,
            'base_amount' => 1000000,
            'electricity_amount' => 100000,
            'water_amount' => 50000,
            'other_amount' => 25000,
            'discount_amount' => 75000,
            'total_amount' => 1100000,
            'due_date' => '2026-09-05',
            'status' => InvoiceStatus::UNPAID,
        ]);

        app(InvoiceService::class)->synchronizeInvoice($invoice);

        $invoice->refresh();
        $this->assertSame($historicalRoom->id, $invoice->room_id);
        $this->assertSame(1000000, $invoice->base_amount);
        $this->assertSame(100000, $invoice->electricity_amount);
        $this->assertSame(50000, $invoice->water_amount);
        $this->assertSame(25000, $invoice->other_amount);
        $this->assertSame(75000, $invoice->discount_amount);
        $this->assertSame(1100000, $invoice->total_amount);
        $this->assertSame('2026-09-17', $invoice->due_date->toDateString());
    }

    public function test_does_not_synchronize_invoice_with_payment_in_progress(): void
    {
        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::PENDING]);

        $this->expectException(ValidationException::class);

        app(InvoiceService::class)->synchronizeInvoice($invoice);
    }

    public function test_synchronize_all_only_writes_changed_invoices(): void
    {
        $tenant = Tenant::factory()->create(['move_in_date' => '2026-01-17', 'due_day' => 5]);
        $expectedBaseAmount = (int) $tenant->room->monthly_price;
        $changed = Invoice::factory()->for($tenant)->create([
            'room_id' => $tenant->room_id,
            'period_month' => 9,
            'period_year' => 2026,
            'base_amount' => 1,
            'total_amount' => 1,
            'due_date' => '2026-09-05',
        ]);
        $unchanged = Invoice::factory()->for($tenant)->create([
            'room_id' => $tenant->room_id,
            'period_month' => 10,
            'period_year' => 2026,
            'base_amount' => $expectedBaseAmount,
            'total_amount' => $expectedBaseAmount,
            'due_date' => '2026-10-17',
            'updated_at' => '2026-01-01 00:00:00',
        ]);

        $result = app(InvoiceService::class)->synchronizeAllChangedInvoices();

        $this->assertSame(['synchronized' => 1, 'unchanged' => 1], $result);
        $this->assertSame('2026-09-17', $changed->fresh()->due_date->toDateString());
        $this->assertSame('2026-01-01 00:00:00', $unchanged->fresh()->updated_at->format('Y-m-d H:i:s'));
    }

    public function test_old_invoice_keeps_old_stay_due_day_after_tenant_checks_in_again(): void
    {
        $room = Room::factory()->create();
        $tenant = Tenant::factory()->for($room)->create(['move_in_date' => '2026-01-05', 'due_day' => 5]);
        $oldHistory = TenantRoomHistory::query()->whereBelongsTo($tenant)->sole();
        $oldHistory->update(['ends_at' => '2026-08-31', 'due_day' => 5]);
        $invoice = Invoice::factory()->for($tenant)->for($room)->create([
            'tenant_room_history_id' => $oldHistory->id,
            'period_month' => 8,
            'period_year' => 2026,
            'due_date' => '2026-08-01',
        ]);
        $tenant->update(['move_in_date' => '2026-09-17', 'due_day' => 17]);
        TenantRoomHistory::query()->create(['tenant_id' => $tenant->id, 'room_id' => $room->id, 'starts_at' => '2026-09-17', 'monthly_price' => 1000000, 'due_day' => 17]);

        app(InvoiceService::class)->synchronizeInvoice($invoice);

        $this->assertSame('2026-08-05', $invoice->fresh()->due_date->toDateString());
    }
}
