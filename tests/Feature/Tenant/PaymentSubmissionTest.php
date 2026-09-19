<?php

namespace Tests\Feature\Tenant;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethodCategory;
use App\Enums\PaymentStatus;
use App\Livewire\Tenant\PaymentSubmission;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaymentSubmissionTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_tenant_submits_exact_invoice_amount_as_pending_without_marking_invoice_paid(): void
    {
        Storage::fake('local');
        [$user, $tenant] = $this->tenantIdentity();
        Role::findOrCreate('owner', 'web');
        $owner = User::factory()->create();
        $owner->assignRole('owner');
        $invoice = Invoice::factory()->for($tenant)->create([
            'room_id' => $tenant->room_id,
            'total_amount' => 1250000,
            'due_date' => now()->addDay(),
        ]);
        $bri = PaymentMethod::factory()->create(['name' => 'Bank BRI', 'code' => 'bank_bri', 'category' => PaymentMethodCategory::BANK_TRANSFER, 'sort_order' => 2]);
        $qris = PaymentMethod::factory()->create(['name' => 'GoPay', 'code' => 'gopay', 'category' => PaymentMethodCategory::E_WALLET, 'sort_order' => 1]);
        PaymentMethod::factory()->create(['name' => 'Metode Nonaktif', 'code' => 'inactive_method', 'is_active' => false]);

        Livewire::actingAs($user)->test(PaymentSubmission::class, ['invoice' => $invoice])
            ->assertSee('Bank Transfer')
            ->assertSee('E-Wallet')
            ->assertSee('Bank BRI')
            ->assertDontSee('GoPay')
            ->assertDontSee('Metode Nonaktif')
            ->set('paymentCategory', PaymentMethodCategory::E_WALLET->value)
            ->assertSet('paymentMethodId', $qris->id)
            ->assertSee('GoPay')
            ->assertDontSee('Bank BRI')
            ->set('paymentMethodId', $qris->id)
            ->set('paidAt', now()->subMinute()->format('Y-m-d\TH:i'))
            ->set('proof', UploadedFile::fake()->image('proof.jpg'))
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(route('tenant.payments.index'));

        $payment = Payment::query()->sole();
        $this->assertSame(1250000, $payment->amount);
        $this->assertSame(PaymentStatus::PENDING, $payment->status);
        $this->assertSame($qris->id, $payment->payment_method_id);
        $this->assertSame('gopay', $payment->payment_method);
        $this->assertSame(InvoiceStatus::PENDING, $invoice->refresh()->status);
        Storage::disk('local')->assertExists($payment->proof);
        $this->assertSame(1, $owner->notifications()->count());
    }

    public function test_proof_rejects_unsupported_file_type(): void
    {
        Storage::fake('local');
        [$user, $tenant] = $this->tenantIdentity();
        $invoice = Invoice::factory()->for($tenant)->create(['room_id' => $tenant->room_id, 'due_date' => now()->addDay()]);

        Livewire::actingAs($user)->test(PaymentSubmission::class, ['invoice' => $invoice])
            ->set('paidAt', now()->subMinute()->format('Y-m-d\TH:i'))
            ->set('proof', UploadedFile::fake()->create('malware.exe', 20, 'application/octet-stream'))
            ->call('submit')
            ->assertHasErrors('proof');

        $this->assertSame(0, Payment::query()->count());
    }

    public function test_rejected_payment_shows_retry_action_and_notification_links_to_payment_form(): void
    {
        [$user, $tenant] = $this->tenantIdentity();
        $invoice = Invoice::factory()->for($tenant)->create([
            'room_id' => $tenant->room_id,
            'status' => InvoiceStatus::PENDING,
            'due_date' => now()->addDay(),
        ]);
        $payment = Payment::factory()->for($invoice)->create([
            'tenant_id' => $tenant->id,
            'status' => PaymentStatus::PENDING,
        ]);

        app(PaymentService::class)->rejectPayment($payment, 'Bukti transfer tidak terbaca.');

        $paymentFormUrl = route('tenant.payments.create', $invoice);
        $this->actingAs($user)->get(route('tenant.payments.index'))
            ->assertSee('Bukti transfer tidak terbaca.')
            ->assertSee('Bayar Ulang')
            ->assertSee($paymentFormUrl, false);
        $this->assertSame($paymentFormUrl, $user->notifications()->sole()->data['url']);
    }

    /** @return array{User, Tenant} */
    private function tenantIdentity(): array
    {
        Role::findOrCreate('tenant', 'web');
        $user = User::factory()->create();
        $tenant = Tenant::factory()->for($user)->create();
        PaymentMethod::query()->where('code', 'bank_transfer')->update(['is_active' => true, 'sort_order' => 1]);
        $user->assignRole('tenant');

        return [$user, $tenant];
    }
}
