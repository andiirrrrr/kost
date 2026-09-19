<?php

namespace App\Filament\Admin\Resources\MaintenanceRequests\Pages;

use App\Filament\Admin\Resources\MaintenanceRequests\MaintenanceRequestResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateMaintenanceRequest extends CreateRecord
{
    protected static string $resource = MaintenanceRequestResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
