<?php

namespace App\Filament\Admin\Pages;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\RoomStatus;
use App\Enums\TenantStatus;
use App\Services\ReportService;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

class Reports extends Page
{
    protected string $view = 'filament.admin.pages.reports';

    protected Width|string|null $maxContentWidth = Width::Full;

    protected static ?string $navigationLabel = 'Laporan';

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Laporan Dasar';

    protected static ?string $slug = 'reports';

    public string $reportType = 'invoices';

    public int $month;

    public int $year;

    public ?string $status = null;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-chart-bar-square';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('reports.view') ?? false;
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->month = now()->month;
        $this->year = now()->year;
    }

    public function updatedReportType(): void
    {
        $this->status = null;
    }

    /** @return Collection<int, mixed> */
    #[Computed]
    public function records(): Collection
    {
        $service = app(ReportService::class);

        return match ($this->reportType) {
            'payments' => $service->payments($this->month, $this->year, $this->status),
            'rooms' => $service->rooms($this->status),
            'tenants' => $service->tenants($this->status),
            default => $service->invoices($this->month, $this->year, $this->status),
        };
    }

    /** @return array{income: int, expenses: int, outstanding: int, occupancy_rate: float} */
    #[Computed]
    public function summary(): array
    {
        return app(ReportService::class)->summary($this->month, $this->year);
    }

    /** @return array<string, string> */
    public function statusOptions(): array
    {
        $cases = match ($this->reportType) {
            'payments' => PaymentStatus::cases(),
            'rooms' => RoomStatus::cases(),
            'tenants' => TenantStatus::cases(),
            default => InvoiceStatus::cases(),
        };

        return collect($cases)->mapWithKeys(fn ($status): array => [$status->value => $status->label()])->all();
    }

    public function rupiah(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
