<?php

namespace App\Filament\Admin\Resources\WhatsAppTemplates\Pages;

use App\Filament\Admin\Resources\WhatsAppTemplates\WhatsAppTemplateResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateWhatsAppTemplate extends CreateRecord
{
    protected static string $resource = WhatsAppTemplateResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
