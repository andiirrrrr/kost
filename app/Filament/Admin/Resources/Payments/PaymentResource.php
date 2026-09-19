<?php

namespace App\Filament\Admin\Resources\Payments;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Setting;
use App\Services\PaymentService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationLabel = 'Pembayaran';

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 2;

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
                Section::make('Tagihan & Penghuni')
                    ->description('Pilih tagihan yang akan dibayar. Data penghuni dan nominal mengikuti tagihan tersebut.')
                    ->columnSpanFull()
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        Forms\Components\Select::make('invoice_id')
                            ->label('Tagihan')
                            ->options(Invoice::query()->with('tenant')->where('status', '!=', InvoiceStatus::PAID)->get()->mapWithKeys(fn (Invoice $invoice): array => [$invoice->id => $invoice->tenant->name.' · '.$invoice->invoice_number.' · Rp '.number_format($invoice->total_amount, 0, ',', '.')]))
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
                            })
                            ->columnSpanFull(),
                        Forms\Components\Select::make('tenant_id')
                            ->label('Penghuni')->hidden(),
                        Forms\Components\TextInput::make('payment_number')
                            ->label('Nomor Pembayaran')->hidden(),
                    ]),
                Section::make('Detail Pembayaran')
                    ->description('Catat nominal, metode, dan waktu pembayaran.')
                    ->columnSpanFull()
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        Forms\Components\TextInput::make('amount')
                            ->label('Jumlah Sesuai Tagihan')
                            ->numeric()
                            ->prefix('Rp')
                            ->readOnly(),
                        Forms\Components\Select::make('payment_method_id')
                            ->label('Metode')
                            ->options(PaymentMethod::query()->where('is_active', true)->orderBy('sort_order')->pluck('name', 'id'))
                            ->required(),
                        Forms\Components\DateTimePicker::make('paid_at')
                            ->label('Tanggal Bayar')
                            ->required()
                            ->default(now()),
                        Forms\Components\Hidden::make('status')
                            ->default(PaymentStatus::PENDING),
                    ]),
                Section::make('Bukti & Catatan')
                    ->description('Lampirkan bukti pembayaran dan informasi tambahan bila diperlukan.')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\FileUpload::make('proof')
                            ->label('Bukti')
                            ->disk('local')
                            ->directory('payments/proofs')
                            ->maxSize(5120) // 5MB
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                            ->preserveFilenames(false)
                            ->visibility('private')
                            ->columnSpanFull(),
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
                    ->visibleFrom('md')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tenant.name')
                    ->label('Penghuni')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('method.name')
                    ->label('Metode')
                    ->visibleFrom('lg')
                    ->formatStateUsing(fn ($state, Payment $record): string => $state ?: str($record->payment_method)->replace('_', ' ')->title()->toString()),
                TextColumn::make('paid_at')
                    ->label('Tanggal Bayar')
                    ->dateTime()
                    ->visibleFrom('md')
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
            ->stackedOnMobile()
            ->filters([
                SelectFilter::make('status')
                    ->options(PaymentStatus::class),
                SelectFilter::make('payment_method')
                    ->label('Metode')
                    ->options(PaymentMethod::withTrashed()->orderBy('sort_order')->pluck('name', 'code')),
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
                ActionGroup::make([
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
                        ->modalHeading(fn (Payment $record): string => 'Bukti '.$record->payment_number)
                        ->modalContent(fn (Payment $record): View => view('filament.admin.payments.proof-preview', ['payment' => $record]))
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Tutup')
                        ->modalWidth(Width::FiveExtraLarge)
                        ->visible(fn (Payment $record): bool => filled($record->proof)),
                    Action::make('receipt')
                        ->label('Kwitansi')
                        ->icon('heroicon-o-printer')
                        ->url(fn (Payment $record): string => route('payments.receipt', $record))
                        ->openUrlInNewTab()
                        ->visible(fn (Payment $record): bool => $record->status === PaymentStatus::VERIFIED),
                    Action::make('send_receipt_whatsapp')
                        ->label('Kirim WA')
                        ->icon('heroicon-o-chat-bubble-left-ellipsis')
                        ->color('success')
                        ->url(function (Payment $record): ?string {
                            $phone = $record->tenant?->phone;
                            if (blank($phone)) {
                                return null;
                            }

                            $phoneDigits = preg_replace('/[^0-9]/', '', (string) $phone);
                            if (str_starts_with($phoneDigits, '0')) {
                                $phoneDigits = '62'.substr($phoneDigits, 1);
                            }

                            $tenantName = $record->tenant?->name ?? 'Penghuni';
                            $roomNumber = $record->tenant?->room?->room_number ?? '—';
                            $paymentNumber = $record->payment_number;
                            $amount = 'Rp '.number_format($record->amount, 0, ',', '.');
                            $businessName = Setting::get('business_name', 'Kost');
                            $receiptUrl = URL::signedRoute('payments.receipt', ['payment' => $record]);

                            $message = "Halo {$tenantName},\n\n"
                                ."Terima kasih, pembayaran sewa Anda telah kami terima dan diverifikasi *LUNAS*:\n"
                                ."• Kamar: {$roomNumber}\n"
                                ."• No. Pembayaran: {$paymentNumber}\n"
                                ."• Jumlah: {$amount}\n\n"
                                ."Kwitansi resmi dapat Anda akses dan unduh pada tautan berikut:\n"
                                ."{$receiptUrl}\n\n"
                                ."Salam hangat,\n"
                                ."Pengelola {$businessName}";

                            return 'https://wa.me/'.$phoneDigits.'?text='.rawurlencode($message);
                        })
                        ->openUrlInNewTab()
                        ->visible(fn (Payment $record): bool => $record->status === PaymentStatus::VERIFIED && filled($record->tenant?->phone)),
                    DeleteAction::make()
                        ->hidden(fn ($record) => $record->status === PaymentStatus::VERIFIED),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->iconButton()
                    ->tooltip('Aksi')
                    ->color('gray'),
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
