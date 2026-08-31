<?php

namespace App\Filament\Admin\Pages;

use App\Enums\BroadcastAudience;
use App\Enums\WhatsAppStatus;
use App\Models\Broadcast;
use App\Models\Tenant;
use App\Models\WhatsAppLog;
use App\Models\WhatsAppTemplate;
use App\Services\BroadcastService;
use App\Services\WhatsAppService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

class WhatsAppCenter extends Page
{
    protected string $view = 'filament.admin.pages.whats-app-center';

    protected static ?string $navigationLabel = 'Pusat WhatsApp';

    protected static string|\UnitEnum|null $navigationGroup = 'Komunikasi';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Pusat WhatsApp';

    protected static ?string $slug = 'whatsapp-center';

    public string $broadcastTitle = '';

    public ?int $templateId = null;

    public string $audience = 'unpaid';

    /** @var list<int> */
    public array $manualTenantIds = [];

    /** @var array{connected: bool, phone: ?string}|null */
    public ?array $connectionResult = null;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-paper-airplane';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('whatsapp.view') ?? false;
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->templateId = WhatsAppTemplate::query()->where('is_active', true)->value('id');
    }

    public function testConnection(): void
    {
        abort_unless(auth()->user()?->can('whatsapp.view'), 403);
        $this->connectionResult = app(WhatsAppService::class)->testConnection();

        Notification::make()
            ->title($this->connectionResult['connected'] ? 'WhatsApp API berhasil terhubung' : 'WhatsApp API belum terhubung')
            ->color($this->connectionResult['connected'] ? 'success' : 'danger')
            ->send();
    }

    public function sendBroadcast(): void
    {
        abort_unless(auth()->user()?->can('whatsapp.send'), 403);

        $validated = $this->validate([
            'broadcastTitle' => ['required', 'string', 'max:255'],
            'templateId' => ['required', 'integer', 'exists:whatsapp_templates,id'],
            'audience' => ['required', 'in:all,unpaid,overdue,manual'],
            'manualTenantIds' => ['array'],
            'manualTenantIds.*' => ['integer', 'exists:tenants,id'],
        ]);

        try {
            $broadcast = app(BroadcastService::class)->createBroadcast(
                $validated['broadcastTitle'],
                WhatsAppTemplate::findOrFail($validated['templateId']),
                BroadcastAudience::from($validated['audience']),
                auth()->id(),
                $validated['manualTenantIds'],
            );

            Notification::make()
                ->title("{$broadcast->total_recipient} pesan masuk antrean")
                ->success()
                ->send();

            $this->broadcastTitle = '';
        } catch (\InvalidArgumentException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }
    }

    #[Computed]
    public function templates(): Collection
    {
        return WhatsAppTemplate::query()->where('is_active', true)->orderBy('display_name')->get();
    }

    #[Computed]
    public function tenants(): Collection
    {
        return Tenant::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'room_id']);
    }

    #[Computed]
    public function recipientCount(): int
    {
        return app(BroadcastService::class)
            ->recipients(BroadcastAudience::from($this->audience), $this->manualTenantIds)
            ->count();
    }

    #[Computed]
    public function preview(): string
    {
        $template = $this->templates->firstWhere('id', $this->templateId);
        $recipient = app(BroadcastService::class)
            ->recipients(BroadcastAudience::from($this->audience), $this->manualTenantIds)
            ->first();

        if (! $template) {
            return 'Pilih templat untuk melihat pratinjau.';
        }

        return $template->renderPreview($recipient['values'] ?? [
            'nama' => 'Nama Penghuni', 'kamar' => 'A1', 'periode' => now()->format('m/Y'),
            'total' => 'Rp 1.000.000', 'jatuh_tempo' => now()->addDays(5)->format('d F Y'),
        ]);
    }

    /** @return array{total: int, sent: int, failed: int} */
    #[Computed]
    public function messageStats(): array
    {
        $query = WhatsAppLog::query()->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);

        return [
            'total' => (clone $query)->count(),
            'sent' => (clone $query)->whereIn('status', [WhatsAppStatus::SENT, WhatsAppStatus::DELIVERED, WhatsAppStatus::READ])->count(),
            'failed' => (clone $query)->where('status', WhatsAppStatus::FAILED)->count(),
        ];
    }

    #[Computed]
    public function recentBroadcasts(): Collection
    {
        return Broadcast::query()->latest()->limit(10)->get();
    }
}
