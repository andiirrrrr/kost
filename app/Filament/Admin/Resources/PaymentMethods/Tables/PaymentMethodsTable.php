<?php

namespace App\Filament\Admin\Resources\PaymentMethods\Tables;

use App\Enums\PaymentMethodCategory;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PaymentMethodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('category')->label('Kategori')->badge()->formatStateUsing(fn (PaymentMethodCategory $state): string => $state->label())->sortable(),
                TextColumn::make('account_number')->label('Tujuan Pembayaran')->placeholder('—')->visibleFrom('md'),
                IconColumn::make('is_active')->label('Aktif')->boolean()->sortable(),
            ])
            ->stackedOnMobile()
            ->filters([
                SelectFilter::make('category')->label('Kategori')->options(PaymentMethodCategory::options()),
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
