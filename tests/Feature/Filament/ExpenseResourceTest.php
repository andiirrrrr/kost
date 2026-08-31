<?php

namespace Tests\Feature\Filament;

use App\Filament\Admin\Resources\Expenses\ExpenseResource;
use App\Models\Expense;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ExpenseResourceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_open_expense_pages(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@kost.test')->sole();
        $expense = Expense::firstOrFail();

        $this->actingAs($admin)->get(ExpenseResource::getUrl('index'))->assertOk();
        $this->actingAs($admin)->get(ExpenseResource::getUrl('create'))->assertOk();
        $this->actingAs($admin)->get(ExpenseResource::getUrl('edit', ['record' => $expense]))->assertOk();
    }

    public function test_public_user_cannot_open_expense_pages_directly(): void
    {
        $this->seed(DatabaseSeeder::class);
        $publicUser = User::factory()->create();
        $publicUser->assignRole('public');

        $this->actingAs($publicUser)->get(ExpenseResource::getUrl('index'))->assertForbidden();
        $this->actingAs($publicUser)->get(ExpenseResource::getUrl('create'))->assertForbidden();
    }
}
