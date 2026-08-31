<?php

namespace Tests\Feature\Filament;

use App\Filament\Admin\Pages\Reports;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReportsPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_open_reports_page(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@kost.test')->sole();

        $this->actingAs($admin)->get(Reports::getUrl())->assertOk()->assertSee('Laporan Dasar');
    }

    public function test_public_user_cannot_open_reports_page_directly(): void
    {
        $this->seed(DatabaseSeeder::class);
        $publicUser = User::factory()->create();
        $publicUser->assignRole('public');

        $this->actingAs($publicUser)->get(Reports::getUrl())->assertForbidden();
    }
}
