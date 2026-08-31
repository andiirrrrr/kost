<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Notifications\TenantActivityNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentService
{
    public function __construct(private TenantNotificationService $notifications) {}

    public function verifyPayment(Payment $payment, int $verifiedBy): bool
    {
        $verified = DB::transaction(function () use ($payment, $verifiedBy): bool {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($lockedPayment->invoice_id);

            $this->assertPaymentCanBeVerified($lockedPayment, $invoice);

            $lockedPayment->update([
                'status' => PaymentStatus::VERIFIED,
                'verified_by' => $verifiedBy,
                'verified_at' => now(),
            ]);

            $invoice->update([
                'status' => InvoiceStatus::PAID,
                'paid_at' => $lockedPayment->paid_at ?? now(),
            ]);

            $payment->refresh();

            return true;
        });

        $this->notifications->notifyTenant(
            $payment->tenant,
            new TenantActivityNotification('payment_verified', 'Pembayaran berhasil diverifikasi', "Pembayaran {$payment->payment_number} telah diterima.", route('tenant.payments.index')),
        );

        return $verified;
    }

    public function recordVerifiedPayment(
        Invoice $invoice,
        PaymentMethod $paymentMethod,
        Carbon|string $paidAt,
        int $verifiedBy,
        ?string $notes = null,
    ): Payment {
        return DB::transaction(function () use ($invoice, $paymentMethod, $paidAt, $verifiedBy, $notes): Payment {
            $lockedInvoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);

            if ($lockedInvoice->status->isTerminal()) {
                throw new InvalidArgumentException('Tagihan sudah lunas atau dibatalkan.');
            }

            if ($lockedInvoice->payments()->where('status', PaymentStatus::VERIFIED)->exists()) {
                throw new InvalidArgumentException('Tagihan sudah memiliki pembayaran terverifikasi.');
            }

            $payment = Payment::create([
                'invoice_id' => $lockedInvoice->id,
                'tenant_id' => $lockedInvoice->tenant_id,
                'payment_number' => Payment::generatePaymentNumber(),
                'amount' => $lockedInvoice->total_amount,
                'payment_method' => $paymentMethod,
                'paid_at' => $paidAt,
                'status' => PaymentStatus::PENDING,
                'notes' => $notes,
            ]);

            $this->verifyPayment($payment, $verifiedBy);

            return $payment->refresh();
        });
    }

    public function submitTenantPayment(
        Invoice $invoice,
        int $tenantId,
        PaymentMethod $paymentMethod,
        Carbon|string $paidAt,
        string $proof,
    ): Payment {
        $payment = DB::transaction(function () use ($invoice, $tenantId, $paymentMethod, $paidAt, $proof): Payment {
            $lockedInvoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);

            if ($lockedInvoice->tenant_id !== $tenantId) {
                throw new InvalidArgumentException('Tagihan tidak tersedia untuk penghuni ini.');
            }

            if (! in_array($lockedInvoice->status, [InvoiceStatus::UNPAID, InvoiceStatus::OVERDUE], true)) {
                throw new InvalidArgumentException('Tagihan ini tidak dapat diajukan pembayarannya.');
            }

            if ($lockedInvoice->payments()->where('status', PaymentStatus::PENDING)->exists()) {
                throw new InvalidArgumentException('Pembayaran untuk tagihan ini sedang menunggu verifikasi.');
            }

            $payment = Payment::create([
                'invoice_id' => $lockedInvoice->id,
                'tenant_id' => $tenantId,
                'payment_number' => Payment::generatePaymentNumber(),
                'amount' => $lockedInvoice->total_amount,
                'payment_method' => $paymentMethod,
                'paid_at' => $paidAt,
                'proof' => $proof,
                'status' => PaymentStatus::PENDING,
            ]);

            $lockedInvoice->update(['status' => InvoiceStatus::PENDING]);

            return $payment;
        });

        $this->notifications->notifyTenant(
            $payment->tenant,
            new TenantActivityNotification('payment_submitted', 'Pembayaran dikirim', "Pembayaran {$payment->payment_number} sedang menunggu verifikasi.", route('tenant.payments.index')),
        );
        $this->notifications->notifyAdministrators(
            new TenantActivityNotification('admin_payment_submitted', 'Pembayaran baru menunggu verifikasi', "{$payment->tenant->name} mengajukan pembayaran {$payment->payment_number}.", '/admin/payments'),
        );

        return $payment;
    }

    public function rejectPayment(Payment $payment, string $reason): bool
    {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('Alasan penolakan wajib diisi.');
        }

        $rejected = DB::transaction(function () use ($payment, $reason): bool {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($lockedPayment->invoice_id);

            if ($lockedPayment->status !== PaymentStatus::PENDING) {
                throw new InvalidArgumentException('Hanya pembayaran pending yang bisa ditolak.');
            }

            $lockedPayment->update([
                'status' => PaymentStatus::REJECTED,
                'rejection_reason' => trim($reason),
            ]);
            $invoice->update([
                'status' => $invoice->due_date->isPast() ? InvoiceStatus::OVERDUE : InvoiceStatus::UNPAID,
            ]);
            $payment->refresh();

            return true;
        });

        $this->notifications->notifyTenant(
            $payment->tenant,
            new TenantActivityNotification('payment_rejected', 'Pembayaran ditolak', trim($reason), route('tenant.payments.index')),
        );

        return $rejected;
    }

    private function assertPaymentCanBeVerified(Payment $payment, Invoice $invoice): void
    {
        if ($payment->status !== PaymentStatus::PENDING) {
            throw new InvalidArgumentException('Hanya pembayaran pending yang bisa diverifikasi.');
        }

        if ($invoice->status === InvoiceStatus::PAID) {
            throw new InvalidArgumentException('Invoice sudah lunas.');
        }

        if ($invoice->status === InvoiceStatus::CANCELLED) {
            throw new InvalidArgumentException('Invoice sudah dibatalkan.');
        }

        if ($payment->tenant_id !== $invoice->tenant_id) {
            throw new InvalidArgumentException('Penghuni pembayaran tidak sesuai dengan tagihan.');
        }

        if ($payment->amount !== $invoice->total_amount) {
            throw new InvalidArgumentException('Jumlah pembayaran tidak sesuai dengan tagihan.');
        }
    }
}
