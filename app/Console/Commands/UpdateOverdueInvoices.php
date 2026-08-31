<?php

namespace App\Console\Commands;

use App\Services\InvoiceService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('invoices:update-overdue')]
#[Description('Menandai tagihan yang melewati jatuh tempo')]
class UpdateOverdueInvoices extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(InvoiceService $service): int
    {
        $updated = $service->updateOverdueStatus();
        $this->info("{$updated} tagihan ditandai sebagai terlambat.");

        return self::SUCCESS;
    }
}
