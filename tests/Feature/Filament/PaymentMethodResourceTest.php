<?php

namespace Tests\Feature\Filament;

use App\Enums\PaymentMethodCategory;
use App\Filament\Admin\Resources\PaymentMethods\Pages\CreatePaymentMethod;
use App\Filament\Admin\Resources\PaymentMethods\PaymentMethodResource;
use App\Models\PaymentMethod;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentMethodResourceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_owner_can_create_payment_method_from_admin(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::where('email', config('demo.owner_email'))->sole();

        Livewire::actingAs($owner)
            ->test(CreatePaymentMethod::class)
            ->assertDontSee('Ikon Material')
            ->assertDontSee('Kode Unik')
            ->assertDontSee('Urutan')
            ->fillForm([
                'name' => 'GoPay',
                'category' => PaymentMethodCategory::E_WALLET->value,
                'account_number' => '081234567890',
                'account_holder' => 'Heritage Residential',
                'instructions' => 'Transfer sesuai nominal tagihan.',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $method = PaymentMethod::query()->where('code', 'gopay')->sole();
        $this->assertSame('GoPay', $method->name);
        $this->assertSame('gopay', $method->code);
        $this->assertGreaterThan(0, $method->sort_order);
        $this->assertSame(PaymentMethodCategory::E_WALLET, $method->category);
        $this->assertTrue($method->is_active);
    }

    public function test_tenant_cannot_open_payment_method_admin(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tenant = User::factory()->create();
        $tenant->assignRole('tenant');

        $this->actingAs($tenant)->get(PaymentMethodResource::getUrl('index'))->assertForbidden();
    }
}
