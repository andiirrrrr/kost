<?php

namespace Tests\Feature\Policies;

use App\Models\Expense;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ExpensePolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_manage_expenses_but_cannot_force_delete_history(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@kost.test')->sole();
        $expense = Expense::factory()->for($admin, 'creator')->create();

        $this->assertTrue($admin->can('viewAny', Expense::class));
        $this->assertTrue($admin->can('create', Expense::class));
        $this->assertTrue($admin->can('update', $expense));
        $this->assertTrue($admin->can('delete', $expense));
        $this->assertTrue($admin->can('restore', $expense));
        $this->assertFalse($admin->can('forceDelete', $expense));
    }

    public function test_public_user_cannot_access_expenses(): void
    {
        $this->seed(DatabaseSeeder::class);
        $publicUser = User::factory()->create();
        $publicUser->assignRole('public');
        $expense = Expense::factory()->create();

        $this->assertFalse($publicUser->can('viewAny', Expense::class));
        $this->assertFalse($publicUser->can('view', $expense));
        $this->assertFalse($publicUser->can('create', Expense::class));
        $this->assertFalse($publicUser->can('update', $expense));
        $this->assertFalse($publicUser->can('delete', $expense));
    }
}
