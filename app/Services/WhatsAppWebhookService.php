<?php

namespace App\Services;

use App\Enums\BroadcastStatus;
use App\Enums\WhatsAppStatus;
use App\Models\Broadcast;
use App\Models\WhatsAppLog;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WhatsAppWebhookService
{
    /** @param array<string, mixed> $payload */
    public function handle(array $payload): void
    {
        foreach (Arr::get($payload, 'entry', []) as $entry) {
            foreach (Arr::get($entry, 'changes', []) as $change) {
                foreach (Arr::get($change, 'value.statuses', []) as $statusPayload) {
                    if (is_array($statusPayload)) {
                        $this->applyStatus($statusPayload);
                    }
                }
            }
        }
    }

    /** @param array<string, mixed> $statusPayload */
    private function applyStatus(array $statusPayload): void
    {
        $messageId = Arr::get($statusPayload, 'id');
        $status = WhatsAppStatus::tryFrom((string) Arr::get($statusPayload, 'status'));

        if (! is_string($messageId) || $messageId === '' || ! $status) {
            return;
        }

        $broadcastId = DB::transaction(function () use ($messageId, $status, $statusPayload): ?int {
            $log = WhatsAppLog::query()
                ->where('message_id', $messageId)
                ->lockForUpdate()
                ->first();

            if (! $log || ! $this->canTransition($log->status, $status)) {
                return null;
            }

            $eventTime = $this->eventTime($statusPayload);
            $attributes = ['status' => $status];

            if ($status === WhatsAppStatus::SENT) {
                $attributes['sent_at'] = $log->sent_at ?? $eventTime;
            } elseif ($status === WhatsAppStatus::DELIVERED) {
                $attributes['delivered_at'] = $log->delivered_at ?? $eventTime;
            } elseif ($status === WhatsAppStatus::READ) {
                $attributes['delivered_at'] = $log->delivered_at ?? $eventTime;
                $attributes['read_at'] = $log->read_at ?? $eventTime;
            } elseif ($status === WhatsAppStatus::FAILED) {
                $attributes['failed_at'] = $log->failed_at ?? $eventTime;
                $attributes['error_message'] = $this->failureMessage($statusPayload);
            }

            $log->update($attributes);

            return $log->broadcast_id;
        });

        if ($broadcastId) {
            $this->refreshBroadcast($broadcastId);
        }
    }

    private function canTransition(WhatsAppStatus $current, WhatsAppStatus $incoming): bool
    {
        if ($current === WhatsAppStatus::FAILED) {
            return $incoming === WhatsAppStatus::FAILED;
        }

        if ($incoming === WhatsAppStatus::FAILED) {
            return ! in_array($current, [WhatsAppStatus::DELIVERED, WhatsAppStatus::READ], true);
        }

        $rank = [
            WhatsAppStatus::QUEUED->value => 0,
            WhatsAppStatus::PROCESSING->value => 1,
            WhatsAppStatus::SENT->value => 2,
            WhatsAppStatus::DELIVERED->value => 3,
            WhatsAppStatus::READ->value => 4,
        ];

        return ($rank[$incoming->value] ?? -1) >= ($rank[$current->value] ?? -1);
    }

    /** @param array<string, mixed> $statusPayload */
    private function eventTime(array $statusPayload): CarbonImmutable
    {
        $timestamp = filter_var(Arr::get($statusPayload, 'timestamp'), FILTER_VALIDATE_INT);

        return $timestamp
            ? CarbonImmutable::createFromTimestampUTC($timestamp)
            : CarbonImmutable::now();
    }

    /** @param array<string, mixed> $statusPayload */
    private function failureMessage(array $statusPayload): string
    {
        $error = Arr::get($statusPayload, 'errors.0', []);
        $message = Arr::get($error, 'error_data.details')
            ?? Arr::get($error, 'message')
            ?? Arr::get($error, 'title')
            ?? 'Pesan ditolak oleh WhatsApp.';

        return Str::limit((string) $message, 1000);
    }

    private function refreshBroadcast(int $broadcastId): void
    {
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
                'finished_at' => $isFinished ? ($broadcast->finished_at ?? now()) : null,
            ]);
        });
    }
}
