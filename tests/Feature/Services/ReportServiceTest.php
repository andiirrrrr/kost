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
use App\Services\ReportService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_filters_invoice_and_payment_reports_by_period_and_status(): void
    {
        $paidInvoice = Invoice::factory()->create(['period_month' => 8, 'period_year' => 2026, 'status' => InvoiceStatus::PAID]);
        Invoice::factory()->create(['period_month' => 8, 'period_year' => 2026, 'status' => InvoiceStatus::UNPAID]);
        Invoice::factory()->create(['period_month' => 7, 'period_year' => 2026, 'status' => InvoiceStatus::PAID]);
        Payment::factory()->for($paidInvoice)->create([
            'tenant_id' => $paidInvoice->tenant_id,
            'status' => PaymentStatus::VERIFIED,
            'paid_at' => '2026-08-20',
        ]);
        Payment::factory()->create(['status' => PaymentStatus::PENDING, 'paid_at' => '2026-08-21']);

        $service = app(ReportService::class);
        $invoices = $service->invoices(8, 2026, InvoiceStatus::PAID->value);
        $payments = $service->payments(8, 2026, PaymentStatus::VERIFIED->value);

        $this->assertCount(1, $invoices);
        $this->assertTrue($invoices->first()->is($paidInvoice));
        $this->assertCount(1, $payments);
        $this->assertSame(PaymentStatus::VERIFIED, $payments->first()->status);
    }

    public function test_filters_room_and_tenant_reports_by_status(): void
    {
        $availableRoom = Room::factory()->create(['status' => RoomStatus::AVAILABLE]);
        $occupiedRoom = Room::factory()->create(['status' => RoomStatus::OCCUPIED]);
        $activeTenant = Tenant::factory()->create(['room_id' => $occupiedRoom->id, 'status' => TenantStatus::ACTIVE]);
        Tenant::factory()->create(['room_id' => $availableRoom->id, 'status' => TenantStatus::INACTIVE]);

        $service = app(ReportService::class);
        $rooms = $service->rooms(RoomStatus::OCCUPIED->value);
        $tenants = $service->tenants(TenantStatus::ACTIVE->value);

        $this->assertCount(1, $rooms);
        $this->assertTrue($rooms->first()->is($occupiedRoom));
        $this->assertSame([$activeTenant->name], $rooms->first()->tenants->pluck('name')->all());
        $this->assertCount(1, $tenants);
        $this->assertTrue($tenants->first()->is($activeTenant));
    }

    public function test_summary_uses_verified_income_and_active_outstanding_invoices(): void
    {
        $invoice = Invoice::factory()->create([
            'period_month' => 8,
            'period_year' => 2026,
            'total_amount' => 1500000,
            'status' => InvoiceStatus::OVERDUE,
        ]);
        Payment::factory()->for($invoice)->create([
            'tenant_id' => $invoice->tenant_id,
            'amount' => 1000000,
            'status' => PaymentStatus::VERIFIED,
            'paid_at' => '2026-08-15',
        ]);
        $cancelledInvoice = Invoice::factory()->create(['status' => InvoiceStatus::CANCELLED]);
        Payment::factory()->for($cancelledInvoice)->create([
            'tenant_id' => $cancelledInvoice->tenant_id,
            'amount' => 9000000,
            'status' => PaymentStatus::REJECTED,
            'paid_at' => '2026-08-16',
        ]);
        Expense::factory()->create(['amount' => 350000, 'expense_date' => '2026-08-10']);

        $summary = app(ReportService::class)->summary(8, 2026);

        $this->assertSame(1000000, $summary['income']);
        $this->assertSame(350000, $summary['expenses']);
        $this->assertSame(1500000, $summary['outstanding']);
    }
}
