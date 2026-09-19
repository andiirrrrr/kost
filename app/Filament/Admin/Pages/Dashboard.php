<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\FinancialTrendChart;
use App\Filament\Admin\Widgets\PrimarySummary;
use App\Filament\Admin\Widgets\RecentActivity;
use App\Filament\Admin\Widgets\RecentPayments;
use App\Filament\Admin\Widgets\RoomStatusChart;
use App\Filament\Admin\Widgets\StatsOverview;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Enums\Width;

class Dashboard extends BaseDashboard
{
    protected Width|string|null $maxContentWidth = Width::Full;

    // Daftar widget yang akan ditampilkan di Dashboard
    public function getWidgets(): array
    {
        return [
            PrimarySummary::class,
            FinancialTrendChart::class,
            RoomStatusChart::class,
            StatsOverview::class,
            RecentActivity::class,
            RecentPayments::class,
        ];
    }

    public function getColumns(): int|array
    {
        return [
            'md' => 1,
            'xl' => 12,
        ];
    }

    public function getHeading(): string
    {
        return 'Dashboard';
    }

    public function getSubheading(): ?string
    {
        return 'Ringkasan operasional dan keuangan kost bulan ini.';
    }
}
