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
                ->description("Tingkat hunian {$summary['occupancy_rate']}%")
                ->color('primary'),
            Stat::make('Kamar Terisi', $summary['occupied_rooms'])
                ->description('Kamar yang dihuni')
                ->color('success'),
            Stat::make('Kamar Kosong', $summary['available_rooms'])
                ->description('Kamar tersedia')
                ->color('warning'),
            Stat::make('Penghuni Aktif', $summary['active_tenants'])
                ->description('Total penghuni aktif')
                ->color('info'),
            Stat::make('Tagihan Bulan Ini', $this->rupiah($summary['invoice_total']))
                ->description('Nilai seluruh tagihan periode ini')
                ->color('primary'),
            Stat::make('Pemasukan Bulan Ini', $this->rupiah($summary['income']))
                ->description('Pembayaran yang telah diverifikasi')
                ->color('success'),
            Stat::make('Belum Dibayar', $this->rupiah($summary['outstanding_total']))
                ->description('Tagihan aktif yang belum dibayar')
                ->color('warning'),
            Stat::make('Tagihan Terlambat', $summary['overdue_count'])
                ->description('Tagihan melewati jatuh tempo')
                ->color('danger'),
            Stat::make('Pengeluaran Bulan Ini', $this->rupiah($summary['expenses']))
                ->description('Total pengeluaran tercatat')
                ->color('danger'),
            Stat::make('Estimasi Bersih', $this->rupiah($summary['estimated_net']))
                ->description('Pemasukan dikurangi pengeluaran')
                ->color($summary['estimated_net'] >= 0 ? 'success' : 'danger'),
        ];
    }

    private function rupiah(int|float $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
