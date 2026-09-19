<?php

namespace App\Filament\Admin\Resources\Invoices\Pages;

use App\Enums\InvoiceStatus;
use App\Filament\Admin\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Notifications\TenantActivityNotification;
use App\Services\InvoiceService;
use App\Services\TenantNotificationService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant = Tenant::with('room.roomCategory')->findOrFail($data['tenant_id']);
        $snapshot = app(InvoiceService::class)->billingSnapshot($tenant, (int) $data['period_month'], (int) $data['period_year']);
        $data['created_by'] = auth()->id();
        $data['tenant_room_history_id'] = $snapshot['history']->id;
        $data['room_id'] = $snapshot['history']->room_id;
        $data['invoice_number'] = Invoice::generateInvoiceNumber($data['period_month'], $data['period_year']);
        $data['base_amount'] = $snapshot['base_amount'];
        $data['due_date'] = $snapshot['due_date'];
        $data['status'] = InvoiceStatus::UNPAID;
        $data['total_amount'] = InvoiceService::calculateTotal($data);

        // Bersihkan data lama jika ada yang soft-deleted pada periode ini
        Invoice::onlyTrashed()
            ->where('tenant_id', $data['tenant_id'])
            ->where('period_month', $data['period_month'])
            ->where('period_year', $data['period_year'])
            ->whereDoesntHave('payments')
            ->forceDelete();

        return $data;
    }

    protected function afterCreate(): void
    {
        $invoice = $this->record->loadMissing('tenant.user');
        if ($invoice->tenant) {
            app(TenantNotificationService::class)->notifyTenant(
                $invoice->tenant,
                new TenantActivityNotification(
                    'invoice_created',
                    'Tagihan baru tersedia',
                    "Tagihan {$invoice->invoice_number} telah tersedia.",
                    route('tenant.invoices.show', $invoice),
                    $invoice->id
                )
            );
        }
    }
}
