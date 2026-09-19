<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /** @return array<string, int|float> */
    public function summary(?CarbonImmutable $date = null): array
    {
        $date ??= CarbonImmutable::now();
        $start = $date->startOfMonth();
        $end = $date->endOfMonth();

        $rooms = Room::query()
            ->toBase()
            ->selectRaw('count(*) as total')
            ->selectRaw("sum(case when status = 'occupied' then 1 else 0 end) as occupied")
            ->selectRaw("sum(case when status = 'available' then 1 else 0 end) as available")
            ->first();

        $invoices = Invoice::query()
            ->where('period_month', $date->month)
            ->where('period_year', $date->year)
            ->toBase()
            ->selectRaw('coalesce(sum(total_amount), 0) as invoice_total')
            ->selectRaw("coalesce(sum(case when status in ('unpaid', 'pending', 'overdue') then total_amount else 0 end), 0) as outstanding_total")
            ->selectRaw("sum(case when status = 'overdue' then 1 else 0 end) as overdue_count")
            ->first();

        $income = (int) Payment::query()
            ->where('status', PaymentStatus::VERIFIED)
            ->whereBetween('paid_at', [$start, $end])
            ->sum('amount');

        $expenses = (int) Expense::query()
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');

        return [
            'total_rooms' => (int) ($rooms->total ?? 0),
            'occupied_rooms' => (int) ($rooms->occupied ?? 0),
            'available_rooms' => (int) ($rooms->available ?? 0),
            'occupancy_rate' => ($rooms->total ?? 0) > 0
                ? round(((int) $rooms->occupied / (int) $rooms->total) * 100, 1)
                : 0.0,
            'active_tenants' => Tenant::query()->where('status', 'active')->count(),
            'invoice_total' => (int) ($invoices->invoice_total ?? 0),
            'income' => $income,
            'outstanding_total' => (int) ($invoices->outstanding_total ?? 0),
            'overdue_count' => (int) ($invoices->overdue_count ?? 0),
            'expenses' => $expenses,
            'estimated_net' => $income - $expenses,
        ];
    }

    /** @return array{labels: list<string>, income: list<int>, expenses: list<int>, estimated_net: list<int>} */
    public function financialTrend(?CarbonImmutable $date = null): array
    {
        $date ??= CarbonImmutable::now();
        $start = $date->startOfMonth()->subMonths(11);
        $end = $date->endOfMonth();

        $incomeByMonth = $this->monthlyTotals(
            Payment::query()
                ->where('status', PaymentStatus::VERIFIED)
                ->whereBetween('paid_at', [$start, $end]),
            'paid_at',
        );
        $expensesByMonth = $this->monthlyTotals(
            Expense::query()->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()]),
            'expense_date',
        );

        $labels = [];
        $income = [];
        $expenses = [];
        $estimatedNet = [];

        for ($month = $start; $month->lessThanOrEqualTo($end); $month = $month->addMonth()) {
            $key = $month->format('Y-m');
            $monthlyIncome = (int) ($incomeByMonth[$key] ?? 0);
            $monthlyExpenses = (int) ($expensesByMonth[$key] ?? 0);

            $labels[] = $month->locale('id')->translatedFormat('M Y');
            $income[] = $monthlyIncome;
            $expenses[] = $monthlyExpenses;
            $estimatedNet[] = $monthlyIncome - $monthlyExpenses;
        }

        return [
            'labels' => $labels,
            'income' => $income,
            'expenses' => $expenses,
            'estimated_net' => $estimatedNet,
        ];
    }

    /** @return array{paid: int, unpaid: int, overdue: int} */
    public function invoiceStatusBreakdown(?CarbonImmutable $date = null): array
    {
        $date ??= CarbonImmutable::now();
        $totals = Invoice::query()
            ->where('period_month', $date->month)
            ->where('period_year', $date->year)
            ->toBase()
            ->selectRaw("sum(case when status = 'paid' then 1 else 0 end) as paid")
            ->selectRaw("sum(case when status in ('unpaid', 'pending') then 1 else 0 end) as unpaid")
            ->selectRaw("sum(case when status = 'overdue' then 1 else 0 end) as overdue")
            ->first();

        return [
            'paid' => (int) ($totals->paid ?? 0),
            'unpaid' => (int) ($totals->unpaid ?? 0),
            'overdue' => (int) ($totals->overdue ?? 0),
        ];
    }

    /** @return array{occupied: int, available: int, reserved: int, maintenance: int} */
    public function roomStatusBreakdown(): array
    {
        $totals = Room::query()
            ->toBase()
            ->selectRaw("sum(case when status = 'occupied' then 1 else 0 end) as occupied")
            ->selectRaw("sum(case when status = 'available' then 1 else 0 end) as available")
            ->selectRaw("sum(case when status = 'reserved' then 1 else 0 end) as reserved")
            ->selectRaw("sum(case when status = 'maintenance' then 1 else 0 end) as maintenance")
            ->first();

        return [
            'occupied' => (int) ($totals->occupied ?? 0),
            'available' => (int) ($totals->available ?? 0),
            'reserved' => (int) ($totals->reserved ?? 0),
            'maintenance' => (int) ($totals->maintenance ?? 0),
        ];
    }

    /** @return Collection<string, int> */
    private function monthlyTotals(Builder $query, string $dateColumn): Collection
    {
        $monthExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', {$dateColumn})"
            : "date_format({$dateColumn}, '%Y-%m')";

        return $query
            ->toBase()
            ->selectRaw("{$monthExpression} as month_key, sum(amount) as total")
            ->groupByRaw($monthExpression)
            ->pluck('total', 'month_key')
            ->map(fn (string|int $total): int => (int) $total);
    }
}
