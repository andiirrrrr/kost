<?php

namespace Tests\Feature\Tenant;

use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvoicePortalTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_tenant_only_sees_own_invoices(): void
    {
        [$user, $tenant] = $this->tenantIdentity();
        $ownInvoice = Invoice::factory()->for($tenant)->create(['room_id' => $tenant->room_id, 'invoice_number' => 'INV-OWN-0001']);
        $otherInvoice = Invoice::factory()->create(['invoice_number' => 'INV-OTHER-0001']);

        $response = $this->actingAs($user)->get('/tagihan');

        $response->assertOk()->assertSee($ownInvoice->invoice_number)->assertDontSee($otherInvoice->invoice_number);
    }

    public function test_tenant_receives_404_for_another_tenants_invoice(): void
    {
        [$user] = $this->tenantIdentity();
        $otherInvoice = Invoice::factory()->create();

        $this->actingAs($user)->get(route('tenant.invoices.show', $otherInvoice))->assertNotFound();
    }

    public function test_detail_shows_receipt_link_for_verified_payment(): void
    {
        [$user, $tenant] = $this->tenantIdentity();
        $invoice = Invoice::factory()->for($tenant)->create(['room_id' => $tenant->room_id]);
        $payment = Payment::factory()->for($invoice)->create([
            'tenant_id' => $tenant->id,
            'status' => PaymentStatus::VERIFIED,
        ]);

        $response = $this->actingAs($user)->get(route('tenant.invoices.show', $invoice));

        $response->assertOk()
            ->assertSeeText('Unduh Kwitansi')
            ->assertSee(route('payments.receipt', $payment), false);
    }

    public function test_detail_does_not_show_receipt_link_before_payment_is_verified(): void
    {
        [$user, $tenant] = $this->tenantIdentity();
        $invoice = Invoice::factory()->for($tenant)->create(['room_id' => $tenant->room_id]);
        Payment::factory()->for($invoice)->create([
            'tenant_id' => $tenant->id,
            'status' => PaymentStatus::PENDING,
        ]);

        $this->actingAs($user)
            ->get(route('tenant.invoices.show', $invoice))
            ->assertOk()
            ->assertDontSeeText('Unduh Kwitansi');
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
