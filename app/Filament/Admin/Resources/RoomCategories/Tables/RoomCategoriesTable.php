<?php

namespace App\Filament\Admin\Resources\RoomCategories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class RoomCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('landing_image')
                    ->label('Foto')
                    ->disk('public')
                    ->square()
                    ->imageSize(56),
                TextColumn::make('name')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('base_monthly_price')
                    ->label('Harga Dasar')
                    ->money('IDR')
                    ->alignEnd()
                    ->placeholder('Belum diatur')
                    ->sortable(),
                TextColumn::make('facilities')
                    ->label('Fasilitas')
                    ->badge()
                    ->separator(',')
                    ->visibleFrom('lg'),
                TextColumn::make('rooms_count')
                    ->label('Jumlah Kamar')
                    ->counts('rooms')
                    ->visibleFrom('md')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
            ])
            ->stackedOnMobile()
            ->filters([
                TernaryFilter::make('is_active')->label('Status Aktif'),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
