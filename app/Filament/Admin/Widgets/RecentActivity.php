<?php

namespace App\Filament\Admin\Widgets;

use App\Models\ActivityLog;
use Filament\Widgets\Widget;

class RecentActivity extends Widget
{
    protected string $view = 'filament.admin.widgets.recent-activity';

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = [
        'md' => 'full',
        'xl' => 5,
    ];

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        return [
            'activities' => ActivityLog::query()
                ->with('user:id,name')
                ->latest()
                ->limit(6)
                ->get(),
        ];
    }
}
