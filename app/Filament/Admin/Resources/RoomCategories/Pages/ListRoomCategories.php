<?php

namespace App\Filament\Admin\Resources\RoomCategories\Pages;

use App\Filament\Admin\Resources\RoomCategories\RoomCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRoomCategories extends ListRecords
{
    protected static string $resource = RoomCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
