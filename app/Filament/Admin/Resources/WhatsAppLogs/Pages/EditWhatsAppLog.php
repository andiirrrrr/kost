<?php

namespace App\Filament\Admin\Resources\WhatsAppLogs\Pages;

use App\Filament\Admin\Resources\WhatsAppLogs\WhatsAppLogResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWhatsAppLog extends EditRecord
{
    protected static string $resource = WhatsAppLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
