<?php

namespace App\Filament\Admin\Pages;

use App\Services\HouseRulesService;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class HouseRulesSettings extends Page
{
    protected string $view = 'filament.admin.pages.house-rules-settings';

    protected static ?string $navigationLabel = 'Tata Tertib Kost';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?int $navigationSort = 90;

    protected static ?string $title = 'Tata Tertib & Aturan Kost';

    protected static ?string $slug = 'tata-tertib';

    protected Width|string|null $maxContentWidth = Width::Full;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('owner') ?? false;
    }

    public function mount(HouseRulesService $service): void
    {
        abort_unless(static::canAccess(), 403);

        $rules = $service->get();

        $categories = array_map(function (array $item): array {
            $formattedRules = [];
            foreach ($item['rules'] ?? [] as $rule) {
                $formattedRules[] = is_array($rule) ? $rule : ['text' => (string) $rule];
            }

            return [
                'category' => $item['category'] ?? '',
                'icon' => $item['icon'] ?? 'security',
                'rules' => $formattedRules,
            ];
        }, $rules);

        $this->form->fill(['categories' => $categories]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Aturan & Tata Tertib Penghuni')
                    ->description('Tata tertib ini tampil secara transparan di portal penghuni untuk menjaga ketertiban, kebersihan, dan kenyamanan bersama.')
                    ->schema([
                        Repeater::make('categories')
                            ->label('Kategori Tata Tertib')
                            ->schema([
                                TextInput::make('category')
                                    ->label('Nama Kategori / Bab')
                                    ->placeholder('Contoh: Ketertiban & Keamanan')
                                    ->required()
                                    ->maxLength(100),
                                Select::make('icon')
                                    ->label('Ikon Kategori')
                                    ->options([
                                        'security' => 'Keamanan (Security)',
                                        'cleaning_services' => 'Kebersihan (Cleaning)',
                                        'block' => 'Larangan Khusus (Prohibition)',
                                        'payments' => 'Pembayaran & Sewa (Payments)',
                                        'schedule' => 'Waktu / Jam Malam (Schedule)',
                                        'groups' => 'Tamu / Kebersamaan (People)',
                                        'home' => 'Fasilitas Kamar (Home)',
                                        'info' => 'Informasi Umum (Info)',
                                    ])
                                    ->default('security')
                                    ->required(),
                                Repeater::make('rules')
                                    ->label('Daftar Poin Aturan')
                                    ->schema([
                                        Textarea::make('text')
                                            ->label('Poin Aturan')
                                            ->placeholder('Tuliskan poin aturan...')
                                            ->required()
                                            ->rows(2)
                                            ->maxLength(500),
                                    ])
                                    ->minItems(1)
                                    ->addActionLabel('Tambah Poin Aturan')
                                    ->columnSpanFull(),
                            ])
                            ->columns([
                                'default' => 1,
                                'md' => 2,
                            ])
                            ->minItems(1)
                            ->addActionLabel('Tambah Kategori Tata Tertib')
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(HouseRulesService $service): void
    {
        abort_unless(static::canAccess(), 403);

        $data = $this->form->getState();

        $saved = [];
        foreach ($data['categories'] ?? [] as $categoryItem) {
            $categoryName = trim((string) ($categoryItem['category'] ?? ''));
            if ($categoryName === '') {
                continue;
            }

            $rulesList = [];
            foreach ($categoryItem['rules'] ?? [] as $ruleItem) {
                $text = trim((string) ($ruleItem['text'] ?? ''));
                if ($text !== '') {
                    $rulesList[] = $text;
                }
            }

            if (! empty($rulesList)) {
                $saved[] = [
                    'category' => $categoryName,
                    'icon' => (string) ($categoryItem['icon'] ?? 'security'),
                    'rules' => $rulesList,
                ];
            }
        }

        $service->save($saved);

        Notification::make()
            ->title('Tata tertib kost berhasil disimpan')
            ->success()
            ->send();
    }

    public function resetToDefault(HouseRulesService $service): void
    {
        abort_unless(static::canAccess(), 403);

        $defaults = $service->defaults();
        $service->save($defaults);
        $this->mount($service);

        Notification::make()
            ->title('Tata tertib telah dikembalikan ke standar')
            ->info()
            ->send();
    }
}
