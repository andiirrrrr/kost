<?php

namespace App\Filament\Admin\Resources\WhatsAppTemplates;

use App\Filament\Admin\Resources\WhatsAppTemplates\Pages\CreateWhatsAppTemplate;
use App\Filament\Admin\Resources\WhatsAppTemplates\Pages\EditWhatsAppTemplate;
use App\Filament\Admin\Resources\WhatsAppTemplates\Pages\ListWhatsAppTemplates;
use App\Models\WhatsAppTemplate;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WhatsAppTemplateResource extends Resource
{
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('whatsapp.manage_templates') ?? false;
    }

    protected static ?string $model = WhatsAppTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Templat WhatsApp';

    protected static string|\UnitEnum|null $navigationGroup = 'Komunikasi';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Templat WhatsApp';

    protected static ?string $pluralModelLabel = 'Templat WhatsApp';

    protected static ?string $recordTitleAttribute = 'display_name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Isi Pesan WhatsApp')
                ->description('Ubah nama dan contoh isi pesan. Pengaturan teknis dikelola otomatis oleh sistem.')
                ->columns([
                    'default' => 1,
                    'md' => 2,
                ])
                ->schema([
                    TextInput::make('display_name')->label('Nama Pesan')->required()->maxLength(255),
                    TextInput::make('template_name')
                        ->label('Nama Sistem')->hidden(),
                    Textarea::make('content')
                        ->label('Contoh Isi Pesan')
                        ->helperText('Bagian seperti {{nama}} dan {{total}} akan diisi otomatis ketika pesan dikirim.')
                        ->required()
                        ->rows(5)
                        ->columnSpanFull(),
                    Toggle::make('is_active')->label('Aktif')->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('display_name')->label('Templat')->searchable()->sortable(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
                TextColumn::make('updated_at')->label('Diperbarui')->since(),
            ])
            ->stackedOnMobile()
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWhatsAppTemplates::route('/'),
            'create' => CreateWhatsAppTemplate::route('/create'),
            'edit' => EditWhatsAppTemplate::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
