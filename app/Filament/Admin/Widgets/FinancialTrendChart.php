<?php

namespace App\Filament\Admin\Widgets;

use App\Services\DashboardService;
use Filament\Widgets\ChartWidget;

class FinancialTrendChart extends ChartWidget
{
    protected ?string $heading = 'Arus Kas 12 Bulan Terakhir';

    protected ?string $description = 'Perbandingan pemasukan, pengeluaran, dan estimasi bersih.';

    protected int|string|array $columnSpan = 2;

    protected ?string $maxHeight = '320px';

    protected function getData(): array
    {
        $trend = app(DashboardService::class)->financialTrend();

        return [
            'datasets' => [
                [
                    'label' => 'Pemasukan',
                    'data' => $trend['income'],
                    'borderColor' => '#16a34a',
                    'backgroundColor' => 'rgba(22, 163, 74, 0.12)',
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Pengeluaran',
                    'data' => $trend['expenses'],
                    'borderColor' => '#dc2626',
                    'backgroundColor' => 'rgba(220, 38, 38, 0.10)',
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Estimasi Bersih',
                    'data' => $trend['estimated_net'],
                    'borderColor' => '#2563eb',
                    'backgroundColor' => 'rgba(37, 99, 235, 0.08)',
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
