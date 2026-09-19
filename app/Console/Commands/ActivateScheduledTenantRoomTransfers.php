<?php

namespace App\Console\Commands;

use App\Services\TenantLifecycleService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tenants:activate-scheduled-room-transfers')]
#[Description('Menjalankan perpindahan kamar penghuni yang tanggal efektifnya sudah tiba')]
class ActivateScheduledTenantRoomTransfers extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(TenantLifecycleService $service): int
    {
        $activated = $service->activateScheduledRoomTransfers();
        $this->info("{$activated} perpindahan kamar terjadwal berhasil dijalankan.");

        return self::SUCCESS;
    }
}
