<?php

namespace Tests\Feature\Services;

use App\Enums\InvoiceStatus;
use App\Enums\TenantStatus;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
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
            'due_day' => 31,
            'status' => TenantStatus::ACTIVE,
        ]);
        Tenant::factory()->create(['status' => TenantStatus::INACTIVE]);

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
        $tenant = Tenant::factory()->create();
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
}
