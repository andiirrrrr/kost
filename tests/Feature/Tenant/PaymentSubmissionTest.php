<?php

namespace Tests\Feature\Tenant;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Livewire\Tenant\PaymentSubmission;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
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

        Livewire::actingAs($user)->test(PaymentSubmission::class, ['invoice' => $invoice])
            ->set('paymentMethod', 'bank_transfer')
            ->set('paidAt', now()->subMinute()->format('Y-m-d\TH:i'))
            ->set('proof', UploadedFile::fake()->image('proof.jpg'))
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(route('tenant.payments.index'));

        $payment = Payment::query()->sole();
        $this->assertSame(1250000, $payment->amount);
        $this->assertSame(PaymentStatus::PENDING, $payment->status);
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

    /** @return array{User, Tenant} */
    private function tenantIdentity(): array
    {
        Role::findOrCreate('tenant', 'web');
        $user = User::factory()->create();
        $tenant = Tenant::factory()->for($user)->create();
        $user->assignRole('tenant');

        return [$user, $tenant];
    }
}
