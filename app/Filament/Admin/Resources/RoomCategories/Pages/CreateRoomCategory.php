<?php

namespace App\Filament\Admin\Resources\RoomCategories\Pages;

use App\Filament\Admin\Resources\RoomCategories\RoomCategoryResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateRoomCategory extends CreateRecord
{
    protected static string $resource = RoomCategoryResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
