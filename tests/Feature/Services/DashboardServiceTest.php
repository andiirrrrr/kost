<?php

namespace Tests\Feature\Services;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\RoomStatus;
use App\Enums\TenantStatus;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Tenant;
use App\Services\DashboardService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_summary_returns_current_month_financial_and_occupancy_totals(): void
    {
        $rooms = collect([
            RoomStatus::OCCUPIED,
            RoomStatus::OCCUPIED,
            RoomStatus::OCCUPIED,
            RoomStatus::AVAILABLE,
            RoomStatus::AVAILABLE,
            RoomStatus::MAINTENANCE,
        ])->map(fn (RoomStatus $status): Room => Room::factory()->create(['status' => $status]));

        $tenants = collect(range(0, 3))->map(fn (int $index): Tenant => Tenant::factory()->create([
            'room_id' => $rooms[$index]->id,
            'status' => TenantStatus::ACTIVE,
        ]));
        Tenant::factory()->create(['room_id' => $rooms[5]->id, 'status' => TenantStatus::INACTIVE]);

        $invoiceAmounts = [1000000, 2000000, 3000000, 4000000];
        $statuses = [InvoiceStatus::PAID, InvoiceStatus::UNPAID, InvoiceStatus::OVERDUE, InvoiceStatus::CANCELLED];
        $invoices = $tenants->map(fn (Tenant $tenant, int $index): Invoice => Invoice::factory()->for($tenant)->create([
            'room_id' => $tenant->room_id,
            'period_month' => 8,
            'period_year' => 2026,
            'total_amount' => $invoiceAmounts[$index],
            'status' => $statuses[$index],
        ]));

        Payment::factory()->for($invoices[0])->create([
            'tenant_id' => $invoices[0]->tenant_id,
            'amount' => 1000000,
            'paid_at' => '2026-08-15 10:00:00',
            'status' => PaymentStatus::VERIFIED,
        ]);
        Expense::factory()->create(['amount' => 400000, 'expense_date' => '2026-08-10']);
        Expense::factory()->create(['amount' => 999999, 'expense_date' => '2026-07-10']);

        $summary = app(DashboardService::class)->summary(CarbonImmutable::parse('2026-08-30'));

        $this->assertSame(6, $summary['total_rooms']);
        $this->assertSame(3, $summary['occupied_rooms']);
        $this->assertSame(2, $summary['available_rooms']);
        $this->assertSame(50.0, $summary['occupancy_rate']);
        $this->assertSame(4, $summary['active_tenants']);
        $this->assertSame(10000000, $summary['invoice_total']);
        $this->assertSame(1000000, $summary['income']);
        $this->assertSame(5000000, $summary['outstanding_total']);
        $this->assertSame(1, $summary['overdue_count']);
        $this->assertSame(400000, $summary['expenses']);
        $this->assertSame(600000, $summary['estimated_net']);
    }

    public function test_financial_trend_returns_zero_filled_twelve_month_series(): void
    {
        $julyInvoice = Invoice::factory()->create();
        $augustInvoice = Invoice::factory()->create(['period_month' => 9]);
        $rejectedInvoice = Invoice::factory()->create(['period_month' => 10]);

        Payment::factory()->for($julyInvoice)->create([
            'tenant_id' => $julyInvoice->tenant_id,
            'amount' => 800000,
            'paid_at' => '2026-07-15',
            'status' => PaymentStatus::VERIFIED,
        ]);
        Payment::factory()->for($augustInvoice)->create([
            'tenant_id' => $augustInvoice->tenant_id,
            'amount' => 1000000,
            'paid_at' => '2026-08-15',
            'status' => PaymentStatus::VERIFIED,
        ]);
        Payment::factory()->for($rejectedInvoice)->create([
            'tenant_id' => $rejectedInvoice->tenant_id,
            'amount' => 9000000,
            'paid_at' => '2026-08-20',
            'status' => PaymentStatus::REJECTED,
        ]);
        Expense::factory()->create(['amount' => 300000, 'expense_date' => '2026-07-10']);
        Expense::factory()->create(['amount' => 400000, 'expense_date' => '2026-08-10']);

        $trend = app(DashboardService::class)->financialTrend(CarbonImmutable::parse('2026-08-30'));

        $this->assertCount(12, $trend['labels']);
        $this->assertCount(12, $trend['income']);
        $this->assertSame(800000, $trend['income'][10]);
        $this->assertSame(1000000, $trend['income'][11]);
        $this->assertSame(300000, $trend['expenses'][10]);
        $this->assertSame(400000, $trend['expenses'][11]);
        $this->assertSame(500000, $trend['estimated_net'][10]);
        $this->assertSame(600000, $trend['estimated_net'][11]);
        $this->assertSame(0, $trend['income'][0]);
    }

    public function test_invoice_status_breakdown_excludes_cancelled_invoices(): void
    {
        Invoice::factory()->create(['status' => InvoiceStatus::PAID]);
        Invoice::factory()->create(['status' => InvoiceStatus::UNPAID]);
        Invoice::factory()->create(['status' => InvoiceStatus::PENDING]);
        Invoice::factory()->create(['status' => InvoiceStatus::OVERDUE]);
        Invoice::factory()->create(['status' => InvoiceStatus::CANCELLED]);

        $breakdown = app(DashboardService::class)->invoiceStatusBreakdown(CarbonImmutable::parse('2026-08-30'));

        $this->assertSame(['paid' => 1, 'unpaid' => 2, 'overdue' => 1], $breakdown);
    }
}
