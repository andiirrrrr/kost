<?php

namespace Tests\Feature;

use App\Enums\ExpenseCategory;
use App\Models\Expense;
use App\Models\MaintenanceRequest;
use App\Services\MaintenanceExpenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MaintenanceExpenseIntegrationTest extends TestCase
{
    use RefreshDatabase;
    use RefreshDatabase;

    public function test_completed_maintenance_cost_is_recorded_once_as_an_expense(): void
    {
        $maintenanceRequest = MaintenanceRequest::factory()->create([
            'status' => 'completed',
            'cost' => 250000,
            'completed_at' => '2026-08-20 10:00:00',
        ]);
        $service = app(MaintenanceExpenseService::class);

        $firstExpense = $service->record($maintenanceRequest);
        $secondExpense = $service->record($maintenanceRequest->fresh());

        $this->assertSame($firstExpense->id, $secondExpense->id);
        $this->assertSame(1, Expense::query()->count());
        $this->assertSame(ExpenseCategory::MAINTENANCE, $firstExpense->category);
        $this->assertSame(250000, $firstExpense->amount);
        $this->assertSame('2026-08-20', $firstExpense->expense_date->toDateString());
    }

    public function test_open_maintenance_cannot_be_recorded_as_an_expense(): void
    {
        $maintenanceRequest = MaintenanceRequest::factory()->create(['status' => 'in_progress', 'cost' => 250000]);

        $this->expectException(ValidationException::class);

        app(MaintenanceExpenseService::class)->record($maintenanceRequest);
    }
}
