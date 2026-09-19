<?php

namespace App\Filament\Admin\Resources\Rooms;

use App\Enums\RoomStatus;
use App\Filament\Admin\Resources\Rooms\Pages\CreateRoom;
use App\Filament\Admin\Resources\Rooms\Pages\EditRoom;
use App\Filament\Admin\Resources\Rooms\Pages\ListRooms;
use App\Models\Room;
use App\Models\RoomCategory;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RoomResource extends Resource
{
    protected static ?string $model = Room::class;

    protected static ?string $navigationLabel = 'Kamar';

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional';

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
                Section::make('Identitas & Kategori')
                    ->description('Tetapkan nomor dan kategori kamar sebagai informasi utama unit.')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        Forms\Components\TextInput::make('room_number')
                            ->label('Nomor Kamar')
                            ->required()
                            ->unique()
                            ->maxLength(255),
                        Forms\Components\Select::make('room_category_id')
                            ->label('Kategori Kamar')
                            ->relationship('roomCategory', 'name', modifyQueryUsing: fn ($query) => $query->where('is_active', true)->orderBy('sort_order')->orderBy('name'))
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set): void {
                                $category = RoomCategory::query()->find($state);

                                if (! $category) {
                                    return;
                                }

                                $set('type', $category->name);
                                $set('monthly_price', $category->base_monthly_price);
                            })
                            ->helperText('Harga dasar akan diisikan otomatis. Deskripsi dan fasilitas kategori diwarisi tanpa perlu disalin.'),
                        Forms\Components\Hidden::make('type'),
                    ]),
                Section::make('Harga & Status')
                    ->description('Periksa harga bulanan dan kondisi operasional kamar.')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        Forms\Components\TextInput::make('monthly_price')
                            ->label('Harga Bulanan')
                            ->required()
                            ->numeric()
                            ->prefix('Rp'),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(RoomStatus::class)
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('room_number')
                    ->label('Nomor Kamar')
                    ->searchable(),
                TextColumn::make('roomCategory.name')
                    ->label('Kategori')
                    ->placeholder(fn (Room $record): string => $record->type ?: 'Belum dipilih')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('monthly_price')
                    ->label('Harga Bulanan')
                    ->money('IDR')
                    ->alignEnd()
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
            ->stackedOnMobile()
            ->filters([
                SelectFilter::make('room_category_id')
                    ->label('Kategori')
                    ->relationship('roomCategory', 'name'),
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
