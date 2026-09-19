<?php

namespace App\Filament\Admin\Resources\Announcements\Pages;

use App\Filament\Admin\Resources\Announcements\AnnouncementResource;
use App\Jobs\NotifyTenantsOfAnnouncement;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditAnnouncement extends EditRecord
{
    protected static string $resource = AnnouncementResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        if ($this->record->wasChanged('is_active') && $this->record->is_active) {
            if (! $this->record->published_at) {
                $this->record->updateQuietly(['published_at' => now()]);
            }

            NotifyTenantsOfAnnouncement::dispatchSync($this->record->id);
        }
    }
}
