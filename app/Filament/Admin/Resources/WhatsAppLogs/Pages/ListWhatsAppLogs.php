<?php

namespace App\Filament\Admin\Resources\WhatsAppLogs\Pages;

use App\Filament\Admin\Resources\WhatsAppLogs\WhatsAppLogResource;
use Filament\Resources\Pages\ListRecords;

class ListWhatsAppLogs extends ListRecords
{
    protected static string $resource = WhatsAppLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
