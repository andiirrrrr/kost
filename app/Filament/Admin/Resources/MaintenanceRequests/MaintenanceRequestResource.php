<?php

namespace App\Filament\Admin\Resources\MaintenanceRequests;

use App\Filament\Admin\Resources\MaintenanceRequests\Pages\CreateMaintenanceRequest;
use App\Filament\Admin\Resources\MaintenanceRequests\Pages\EditMaintenanceRequest;
use App\Filament\Admin\Resources\MaintenanceRequests\Pages\ListMaintenanceRequests;
use App\Filament\Admin\Resources\MaintenanceRequests\Schemas\MaintenanceRequestForm;
use App\Filament\Admin\Resources\MaintenanceRequests\Tables\MaintenanceRequestsTable;
use App\Models\MaintenanceRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MaintenanceRequestResource extends Resource
{
    protected static ?string $model = MaintenanceRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Keluhan & Perbaikan';

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Laporan Perbaikan';

    protected static ?string $pluralModelLabel = 'Keluhan & Perbaikan';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return MaintenanceRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MaintenanceRequestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMaintenanceRequests::route('/'),
            'create' => CreateMaintenanceRequest::route('/create'),
            'edit' => EditMaintenanceRequest::route('/{record}/edit'),
        ];
    }
}
