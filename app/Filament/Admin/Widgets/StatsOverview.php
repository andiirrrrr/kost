<?php

namespace App\Filament\Admin\Widgets;

use App\Services\DashboardService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $summary = app(DashboardService::class)->summary();

        return [
            Stat::make('Total Kamar', $summary['total_rooms'])
                ->description("{$summary['occupied_rooms']} kamar terisi")
                ->icon('heroicon-o-building-office-2')
                ->color('primary'),
            Stat::make('Penghuni Aktif', $summary['active_tenants'])
                ->description("Tingkat hunian {$summary['occupancy_rate']}%")
                ->icon('heroicon-o-users')
                ->color('primary'),
            Stat::make('Belum Dibayar', $this->rupiah($summary['outstanding_total']))
                ->description("{$summary['overdue_count']} tagihan terlambat")
                ->descriptionIcon($summary['overdue_count'] > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->icon('heroicon-o-document-currency-dollar')
                ->color($summary['overdue_count'] > 0 ? 'danger' : 'primary'),
            Stat::make('Estimasi Bersih', $this->rupiah($summary['estimated_net']))
                ->description('Pemasukan dikurangi pengeluaran')
                ->icon('heroicon-o-scale')
                ->color($summary['estimated_net'] >= 0 ? 'success' : 'danger'),
        ];
    }

    private function rupiah(int|float $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
