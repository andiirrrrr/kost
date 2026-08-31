<?php

namespace App\Console\Commands;

use App\Services\ReminderService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('whatsapp:send-reminders')]
#[Description('Menjadwalkan pengingat WhatsApp tagihan')]
class SendWhatsAppReminders extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ReminderService $service): int
    {
        $result = $service->dispatchForDate();
        $this->info("{$result['created']} pengingat masuk antrean; {$result['skipped']} dilewati.");

        return self::SUCCESS;
    }
}
