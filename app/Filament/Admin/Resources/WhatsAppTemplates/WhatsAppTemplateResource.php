<?php

namespace App\Filament\Admin\Resources\WhatsAppTemplates;

use App\Filament\Admin\Resources\WhatsAppTemplates\Pages\CreateWhatsAppTemplate;
use App\Filament\Admin\Resources\WhatsAppTemplates\Pages\EditWhatsAppTemplate;
use App\Filament\Admin\Resources\WhatsAppTemplates\Pages\ListWhatsAppTemplates;
use App\Models\WhatsAppTemplate;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WhatsAppTemplateResource extends Resource
{
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
            Section::make('Templat Meta WhatsApp')
                ->columns(2)
                ->schema([
                    TextInput::make('display_name')->label('Nama Tampilan')->required()->maxLength(255),
                    TextInput::make('template_name')
                        ->label('Nama Templat Meta')
                        ->helperText('Harus sama dengan nama templat yang telah disetujui di Meta.')
                        ->required()
                        ->alphaDash()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                    Select::make('category')
                        ->label('Kategori')
                        ->options(['utility' => 'Layanan', 'marketing' => 'Pemasaran'])
                        ->required()
                        ->default('utility'),
                    TextInput::make('language')->label('Kode Bahasa')->required()->default('id')->maxLength(10),
                    TagsInput::make('variables')
                        ->label('Urutan Variabel')
                        ->helperText('Contoh: nama, kamar, periode, total, jatuh_tempo.')
                        ->columnSpanFull(),
                    Textarea::make('content')
                        ->label('Pratinjau Konten')
                        ->helperText('Gunakan penanda seperti {{nama}}. Pengiriman API tetap memakai templat resmi Meta.')
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
                TextColumn::make('template_name')->label('Nama Templat Meta')->copyable()->searchable(),
                TextColumn::make('category')->label('Kategori')->badge()->formatStateUsing(fn (string $state): string => match ($state) {
                    'utility' => 'Layanan',
                    'marketing' => 'Pemasaran',
                    default => $state,
                }),
                TextColumn::make('language')->label('Bahasa'),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
                TextColumn::make('updated_at')->label('Diperbarui')->since(),
            ])
            ->filters([
                SelectFilter::make('category')->label('Kategori')->options(['utility' => 'Layanan', 'marketing' => 'Pemasaran']),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->requiresConfirmation(),
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
}
