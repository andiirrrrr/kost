<?php

namespace App\Livewire\Tenant;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Setting;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class PaymentSubmission extends Component
{
    use WithFileUploads;

    public Invoice $invoice;

    public string $paymentMethod = 'bank_transfer';

    public string $paidAt = '';

    public $proof;

    public function mount(Invoice $invoice): void
    {
        $tenant = auth()->user()->tenant;
        abort_unless($invoice->tenant_id === $tenant->id, 404);
        abort_unless(in_array($invoice->status, [InvoiceStatus::UNPAID, InvoiceStatus::OVERDUE], true), 422);
        abort_if($invoice->payments()->where('status', PaymentStatus::PENDING)->exists(), 422);

        $this->invoice = $invoice;
        $this->paidAt = now()->format('Y-m-d\TH:i');
    }

    public function submit(PaymentService $service): void
    {
        $tenant = auth()->user()->tenant;
        abort_unless($this->invoice->tenant_id === $tenant->id, 404);

        $validated = $this->validate([
            'paymentMethod' => ['required', 'in:bank_transfer'],
            'paidAt' => ['required', 'date', 'before_or_equal:now'],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'extensions:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ], [
            'proof.required' => 'Bukti pembayaran wajib diunggah.',
            'proof.mimes' => 'Bukti harus berupa JPG, PNG, WEBP, atau PDF.',
            'proof.extensions' => 'Ekstensi bukti pembayaran tidak valid.',
            'proof.max' => 'Ukuran bukti maksimal 5 MB.',
        ]);

        $path = $this->proof->store('payments/proofs', 'local');

        try {
            $service->submitTenantPayment(
                $this->invoice,
                $tenant->id,
                PaymentMethod::from($validated['paymentMethod']),
                $validated['paidAt'],
                $path,
            );
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        session()->flash('success', 'Pembayaran berhasil diajukan dan sedang menunggu verifikasi.');
        $this->redirectRoute('tenant.payments.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.tenant.payment-submission', [
            'bankName' => Setting::get('bank_name', 'Belum diatur'),
            'bankAccountNumber' => Setting::get('bank_account_number', 'Belum diatur'),
            'bankAccountHolder' => Setting::get('bank_account_holder', 'Belum diatur'),
        ]);
    }
}
