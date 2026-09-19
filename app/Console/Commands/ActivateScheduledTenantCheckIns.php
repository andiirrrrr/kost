<?php

namespace App\Console\Commands;

use App\Services\TenantLifecycleService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tenants:activate-scheduled-check-ins')]
#[Description('Mengaktifkan penghuni yang jadwal check-in-nya sudah tiba')]
class ActivateScheduledTenantCheckIns extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(TenantLifecycleService $service): int
    {
        $activated = $service->activateScheduledCheckIns();
        $this->info("{$activated} penghuni terjadwal berhasil diaktifkan.");

        return self::SUCCESS;
    }
}
