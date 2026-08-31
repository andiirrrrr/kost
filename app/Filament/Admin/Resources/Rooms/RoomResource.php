<?php

namespace App\Filament\Admin\Resources\Rooms;

use App\Enums\RoomStatus;
use App\Filament\Admin\Resources\Rooms\Pages\CreateRoom;
use App\Filament\Admin\Resources\Rooms\Pages\EditRoom;
use App\Filament\Admin\Resources\Rooms\Pages\ListRooms;
use App\Models\Room;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RoomResource extends Resource
{
    protected static ?string $model = Room::class;

    protected static ?string $navigationLabel = 'Kamar';

    protected static string|\UnitEnum|null $navigationGroup = 'Pengelolaan Kost';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Kamar';

    protected static ?string $pluralModelLabel = 'Kamar';

    protected static ?string $recordTitleAttribute = 'room_number';

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-rectangle-stack';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('room_number')
                    ->label('Nomor Kamar')
                    ->required()
                    ->unique()
                    ->maxLength(255),
                Forms\Components\TextInput::make('room_name')
                    ->label('Nama Kamar')
                    ->maxLength(255),
                Forms\Components\TextInput::make('type')
                    ->label('Tipe')
                    ->maxLength(255),
                Forms\Components\TextInput::make('monthly_price')
                    ->label('Harga Bulanan')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options(RoomStatus::class)
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->label('Deskripsi')
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('landing_image')
                    ->label('Foto untuk Landing Page')
                    ->image()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(4096)
                    ->disk('public')
                    ->directory('landing/rooms')
                    ->visibility('public')
                    ->columnSpanFull(),
                Forms\Components\TagsInput::make('facilities')
                    ->label('Fasilitas Kamar')
                    ->placeholder('Contoh: AC')
                    ->helperText('Tekan Enter setelah menulis setiap fasilitas.')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('room_number')
                    ->label('Nomor Kamar')
                    ->searchable(),
                TextColumn::make('room_name')
                    ->label('Nama Kamar')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Tipe')
                    ->searchable(),
                TextColumn::make('monthly_price')
                    ->label('Harga Bulanan')
                    ->money('IDR')
                    ->sortable(),
                SelectColumn::make('status')
                    ->label('Status')
                    ->options(RoomStatus::class),
                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(RoomStatus::class),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRooms::route('/'),
            'create' => CreateRoom::route('/create'),
            'edit' => EditRoom::route('/{record}/edit'),
        ];
    }
}
