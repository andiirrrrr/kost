<?php

namespace App\Filament\Admin\Resources\Invoices\Pages;

use App\Filament\Admin\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Services\InvoiceService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class GenerateInvoices extends Page
{
    protected static string $resource = InvoiceResource::class;

    protected string $view = 'filament.admin.resources.invoices.pages.generate-invoices';

    protected static ?string $title = 'Buat Tagihan Bulanan';

    public $month;

    public $year;

    public $totalTenants = 0;

    public $result = null;

    public function mount()
    {
        Gate::authorize('generate', Invoice::class);
        $this->month = Carbon::now()->month;
        $this->year = Carbon::now()->year;
        $this->countTenants();
    }

    public function countTenants()
    {
        $this->totalTenants = Tenant::where('status', 'active')->count();
    }

    public function generate()
    {
        Gate::authorize('generate', Invoice::class);
        $service = new InvoiceService;
        $result = $service->generateMonthlyInvoices(
            $this->month,
            $this->year,
            Auth::id()
        );

        $this->result = $result;

        if ($result['created'] > 0) {
            Notification::make()
                ->title("{$result['created']} tagihan berhasil dibuat")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Tidak ada tagihan baru yang dibuat')
                ->info()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate')
                ->label('Buat Sekarang')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Pembuatan Tagihan')
                ->modalDescription("Anda akan membuat tagihan untuk {$this->totalTenants} penghuni aktif pada periode {$this->month}/{$this->year}.")
                ->modalSubmitActionLabel('Ya, Buat Tagihan')
                ->action(fn () => $this->generate()),
        ];
    }
}
