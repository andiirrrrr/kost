<x-filament-panels::page>
    <form wire:submit="save" class="landing-page-settings-form space-y-6">
        {{ $this->form }}

        <div class="landing-page-settings-actions">
            <x-filament::button type="submit" icon="heroicon-o-check">
                Simpan Perubahan
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
