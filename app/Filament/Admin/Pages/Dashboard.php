<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\FinancialTrendChart;
use App\Filament\Admin\Widgets\InvoiceStatusChart;
use App\Filament\Admin\Widgets\StatsOverview;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    // Daftar widget yang akan ditampilkan di Dashboard
    public function getWidgets(): array
    {
        return [
            StatsOverview::class,
            FinancialTrendChart::class,
            InvoiceStatusChart::class,
        ];
    }

    public function getColumns(): int|array
    {
        return [
            'md' => 1,
            'xl' => 3,
        ];
    }
}
