<?php

namespace App\Filament\Admin\Pages;

use App\Services\LandingPageService;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class LandingPageSettings extends Page
{
    protected string $view = 'filament.admin.pages.landing-page-settings';

    protected static ?string $navigationLabel = 'Landing Page';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static string|\UnitEnum|null $navigationGroup = 'Pengaturan Website';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Pengaturan Landing Page';

    protected static ?string $slug = 'landing-page';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('owner') ?? false;
    }

    public function mount(LandingPageService $landingPage): void
    {
        abort_unless(static::canAccess(), 403);

        $data = $landingPage->get();
        if (isset($data['landmarks']) && is_array($data['landmarks'])) {
            $data['landmarks'] = collect($data['landmarks'])
                ->map(function (array $item) use ($landingPage): array {
                    $label = (string) ($item['label'] ?? '');
                    $detected = $landingPage->detectLandmarkIconByKeywords($label);
                    if (($item['icon'] ?? '') === $detected || empty($item['icon'])) {
                        $item['icon'] = 'auto';
                    }

                    return $item;
                })
                ->all();
        }

        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas & Hero')
                    ->description('Konten utama yang pertama kali dilihat pengunjung.')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
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
                                TextInput::make('label')->label('Teks')->required()->maxLength(100),
                            ])
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
                                TextInput::make('title')->label('Judul')->required()->maxLength(100),
                                Textarea::make('description')->label('Deskripsi')->required()->rows(2)->maxLength(300)->columnSpanFull(),
                            ])
                            ->columns([
                                'default' => 1,
                                'md' => 2,
                            ])
                            ->minItems(1)
                            ->maxItems(6)
                            ->addActionLabel('Tambah keunggulan'),
                    ]),
                Section::make('Tentang Kami')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
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
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        Textarea::make('address')
                            ->label('Alamat')
                            ->required()
                            ->rows(3)
                            ->maxLength(500),
                        TextInput::make('map_url')
                            ->label('Tautan Google Maps')
                            ->helperText('Tautan langsung saat tombol "Buka di Google Maps" diklik.')
                            ->url()
                            ->maxLength(500),
                        Textarea::make('maps_iframe')
                            ->label('Kode Embed Google Maps (iFrame)')
                            ->helperText('Salin kode sematan dari Google Maps (Bagikan > Sematkan peta) lalu tempel kode <iframe> di sini untuk menampilkan peta interaktif di landing page.')
                            ->placeholder('<iframe src="https://www.google.com/maps/embed?..." width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>')
                            ->rows(4)
                            ->maxLength(3000)
                            ->rule(function () {
                                return function (string $attribute, mixed $value, \Closure $fail): void {
                                    if (blank($value)) {
                                        return;
                                    }

                                    $service = app(LandingPageService::class);
                                    if (! $service->extractMapsEmbedUrl($value)) {
                                        $fail('Kode embed harus berupa kode <iframe> Google Maps yang valid atau URL embed Google Maps.');
                                    }
                                };
                            })
                            ->columnSpanFull(),
                        TextInput::make('contact_phone')
                            ->label('Nomor WhatsApp')
                            ->helperText('Tersinkronisasi otomatis dengan nomor WhatsApp pada Profil Saya.')
                            ->tel()
                            ->required()
                            ->maxLength(30),
                        TextInput::make('contact_email')->label('Email')->email()->required()->maxLength(150),
                        TextInput::make('operating_hours')->label('Jam Operasional')->required()->maxLength(100)->columnSpanFull(),
                        Repeater::make('landmarks')
                            ->label('Landmark Terdekat')
                            ->schema([
                                TextInput::make('label')
                                    ->label('Keterangan Landmark')
                                    ->placeholder('Contoh: 5 menit ke Stasiun MRT / Kampus / Rumah Sakit')
                                    ->helperText('Ikon akan menyesuaikan secara otomatis sesuai nama/keterangan yang Anda tulis.')
                                    ->required()
                                    ->maxLength(150),
                                Select::make('icon')
                                    ->label('Pilihan Ikon')
                                    ->options([
                                        'auto' => 'Otomatis (Sesuai Keterangan)',
                                        'directions_bus' => 'Bus / Halte / Transportasi Umum',
                                        'train' => 'Kereta / KRL / MRT / LRT / Stasiun',
                                        'school' => 'Kampus / Sekolah / Universitas',
                                        'business' => 'Pusat Bisnis / Perkantoran',
                                        'restaurant' => 'Restoran / Kuliner / Kafe',
                                        'shopping_bag' => 'Mall / Pusat Perbelanjaan',
                                        'storefront' => 'Minimarket / Supermarket / Pasar',
                                        'local_hospital' => 'Rumah Sakit / Klinik / Apotek',
                                        'add_road' => 'Gerbang Tol / Akses Tol',
                                        'flight' => 'Bandara / Airport',
                                        'fitness_center' => 'Gym / Fasilitas Olahraga',
                                        'park' => 'Taman / Ruang Terbuka Hijau',
                                        'local_atm' => 'ATM / Bank',
                                        'place_of_worship' => 'Tempat Ibadah (Masjid / Gereja)',
                                        'location_on' => 'Pin Lokasi Peta',
                                    ])
                                    ->default('auto'),
                            ])
                            ->columns([
                                'default' => 1,
                                'md' => 2,
                            ])
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
