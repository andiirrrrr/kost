<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}
        <div class="flex flex-wrap items-center gap-3">
            <x-filament::button type="submit">
                Simpan Tata Tertib
            </x-filament::button>
            <x-filament::button type="button" color="gray" wire:click="resetToDefault" wire:confirm="Kembalikan semua tata tertib ke aturan standar?">
                Kembalikan ke Standar
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
