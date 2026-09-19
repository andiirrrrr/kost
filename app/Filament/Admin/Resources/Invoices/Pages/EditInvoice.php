<?php

namespace App\Filament\Admin\Resources\Invoices\Pages;

use App\Filament\Admin\Resources\Invoices\InvoiceResource;
use App\Services\InvoiceService;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['tenant_id'] = $this->record->tenant_id;
        $data['tenant_room_history_id'] = $this->record->tenant_room_history_id;
        $data['room_id'] = $this->record->room_id;
        $data['period_month'] = $this->record->period_month;
        $data['period_year'] = $this->record->period_year;
        $data['base_amount'] = $this->record->base_amount;
        $data['due_date'] = $this->record->due_date;
        $data['total_amount'] = InvoiceService::calculateTotal($data);

        return $data;
    }
}
