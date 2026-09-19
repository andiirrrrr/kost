<?php

namespace Tests\Feature\Filament;

use App\Filament\Admin\Pages\PaymentSettings;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentSettingsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_owner_can_update_tenant_payment_account(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::where('email', 'admin@kost.test')->sole();

        Livewire::actingAs($owner)
            ->test(PaymentSettings::class)
            ->set('data.bank_name', 'Bank Mandiri')
            ->set('data.bank_account_number', '9876543210')
            ->set('data.bank_account_holder', 'Heritage Residential')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Bank Mandiri', Setting::get('bank_name'));
        $this->assertSame('9876543210', Setting::get('bank_account_number'));
        $this->assertSame('Heritage Residential', Setting::get('bank_account_holder'));
    }

    public function test_non_owner_cannot_open_payment_settings(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tenant = User::factory()->create();
        $tenant->assignRole('tenant');

        $this->actingAs($tenant)->get(PaymentSettings::getUrl())->assertForbidden();
    }
}
