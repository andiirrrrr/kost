<?php

namespace Tests\Feature\Filament\Resources\Invoices\Pages;

use App\Filament\Admin\Resources\Invoices\Pages\GenerateInvoices;
use App\Models\Invoice;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GenerateInvoicesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_owner_generates_monthly_invoices_through_filament_action(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::where('email', config('demo.owner_email'))->sole();

        Livewire::actingAs($owner)
            ->test(GenerateInvoices::class)
            ->set('month', 10)
            ->set('year', 2026)
            ->call('generate')
            ->assertHasNoErrors();

        $this->assertTrue(Invoice::query()->where('period_month', 10)->where('period_year', 2026)->exists());
    }
}
