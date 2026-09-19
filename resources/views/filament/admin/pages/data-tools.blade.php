<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section
            heading="Ekspor Data"
            description="Pilih beberapa data sekaligus dan periksa preview sebelum mengunduh satu file Excel."
        >
            <div class="flex flex-wrap items-center gap-4">
                <x-filament::modal id="flexible-data-export" width="7xl" sticky-header>
                    <x-slot name="trigger">
                        <x-filament::button icon="heroicon-o-arrow-down-tray">
                            Ekspor Data
                        </x-filament::button>
                    </x-slot>

                    <x-slot name="heading">Buat Ekspor Excel</x-slot>
                    <x-slot name="description">Setiap jenis data akan dibuat sebagai sheet terpisah dalam satu file.</x-slot>

                    <div class="space-y-6">
                        <fieldset>
                            <legend class="text-sm font-semibold text-gray-950 dark:text-white">Data yang akan diekspor</legend>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                @foreach ($this->exportOptions as $type => $label)
                                    <label
                                        wire:key="export-option-{{ $type }}"
                                        class="flex cursor-pointer items-center gap-3 rounded-xl border p-4 transition {{ in_array($type, $selectedExportTypes, true) ? 'border-primary-500 bg-primary-50 dark:border-primary-400 dark:bg-primary-950/30' : 'border-gray-200 bg-white hover:border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:hover:border-gray-600' }}"
                                    >
                                        <x-filament::input.checkbox wire:model.live="selectedExportTypes" value="{{ $type }}" />
                                        <span class="font-medium text-gray-950 dark:text-white">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>

                        @if (array_intersect($selectedExportTypes, ['tagihan', 'pembayaran', 'pengeluaran']))
                            <div class="grid gap-4 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900 sm:grid-cols-2">
                                <label class="space-y-2">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Bulan</span>
                                    <select wire:model.live.number="exportMonth" class="kost-admin-select block w-full">
                                        <option value="">Semua bulan</option>
                                        @foreach (range(1, 12) as $month)
                                            <option value="{{ $month }}">{{ \Carbon\CarbonImmutable::create(null, $month)->locale('id')->translatedFormat('F') }}</option>
                                        @endforeach
                                    </select>
                                </label>

                                <label class="space-y-2">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Tahun</span>
                                    <select wire:model.live.number="exportYear" class="kost-admin-select block w-full">
                                        <option value="">Semua tahun</option>
                                        @foreach (range(now()->year, now()->year - 5) as $year)
                                            <option value="{{ $year }}">{{ $year }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            </div>
                        @endif

                        <div
                            wire:loading.flex
                            wire:target="selectedExportTypes,exportMonth,exportYear"
                            class="items-center gap-2 rounded-xl bg-primary-50 p-4 text-sm text-primary-700 dark:bg-primary-950/30 dark:text-primary-300"
                        >
                            <x-filament::loading-indicator class="h-5 w-5" />
                            Memperbarui preview...
                        </div>

                        <div wire:loading.remove wire:target="selectedExportTypes,exportMonth,exportYear" class="space-y-4">
                            @forelse ($this->exportPreviews as $type => $preview)
                                <section wire:key="export-preview-{{ $type }}" class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center justify-between gap-4 bg-gray-50 px-4 py-3 dark:bg-gray-900">
                                        <h3 class="font-semibold text-gray-950 dark:text-white">Preview {{ $preview['label'] }}</h3>
                                        <x-filament::badge color="gray">{{ $preview['total'] }} data</x-filament::badge>
                                    </div>

                                    <div class="overflow-x-auto">
                                        <table class="w-full min-w-max text-left text-sm">
                                            <thead class="border-y border-gray-200 bg-white text-xs uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-400">
                                                <tr>
                                                    @foreach ($preview['headers'] as $header)
                                                        <th class="whitespace-nowrap px-4 py-3 font-semibold">{{ $header }}</th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                                @forelse ($preview['rows'] as $rowIndex => $row)
                                                    <tr wire:key="preview-row-{{ $type }}-{{ $rowIndex }}">
                                                        @foreach ($row as $value)
                                                            <td class="max-w-64 truncate whitespace-nowrap px-4 py-3 text-gray-700 dark:text-gray-200">{{ filled($value) ? $value : '-' }}</td>
                                                        @endforeach
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="{{ count($preview['headers']) }}" class="px-4 py-8 text-center text-gray-500">Tidak ada data untuk filter ini.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>

                                    @if ($preview['total'] > count($preview['rows']))
                                        <p class="border-t border-gray-200 px-4 py-2 text-xs text-gray-500 dark:border-gray-700">
                                            Menampilkan {{ count($preview['rows']) }} dari {{ $preview['total'] }} data.
                                        </p>
                                    @endif
                                </section>
                            @empty
                                <div class="rounded-xl border border-dashed border-gray-300 px-6 py-12 text-center dark:border-gray-700">
                                    <p class="font-medium text-gray-950 dark:text-white">Belum ada data yang dipilih</p>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Pilih satu atau beberapa jenis data untuk menampilkan preview.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <x-slot name="footerActions">
                        <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'flexible-data-export' })">
                            Batal
                        </x-filament::button>

                        @if ($this->exportUrl)
                            <x-filament::button tag="a" :href="$this->exportUrl" icon="heroicon-o-arrow-down-tray">
                                Unduh Excel ({{ count($this->exportPreviews) }} sheet)
                            </x-filament::button>
                        @else
                            <x-filament::button disabled>Pilih Data</x-filament::button>
                        @endif
                    </x-slot>
                </x-filament::modal>

                <a href="{{ route('admin.data.backup') }}" class="text-sm font-medium text-gray-600 underline-offset-4 hover:text-primary-600 hover:underline dark:text-gray-300">
                    Unduh backup JSON
                </a>
            </div>
        </x-filament::section>
        <x-filament::section heading="Aktivitas Terbaru" description="Jejak perubahan operasional penting.">
            <div class="divide-y divide-gray-100">
                @forelse ($activities as $activity)
                    <div wire:key="activity-{{ $activity->id }}" class="flex flex-col gap-1 py-3 text-sm sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                        <div class="min-w-0 truncate"><strong>{{ $activity->user?->name ?? 'Sistem' }}</strong> · {{ str_replace('.', ' ', $activity->action) }}</div>
                        <time class="shrink-0 text-xs text-gray-500 sm:text-sm" datetime="{{ $activity->created_at->toIso8601String() }}">{{ $activity->created_at->diffForHumans() }}</time>
                    </div>
                @empty
                    <p class="py-6 text-sm text-gray-500">Belum ada aktivitas.</p>
                @endforelse
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
