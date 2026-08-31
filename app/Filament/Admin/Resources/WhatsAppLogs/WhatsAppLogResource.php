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
    protected static ?string $model = WhatsAppLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Log WhatsApp';

    protected static string|\UnitEnum|null $navigationGroup = 'Komunikasi';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Log WhatsApp';

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
                TextColumn::make('phone')->label('Nomor')->formatStateUsing(fn (string $state): string => '******'.substr($state, -4)),
                TextColumn::make('template_name')->label('Templat')->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (WhatsAppStatus $state): string => $state->label())
                    ->color(fn (WhatsAppStatus $state): string => $state->color()),
                TextColumn::make('attempts')->label('Percobaan'),
                TextColumn::make('sent_at')->label('Terkirim')->dateTime('d M Y H:i'),
                TextColumn::make('error_message')->label('Pesan Kesalahan')->limit(50)->toggleable(),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y H:i')->sortable(),
            ])
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
