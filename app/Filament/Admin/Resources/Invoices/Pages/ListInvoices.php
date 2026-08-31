<?php

namespace App\Filament\Admin\Resources\Invoices\Pages;

use App\Filament\Admin\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('generate')
                ->label('Buat Tagihan Bulanan')
                ->icon('heroicon-o-plus-circle')
                ->visible(fn (): bool => auth()->user()?->can('generate', Invoice::class) ?? false)
                ->url(InvoiceResource::getUrl('generate')),
        ];
    }
}
