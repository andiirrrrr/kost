<?php

namespace App\Filament\Admin\Widgets;

use App\Services\DashboardService;
use Filament\Widgets\Widget;

class PrimarySummary extends Widget
{
    protected string $view = 'filament.admin.widgets.primary-summary';

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = [
        'md' => 'full',
        'xl' => 3,
    ];

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        return ['summary' => app(DashboardService::class)->summary()];
    }
}
