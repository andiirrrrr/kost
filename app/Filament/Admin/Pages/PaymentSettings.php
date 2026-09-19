<?php

namespace App\Filament\Admin\Pages;

use App\Enums\PaymentMethodCategory;
use App\Models\PaymentMethod;
use App\Models\Setting;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

class PaymentSettings extends Page
{
    protected Width|string|null $maxContentWidth = Width::Full;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.admin.pages.payment-settings';

    protected static ?string $navigationLabel = 'Rekening Pembayaran';

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Rekening Pembayaran';

    protected static ?string $slug = 'payment-settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-banknotes';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('owner') ?? false;
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->form->fill([
            'bank_name' => Setting::get('bank_name', ''),
            'bank_account_number' => Setting::get('bank_account_number', ''),
            'bank_account_holder' => Setting::get('bank_account_holder', ''),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Tujuan Transfer Penghuni')
                    ->description('Informasi ini tampil saat penghuni mengajukan pembayaran.')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        TextInput::make('bank_name')->label('Nama Bank')->required()->maxLength(100),
                        TextInput::make('bank_account_number')->label('Nomor Rekening')->required()->maxLength(50),
                        TextInput::make('bank_account_holder')->label('Nama Pemilik Rekening')->required()->maxLength(150)->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        PaymentMethod::query()->where('code', 'bank_transfer')->update([
            'name' => $data['bank_name'],
            'category' => PaymentMethodCategory::BANK_TRANSFER,
            'account_number' => $data['bank_account_number'],
            'account_holder' => $data['bank_account_holder'],
        ]);

        Notification::make()->title('Rekening pembayaran berhasil diperbarui')->success()->send();
    }
}
