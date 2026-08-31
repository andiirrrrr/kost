<?php

namespace App\Filament\Admin\Resources\Payments\Pages;

use App\Enums\PaymentStatus;
use App\Filament\Admin\Resources\Payments\PaymentResource;
use App\Models\Invoice;
use App\Models\Payment;
use Filament\Resources\Pages\CreateRecord;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $invoice = Invoice::findOrFail($data['invoice_id']);

        $data['tenant_id'] = $invoice->tenant_id;
        $data['payment_number'] = Payment::generatePaymentNumber();
        $data['status'] = PaymentStatus::PENDING;

        return $data;
    }
}
