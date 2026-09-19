<?php

namespace App\Filament\Admin\Pages;

use App\Models\ActivityLog;
use App\Services\AdminExportService;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Livewire\Attributes\Computed;

class DataTools extends Page
{
    protected Width|string|null $maxContentWidth = Width::Full;

    private const int PREVIEW_LIMIT = 3;

    protected string $view = 'filament.admin.pages.data-tools';

    protected static ?string $navigationLabel = 'Ekspor & Backup';

    protected static string|\UnitEnum|null $navigationGroup = 'Sistem & Data';

    protected static ?int $navigationSort = 9;

    protected static ?string $title = 'Ekspor, Backup & Aktivitas';

    /** @var list<string> */
    public array $selectedExportTypes = [];

    public ?int $exportMonth = null;

    public ?int $exportYear = null;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-circle-stack';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('reports.view') ?? false;
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    /** @return array<string, string> */
    #[Computed]
    public function exportOptions(): array
    {
        return AdminExportService::TYPES;
    }

    /** @return array<string, array{label: string, headers: list<string>, rows: array<int, array<int, mixed>>, total: int}> */
    #[Computed]
    public function exportPreviews(): array
    {
        $exportService = app(AdminExportService::class);

        return collect($this->validSelectedExportTypes())
            ->mapWithKeys(fn (string $type): array => [
                $type => $exportService->dataset($type, $this->exportFilters(), self::PREVIEW_LIMIT),
            ])
            ->all();
    }

    #[Computed]
    public function exportUrl(): ?string
    {
        $types = $this->validSelectedExportTypes();

        if ($types === []) {
            return null;
        }

        return route('admin.data.export', [
            'types' => $types,
            ...array_filter($this->exportFilters(), fn (?int $value): bool => $value !== null),
        ]);
    }

    public function getViewData(): array
    {
        return ['activities' => ActivityLog::query()->with('user')->latest()->limit(20)->get()];
    }

    /** @return list<string> */
    private function validSelectedExportTypes(): array
    {
        return array_values(array_intersect(array_keys(AdminExportService::TYPES), $this->selectedExportTypes));
    }

    /** @return array{month: int|null, year: int|null} */
    private function exportFilters(): array
    {
        return [
            'month' => $this->exportMonth,
            'year' => $this->exportYear,
        ];
    }
}
