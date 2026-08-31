<?php

namespace App\Filament\Admin\Resources\Payments\Pages;

use App\Enums\PaymentStatus;
use App\Filament\Admin\Resources\Payments\PaymentResource;
use App\Models\Invoice;
use Filament\Resources\Pages\EditRecord;

class EditPayment extends EditRecord
{
    protected static string $resource = PaymentResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $invoice = Invoice::findOrFail($data['invoice_id']);

        $data['tenant_id'] = $invoice->tenant_id;
        $data['status'] = PaymentStatus::PENDING;

        return $data;
    }
}
