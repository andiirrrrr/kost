<?php

namespace App\Filament\Admin\Resources\Invoices;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Tenant;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use BackedEnum;
use Carbon\Carbon;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
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
                    ->description('Pilih penghuni dan periode tagihan. Kamar serta jatuh tempo mengikuti data sewa aktif.')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        Forms\Components\Select::make('tenant_id')
                            ->label('Penghuni')
                            ->options(Tenant::where('status', 'active')->pluck('name', 'id'))
                            ->required()
                            ->disabled(fn (?Invoice $record): bool => $record !== null)
                            ->searchable()
                            ->live()
                            ->rules([
                                fn (callable $get, ?Invoice $record): Closure => function (string $attribute, $value, Closure $fail) use ($get, $record) {
                                    $month = (int) ($get('period_month') ?? now()->month);
                                    $year = (int) ($get('period_year') ?? now()->year);

                                    $exists = Invoice::withTrashed()->where('tenant_id', $value)
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
                                    $tenant = Tenant::with('room.roomCategory')->find($state);
                                    if ($tenant) {
                                        $set('room_id', $tenant->room_id);
                                        $set('base_amount', InvoiceService::resolveBaseAmount($tenant));
                                        static::calculateTotal($get, $set);

                                        $month = (int) ($get('period_month') ?? now()->month);
                                        $year = (int) ($get('period_year') ?? now()->year);
                                        $set('due_date', InvoiceService::calculateTenantDueDate($tenant, $month, $year)->toDateString());

                                        if (! $get('invoice_number')) {
                                            $set('invoice_number', Invoice::generateInvoiceNumber($month, $year));
                                        }
                                    }
                                }
                            })
                            ->columnSpanFull(),
                        Forms\Components\Select::make('room_id')
                            ->label('Kamar')->hidden(),
                        Forms\Components\TextInput::make('invoice_number')
                            ->label('Nomor Tagihan')->hidden(),
                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                        ])
                            ->schema([
                                Forms\Components\Select::make('period_month')
                                    ->label('Bulan')
                                    ->required()
                                    ->disabled(fn (?Invoice $record): bool => $record !== null)
                                    ->options(collect(range(1, 12))->mapWithKeys(fn (int $month): array => [$month => Carbon::create()->month($month)->locale('id')->translatedFormat('F')])->all())
                                    ->native(false)
                                    ->default(now()->month)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        $tenantId = $get('tenant_id');
                                        if ($tenantId) {
                                            $tenant = Tenant::find($tenantId);
                                            $month = (int) ($state ?? now()->month);
                                            $year = (int) ($get('period_year') ?? now()->year);
                                            $set('due_date', InvoiceService::calculateTenantDueDate($tenant, $month, $year)->toDateString());
                                            $set('invoice_number', Invoice::generateInvoiceNumber($month, $year));
                                        }
                                    }),
                                Forms\Components\Select::make('period_year')
                                    ->label('Tahun')
                                    ->required()
                                    ->disabled(fn (?Invoice $record): bool => $record !== null)
                                    ->options(collect(range(now()->year - 1, now()->year + 1))->mapWithKeys(fn (int $year): array => [$year => $year])->all())
                                    ->native(false)
                                    ->default(now()->year)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        $tenantId = $get('tenant_id');
                                        if ($tenantId) {
                                            $tenant = Tenant::find($tenantId);
                                            $month = (int) ($get('period_month') ?? now()->month);
                                            $year = (int) ($state ?? now()->year);
                                            $set('due_date', InvoiceService::calculateTenantDueDate($tenant, $month, $year)->toDateString());
                                            $set('invoice_number', Invoice::generateInvoiceNumber($month, $year));
                                        }
                                    }),
                            ])
                            ->columnSpanFull(),
                    ]),

                Section::make('Rincian Biaya')
                    ->description('Isi biaya tambahan atau diskon. Total dihitung otomatis oleh sistem.')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                        ])
                            ->schema([
                                Forms\Components\TextInput::make('base_amount')
                                    ->label('Sewa Pokok')->hidden(),
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
                            ])
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('discount_amount')
                            ->label('Diskon')
                            ->numeric()
                            ->default(0)
                            ->prefix('Rp')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $get, callable $set) => static::calculateTotal($get, $set)),
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total Otomatis')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->readOnly()
                            ->dehydrated()
                            ->default(0),
                        Forms\Components\DatePicker::make('due_date')
                            ->label('Jatuh Tempo Otomatis')
                            ->readOnly(),
                        Forms\Components\Select::make('status')
                            ->options(InvoiceStatus::class)->hidden(),
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
                    ->visibleFrom('md')
                    ->sortable(),
                TextColumn::make('period')
                    ->label('Periode')
                    ->formatStateUsing(fn ($record) => $record->period_month.'/'.$record->period_year),
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('IDR')
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date()
                    ->visibleFrom('lg')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (InvoiceStatus $state): string => $state->label())
                    ->color(fn (InvoiceStatus $state): string => $state->getColor())
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->stackedOnMobile()
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
                ActionGroup::make([
                    Action::make('synchronize')
                        ->label('Sinkronkan Tagihan')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->hidden()
                        ->requiresConfirmation()
                        ->modalHeading('Sinkronkan tagihan dengan data terbaru?')
                        ->modalDescription(function (Invoice $record): string {
                            $record->loadMissing(['tenant', 'room.roomCategory']);
                            $newBase = (int) ($record->room?->roomCategory?->base_monthly_price ?? $record->room?->monthly_price ?? $record->base_amount);
                            $newDueDate = InvoiceService::calculateTenantDueDate($record->tenant, $record->period_month, $record->period_year);

                            return 'Sewa pokok: Rp '.number_format($record->base_amount, 0, ',', '.').' → Rp '.number_format($newBase, 0, ',', '.').'. Jatuh tempo: '.$record->due_date->format('d/m/Y').' → '.$newDueDate->format('d/m/Y').'. Biaya listrik, air, biaya lain, diskon, kamar, dan histori pembayaran tetap dipertahankan.';
                        })
                        ->modalSubmitActionLabel('Ya, sinkronkan')
                        ->action(function (Invoice $record): void {
                            Gate::authorize('update', $record);
                            app(InvoiceService::class)->synchronizeInvoice($record);
                            Notification::make()->title("Tagihan {$record->invoice_number} berhasil disinkronkan")->success()->send();
                        }),
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
                            Forms\Components\Select::make('payment_method_id')
                                ->label('Metode Pembayaran')
                                ->options(PaymentMethod::query()->where('is_active', true)->orderBy('sort_order')->pluck('name', 'id'))
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

                            $paymentMethod = PaymentMethod::query()->where('is_active', true)->findOrFail($data['payment_method_id']);

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
                    DeleteAction::make()
                        ->hidden(fn (Invoice $record): bool => $record->payments()->exists())
                        ->modalDescription('Tagihan tanpa riwayat pembayaran akan dipindahkan ke arsip.'),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->iconButton()
                    ->tooltip('Aksi')
                    ->color('gray'),
            ])
            ->bulkActions([]);
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
}
