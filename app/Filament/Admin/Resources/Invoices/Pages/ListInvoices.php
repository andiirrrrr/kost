<?php

namespace App\Filament\Admin\Resources\Invoices\Pages;

use App\Filament\Admin\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('synchronizeAll')
                ->label('Sinkronkan Tagihan')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->visible(fn (): bool => auth()->user()?->can('invoices.update') ?? false)
                ->action(function (): void {
                    abort_unless(auth()->user()?->can('invoices.update'), 403);
                    $result = app(InvoiceService::class)->synchronizeAllChangedInvoices();

                    Notification::make()
                        ->title("{$result['synchronized']} tagihan diperbarui")
                        ->body("{$result['unchanged']} tagihan sudah sesuai dan tidak diubah.")
                        ->success()
                        ->send();
                }),
            Actions\CreateAction::make(),
            Actions\Action::make('generate')
                ->label('Buat Tagihan Bulanan')
                ->icon('heroicon-o-plus-circle')
                ->visible(fn (): bool => auth()->user()?->can('generate', Invoice::class) ?? false)
                ->url(InvoiceResource::getUrl('generate')),
        ];
    }
}
