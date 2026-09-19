<?php

namespace App\Filament\Admin\Resources\Rooms\Pages;

use App\Filament\Admin\Resources\Rooms\RoomResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateRoom extends CreateRecord
{
    protected static string $resource = RoomResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
