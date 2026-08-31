<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Support\Collection;

class ReportService
{
    /** @return Collection<int, Invoice> */
    public function invoices(int $month, int $year, ?string $status = null): Collection
    {
        return Invoice::query()
            ->with(['tenant:id,name', 'room:id,room_number'])
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest('due_date')
            ->limit(100)
            ->get();
    }

    /** @return Collection<int, Payment> */
    public function payments(int $month, int $year, ?string $status = null): Collection
    {
        return Payment::query()
            ->with(['tenant:id,name', 'invoice:id,invoice_number'])
            ->whereMonth('paid_at', $month)
            ->whereYear('paid_at', $year)
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest('paid_at')
            ->limit(100)
            ->get();
    }

    /** @return Collection<int, Room> */
    public function rooms(?string $status = null): Collection
    {
        return Room::query()
            ->with(['tenants' => fn ($query) => $query->where('status', 'active')->select('id', 'room_id', 'name')])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderBy('room_number')
            ->limit(100)
            ->get();
    }

    /** @return Collection<int, Tenant> */
    public function tenants(?string $status = null): Collection
    {
        return Tenant::query()
            ->with('room:id,room_number')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderBy('name')
            ->limit(100)
            ->get();
    }

    /** @return array{income: int, expenses: int, outstanding: int, occupancy_rate: float} */
    public function summary(int $month, int $year): array
    {
        $income = (int) Payment::query()
            ->where('status', PaymentStatus::VERIFIED)
            ->whereMonth('paid_at', $month)
            ->whereYear('paid_at', $year)
            ->sum('amount');
        $expenses = (int) Expense::query()
            ->whereMonth('expense_date', $month)
            ->whereYear('expense_date', $year)
            ->sum('amount');
        $outstanding = (int) Invoice::query()
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->whereIn('status', [InvoiceStatus::UNPAID, InvoiceStatus::PENDING, InvoiceStatus::OVERDUE])
            ->sum('total_amount');
        $totalRooms = Room::query()->count();
        $occupiedRooms = Room::query()->where('status', 'occupied')->count();

        return [
            'income' => $income,
            'expenses' => $expenses,
            'outstanding' => $outstanding,
            'occupancy_rate' => $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0.0,
        ];
    }
}
