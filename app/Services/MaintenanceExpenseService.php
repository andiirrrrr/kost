<?php

namespace App\Services;

use App\Enums\ExpenseCategory;
use App\Models\ActivityLog;
use App\Models\Expense;
use App\Models\MaintenanceRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MaintenanceExpenseService
{
    public function record(MaintenanceRequest $maintenanceRequest, ?int $createdBy = null): Expense
    {
        return DB::transaction(function () use ($maintenanceRequest, $createdBy): Expense {
            $maintenanceRequest = MaintenanceRequest::query()->lockForUpdate()->findOrFail($maintenanceRequest->id);

            if ($maintenanceRequest->status !== 'completed' || $maintenanceRequest->cost <= 0) {
                throw ValidationException::withMessages(['cost' => 'Perbaikan harus selesai dan memiliki biaya sebelum dicatat sebagai pengeluaran.']);
            }

            if ($maintenanceRequest->expense_id) {
                return Expense::withTrashed()->findOrFail($maintenanceRequest->expense_id);
            }

            $expense = Expense::query()->create([
                'category' => ExpenseCategory::MAINTENANCE,
                'description' => "Perbaikan {$maintenanceRequest->title} (kamar {$maintenanceRequest->room->room_number})",
                'amount' => $maintenanceRequest->cost,
                'expense_date' => $maintenanceRequest->completed_at?->toDateString() ?? now()->toDateString(),
                'created_by' => $createdBy,
            ]);

            $maintenanceRequest->updateQuietly(['expense_id' => $expense->id]);
            ActivityLog::record('maintenance.expense_recorded', $maintenanceRequest, ['expense_id' => $expense->id, 'amount' => $expense->amount]);

            return $expense;
        });
    }
}
