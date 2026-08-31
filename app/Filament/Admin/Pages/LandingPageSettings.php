<?php

namespace App\Filament\Admin\Pages;

use App\Services\LandingPageService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LandingPageSettings extends Page
{
    protected string $view = 'filament.admin.pages.landing-page-settings';

    protected static ?string $navigationLabel = 'Landing Page';

    protected static string|\UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Pengaturan Landing Page';

    protected static ?string $slug = 'landing-page';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-globe-alt';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('owner') ?? false;
    }

    public function mount(LandingPageService $landingPage): void
    {
        abort_unless(static::canAccess(), 403);

        $this->form->fill($landingPage->get());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas & Hero')
                    ->description('Konten utama yang pertama kali dilihat pengunjung.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('business_name')->label('Nama Properti')->required()->maxLength(100),
                        TextInput::make('hero_eyebrow')->label('Label Hero')->required()->maxLength(100),
                        TextInput::make('hero_title')->label('Judul Hero')->required()->maxLength(120),
                        TextInput::make('hero_emphasis')->label('Teks Penekanan')->required()->maxLength(120),
                        Textarea::make('tagline')->label('Deskripsi Hero')->required()->rows(3)->maxLength(500)->columnSpanFull(),
                        $this->imageUpload('hero_image', 'Foto Hero'),
                        $this->imageUpload('about_image', 'Foto Tentang Kami'),
                        Repeater::make('hero_benefits')
                            ->label('Poin Singkat Hero')
                            ->schema([
                                TextInput::make('icon')->label('Ikon Material')->required()->maxLength(50),
                                TextInput::make('label')->label('Teks')->required()->maxLength(100),
                            ])
                            ->columns(2)
                            ->minItems(1)
                            ->maxItems(4)
                            ->addActionLabel('Tambah poin')
                            ->columnSpanFull(),
                    ]),
                Section::make('Keunggulan')
                    ->schema([
                        Repeater::make('advantages')
                            ->hiddenLabel()
                            ->schema([
                                TextInput::make('icon')->label('Ikon Material')->required()->maxLength(50),
                                TextInput::make('title')->label('Judul')->required()->maxLength(100),
                                Textarea::make('description')->label('Deskripsi')->required()->rows(2)->maxLength(300)->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->minItems(1)
                            ->maxItems(6)
                            ->addActionLabel('Tambah keunggulan'),
                    ]),
                Section::make('Tentang Kami')
                    ->columns(2)
                    ->schema([
                        TextInput::make('about_title')->label('Judul')->required()->maxLength(150),
                        Textarea::make('about_description')->label('Ringkasan')->required()->rows(3)->maxLength(500),
                        Repeater::make('about_points')
                            ->label('Poin Tentang Kami')
                            ->schema([
                                TextInput::make('title')->label('Judul')->required()->maxLength(100),
                                Textarea::make('description')->label('Deskripsi')->required()->rows(2)->maxLength(300),
                            ])
                            ->minItems(1)
                            ->maxItems(6)
                            ->addActionLabel('Tambah poin')
                            ->columnSpanFull(),
                    ]),
                Section::make('Lokasi & Kontak')
                    ->columns(2)
                    ->schema([
                        Textarea::make('address')->label('Alamat')->required()->rows(3)->maxLength(500),
                        TextInput::make('map_url')->label('Tautan Google Maps')->url()->maxLength(500),
                        TextInput::make('contact_phone')->label('Nomor WhatsApp')->tel()->required()->maxLength(30),
                        TextInput::make('contact_email')->label('Email')->email()->required()->maxLength(150),
                        TextInput::make('operating_hours')->label('Jam Operasional')->required()->maxLength(100)->columnSpanFull(),
                        Repeater::make('landmarks')
                            ->label('Landmark Terdekat')
                            ->schema([
                                TextInput::make('icon')->label('Ikon Material')->required()->maxLength(50),
                                TextInput::make('label')->label('Keterangan')->required()->maxLength(150),
                            ])
                            ->columns(2)
                            ->maxItems(6)
                            ->addActionLabel('Tambah landmark')
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(LandingPageService $landingPage): void
    {
        abort_unless(static::canAccess(), 403);

        $landingPage->save($this->form->getState());

        Notification::make()
            ->title('Landing page berhasil diperbarui')
            ->success()
            ->send();
    }

    private function imageUpload(string $name, string $label): FileUpload
    {
        return FileUpload::make($name)
            ->label($label)
            ->image()
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->maxSize(5120)
            ->disk('public')
            ->directory('landing/settings')
            ->visibility('public')
            ->imageEditor();
    }
}
