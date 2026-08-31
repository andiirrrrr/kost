<?php

namespace Tests\Feature\Services;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_verification_updates_payment_and_invoice_atomically(): void
    {
        $verifier = User::factory()->create();
        $invoice = Invoice::factory()->create();
        $payment = Payment::factory()->for($invoice)->create([
            'tenant_id' => $invoice->tenant_id,
            'amount' => $invoice->total_amount,
        ]);

        app(PaymentService::class)->verifyPayment($payment, $verifier->id);

        $this->assertSame(PaymentStatus::VERIFIED, $payment->refresh()->status);
        $this->assertSame($verifier->id, $payment->verified_by);
        $this->assertSame(InvoiceStatus::PAID, $invoice->refresh()->status);
        $this->assertNotNull($invoice->paid_at);
    }

    public function test_invalid_amount_rolls_back_payment_and_invoice_changes(): void
    {
        $verifier = User::factory()->create();
        $invoice = Invoice::factory()->create(['total_amount' => 1000000]);
        $payment = Payment::factory()->for($invoice)->create([
            'tenant_id' => $invoice->tenant_id,
            'amount' => 900000,
        ]);

        try {
            app(PaymentService::class)->verifyPayment($payment, $verifier->id);
            $this->fail('Pembayaran dengan jumlah salah seharusnya ditolak.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Jumlah pembayaran tidak sesuai dengan tagihan.', $exception->getMessage());
        }

        $this->assertSame(PaymentStatus::PENDING, $payment->refresh()->status);
        $this->assertSame(InvoiceStatus::UNPAID, $invoice->refresh()->status);
    }

    public function test_quick_payment_creates_verified_payment_before_marking_invoice_paid(): void
    {
        $verifier = User::factory()->create();
        $invoice = Invoice::factory()->create();

        $payment = app(PaymentService::class)->recordVerifiedPayment(
            $invoice,
            PaymentMethod::CASH,
            '2026-08-30 10:00:00',
            $verifier->id,
            'Diterima di kantor',
        );

        $this->assertSame(PaymentStatus::VERIFIED, $payment->status);
        $this->assertSame($invoice->id, $payment->invoice_id);
        $this->assertSame($invoice->tenant_id, $payment->tenant_id);
        $this->assertSame($invoice->total_amount, $payment->amount);
        $this->assertSame(InvoiceStatus::PAID, $invoice->refresh()->status);
    }

    public function test_rejection_requires_reason_and_keeps_invoice_unpaid(): void
    {
        $invoice = Invoice::factory()->create(['due_date' => now()->addDay()]);
        $payment = Payment::factory()->for($invoice)->create([
            'tenant_id' => $invoice->tenant_id,
            'amount' => $invoice->total_amount,
        ]);

        try {
            app(PaymentService::class)->rejectPayment($payment, '');
            $this->fail('Penolakan tanpa alasan seharusnya ditolak.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Alasan penolakan wajib diisi.', $exception->getMessage());
        }

        $this->assertSame(PaymentStatus::PENDING, $payment->refresh()->status);
        $this->assertSame(InvoiceStatus::UNPAID, $invoice->refresh()->status);
    }

    public function test_tenant_can_submit_again_after_rejected_payment(): void
    {
        $invoice = Invoice::factory()->create(['due_date' => now()->addDay()]);
        $payment = Payment::factory()->for($invoice)->create([
            'tenant_id' => $invoice->tenant_id,
            'amount' => $invoice->total_amount,
        ]);
        $service = app(PaymentService::class);

        $service->rejectPayment($payment, 'Bukti transfer tidak terbaca.');
        $resubmission = $service->submitTenantPayment(
            $invoice->refresh(),
            $invoice->tenant_id,
            PaymentMethod::BANK_TRANSFER,
            now(),
            'payments/proofs/retry.jpg',
        );

        $this->assertSame(PaymentStatus::REJECTED, $payment->refresh()->status);
        $this->assertSame('Bukti transfer tidak terbaca.', $payment->rejection_reason);
        $this->assertSame(PaymentStatus::PENDING, $resubmission->status);
        $this->assertSame(InvoiceStatus::PENDING, $invoice->refresh()->status);
        $this->assertSame(2, $invoice->payments()->count());
    }
}
