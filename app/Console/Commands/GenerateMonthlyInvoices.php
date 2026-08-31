<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\InvoiceService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('invoices:generate-monthly {--month= : Bulan angka 1-12} {--year= : Tahun tagihan}')]
#[Description('Membuat tagihan bulanan untuk seluruh penghuni aktif')]
class GenerateMonthlyInvoices extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(InvoiceService $service): int
    {
        if (! filter_var(Setting::get('automatic_invoice_enabled', true), FILTER_VALIDATE_BOOLEAN)) {
            $this->info('Pembuatan tagihan otomatis sedang dinonaktifkan.');

            return self::SUCCESS;
        }

        $month = (int) ($this->option('month') ?: now()->month);
        $year = (int) ($this->option('year') ?: now()->year);

        if ($month < 1 || $month > 12 || $year < 2000) {
            $this->error('Bulan atau tahun tidak valid.');

            return self::INVALID;
        }

        $result = $service->generateMonthlyInvoices($month, $year);
        $this->info("Berhasil: {$result['created']}; dilewati: {$result['skipped']}; gagal: {$result['failed']}.");

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
