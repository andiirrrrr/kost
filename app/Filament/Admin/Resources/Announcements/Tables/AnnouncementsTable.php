<?php

namespace App\Filament\Admin\Resources\Announcements\Tables;

use App\Jobs\NotifyTenantsOfAnnouncement;
use App\Models\Announcement;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AnnouncementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Judul')->searchable()->sortable(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
                TextColumn::make('published_at')->label('Dipublikasikan')->dateTime()->sortable(),
                TextColumn::make('expires_at')->label('Berakhir')->dateTime()->placeholder('Tidak dibatasi')->visibleFrom('md'),
            ])
            ->stackedOnMobile()
            ->filters([])
            ->recordActions([
                ActionGroup::make([
                    Action::make('publish')
                        ->label('Publikasikan')
                        ->icon('heroicon-o-megaphone')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (Announcement $record): bool => ! $record->is_active)
                        ->action(function (Announcement $record): void {
                            $record->update(['is_active' => true, 'published_at' => now()]);
                            NotifyTenantsOfAnnouncement::dispatchSync($record->id);
                        }),
                    Action::make('deactivate')
                        ->label('Nonaktifkan')
                        ->icon('heroicon-o-pause')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->visible(fn (Announcement $record): bool => $record->is_active)
                        ->action(fn (Announcement $record) => $record->update(['is_active' => false])),
                    EditAction::make(),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->iconButton()
                    ->tooltip('Aksi')
                    ->color('gray'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
