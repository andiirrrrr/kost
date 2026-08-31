<?php

namespace App\Filament\Admin\Resources\Payments;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Services\PaymentService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationLabel = 'Pembayaran';

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Pembayaran';

    protected static ?string $pluralModelLabel = 'Pembayaran';

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-credit-card';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Pembayaran')
                    ->schema([
                        Forms\Components\Select::make('invoice_id')
                            ->label('Tagihan')
                            ->options(Invoice::where('status', '!=', InvoiceStatus::PAID)->pluck('invoice_number', 'id'))
                            ->required()
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $invoice = Invoice::find($state);
                                    if ($invoice) {
                                        $set('tenant_id', $invoice->tenant_id);
                                        $set('amount', $invoice->total_amount);
                                    }
                                }
                            }),
                        Forms\Components\Select::make('tenant_id')
                            ->label('Penghuni')
                            ->options(Tenant::pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\TextInput::make('payment_number')
                            ->label('Nomor Pembayaran')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->default(Payment::generatePaymentNumber()),
                        Forms\Components\TextInput::make('amount')
                            ->label('Jumlah')
                            ->required()
                            ->numeric()
                            ->prefix('Rp'),
                        Forms\Components\Select::make('payment_method')
                            ->label('Metode')
                            ->options(PaymentMethod::class)
                            ->required(),
                        Forms\Components\DateTimePicker::make('paid_at')
                            ->label('Tanggal Bayar')
                            ->required()
                            ->default(now()),
                        Forms\Components\FileUpload::make('proof')
                            ->label('Bukti')
                            ->disk('local')
                            ->directory('payments/proofs')
                            ->maxSize(5120) // 5MB
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                            ->preserveFilenames(false)
                            ->visibility('private'),
                        Forms\Components\Hidden::make('status')
                            ->default(PaymentStatus::PENDING),
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
                TextColumn::make('payment_number')
                    ->label('No. Pembayaran')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice.invoice_number')
                    ->label('Tagihan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tenant.name')
                    ->label('Penghuni')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->label('Metode')
                    ->formatStateUsing(function ($state): string {
                        $method = $state instanceof PaymentMethod
                            ? $state
                            : PaymentMethod::tryFrom((string) $state);

                        return $method?->label() ?? (string) $state;
                    }),
                TextColumn::make('paid_at')
                    ->label('Tanggal Bayar')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (PaymentStatus $state): string => $state->label())
                    ->color(fn (PaymentStatus $state): string => $state->color())
                    ->sortable(),
                TextColumn::make('verifier.name')
                    ->label('Diverifikasi Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('verified_at')
                    ->label('Diverifikasi Pada')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(PaymentStatus::class),
                SelectFilter::make('payment_method')
                    ->label('Metode')
                    ->options(PaymentMethod::class),
                Tables\Filters\Filter::make('paid_at')
                    ->label('Tanggal Pembayaran')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Dari'),
                        Forms\Components\DatePicker::make('to')->label('Sampai'),
                    ])
                    ->query(function ($query, array $data) {
                        if ($data['from'] ?? false) {
                            $query->whereDate('paid_at', '>=', $data['from']);
                        }
                        if ($data['to'] ?? false) {
                            $query->whereDate('paid_at', '<=', $data['to']);
                        }
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->hidden(fn ($record) => $record->status !== PaymentStatus::PENDING),
                Action::make('verify')
                    ->label('Verifikasi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Verifikasi')
                    ->modalDescription(fn ($record) => 'Konfirmasi pembayaran Rp '.number_format($record->amount, 0, ',', '.')." dari {$record->tenant->name} untuk tagihan {$record->invoice->invoice_number}?")
                    ->modalSubmitActionLabel('Ya, Verifikasi')
                    ->hidden(fn ($record) => ! auth()->user()?->can('verify', $record))
                    ->action(function ($record) {
                        try {
                            Gate::authorize('verify', $record);
                            $service = app(PaymentService::class);
                            $service->verifyPayment($record, Auth::id());
                            Notification::make()
                                ->title('Pembayaran berhasil diverifikasi')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal verifikasi: '.$e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->modalHeading('Konfirmasi Penolakan')
                    ->modalSubmitActionLabel('Ya, Tolak')
                    ->schema([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->hidden(fn ($record) => ! auth()->user()?->can('reject', $record))
                    ->action(function ($record, array $data) {
                        try {
                            Gate::authorize('reject', $record);
                            $service = app(PaymentService::class);
                            $service->rejectPayment($record, $data['rejection_reason']);
                            Notification::make()
                                ->title('Pembayaran ditolak')
                                ->warning()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal menolak: '.$e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('proof')
                    ->label('Lihat Bukti')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->url(fn (Payment $record): string => route('tenant.payments.proof', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (Payment $record): bool => filled($record->proof)),
                DeleteAction::make()
                    ->hidden(fn ($record) => $record->status === PaymentStatus::VERIFIED),
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
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Payment::where('status', PaymentStatus::PENDING)->count();
    }
}
