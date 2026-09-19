<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\WhatsAppStatus;
use App\Jobs\SendWhatsAppMessage;
use App\Models\Invoice;
use App\Models\Setting;
use App\Models\WhatsAppLog;
use App\Models\WhatsAppTemplate;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;

class ReminderService
{
    /** @return array{created: int, skipped: int} */
    public function dispatchForDate(?CarbonImmutable $date = null): array
    {
        $date ??= CarbonImmutable::today();

        if (! $this->isEnabled()) {
            return ['created' => 0, 'skipped' => 0];
        }

        $created = 0;
        $skipped = 0;
        $templates = [];

        foreach ($this->rulesForDate($date) as $rule) {
            $template = $templates[$rule['template']] ??= WhatsAppTemplate::query()
                ->where('template_name', $rule['template'])
                ->where('is_active', true)
                ->first();

            if (! $template) {
                continue;
            }

            $invoices = Invoice::query()
                ->with(['tenant.room:id,room_number'])
                ->whereDate('due_date', $rule['due_date'])
                ->whereIn('status', $rule['statuses'])
                ->whereHas('tenant', fn ($query) => $query->where('status', 'active')->whereNull('move_out_date')->whereDate('move_in_date', '<=', $date))
                ->get();

            foreach ($invoices as $invoice) {
                $phone = WhatsAppService::normalizePhone((string) $invoice->tenant?->phone);

                if (! $invoice->tenant || ! WhatsAppService::isValidPhone($phone)) {
                    $skipped++;

                    continue;
                }

                $key = sprintf('reminder:%d:%s:%s', $invoice->id, $template->template_name, $date->toDateString());
                $values = app(BroadcastService::class)->templateValues($invoice->tenant, $invoice);

                try {
                    $log = WhatsAppLog::create([
                        'tenant_id' => $invoice->tenant_id,
                        'invoice_id' => $invoice->id,
                        'phone' => $phone,
                        'template_name' => $template->template_name,
                        'parameters' => collect($template->variables ?? [])
                            ->map(fn (string $variable): string => $values[$variable] ?? '-')
                            ->values()
                            ->all(),
                        'deduplication_key' => $key,
                        'status' => WhatsAppStatus::QUEUED,
                    ]);

                    SendWhatsAppMessage::dispatch($log->id);
                    $created++;
                } catch (QueryException $exception) {
                    if ($exception->getCode() === '23000') {
                        $skipped++;

                        continue;
                    }

                    throw $exception;
                }
            }
        }

        return compact('created', 'skipped');
    }

    /** @return list<array{due_date: string, template: string, statuses: list<InvoiceStatus>}> */
    private function rulesForDate(CarbonImmutable $date): array
    {
        $rules = [];
        $beforeDays = max(0, (int) Setting::get('reminder_before_days', 3));
        $afterDays = max(0, (int) Setting::get('reminder_after_days', 3));

        if ($beforeDays > 0) {
            $rules[] = ['due_date' => $date->addDays($beforeDays)->toDateString(), 'template' => 'pengingat_jatuh_tempo', 'statuses' => [InvoiceStatus::UNPAID, InvoiceStatus::PENDING]];
        }

        if ($this->settingBool('reminder_due_date_enabled', true)) {
            $rules[] = ['due_date' => $date->toDateString(), 'template' => 'pengingat_jatuh_tempo', 'statuses' => [InvoiceStatus::UNPAID, InvoiceStatus::PENDING]];
        }

        if ($afterDays > 0) {
            $rules[] = ['due_date' => $date->subDays($afterDays)->toDateString(), 'template' => 'tagihan_terlambat', 'statuses' => [InvoiceStatus::OVERDUE]];
        }

        return $rules;
    }

    private function isEnabled(): bool
    {
        return $this->settingBool('automatic_reminder_enabled', false)
            || $this->settingBool('auto_reminder_enabled', false);
    }

    private function settingBool(string $key, bool $default): bool
    {
        return filter_var(Setting::get($key, $default), FILTER_VALIDATE_BOOLEAN);
    }
}
