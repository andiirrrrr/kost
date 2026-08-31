<?php

namespace App\Services;

use App\Enums\BroadcastAudience;
use App\Enums\BroadcastStatus;
use App\Enums\InvoiceStatus;
use App\Enums\WhatsAppStatus;
use App\Jobs\SendWhatsAppMessage;
use App\Models\Broadcast;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\WhatsAppLog;
use App\Models\WhatsAppTemplate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BroadcastService
{
    /**
     * @param  list<int>  $manualTenantIds
     * @return Collection<int, array{tenant: Tenant, invoice: ?Invoice, phone: string, values: array<string, string>}>
     */
    public function recipients(BroadcastAudience $audience, array $manualTenantIds = []): Collection
    {
        $records = match ($audience) {
            BroadcastAudience::ALL => Tenant::query()
                ->with('room:id,room_number')
                ->where('status', 'active')
                ->get()
                ->map(fn (Tenant $tenant): array => ['tenant' => $tenant, 'invoice' => null]),
            BroadcastAudience::MANUAL => Tenant::query()
                ->with('room:id,room_number')
                ->where('status', 'active')
                ->whereIn('id', $manualTenantIds)
                ->get()
                ->map(fn (Tenant $tenant): array => ['tenant' => $tenant, 'invoice' => null]),
            BroadcastAudience::UNPAID => $this->invoiceRecipients([
                InvoiceStatus::UNPAID,
                InvoiceStatus::PENDING,
                InvoiceStatus::OVERDUE,
            ]),
            BroadcastAudience::OVERDUE => $this->invoiceRecipients([InvoiceStatus::OVERDUE]),
        };

        return $records
            ->map(function (array $record): array {
                $phone = WhatsAppService::normalizePhone($record['tenant']->phone);

                return [
                    ...$record,
                    'phone' => $phone,
                    'values' => $this->templateValues($record['tenant'], $record['invoice']),
                ];
            })
            ->filter(fn (array $record): bool => WhatsAppService::isValidPhone($record['phone']))
            ->unique('phone')
            ->values();
    }

    /**
     * @param  list<int>  $manualTenantIds
     */
    public function createBroadcast(
        string $title,
        WhatsAppTemplate $template,
        BroadcastAudience $audience,
        int $createdBy,
        array $manualTenantIds = [],
    ): Broadcast {
        if (! $template->is_active) {
            throw new InvalidArgumentException('Template WhatsApp tidak aktif.');
        }

        $lock = Cache::lock("whatsapp-broadcast-user-{$createdBy}", 10);
        if (! $lock->get()) {
            throw new InvalidArgumentException('Broadcast sedang diproses. Tunggu beberapa detik.');
        }

        $recipients = $this->recipients($audience, $manualTenantIds);
        if ($recipients->isEmpty()) {
            throw new InvalidArgumentException('Tidak ada penerima valid untuk broadcast ini.');
        }

        $broadcast = DB::transaction(function () use ($title, $template, $audience, $createdBy, $recipients): Broadcast {
            $broadcast = Broadcast::create([
                'title' => $title,
                'whatsapp_template_id' => $template->id,
                'template_name' => $template->template_name,
                'audience_type' => $audience,
                'total_recipient' => $recipients->count(),
                'status' => BroadcastStatus::QUEUED,
                'created_by' => $createdBy,
                'started_at' => now(),
            ]);

            $recipients->each(function (array $recipient) use ($broadcast, $template): void {
                $orderedParameters = collect($template->variables ?? [])
                    ->map(fn (string $variable): string => $recipient['values'][$variable] ?? '-')
                    ->values()
                    ->all();

                WhatsAppLog::create([
                    'tenant_id' => $recipient['tenant']->id,
                    'invoice_id' => $recipient['invoice']?->id,
                    'broadcast_id' => $broadcast->id,
                    'phone' => $recipient['phone'],
                    'template_name' => $template->template_name,
                    'parameters' => $orderedParameters,
                    'status' => WhatsAppStatus::QUEUED,
                ]);

            });

            return $broadcast;
        });

        $broadcast->logs()->pluck('id')->each(fn (int $logId) => SendWhatsAppMessage::dispatch($logId));

        return $broadcast;
    }

    /** @return array<string, string> */
    public function templateValues(Tenant $tenant, ?Invoice $invoice): array
    {
        return [
            'nama' => $tenant->name,
            'kamar' => $tenant->room?->room_number ?? '-',
            'periode' => $invoice ? sprintf('%02d/%d', $invoice->period_month, $invoice->period_year) : '-',
            'total' => $invoice ? 'Rp '.number_format($invoice->total_amount, 0, ',', '.') : '-',
            'jatuh_tempo' => $invoice?->due_date?->locale('id')->translatedFormat('d F Y') ?? '-',
        ];
    }

    /** @param list<InvoiceStatus> $statuses */
    private function invoiceRecipients(array $statuses): Collection
    {
        return Invoice::query()
            ->with(['tenant.room:id,room_number'])
            ->whereIn('status', $statuses)
            ->latest('period_year')
            ->latest('period_month')
            ->latest('id')
            ->get()
            ->filter(fn (Invoice $invoice): bool => $invoice->tenant?->status->value === 'active')
            ->unique('tenant_id')
            ->map(fn (Invoice $invoice): array => ['tenant' => $invoice->tenant, 'invoice' => $invoice])
            ->values();
    }
}
