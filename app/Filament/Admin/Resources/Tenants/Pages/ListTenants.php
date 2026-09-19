<?php

namespace App\Filament\Admin\Resources\Tenants\Pages;

use App\Enums\TenantStatus;
use App\Filament\Admin\Resources\Tenants\TenantResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTenants extends ListRecords
{
    private const int ARCHIVE_AFTER_MONTHS = 3;

    protected static string $resource = TenantResource::class;

    /** @return array<string, Tab> */
    public function getTabs(): array
    {
        $archiveThreshold = today()->subMonthsNoOverflow(self::ARCHIVE_AFTER_MONTHS)->toDateString();

        return [
            'current' => Tab::make('Penghuni Saat Ini')
                ->icon('heroicon-o-users')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where(function (Builder $query) use ($archiveThreshold): void {
                    $query->where('status', '!=', TenantStatus::INACTIVE)
                        ->orWhereNull('move_out_date')
                        ->orWhereDate('move_out_date', '>', $archiveThreshold);
                })),
            'archived' => Tab::make('Arsip')
                ->icon('heroicon-o-archive-box')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->where('status', TenantStatus::INACTIVE)
                    ->whereNotNull('move_out_date')
                    ->whereDate('move_out_date', '<=', $archiveThreshold)),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
