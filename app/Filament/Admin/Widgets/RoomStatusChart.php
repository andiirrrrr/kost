<?php

namespace App\Filament\Admin\Widgets;

use App\Services\DashboardService;
use Filament\Widgets\ChartWidget;

class RoomStatusChart extends ChartWidget
{
    protected ?string $heading = 'Kondisi Kamar';

    protected ?string $description = 'Komposisi seluruh unit saat ini.';

    protected int|string|array $columnSpan = [
        'md' => 'full',
        'xl' => 3,
    ];

    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $totals = app(DashboardService::class)->roomStatusBreakdown();

        return [
            'datasets' => [[
                'data' => [$totals['occupied'], $totals['available'], $totals['reserved'], $totals['maintenance']],
                'backgroundColor' => ['#031636', '#67645c', '#d97706', '#c5c6cf'],
                'borderColor' => '#ffffff',
                'borderWidth' => 4,
                'hoverOffset' => 5,
            ]],
            'labels' => ['Terisi', 'Tersedia', 'Dipesan', 'Perawatan'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
