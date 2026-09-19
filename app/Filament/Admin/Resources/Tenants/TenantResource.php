<?php

namespace App\Filament\Admin\Resources\Tenants;

use App\Enums\RoomStatus;
use App\Enums\TenantStatus;
use App\Filament\Admin\Resources\Tenants\Pages\CreateTenant;
use App\Filament\Admin\Resources\Tenants\Pages\EditTenant;
use App\Filament\Admin\Resources\Tenants\Pages\ListTenants;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\Tenant;
use App\Services\TenantAccountService;
use App\Services\TenantLifecycleService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationLabel = 'Penghuni';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Penghuni';

    protected static ?string $pluralModelLabel = 'Penghuni';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Data Pribadi')
                    ->description('Identitas utama penghuni dan dokumen pendukung.')
                    ->columnSpanFull()
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        Forms\Components\TextInput::make('name')->label('Nama Lengkap')->required()->maxLength(255),
                        Forms\Components\TextInput::make('identity_number')->label('Nomor Identitas / NIK')->maxLength(255),
                        Forms\Components\FileUpload::make('identity_document')
                            ->label('KTP / Dokumen Identitas')
                            ->disk('local')
                            ->directory('tenants/identity-documents')
                            ->visibility('private')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                            ->maxSize(5120)
                            ->downloadable()
                            ->helperText('JPG, PNG, WEBP, atau PDF. Maksimal 5 MB.')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('address')->label('Alamat')->rows(3)->columnSpanFull(),
                    ]),
                Section::make('Kontak')
                    ->description('Informasi komunikasi penghuni dan kontak darurat.')
                    ->columnSpanFull()
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        Forms\Components\TextInput::make('phone')->label('Nomor WhatsApp')->required()->maxLength(20)->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('email')->label('Email')->email()->maxLength(255),
                        Forms\Components\TextInput::make('emergency_contact')->label('Kontak Darurat')->maxLength(255)->columnSpanFull(),
                    ]),
                Section::make('Data Kamar')
                    ->description('Pilih kategori terlebih dahulu. Harga akan mengikuti kamar yang dipilih.')
                    ->columnSpanFull()
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        Forms\Components\Select::make('room_category_id')
                            ->label('Kategori Kamar')
                            ->options(fn (): array => RoomCategory::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->pluck('name', 'id')->all())
                            ->default(fn (?Tenant $record): ?int => $record?->room?->room_category_id)
                            ->dehydrated(false)
                            ->disabled(fn (?Tenant $record): bool => $record !== null)
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set): void {
                                $category = RoomCategory::query()->find($state);
                                $set('room_id', null);
                                $set('monthly_price', $category?->base_monthly_price);
                            }),
                        Forms\Components\Select::make('room_id')
                            ->label('Nomor Kamar')
                            ->options(fn (callable $get, ?Tenant $record): array => Room::query()
                                ->where('room_category_id', $get('room_category_id'))
                                ->where(function ($query) use ($record): void {
                                    $query->where('status', RoomStatus::AVAILABLE)
                                        ->when($record, fn ($query) => $query->orWhereKey($record->room_id));
                                })
                                ->orderBy('room_number')
                                ->get()
                                ->mapWithKeys(fn (Room $room): array => [$room->id => $room->room_number.' · Rp '.number_format((float) $room->monthly_price, 0, ',', '.')])
                                ->all())
                            ->required()
                            ->disabled(fn (?Tenant $record): bool => $record !== null)
                            ->searchable()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set): void {
                                $set('monthly_price', Room::query()->find($state)?->monthly_price);
                            })
                            ->helperText('Hanya kamar yang masih tersedia pada kategori ini yang ditampilkan.'),
                        Forms\Components\TextInput::make('monthly_price')
                            ->label('Harga Bulanan')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->readOnly()
                            ->disabled(fn (?Tenant $record): bool => $record !== null)
                            ->helperText('Terisi otomatis dari harga kamar.')
                            ->columnSpanFull(),
                    ]),
                Section::make('Status Akun')
                    ->description('Atur periode sewa dan catatan internal penghuni.')
                    ->columnSpanFull()
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        Forms\Components\DatePicker::make('move_in_date')
                            ->label('Tanggal Masuk')
                            ->required()
                            ->default(now())
                            ->helperText('Tanggal mendatang akan disimpan sebagai jadwal dan kamar otomatis dipesan.')
                            ->disabled(fn (?Tenant $record): bool => $record !== null),
                        Forms\Components\DatePicker::make('move_out_date')->label('Tanggal Keluar')->disabled()->dehydrated(false),
                        Forms\Components\Hidden::make('status')->default(TenantStatus::ACTIVE),
                        Forms\Components\Textarea::make('notes')->label('Catatan')->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Nomor WhatsApp')
                    ->visibleFrom('md')
                    ->searchable(),
                TextColumn::make('room.room_number')
                    ->label('Kamar')
                    ->sortable(),
                TextColumn::make('monthly_price')
                    ->label('Harga Bulanan')
                    ->money('IDR')
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (TenantStatus $state): string => $state->label())
                    ->color(fn (TenantStatus $state): string => $state->getColor()),
                TextColumn::make('move_in_date')
                    ->label('Tanggal Masuk')
                    ->date()
                    ->visibleFrom('lg')
                    ->sortable(),
                TextColumn::make('move_out_date')
                    ->label('Tanggal Keluar')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->stackedOnMobile()
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(TenantStatus::class),
                SelectFilter::make('room_id')
                    ->label('Kamar')
                    ->options(Room::pluck('room_number', 'id')),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('transferRoom')
                        ->label('Pindah kamar')
                        ->icon('heroicon-o-arrows-right-left')
                        ->color('warning')
                        ->visible(fn (Tenant $record): bool => $record->status === TenantStatus::ACTIVE && $record->move_out_date === null && $record->scheduled_room_id === null)
                        ->schema([
                            Forms\Components\Select::make('room_category_id')
                                ->label('Kategori Kamar')
                                ->options(fn (): array => RoomCategory::query()->where('is_active', true)->whereHas('rooms', fn ($query) => $query->where('status', RoomStatus::AVAILABLE))->orderBy('name')->pluck('name', 'id')->all())
                                ->live()
                                ->afterStateUpdated(fn (callable $set) => $set('room_id', null))
                                ->required(),
                            Forms\Components\Select::make('room_id')
                                ->label('Nomor Kamar Baru')
                                ->options(fn (callable $get): array => Room::query()->where('room_category_id', $get('room_category_id'))->where('status', RoomStatus::AVAILABLE)->orderBy('room_number')->pluck('room_number', 'id')->all())
                                ->required(),
                            Forms\Components\DatePicker::make('effective_date')
                                ->label('Tanggal efektif pindah')
                                ->default(now())
                                ->helperText('Tanggal mendatang akan menjadi jadwal. Kamar tujuan langsung dipesan dan perpindahan berjalan otomatis pada tanggal tersebut.')
                                ->required(),
                        ])
                        ->action(function (Tenant $record, array $data, Action $action): void {
                            $oldRoomNumber = $record->room->room_number;
                            $newRoom = Room::query()->findOrFail($data['room_id']);

                            try {
                                $tenant = app(TenantLifecycleService::class)->transferRoom($record, $newRoom, $data['effective_date']);
                            } catch (ValidationException $exception) {
                                self::notifyActionFailure($exception, $action, 'Penghuni tidak dapat dipindahkan');
                            }

                            Notification::make()
                                ->title($tenant->scheduled_room_id ? 'Pindah kamar berhasil dijadwalkan' : 'Perpindahan kamar berhasil')
                                ->body($tenant->scheduled_room_id
                                    ? "{$record->name} tetap di {$oldRoomNumber} sampai {$data['effective_date']}. Kamar {$newRoom->room_number} sudah dipesan dan perpindahan akan berjalan otomatis."
                                    : "{$record->name} dipindahkan dari {$oldRoomNumber} ke {$newRoom->room_number} efektif {$data['effective_date']}. Histori dan tagihan lama tetap tersimpan.")
                                ->success()
                                ->send();
                        }),
                    Action::make('cancelScheduledRoomTransfer')
                        ->label('Batalkan jadwal pindah')
                        ->icon('heroicon-o-calendar-days')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalDescription(fn (Tenant $record): string => "Jadwal pindah ke {$record->scheduledRoom?->room_number} pada {$record->scheduled_transfer_date?->translatedFormat('d M Y')} akan dibatalkan.")
                        ->visible(fn (Tenant $record): bool => $record->status === TenantStatus::ACTIVE && $record->scheduled_room_id !== null)
                        ->action(function (Tenant $record, Action $action): void {
                            $roomNumber = $record->scheduledRoom?->room_number;

                            try {
                                app(TenantLifecycleService::class)->cancelScheduledRoomTransfer($record);
                            } catch (ValidationException $exception) {
                                self::notifyActionFailure($exception, $action, 'Jadwal pindah tidak dapat dibatalkan');
                            }

                            Notification::make()
                                ->title('Jadwal pindah dibatalkan')
                                ->body("Kamar {$roomNumber} kembali tersedia. Kamar dan histori aktif penghuni tidak berubah.")
                                ->success()
                                ->send();
                        }),
                    Action::make('checkInAgain')
                        ->label('Check-in kembali')
                        ->icon('heroicon-o-arrow-right-end-on-rectangle')
                        ->color('success')
                        ->visible(fn (Tenant $record): bool => $record->status === TenantStatus::INACTIVE)
                        ->schema([
                            Forms\Components\Select::make('room_category_id')
                                ->label('Kategori Kamar')
                                ->options(fn (): array => RoomCategory::query()
                                    ->where('is_active', true)
                                    ->whereHas('rooms', fn ($query) => $query->where('status', RoomStatus::AVAILABLE))
                                    ->orderBy('sort_order')
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all())
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->live()
                                ->afterStateUpdated(fn (callable $set) => $set('room_id', null))
                                ->required(),
                            Forms\Components\Select::make('room_id')
                                ->label('Nomor Kamar')
                                ->options(fn (callable $get): array => Room::query()
                                    ->where('room_category_id', $get('room_category_id'))
                                    ->where('status', RoomStatus::AVAILABLE)
                                    ->orderBy('room_number')
                                    ->get()
                                    ->mapWithKeys(fn (Room $room): array => [$room->id => $room->room_number.' · Rp '.number_format((float) $room->monthly_price, 0, ',', '.')])
                                    ->all())
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->required(),
                            Forms\Components\DatePicker::make('move_in_date')
                                ->label('Tanggal masuk baru')
                                ->helperText('Jatuh tempo mengikuti tanggal ini. Tanggal mendatang akan menjadi jadwal check-in.')
                                ->required()
                                ->default(now()),
                        ])
                        ->action(function (Tenant $record, array $data, Action $action): void {
                            $room = Room::query()->findOrFail($data['room_id']);

                            try {
                                $tenant = app(TenantLifecycleService::class)->checkInAgain($record, $room, $data['move_in_date']);
                            } catch (ValidationException $exception) {
                                self::notifyActionFailure($exception, $action, 'Check-in tidak dapat diproses');
                            }

                            Notification::make()
                                ->title($tenant->status === TenantStatus::SCHEDULED ? 'Check-in berhasil dijadwalkan' : 'Penghuni berhasil check-in kembali')
                                ->body($tenant->status === TenantStatus::SCHEDULED
                                    ? "{$tenant->name} dijadwalkan masuk kamar {$room->room_number} pada {$data['move_in_date']}. Jatuh tempo tanggal {$tenant->due_day}; portal aktif saat tanggal masuk."
                                    : "{$tenant->name} masuk kamar {$room->room_number} pada {$data['move_in_date']}. Jatuh tempo tanggal {$tenant->due_day}; portal sudah aktif.")
                                ->success()
                                ->send();
                        }),
                    Action::make('cancelScheduledCheckIn')
                        ->label('Batalkan jadwal check-in')
                        ->icon('heroicon-o-calendar-days')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (Tenant $record): bool => $record->status === TenantStatus::SCHEDULED)
                        ->action(function (Tenant $record, Action $action): void {
                            try {
                                app(TenantLifecycleService::class)->cancelScheduledCheckIn($record);
                            } catch (ValidationException $exception) {
                                self::notifyActionFailure($exception, $action, 'Jadwal tidak dapat dibatalkan');
                            }

                            Notification::make()->title('Jadwal check-in dibatalkan')->body('Kamar kembali tersedia dan histori sewa lama tetap tersimpan.')->success()->send();
                        }),
                    Action::make('activateScheduledCheckInNow')
                        ->label('Check-in sekarang')
                        ->icon('heroicon-o-bolt')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalDescription('Tanggal masuk dan jatuh tempo akan diubah menjadi hari ini. Histori serta tagihan lama tetap tersimpan.')
                        ->visible(fn (Tenant $record): bool => $record->status === TenantStatus::SCHEDULED)
                        ->action(function (Tenant $record, Action $action): void {
                            try {
                                app(TenantLifecycleService::class)->activateScheduledCheckInNow($record);
                            } catch (ValidationException $exception) {
                                self::notifyActionFailure($exception, $action, 'Check-in sekarang tidak dapat diproses');
                            }

                            Notification::make()->title('Penghuni berhasil check-in hari ini')->body('Kamar telah terisi dan akses portal sudah aktif.')->success()->send();
                        }),
                    Action::make('checkOut')
                        ->label('Check-out')
                        ->icon('heroicon-o-arrow-right-start-on-rectangle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (Tenant $record): bool => $record->status === TenantStatus::ACTIVE)
                        ->schema([
                            Forms\Components\DatePicker::make('move_out_date')->label('Tanggal keluar')->required()->default(now()),
                        ])
                        ->action(function (Tenant $record, array $data, Action $action): void {
                            $roomNumber = $record->room->room_number;

                            try {
                                app(TenantLifecycleService::class)->checkOut($record, $data['move_out_date']);
                            } catch (ValidationException $exception) {
                                self::notifyActionFailure($exception, $action, 'Check-out tidak dapat diproses');
                            }

                            Notification::make()
                                ->title('Check-out selesai')
                                ->body("{$record->name} keluar dari kamar {$roomNumber} pada {$data['move_out_date']}. Kamar dikosongkan, tagihan berikutnya dihentikan, dan portal dinonaktifkan.")
                                ->success()
                                ->send();
                        }),
                    Action::make('createAccount')
                        ->label('Buat Akun Penghuni')
                        ->icon('heroicon-o-key')
                        ->color('success')
                        ->hidden(fn (Tenant $record): bool => (bool) $record->user_id)
                        ->schema([
                            Forms\Components\TextInput::make('email')
                                ->label('Email Login')
                                ->email()
                                ->required()
                                ->unique('users', 'email')
                                ->default(fn (Tenant $record): ?string => $record->email),
                        ])
                        ->action(function (Tenant $record, array $data): void {
                            $result = app(TenantAccountService::class)->create(
                                $record,
                                $data['email'],
                                null,
                            );

                            $phone = preg_replace('/\D/', '', (string) $record->phone);
                            if (str_starts_with($phone, '0')) {
                                $phone = '62'.substr($phone, 1);
                            }

                            $loginUrl = route('login');
                            $appName = config('app.name', 'Kost');
                            $waMessage = rawurlencode(
                                "Halo {$record->name},\n\n"
                                ."Akun portal {$appName} Anda telah dibuat oleh pengelola.\n\n"
                                ."Detail Login:\n"
                                ."• Link Login: {$loginUrl}\n"
                                ."• Email: {$result['user']->email}\n"
                                ."• Password Sementara: {$result['temporary_password']}\n\n"
                                .'Silakan login dan ganti password Anda demi keamanan. Terima kasih!'
                            );
                            $waUrl = filled($phone) ? "https://wa.me/{$phone}?text={$waMessage}" : null;

                            $notification = Notification::make()
                                ->title('Akun penghuni berhasil dibuat')
                                ->body("Email: {$result['user']->email}\nPassword sementara: {$result['temporary_password']}")
                                ->success()
                                ->persistent();

                            if ($waUrl) {
                                $notification->actions([
                                    Action::make('sendWhatsApp')
                                        ->label('Kirim ke WhatsApp')
                                        ->icon('heroicon-o-chat-bubble-left-ellipsis')
                                        ->url($waUrl, shouldOpenInNewTab: true)
                                        ->button(),
                                ]);
                            }

                            $notification->send();
                        }),
                    Action::make('resetPassword')
                        ->label('Reset Password')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->visible(fn (Tenant $record): bool => (bool) $record->user_id)
                        ->modalHeading(fn (Tenant $record): string => "Reset Password — {$record->name}")
                        ->modalDescription('Anda dapat menentukan password baru secara manual atau mengosongkannya untuk membuat password acak otomatis.')
                        ->modalSubmitActionLabel('Reset Password')
                        ->schema([
                            Forms\Components\TextInput::make('password')
                                ->label('Password Baru (Opsional)')
                                ->placeholder('Kosongkan untuk generate password otomatis')
                                ->password()
                                ->revealable()
                                ->minLength(8)
                                ->maxLength(100)
                                ->helperText('Minimal 8 karakter. Kosongkan jika ingin password acak otomatis.'),
                        ])
                        ->action(function (Tenant $record, array $data): void {
                            $result = app(TenantAccountService::class)->resetPassword(
                                $record,
                                filled($data['password'] ?? null) ? (string) $data['password'] : null,
                            );

                            $phone = preg_replace('/\D/', '', (string) $record->phone);
                            if (str_starts_with($phone, '0')) {
                                $phone = '62'.substr($phone, 1);
                            }

                            $loginUrl = route('login');
                            $appName = config('app.name', 'Kost');
                            $waMessage = rawurlencode(
                                "Halo {$record->name},\n\n"
                                ."Password akun portal {$appName} Anda telah berhasil direset oleh pengelola.\n\n"
                                ."Detail Login Baru:\n"
                                ."• Link Login: {$loginUrl}\n"
                                ."• Email: {$result['user']->email}\n"
                                ."• Password Baru: {$result['password']}\n\n"
                                .'Silakan login dan ganti password Anda secara berkala demi keamanan. Terima kasih!'
                            );
                            $waUrl = filled($phone) ? "https://wa.me/{$phone}?text={$waMessage}" : null;

                            $notification = Notification::make()
                                ->title('Password penghuni berhasil direset')
                                ->body("Email: {$result['user']->email}\nPassword Baru: {$result['password']}")
                                ->success()
                                ->persistent();

                            if ($waUrl) {
                                $notification->actions([
                                    Action::make('sendWhatsApp')
                                        ->label('Kirim ke WhatsApp')
                                        ->icon('heroicon-o-chat-bubble-left-ellipsis')
                                        ->url($waUrl, shouldOpenInNewTab: true)
                                        ->button(),
                                ]);
                            }

                            $notification->send();
                        }),
                    EditAction::make(),
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
            'index' => ListTenants::route('/'),
            'create' => CreateTenant::route('/create'),
            'edit' => EditTenant::route('/{record}/edit'),
        ];
    }

    private static function notifyActionFailure(ValidationException $exception, Action $action, string $title): void
    {
        $message = collect($exception->errors())->flatten()->first() ?? 'Periksa kembali data yang dikirim.';

        Notification::make()
            ->title($title)
            ->body($message)
            ->danger()
            ->persistent()
            ->send();

        $action->halt();
    }
}
