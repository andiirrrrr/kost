<?php

namespace Tests\Feature\Models;

use App\Enums\ExpenseCategory;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_casts_financial_fields_and_tracks_creator(): void
    {
        $admin = User::factory()->create();
        $expense = Expense::factory()->for($admin, 'creator')->create([
            'category' => ExpenseCategory::MAINTENANCE,
            'amount' => 750000,
            'expense_date' => '2026-08-30',
        ]);

        $this->assertSame(ExpenseCategory::MAINTENANCE, $expense->category);
        $this->assertSame(750000, $expense->amount);
        $this->assertSame('2026-08-30', $expense->expense_date->toDateString());
        $this->assertTrue($expense->creator->is($admin));
    }

    public function test_delete_preserves_expense_as_soft_deleted_history(): void
    {
        $expense = Expense::factory()->create();

        $expense->delete();

        $this->assertSoftDeleted($expense);
        $this->assertNotNull(Expense::withTrashed()->find($expense->id));
    }
}
