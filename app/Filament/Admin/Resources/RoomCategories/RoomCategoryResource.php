<?php

namespace App\Filament\Admin\Resources\RoomCategories;

use App\Filament\Admin\Resources\RoomCategories\Pages\CreateRoomCategory;
use App\Filament\Admin\Resources\RoomCategories\Pages\EditRoomCategory;
use App\Filament\Admin\Resources\RoomCategories\Pages\ListRoomCategories;
use App\Filament\Admin\Resources\RoomCategories\Schemas\RoomCategoryForm;
use App\Filament\Admin\Resources\RoomCategories\Tables\RoomCategoriesTable;
use App\Models\RoomCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RoomCategoryResource extends Resource
{
    protected static ?string $model = RoomCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Kategori Kamar';

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Kategori Kamar';

    protected static ?string $pluralModelLabel = 'Kategori Kamar';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return RoomCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RoomCategoriesTable::configure($table);
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
            'index' => ListRoomCategories::route('/'),
            'create' => CreateRoomCategory::route('/create'),
            'edit' => EditRoomCategory::route('/{record}/edit'),
        ];
    }
}
