<?php

namespace App\Filament\Admin\Resources\Invoices;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Room;
use App\Models\Tenant;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Navigation\NavigationItem;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Tagihan';

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Tagihan';

    protected static ?string $pluralModelLabel = 'Tagihan';

    public static function calculateTotal(callable $get, callable $set): void
    {
        $base = (int) ($get('base_amount') ?? 0);
        $elec = (int) ($get('electricity_amount') ?? 0);
        $water = (int) ($get('water_amount') ?? 0);
        $other = (int) ($get('other_amount') ?? 0);
        $disc = (int) ($get('discount_amount') ?? 0);

        $total = max(0, $base + $elec + $water + $other - $disc);
        $set('total_amount', $total);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Tagihan')
                    ->schema([
                        Forms\Components\Select::make('tenant_id')
                            ->label('Penghuni')
                            ->options(Tenant::where('status', 'active')->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->live()
                            ->rules([
                                fn (callable $get, ?Invoice $record): Closure => function (string $attribute, $value, Closure $fail) use ($get, $record) {
                                    $month = (int) ($get('period_month') ?? now()->month);
                                    $year = (int) ($get('period_year') ?? now()->year);

                                    $exists = Invoice::where('tenant_id', $value)
                                        ->where('period_month', $month)
                                        ->where('period_year', $year)
                                        ->when($record, fn ($query) => $query->where('id', '!=', $record->id))
                                        ->exists();

                                    if ($exists) {
                                        $fail("Tagihan untuk penghuni ini pada periode {$month}/{$year} sudah ada.");
                                    }
                                },
                            ])
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                if ($state) {
                                    $tenant = Tenant::find($state);
                                    if ($tenant) {
                                        $set('room_id', $tenant->room_id);
                                        $set('base_amount', (int) $tenant->monthly_price);
                                        static::calculateTotal($get, $set);

                                        $month = (int) ($get('period_month') ?? now()->month);
                                        $year = (int) ($get('period_year') ?? now()->year);
                                        $dueDay = $tenant->due_day ?? 5;
                                        $set('due_date', InvoiceService::calculateDueDate($month, $year, $dueDay)->toDateString());

                                        if (! $get('invoice_number')) {
                                            $set('invoice_number', Invoice::generateInvoiceNumber($month, $year));
                                        }
                                    }
                                }
                            }),
                        Forms\Components\Select::make('room_id')
                            ->label('Kamar')
                            ->options(Room::pluck('room_number', 'id'))
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('invoice_number')
                            ->label('Nomor Tagihan')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('period_month')
                                    ->label('Bulan')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(12)
                                    ->default(now()->month)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        $tenantId = $get('tenant_id');
                                        if ($tenantId) {
                                            $tenant = Tenant::find($tenantId);
                                            $month = (int) ($state ?? now()->month);
                                            $year = (int) ($get('period_year') ?? now()->year);
                                            $dueDay = $tenant?->due_day ?? 5;
                                            $set('due_date', InvoiceService::calculateDueDate($month, $year, $dueDay)->toDateString());
                                            $set('invoice_number', Invoice::generateInvoiceNumber($month, $year));
                                        }
                                    }),
                                Forms\Components\TextInput::make('period_year')
                                    ->label('Tahun')
                                    ->required()
                                    ->numeric()
                                    ->minValue(2000)
                                    ->default(now()->year)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        $tenantId = $get('tenant_id');
                                        if ($tenantId) {
                                            $tenant = Tenant::find($tenantId);
                                            $month = (int) ($get('period_month') ?? now()->month);
                                            $year = (int) ($state ?? now()->year);
                                            $dueDay = $tenant?->due_day ?? 5;
                                            $set('due_date', InvoiceService::calculateDueDate($month, $year, $dueDay)->toDateString());
                                            $set('invoice_number', Invoice::generateInvoiceNumber($month, $year));
                                        }
                                    }),
                            ]),
                    ]),

                Section::make('Rincian Biaya')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('base_amount')
                                    ->label('Sewa Pokok')
                                    ->required()
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, callable $get, callable $set) => static::calculateTotal($get, $set)),
                                Forms\Components\TextInput::make('electricity_amount')
                                    ->label('Listrik')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('Rp')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, callable $get, callable $set) => static::calculateTotal($get, $set)),
                                Forms\Components\TextInput::make('water_amount')
                                    ->label('Air')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('Rp')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, callable $get, callable $set) => static::calculateTotal($get, $set)),
                                Forms\Components\TextInput::make('other_amount')
                                    ->label('Lain-lain')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('Rp')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, callable $get, callable $set) => static::calculateTotal($get, $set)),
                            ]),
                        Forms\Components\TextInput::make('discount_amount')
                            ->label('Diskon')
                            ->numeric()
                            ->default(0)
                            ->prefix('Rp')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $get, callable $set) => static::calculateTotal($get, $set)),
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->readOnly()
                            ->dehydrated()
                            ->default(0),
                        Forms\Components\DatePicker::make('due_date')
                            ->label('Jatuh Tempo')
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options(InvoiceStatus::class)
                            ->required()
                            ->default(InvoiceStatus::UNPAID),
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Tagihan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tenant.name')
                    ->label('Penghuni')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('room.room_number')
                    ->label('Kamar')
                    ->sortable(),
                TextColumn::make('period')
                    ->label('Periode')
                    ->formatStateUsing(fn ($record) => $record->period_month.'/'.$record->period_year),
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date()
                    ->sortable(),
                SelectColumn::make('status')
                    ->options(InvoiceStatus::class)
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(InvoiceStatus::class),
                SelectFilter::make('period_month')
                    ->label('Bulan')
                    ->options(array_combine(range(1, 12), range(1, 12))),
                SelectFilter::make('period_year')
                    ->label('Tahun')
                    ->options(array_combine(range(2020, now()->year), range(2020, now()->year))),
            ])
            ->actions([
                EditAction::make(),
                Action::make('pay')
                    ->label('Bayar')
                    ->icon('heroicon-o-credit-card')
                    ->color('success')
                    ->hidden(fn ($record) => ! auth()->user()?->can('recordPayment', $record))
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Pembayaran Tagihan')
                    ->modalDescription(fn ($record) => "Tandai tagihan {$record->invoice_number} ({$record->tenant?->name}) sebesar Rp ".number_format($record->total_amount, 0, ',', '.').' sebagai Lunas?')
                    ->modalSubmitActionLabel('Ya, Tandai Lunas')
                    ->form([
                        Forms\Components\Select::make('payment_method')
                            ->label('Metode Pembayaran')
                            ->options(PaymentMethod::class)
                            ->required(),
                        Forms\Components\DateTimePicker::make('paid_at')
                            ->label('Waktu Pembayaran')
                            ->default(now())
                            ->required(),
                        Forms\Components\TextInput::make('payment_note')
                            ->label('Catatan Pembayaran (Opsional)')
                            ->placeholder('Contoh: Transfer BCA, Tunai, dsb.'),
                    ])
                    ->action(function ($record, array $data) {
                        Gate::authorize('recordPayment', $record);

                        $paymentMethod = $data['payment_method'] instanceof PaymentMethod
                            ? $data['payment_method']
                            : PaymentMethod::from((string) $data['payment_method']);

                        app(PaymentService::class)->recordVerifiedPayment(
                            invoice: $record,
                            paymentMethod: $paymentMethod,
                            paidAt: $data['paid_at'],
                            verifiedBy: auth()->id(),
                            notes: $data['payment_note'] ?? null,
                        );

                        Notification::make()
                            ->title("Tagihan {$record->invoice_number} berhasil ditandai Lunas!")
                            ->success()
                            ->send();
                    }),
                Action::make('cancel')
                    ->label('Batal')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->hidden(fn ($record) => ! auth()->user()?->can('cancel', $record))
                    ->action(function ($record) {
                        Gate::authorize('cancel', $record);
                        $record->update(['status' => InvoiceStatus::CANCELLED]);
                        Notification::make()->title('Tagihan dibatalkan')->success()->send();
                    }),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
            'generate' => Pages\GenerateInvoices::route('/generate'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Invoice::where('status', InvoiceStatus::UNPAID)->count();
    }

    public static function getNavigationItems(): array
    {
        $items = parent::getNavigationItems();
        if (auth()->user()?->can('generate', Invoice::class)) {
            $items[] = NavigationItem::make('Buat Tagihan Bulanan')
                ->url(fn (): string => static::getUrl('generate'))
                ->icon('heroicon-o-plus-circle')
                ->group(static::getNavigationGroup())
                ->sort(2);
        }

        return $items;
    }
}
