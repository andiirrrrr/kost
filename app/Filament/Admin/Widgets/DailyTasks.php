<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Filament\Admin\Resources\Announcements\AnnouncementResource;
use App\Filament\Admin\Resources\Expenses\ExpenseResource;
use App\Filament\Admin\Resources\Invoices\InvoiceResource;
use App\Filament\Admin\Resources\MaintenanceRequests\MaintenanceRequestResource;
use App\Filament\Admin\Resources\Payments\PaymentResource;
use App\Filament\Admin\Resources\Tenants\TenantResource;
use App\Models\Invoice;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Tenant;
use Filament\Widgets\Widget;

class DailyTasks extends Widget
{
    protected string $view = 'filament.admin.widgets.daily-tasks';

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        return [
            'tasks' => [
                ['label' => 'Pembayaran perlu diperiksa', 'count' => Payment::query()->where('status', PaymentStatus::PENDING)->count(), 'url' => PaymentResource::getUrl(), 'colorClass' => 'text-amber-600'],
                ['label' => 'Tagihan terlambat', 'count' => Invoice::query()->where('status', InvoiceStatus::OVERDUE)->count(), 'url' => InvoiceResource::getUrl(), 'colorClass' => 'text-red-600'],
                ['label' => 'Keluhan belum selesai', 'count' => MaintenanceRequest::query()->whereIn('status', ['reported', 'in_progress'])->count(), 'url' => MaintenanceRequestResource::getUrl(), 'colorClass' => 'text-orange-600'],
                ['label' => 'Penghuni belum punya akun', 'count' => Tenant::query()->where('status', 'active')->whereNull('user_id')->count(), 'url' => TenantResource::getUrl(), 'colorClass' => 'text-blue-600'],
            ],
            'quickActions' => [
                ['label' => 'Tambah Penghuni', 'url' => TenantResource::getUrl('create')],
                ['label' => 'Buat Tagihan', 'url' => InvoiceResource::getUrl('create')],
                ['label' => 'Catat Pembayaran', 'url' => PaymentResource::getUrl('create')],
                ['label' => 'Catat Pengeluaran', 'url' => ExpenseResource::getUrl('create')],
                ['label' => 'Buat Pengumuman', 'url' => AnnouncementResource::getUrl('create')],
            ],
        ];
    }
}
