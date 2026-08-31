<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Notifications\TenantActivityNotification;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        $tenants = Tenant::with('room')->where('status', 'active')->get();

        if ($tenants->isEmpty()) {
            $result['details'][] = 'Tidak ada penghuni aktif.';

            return $result;
        }

        DB::beginTransaction();

        try {
            foreach ($tenants as $tenant) {
                // Cek duplikasi (termasuk soft-deleted)
                $existing = Invoice::withTrashed()
                    ->where('tenant_id', $tenant->id)
                    ->where('period_month', $month)
                    ->where('period_year', $year)
                    ->first();

                if ($existing) {
                    if ($existing->trashed()) {
                        // Jika sudah pernah dihapus, hapus permanen agar bisa digenerate ulang
                        $existing->forceDelete();
                    } else {
                        $result['skipped']++;
                        $result['details'][] = "{$tenant->name}: Invoice sudah ada, dilewati.";

                        continue;
                    }
                }

                $baseAmount = $tenant->monthly_price;
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
                $dueDay = $tenant->due_day ?? 5;
                $dueDate = self::calculateDueDate($month, $year, $dueDay)->toDateString();

                $invoice = Invoice::create([
                    'tenant_id' => $tenant->id,
                    'room_id' => $tenant->room_id,
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
                new TenantActivityNotification('invoice_created', 'Tagihan baru tersedia', "Tagihan {$invoice->invoice_number} telah tersedia.", route('tenant.invoices.show', $invoice)),
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
                new TenantActivityNotification('invoice_overdue', 'Tagihan melewati jatuh tempo', "Tagihan {$invoice->invoice_number} telah terlambat.", route('tenant.invoices.show', $invoice)),
            );
        }

        return $invoices->count();
    }

    public static function calculateDueDate(int $month, int $year, int $dueDay): CarbonImmutable
    {
        $monthStart = CarbonImmutable::create($year, $month, 1)->startOfDay();

        return $monthStart->day(min(max($dueDay, 1), $monthStart->daysInMonth));
    }
}
