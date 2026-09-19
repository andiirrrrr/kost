<?php

namespace App\Livewire\Tenant;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethodCategory;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class PaymentSubmission extends Component
{
    use WithFileUploads;

    public Invoice $invoice;

    public ?int $paymentMethodId = null;

    public string $paymentCategory = '';

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
        $firstCategory = collect(PaymentMethodCategory::cases())->first(
            fn (PaymentMethodCategory $category): bool => PaymentMethod::query()
                ->where('is_active', true)
                ->where('category', $category)
                ->exists(),
        );
        $firstMethod = $firstCategory
            ? PaymentMethod::query()->where('is_active', true)->where('category', $firstCategory)->orderBy('sort_order')->orderBy('name')->first()
            : null;
        $this->paymentCategory = $firstMethod?->category->value ?? '';
        $this->paymentMethodId = $firstMethod?->id;
    }

    public function updatedPaymentCategory(string $category): void
    {
        $this->paymentMethodId = PaymentMethod::query()
            ->where('is_active', true)
            ->where('category', $category)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->value('id');
    }

    public function submit(PaymentService $service): void
    {
        $tenant = auth()->user()->tenant;
        abort_unless($this->invoice->tenant_id === $tenant->id, 404);

        $validated = $this->validate([
            'paymentCategory' => ['required', Rule::enum(PaymentMethodCategory::class)],
            'paymentMethodId' => ['required', 'integer', Rule::exists('payment_methods', 'id')->where('category', $this->paymentCategory)->where('is_active', true)->whereNull('deleted_at')],
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
                PaymentMethod::query()->findOrFail($validated['paymentMethodId']),
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
        $allPaymentMethods = PaymentMethod::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $paymentCategories = collect(PaymentMethodCategory::cases())
            ->filter(fn (PaymentMethodCategory $category): bool => $allPaymentMethods->contains('category', $category));
        $paymentMethods = $allPaymentMethods->where('category', $this->paymentCategory)->values();

        return view('livewire.tenant.payment-submission', [
            'paymentCategories' => $paymentCategories,
            'paymentMethods' => $paymentMethods,
            'selectedPaymentMethod' => $allPaymentMethods->firstWhere('id', $this->paymentMethodId),
        ]);
    }
}
