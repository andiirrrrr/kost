<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class AdminExportService
{
    /** @var array<string, string> */
    public const array TYPES = [
        'penghuni' => 'Penghuni',
        'tagihan' => 'Tagihan',
        'pembayaran' => 'Pembayaran',
        'pengeluaran' => 'Pengeluaran',
    ];

    /**
     * @param  array{month?: int|null, year?: int|null, status?: string|null, category?: string|null}  $filters
     * @return array{label: string, headers: list<string>, rows: array<int, array<int, mixed>>, total: int}
     */
    public function dataset(string $type, array $filters = [], ?int $limit = null): array
    {
        [$headers, $query, $mapRecord] = $this->definition($type, $filters);
        $total = $limit === null ? null : (clone $query)->count();

        if ($limit !== null) {
            $query->limit($limit);
        }

        $rows = $query->get()->map($mapRecord)->values()->all();

        return [
            'label' => self::TYPES[$type],
            'headers' => $headers,
            'rows' => $rows,
            'total' => $total ?? count($rows),
        ];
    }

    /**
     * @param  array{month?: int|null, year?: int|null, status?: string|null, category?: string|null}  $filters
     * @return array{list<string>, Builder, Closure(object): array<int, mixed>}
     */
    private function definition(string $type, array $filters): array
    {
        $period = function (Builder $query, string $column) use ($filters): Builder {
            return $query
                ->when($filters['month'] ?? null, fn (Builder $query, int $month): Builder => $query->whereMonth($column, $month))
                ->when($filters['year'] ?? null, fn (Builder $query, int $year): Builder => $query->whereYear($column, $year));
        };

        return match ($type) {
            'penghuni' => [
                ['Nama', 'Kamar', 'Telepon', 'Email', 'Tanggal Masuk', 'Tanggal Keluar', 'Harga', 'Status'],
                Tenant::query()
                    ->with('room')
                    ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
                    ->latest('id'),
                fn (Tenant $tenant): array => [$tenant->name, $tenant->room?->room_number, $tenant->phone, $tenant->email, $tenant->move_in_date?->format('Y-m-d'), $tenant->move_out_date?->format('Y-m-d'), $tenant->monthly_price, $tenant->status->value],
            ],
            'tagihan' => [
                ['Nomor', 'Penghuni', 'Periode', 'Jatuh Tempo', 'Total', 'Status'],
                Invoice::query()
                    ->with('tenant')
                    ->when($filters['month'] ?? null, fn (Builder $query, int $month): Builder => $query->where('period_month', $month))
                    ->when($filters['year'] ?? null, fn (Builder $query, int $year): Builder => $query->where('period_year', $year))
                    ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
                    ->latest('id'),
                fn (Invoice $invoice): array => [$invoice->invoice_number, $invoice->tenant?->name, sprintf('%02d-%04d', $invoice->period_month, $invoice->period_year), $invoice->due_date?->format('Y-m-d'), $invoice->total_amount, $invoice->status->value],
            ],
            'pembayaran' => [
                ['Nomor', 'Penghuni', 'Tanggal', 'Jumlah', 'Metode', 'Status'],
                $period(Payment::query()->with(['tenant', 'method']), 'paid_at')
                    ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
                    ->latest('id'),
                fn (Payment $payment): array => [$payment->payment_number, $payment->tenant?->name, $payment->paid_at?->format('Y-m-d H:i'), $payment->amount, $payment->method?->name ?? $payment->payment_method, $payment->status->value],
            ],
            'pengeluaran' => [
                ['Tanggal', 'Kategori', 'Deskripsi', 'Jumlah'],
                $period(Expense::query(), 'expense_date')
                    ->when($filters['category'] ?? null, fn (Builder $query, string $category): Builder => $query->where('category', $category))
                    ->latest('expense_date')
                    ->latest('id'),
                fn (Expense $expense): array => [$expense->expense_date?->format('Y-m-d'), $expense->category->value, $expense->description, $expense->amount],
            ],
            default => throw new \InvalidArgumentException("Jenis ekspor {$type} tidak tersedia."),
        };
    }
}
