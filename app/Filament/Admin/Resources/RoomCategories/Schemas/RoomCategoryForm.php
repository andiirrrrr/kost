<?php

namespace App\Filament\Admin\Resources\RoomCategories\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RoomCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detail Kategori')
                    ->description('Atur tipe kamar beserta fasilitas dan harga dasarnya.')
                    ->columnSpanFull()
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Kategori')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100),
                        TextInput::make('base_monthly_price')
                            ->label('Harga Dasar Bulanan')
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(0),
                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(4)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                        FileUpload::make('landing_image')
                            ->label('Foto Landing Page')
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(4096)
                            ->disk('public')
                            ->directory('landing/room-categories')
                            ->visibility('public')
                            ->helperText('Foto ini mewakili seluruh kamar dalam kategori. Format: JPG, PNG, WebP. Max 4MB.')
                            ->columnSpanFull()
                            ->imageEditor()
                            ->downloadable()
                            ->openable(),
                        TagsInput::make('facilities')
                            ->label('Fasilitas yang Didapatkan')
                            ->placeholder('Contoh: AC')
                            ->helperText('Tekan Enter setelah menulis setiap fasilitas.')
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Dapat dipilih pada kamar')
                            ->default(true),
                        TextInput::make('sort_order')
                            ->label('Urutan')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                    ]),
            ]);
    }
}
