<?php

namespace App\Filament\Admin\Widgets;

use App\Services\DashboardService;
use Filament\Widgets\ChartWidget;

class InvoiceStatusChart extends ChartWidget
{
    protected ?string $heading = 'Status Tagihan Bulan Ini';

    protected ?string $description = 'Distribusi tagihan lunas, belum dibayar, dan terlambat.';

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '320px';

    protected function getData(): array
    {
        $totals = app(DashboardService::class)->invoiceStatusBreakdown();

        return [
            'datasets' => [[
                'data' => [$totals['paid'], $totals['unpaid'], $totals['overdue']],
                'backgroundColor' => ['#16a34a', '#f59e0b', '#dc2626'],
                'borderWidth' => 0,
            ]],
            'labels' => ['Lunas', 'Belum Dibayar', 'Terlambat'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
