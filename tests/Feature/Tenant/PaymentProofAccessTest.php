<?php

namespace Tests\Feature\Tenant;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaymentProofAccessTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_tenant_can_preview_and_download_own_proof_but_receives_404_for_another_tenant(): void
    {
        Storage::fake('local');
        [$user, $tenant] = $this->tenantIdentity();
        $invoice = Invoice::factory()->for($tenant)->create(['room_id' => $tenant->room_id]);
        $ownPayment = Payment::factory()->for($invoice)->create(['tenant_id' => $tenant->id, 'proof' => 'payments/proofs/own.pdf']);
        $otherPayment = Payment::factory()->create(['proof' => 'payments/proofs/other.pdf']);
        Storage::disk('local')->put($ownPayment->proof, 'private proof');
        Storage::disk('local')->put($otherPayment->proof, 'other private proof');

        $previewResponse = $this->actingAs($user)->get(route('tenant.payments.proof', $ownPayment));
        $previewResponse->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('inline;', (string) $previewResponse->headers->get('Content-Disposition'));

        $downloadResponse = $this->actingAs($user)->get(route('tenant.payments.proof', ['payment' => $ownPayment, 'download' => 1]));
        $downloadResponse->assertOk();
        $this->assertStringStartsWith('attachment;', (string) $downloadResponse->headers->get('Content-Disposition'));

        $this->actingAs($user)->get(route('tenant.payments.proof', $otherPayment))->assertNotFound();

        $this->actingAs($user)->get(route('tenant.payments.index'))
            ->assertSee('Lihat Bukti')
            ->assertSee('Buka di Tab Baru')
            ->assertSee('Unduh');
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
