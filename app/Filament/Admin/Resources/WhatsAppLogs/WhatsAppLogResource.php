<?php

namespace App\Filament\Admin\Resources\WhatsAppLogs;

use App\Enums\WhatsAppStatus;
use App\Filament\Admin\Resources\WhatsAppLogs\Pages\ListWhatsAppLogs;
use App\Models\WhatsAppLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WhatsAppLogResource extends Resource
{
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('whatsapp.view') ?? false;
    }

    protected static ?string $model = WhatsAppLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Riwayat WhatsApp';

    protected static string|\UnitEnum|null $navigationGroup = 'Komunikasi';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Riwayat WhatsApp';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('tenant.name')->label('Penerima')->searchable(),
                TextColumn::make('phone')->label('Nomor')->formatStateUsing(fn (string $state): string => '******'.substr($state, -4))->visibleFrom('md'),
                TextColumn::make('template_name')->label('Jenis Pesan')->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (WhatsAppStatus $state): string => $state->label())
                    ->color(fn (WhatsAppStatus $state): string => $state->color()),
                TextColumn::make('attempts')->label('Jumlah Percobaan')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sent_at')->label('Terkirim')->dateTime('d M Y H:i')->visibleFrom('md'),
                TextColumn::make('error_message')->label('Keterangan Jika Gagal')->limit(50)->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y H:i')->visibleFrom('lg')->sortable(),
            ])
            ->stackedOnMobile()
            ->filters([
                SelectFilter::make('status')->label('Status')->options(
                    collect(WhatsAppStatus::cases())->mapWithKeys(fn (WhatsAppStatus $status): array => [$status->value => $status->label()]),
                ),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListWhatsAppLogs::route('/')];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) WhatsAppLog::query()->where('status', WhatsAppStatus::FAILED)->count();
    }
}
