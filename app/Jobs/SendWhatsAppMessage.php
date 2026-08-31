<?php

namespace App\Jobs;

use App\Enums\BroadcastStatus;
use App\Enums\WhatsAppStatus;
use App\Models\Broadcast;
use App\Models\WhatsAppLog;
use App\Models\WhatsAppTemplate;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

class SendWhatsAppMessage implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public int $uniqueFor = 3600;

    /** @var list<int> */
    public array $backoff = [5, 30, 120];

    public function __construct(public int $whatsAppLogId) {}

    public function uniqueId(): string
    {
        return (string) $this->whatsAppLogId;
    }

    public function handle(WhatsAppService $service): void
    {
        $log = WhatsAppLog::query()->findOrFail($this->whatsAppLogId);

        if (in_array($log->status, [WhatsAppStatus::SENT, WhatsAppStatus::DELIVERED, WhatsAppStatus::READ], true)) {
            return;
        }

        $template = WhatsAppTemplate::query()->where('template_name', $log->template_name)->firstOrFail();
        $log->update([
            'status' => WhatsAppStatus::PROCESSING,
            'attempts' => $log->attempts + 1,
            'error_message' => null,
        ]);

        try {
            $messageId = $service->sendTemplate(
                $log->phone,
                $template->template_name,
                $template->language,
                $log->parameters ?? [],
            );

            $log->update([
                'message_id' => $messageId,
                'status' => WhatsAppStatus::SENT,
                'sent_at' => now(),
                'error_message' => null,
            ]);
            $this->refreshBroadcast($log->broadcast_id);
        } catch (Throwable $exception) {
            $log->update([
                'status' => WhatsAppStatus::QUEUED,
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $log = WhatsAppLog::query()->find($this->whatsAppLogId);
        if (! $log) {
            return;
        }

        $log->update([
            'status' => WhatsAppStatus::FAILED,
            'error_message' => 'Pesan gagal dikirim setelah beberapa percobaan.',
            'failed_at' => now(),
        ]);
        $this->refreshBroadcast($log->broadcast_id);
    }

    private function refreshBroadcast(?int $broadcastId): void
    {
        if (! $broadcastId) {
            return;
        }

        DB::transaction(function () use ($broadcastId): void {
            $broadcast = Broadcast::query()->lockForUpdate()->find($broadcastId);
            if (! $broadcast) {
                return;
            }

            $sent = $broadcast->logs()->whereIn('status', [
                WhatsAppStatus::SENT,
                WhatsAppStatus::DELIVERED,
                WhatsAppStatus::READ,
            ])->count();
            $failed = $broadcast->logs()->where('status', WhatsAppStatus::FAILED)->count();
            $isFinished = ($sent + $failed) >= $broadcast->total_recipient;

            $broadcast->update([
                'total_sent' => $sent,
                'total_failed' => $failed,
                'status' => $isFinished
                    ? ($sent > 0 ? BroadcastStatus::COMPLETED : BroadcastStatus::FAILED)
                    : BroadcastStatus::PROCESSING,
                'finished_at' => $isFinished ? now() : null,
            ]);
        });
    }
}
