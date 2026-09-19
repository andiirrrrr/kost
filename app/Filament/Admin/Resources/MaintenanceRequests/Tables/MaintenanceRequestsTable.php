<?php

namespace App\Filament\Admin\Resources\MaintenanceRequests\Tables;

use App\Models\MaintenanceRequest;
use App\Services\MaintenanceExpenseService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MaintenanceRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Masalah')->searchable(),
                TextColumn::make('room.room_number')->label('Kamar')->sortable(),
                TextColumn::make('tenant.name')->label('Pelapor')->placeholder('Admin')->visibleFrom('md'),
                TextColumn::make('priority')
                    ->label('Prioritas')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'low' => 'Rendah',
                        'urgent' => 'Mendesak',
                        default => 'Normal',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'low' => 'gray',
                        'urgent' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'reported' => 'Baru',
                        'in_progress' => 'Diproses',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'reported' => 'warning',
                        'in_progress' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')->label('Dilaporkan')->since()->visibleFrom('lg')->sortable(),
            ])
            ->stackedOnMobile()
            ->filters([
                SelectFilter::make('status')->options(['reported' => 'Baru', 'in_progress' => 'Diproses', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan']),
                SelectFilter::make('priority')->options(['low' => 'Rendah', 'normal' => 'Normal', 'urgent' => 'Mendesak']),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('recordExpense')
                        ->label('Catat sebagai pengeluaran')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (MaintenanceRequest $record): bool => $record->status === 'completed' && $record->cost > 0 && ! $record->expense_id)
                        ->action(function (MaintenanceRequest $record): void {
                            app(MaintenanceExpenseService::class)->record($record, auth()->id());
                            Notification::make()->title('Biaya perbaikan dicatat sebagai pengeluaran')->success()->send();
                        }),
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
