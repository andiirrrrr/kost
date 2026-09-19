<?php

namespace App\Filament\Admin\Resources\Payments\Pages;

use App\Enums\PaymentStatus;
use App\Filament\Admin\Resources\Payments\PaymentResource;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $invoice = Invoice::findOrFail($data['invoice_id']);

        $data['tenant_id'] = $invoice->tenant_id;
        $data['payment_number'] = Payment::generatePaymentNumber();
        $data['amount'] = $invoice->total_amount;
        $data['status'] = PaymentStatus::PENDING;
        $data['payment_method'] = PaymentMethod::withTrashed()->findOrFail($data['payment_method_id'])->code;

        return $data;
    }
}
