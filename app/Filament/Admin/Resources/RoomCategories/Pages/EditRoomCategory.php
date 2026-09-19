<?php

namespace App\Filament\Admin\Resources\RoomCategories\Pages;

use App\Filament\Admin\Resources\RoomCategories\RoomCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditRoomCategory extends EditRecord
{
    protected static string $resource = RoomCategoryResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
