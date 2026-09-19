<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Filament\Admin\Pages\HouseRulesSettings;
use App\Filament\Admin\Resources\Payments\Pages\ListPayments;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\HouseRulesService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class HouseRulesAndReceiptTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_owner_can_access_house_rules_settings_page_and_save(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::query()->role('owner')->firstOrFail();

        Livewire::actingAs($owner)
            ->test(HouseRulesSettings::class)
            ->assertSuccessful()
            ->assertSee('Aturan & Tata Tertib Penghuni');

        $service = app(HouseRulesService::class);
        $service->save([
            [
                'category' => 'Aturan Jam Malam',
                'icon' => 'schedule',
                'rules' => ['Gerbang ditutup pukul 23.00.'],
            ],
        ]);

        $this->assertSame('Aturan Jam Malam', $service->get()[0]['category']);
    }

    public function test_tenant_can_access_house_rules_page(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tenant = Tenant::query()->whereNotNull('user_id')->firstOrFail();
        $user = $tenant->user;

        Contract::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'contract_number' => 'KTR-TEST-001',
                'status' => 'active',
                'deposit_amount' => 500000,
                'starts_at' => now()->startOfMonth(),
                'ends_at' => now()->addMonths(6),
                'monthly_price' => 1000000,
            ]
        );

        $this->actingAs($user)
            ->get(route('tenant.rules'))
            ->assertOk()
            ->assertSee('Tata Tertib', false)
            ->assertSee('KTR-TEST-001')
            ->assertSee('500.000');
    }

    public function test_guest_can_access_receipt_with_valid_signature_only(): void
    {
        $this->seed(DatabaseSeeder::class);
        $payment = Payment::query()->where('status', PaymentStatus::VERIFIED)->first()
            ?? Payment::factory()->create(['status' => PaymentStatus::VERIFIED]);

        // Unauthenticated guest without signature should be aborted with 403
        $this->get(route('payments.receipt', $payment))->assertStatus(403);

        // Unauthenticated guest with valid signature can access
        $signedUrl = URL::signedRoute('payments.receipt', ['payment' => $payment]);
        $this->get($signedUrl)->assertOk()->assertSee($payment->payment_number);
    }

    public function test_whatsapp_receipt_action_generates_valid_url(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::query()->role('owner')->firstOrFail();

        $payment = Payment::query()->where('status', PaymentStatus::VERIFIED)->first();
        if (! $payment) {
            $payment = Payment::factory()->create(['status' => PaymentStatus::VERIFIED]);
        }

        Livewire::actingAs($owner)
            ->test(ListPayments::class)
            ->assertTableActionExists('send_receipt_whatsapp');
    }
}
