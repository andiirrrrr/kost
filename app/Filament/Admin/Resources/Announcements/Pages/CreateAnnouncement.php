<?php

namespace App\Filament\Admin\Resources\Announcements\Pages;

use App\Filament\Admin\Resources\Announcements\AnnouncementResource;
use App\Jobs\NotifyTenantsOfAnnouncement;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateAnnouncement extends CreateRecord
{
    protected static string $resource = AnnouncementResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        if (! empty($data['is_active'])) {
            $data['published_at'] = now();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->record->is_active) {
            NotifyTenantsOfAnnouncement::dispatchSync($this->record->id);
        }
    }
}
