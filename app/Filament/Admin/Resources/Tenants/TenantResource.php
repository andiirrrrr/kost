<?php

namespace App\Filament\Admin\Resources\Tenants;

use App\Enums\TenantStatus;
use App\Filament\Admin\Resources\Tenants\Pages\CreateTenant;
use App\Filament\Admin\Resources\Tenants\Pages\EditTenant;
use App\Filament\Admin\Resources\Tenants\Pages\ListTenants;
use App\Models\Room;
use App\Models\Tenant;
use App\Services\TenantAccountService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationLabel = 'Penghuni';

    protected static string|\UnitEnum|null $navigationGroup = 'Pengelolaan Kost';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Penghuni';

    protected static ?string $pluralModelLabel = 'Penghuni';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-users';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('room_id')
                    ->label('Kamar')
                    ->options(Room::pluck('room_number', 'id'))
                    ->required()
                    ->searchable(),
                Forms\Components\TextInput::make('name')
                    ->label('Nama Lengkap')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->label('Nomor WhatsApp')
                    ->required()
                    ->maxLength(20)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->maxLength(255),
                Forms\Components\TextInput::make('identity_number')
                    ->label('Nomor Identitas')
                    ->maxLength(255),
                Forms\Components\Textarea::make('address')
                    ->label('Alamat')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('emergency_contact')
                    ->label('Kontak Darurat')
                    ->maxLength(255),
                Forms\Components\DatePicker::make('move_in_date')
                    ->label('Tanggal Masuk')
                    ->required(),
                Forms\Components\DatePicker::make('move_out_date')
                    ->label('Tanggal Keluar'),
                Forms\Components\TextInput::make('monthly_price')
                    ->label('Harga Bulanan')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
                Forms\Components\TextInput::make('due_day')
                    ->label('Jatuh Tempo (tanggal)')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(31)
                    ->default(5),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options(TenantStatus::class)
                    ->required(),
                Forms\Components\Textarea::make('notes')
                    ->label('Catatan')
                    ->columnSpanFull(),
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
                    ->searchable(),
                TextColumn::make('room.room_number')
                    ->label('Kamar')
                    ->sortable(),
                TextColumn::make('monthly_price')
                    ->label('Harga Bulanan')
                    ->money('IDR')
                    ->sortable(),
                SelectColumn::make('status')
                    ->label('Status')
                    ->options(TenantStatus::class),
                TextColumn::make('move_in_date')
                    ->label('Tanggal Masuk')
                    ->date()
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
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(TenantStatus::class),
                SelectFilter::make('room_id')
                    ->label('Kamar')
                    ->options(Room::pluck('room_number', 'id')),
            ])
            ->actions([
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
                        Forms\Components\TextInput::make('password')
                            ->label('Password Sementara (opsional)')
                            ->password()
                            ->revealable()
                            ->minLength(12)
                            ->confirmed()
                            ->helperText('Kosongkan untuk menghasilkan password acak yang aman.'),
                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('Konfirmasi Password')
                            ->password()
                            ->revealable(),
                    ])
                    ->action(function (Tenant $record, array $data): void {
                        $result = app(TenantAccountService::class)->create(
                            $record,
                            $data['email'],
                            $data['password'] ?? null,
                        );

                        Notification::make()
                            ->title('Akun penghuni berhasil dibuat')
                            ->body("Email: {$result['user']->email}\nPassword sementara: {$result['temporary_password']}")
                            ->success()
                            ->persistent()
                            ->send();
                    }),
                EditAction::make(),
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
            'index' => ListTenants::route('/'),
            'create' => CreateTenant::route('/create'),
            'edit' => EditTenant::route('/{record}/edit'),
        ];
    }
}
