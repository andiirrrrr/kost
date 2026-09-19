<?php

namespace App\Filament\Admin\Widgets;

use App\Services\DashboardService;
use Filament\Widgets\ChartWidget;

class FinancialTrendChart extends ChartWidget
{
    protected ?string $heading = 'Arus Kas 12 Bulan Terakhir';

    protected ?string $description = 'Perbandingan pemasukan, pengeluaran, dan estimasi bersih.';

    protected int|string|array $columnSpan = [
        'md' => 'full',
        'xl' => 6,
    ];

    protected ?string $maxHeight = '320px';

    protected function getData(): array
    {
        $trend = app(DashboardService::class)->financialTrend();

        return [
            'datasets' => [
                [
                    'label' => 'Pemasukan',
                    'data' => $trend['income'],
                    'borderColor' => '#031636',
                    'backgroundColor' => 'rgba(3, 22, 54, 0.10)',
                    'borderWidth' => 2,
                    'tension' => 0.35,
                    'fill' => true,
                ],
                [
                    'label' => 'Pengeluaran',
                    'data' => $trend['expenses'],
                    'borderColor' => '#ba1a1a',
                    'backgroundColor' => 'rgba(186, 26, 26, 0.04)',
                    'borderWidth' => 2,
                    'tension' => 0.35,
                ],
                [
                    'label' => 'Estimasi Bersih',
                    'data' => $trend['estimated_net'],
                    'borderColor' => '#67645c',
                    'backgroundColor' => 'rgba(103, 100, 92, 0.05)',
                    'borderWidth' => 2,
                    'borderDash' => [6, 4],
                    'tension' => 0.3,
                ],
            ],
            'labels' => $trend['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
