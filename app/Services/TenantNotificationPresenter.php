<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

class TenantNotificationPresenter
{
    /** @param Collection<int, DatabaseNotification> $notifications */
    public function forDisplay(Collection $notifications): Collection
    {
        $invoiceIds = $notifications
            ->map(fn (DatabaseNotification $notification): ?int => $this->invoiceId($notification))
            ->filter()
            ->unique()
            ->values();

        $invoices = Invoice::withTrashed()
            ->whereKey($invoiceIds)
            ->get(['id', 'deleted_at'])
            ->keyBy('id');

        return $notifications->map(function (DatabaseNotification $notification) use ($invoices): DatabaseNotification {
            $invoiceId = $this->invoiceId($notification);

            if (! $invoiceId) {
                return $notification;
            }

            $invoice = $invoices->get($invoiceId);
            $data = $notification->data;
            $data['invoice_id'] = $invoiceId;
            $data['resource_unavailable'] = ! $invoice || $invoice->trashed();

            if ($data['resource_unavailable']) {
                $data['url'] = null;
            }

            $notification->setAttribute('data', $data);

            return $notification;
        });
    }

    private function invoiceId(DatabaseNotification $notification): ?int
    {
        $invoiceId = $notification->data['invoice_id'] ?? null;

        if (is_numeric($invoiceId)) {
            return (int) $invoiceId;
        }

        $path = parse_url((string) ($notification->data['url'] ?? ''), PHP_URL_PATH);

        return is_string($path) && preg_match('#/tagihan/(\d+)(?:/bayar)?$#', $path, $matches)
            ? (int) $matches[1]
            : null;
    }
}
