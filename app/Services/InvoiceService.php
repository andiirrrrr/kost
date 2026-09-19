<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\TenantRoomHistory;
use App\Notifications\TenantActivityNotification;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function __construct(private TenantNotificationService $notifications) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function calculateTotal(array $data): int
    {
        return max(
            0,
            (int) ($data['base_amount'] ?? 0)
                + (int) ($data['electricity_amount'] ?? 0)
                + (int) ($data['water_amount'] ?? 0)
                + (int) ($data['other_amount'] ?? 0)
                - (int) ($data['discount_amount'] ?? 0),
        );
    }

    /**
     * Generate invoices for all active tenants for a given month/year
     */
    public function generateMonthlyInvoices(int $month, int $year, ?int $createdBy = null): array
    {
        $result = [
            'created' => 0,
            'skipped' => 0,
            'failed' => 0,
            'details' => [],
        ];
        $createdInvoices = [];

        $billingPeriodEnd = CarbonImmutable::create($year, $month, 1)->endOfMonth();
        $tenants = Tenant::with('room.roomCategory')
            ->where('status', 'active')
            ->whereDate('move_in_date', '<=', $billingPeriodEnd)
            ->where(function ($query) use ($billingPeriodEnd): void {
                $query->whereNull('move_out_date')->orWhereDate('move_out_date', '>=', $billingPeriodEnd->startOfMonth());
            })
            ->get();

        if ($tenants->isEmpty()) {
            $result['details'][] = 'Tidak ada penghuni aktif.';

            return $result;
        }

        DB::beginTransaction();

        try {
            foreach ($tenants as $tenant) {
                $history = $this->historyForPeriod($tenant, $month, $year);

                if (! $history) {
                    $result['skipped']++;
                    $result['details'][] = "{$tenant->name}: Periode sewa tidak ditemukan, dilewati.";

                    continue;
                }

                // Cek duplikasi (termasuk soft-deleted)
                $existing = Invoice::withTrashed()
                    ->where('tenant_id', $tenant->id)
                    ->where('period_month', $month)
                    ->where('period_year', $year)
                    ->first();

                if ($existing) {
                    if ($existing->trashed() && $existing->payments()->doesntExist()) {
                        // Jika sudah pernah dihapus, hapus permanen agar bisa digenerate ulang
                        $existing->forceDelete();
                    } else {
                        $result['skipped']++;
                        $result['details'][] = "{$tenant->name}: Invoice sudah ada, dilewati.";

                        continue;
                    }
                }

                $baseAmount = $history->ends_at === null ? self::resolveBaseAmount($tenant) : $history->monthly_price;
                $electricity = 0;
                $water = 0;
                $other = 0;
                $discount = 0;
                $totalAmount = self::calculateTotal([
                    'base_amount' => $baseAmount,
                    'electricity_amount' => $electricity,
                    'water_amount' => $water,
                    'other_amount' => $other,
                    'discount_amount' => $discount,
                ]);

                $invoiceNumber = Invoice::generateInvoiceNumber($month, $year);
                $dueDate = self::calculateDueDate($month, $year, $history->due_day)->toDateString();

                $invoice = Invoice::create([
                    'tenant_id' => $tenant->id,
                    'tenant_room_history_id' => $history->id,
                    'room_id' => $history->room_id,
                    'invoice_number' => $invoiceNumber,
                    'period_month' => $month,
                    'period_year' => $year,
                    'base_amount' => $baseAmount,
                    'electricity_amount' => $electricity,
                    'water_amount' => $water,
                    'other_amount' => $other,
                    'discount_amount' => $discount,
                    'total_amount' => $totalAmount,
                    'due_date' => $dueDate,
                    'status' => InvoiceStatus::UNPAID,
                    'created_by' => $createdBy,
                ]);
                $createdInvoices[] = $invoice;

                $result['created']++;
                $result['details'][] = "{$tenant->name}: Invoice {$invoiceNumber} berhasil dibuat.";
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Generate invoice failed: '.$e->getMessage());
            $result['failed'] = count($tenants);
            $result['details'][] = 'Error: '.$e->getMessage();
        }

        foreach ($createdInvoices as $invoice) {
            $this->notifications->notifyTenant(
                $invoice->tenant,
                new TenantActivityNotification('invoice_created', 'Tagihan baru tersedia', "Tagihan {$invoice->invoice_number} telah tersedia.", route('tenant.invoices.show', $invoice), $invoice->id),
            );
        }

        return $result;
    }

    /**
     * Update overdue invoices (reusable for scheduler)
     */
    public function updateOverdueStatus(): int
    {
        $today = Carbon::today()->toDateString();

        $invoices = Invoice::query()->with('tenant.user')->where('status', InvoiceStatus::UNPAID)
            ->where('due_date', '<', $today)
            ->get();

        Invoice::query()->whereKey($invoices->modelKeys())->update(['status' => InvoiceStatus::OVERDUE]);

        foreach ($invoices as $invoice) {
            $this->notifications->notifyTenant(
                $invoice->tenant,
                new TenantActivityNotification('invoice_overdue', 'Tagihan melewati jatuh tempo', "Tagihan {$invoice->invoice_number} telah terlambat.", route('tenant.invoices.show', $invoice), $invoice->id),
            );
        }

        return $invoices->count();
    }

    /** @return array<string, array{from: int|string, to: int|string}> */
    public function synchronizeInvoice(Invoice $invoice): array
    {
        return DB::transaction(function () use ($invoice): array {
            $invoice = Invoice::query()->with(['tenant', 'room.roomCategory', 'tenantRoomHistory'])->lockForUpdate()->findOrFail($invoice->id);

            if (! in_array($invoice->status, [InvoiceStatus::UNPAID, InvoiceStatus::OVERDUE], true)) {
                throw ValidationException::withMessages(['invoice' => 'Hanya tagihan belum dibayar atau terlambat yang dapat disinkronkan.']);
            }

            $values = $this->synchronizationValues($invoice);
            $changes = collect([
                'base_amount' => ['from' => $invoice->base_amount, 'to' => $values['base_amount']],
                'due_date' => ['from' => $invoice->due_date->toDateString(), 'to' => $values['due_date']],
                'total_amount' => ['from' => $invoice->total_amount, 'to' => $values['total_amount']],
            ])->filter(fn (array $change): bool => $change['from'] !== $change['to'])->all();

            if ($changes === []) {
                return [];
            }

            $invoice->update($values);
            ActivityLog::record('invoice.synchronized', $invoice, ['changes' => $changes]);

            return $changes;
        });
    }

    /** @return array{synchronized: int, unchanged: int} */
    public function synchronizeAllChangedInvoices(): array
    {
        $result = ['synchronized' => 0, 'unchanged' => 0];

        Invoice::query()
            ->whereIn('status', [InvoiceStatus::UNPAID, InvoiceStatus::OVERDUE])
            ->orderBy('id')
            ->chunkById(100, function ($invoices) use (&$result): void {
                foreach ($invoices as $invoice) {
                    if ($this->synchronizeInvoice($invoice) === []) {
                        $result['unchanged']++;
                    } else {
                        $result['synchronized']++;
                    }
                }
            });

        return $result;
    }

    public static function calculateDueDate(int $month, int $year, int $dueDay): CarbonImmutable
    {
        $monthStart = CarbonImmutable::create($year, $month, 1)->startOfDay();

        return $monthStart->day(min(max($dueDay, 1), $monthStart->daysInMonth));
    }

    public static function calculateTenantDueDate(Tenant $tenant, int $month, int $year): CarbonImmutable
    {
        return self::calculateDueDate($month, $year, $tenant->move_in_date->day);
    }

    /** @return array{history: TenantRoomHistory, base_amount: int, due_date: string} */
    public function billingSnapshot(Tenant $tenant, int $month, int $year): array
    {
        $history = $this->historyForPeriod($tenant, $month, $year);

        if (! $history) {
            throw ValidationException::withMessages(['period_month' => 'Penghuni tidak memiliki periode sewa aktif pada bulan tersebut.']);
        }

        return [
            'history' => $history,
            'base_amount' => $history->ends_at === null ? self::resolveBaseAmount($tenant) : $history->monthly_price,
            'due_date' => self::calculateDueDate($month, $year, $history->due_day)->toDateString(),
        ];
    }

    /** @return array{base_amount: int, due_date: string, total_amount: int} */
    private function synchronizationValues(Invoice $invoice): array
    {
        $history = $invoice->tenantRoomHistory ?? $this->historyForPeriod($invoice->tenant, $invoice->period_month, $invoice->period_year, $invoice->room_id);

        if (! $history) {
            throw ValidationException::withMessages(['invoice' => 'Periode sewa untuk tagihan ini tidak ditemukan.']);
        }

        $isCurrentPeriod = $history->ends_at === null && $invoice->tenant->room_id === $history->room_id;
        $baseAmount = $isCurrentPeriod
            ? (int) ($invoice->room?->roomCategory?->base_monthly_price ?? $invoice->room?->monthly_price ?? $history->monthly_price)
            : $invoice->base_amount;

        if ($invoice->tenant_room_history_id === null) {
            $invoice->updateQuietly(['tenant_room_history_id' => $history->id]);
        }

        return [
            'base_amount' => $baseAmount,
            'due_date' => self::calculateDueDate($invoice->period_month, $invoice->period_year, $history->due_day)->toDateString(),
            'total_amount' => self::calculateTotal([
                'base_amount' => $baseAmount,
                'electricity_amount' => $invoice->electricity_amount,
                'water_amount' => $invoice->water_amount,
                'other_amount' => $invoice->other_amount,
                'discount_amount' => $invoice->discount_amount,
            ]),
        ];
    }

    private function historyForPeriod(Tenant $tenant, int $month, int $year, ?int $roomId = null): ?TenantRoomHistory
    {
        $periodStart = CarbonImmutable::create($year, $month, 1)->startOfMonth();
        $periodEnd = $periodStart->endOfMonth();

        return TenantRoomHistory::query()
            ->whereBelongsTo($tenant)
            ->when($roomId, fn ($query) => $query->where('room_id', $roomId))
            ->whereDate('starts_at', '<=', $periodEnd)
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', $periodStart))
            ->latest('starts_at')
            ->first();
    }

    public static function resolveBaseAmount(Tenant $tenant): int
    {
        $tenant->loadMissing('room.roomCategory');

        return (int) ($tenant->room?->roomCategory?->base_monthly_price ?? $tenant->monthly_price);
    }
}
