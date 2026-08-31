<?php

namespace App\Filament\Admin\Resources\Invoices\Pages;

use App\Filament\Admin\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Services\InvoiceService;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['room_id'] = Tenant::findOrFail($data['tenant_id'])->room_id;
        $data['total_amount'] = InvoiceService::calculateTotal($data);

        // Bersihkan data lama jika ada yang soft-deleted pada periode ini
        Invoice::onlyTrashed()
            ->where('tenant_id', $data['tenant_id'])
            ->where('period_month', $data['period_month'])
            ->where('period_year', $data['period_year'])
            ->forceDelete();

        return $data;
    }
}
