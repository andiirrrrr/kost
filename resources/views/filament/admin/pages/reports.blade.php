<x-filament-panels::page>
    <div class="w-full space-y-6">
        {{-- Filter --}}
        <x-filament::section
            heading="Filter Laporan"
            description="Pilih jenis laporan dan periode yang ingin ditinjau."
            icon="heroicon-o-funnel"
        >
            @if (in_array($reportType, ['invoices', 'payments', 'tenants'], true))
                <div class="mb-5 flex justify-end">
                    <x-filament::button tag="a" icon="heroicon-o-arrow-down-tray" :href="route('admin.data.export', ['type' => $reportType === 'invoices' ? 'tagihan' : ($reportType === 'payments' ? 'pembayaran' : 'penghuni'), 'month' => $month, 'year' => $year, 'status' => $status])">Export sesuai filter</x-filament::button>
                </div>
            @endif
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
                <label class="space-y-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Jenis Laporan</span>
                    <select wire:model.live="reportType" class="kost-admin-select block w-full">
                        <option value="invoices">Tagihan</option>
                        <option value="payments">Pembayaran</option>
                        <option value="rooms">Kamar</option>
                        <option value="tenants">Penghuni</option>
                    </select>
                </label>

                @if (in_array($reportType, ['invoices', 'payments'], true))
                    <label class="space-y-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Bulan</span>
                        <select wire:model.live="month" class="kost-admin-select block w-full">
                            @foreach (range(1, 12) as $monthNumber)
                                <option value="{{ $monthNumber }}">{{ \Carbon\CarbonImmutable::create(null, $monthNumber)->locale('id')->translatedFormat('F') }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="space-y-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Tahun</span>
                        <select wire:model.live="year" class="kost-admin-select block w-full">
                            @foreach (range(now()->year, now()->year - 5) as $yearOption)
                                <option value="{{ $yearOption }}">{{ $yearOption }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif

                <label class="space-y-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Status</span>
                    <select wire:model.live="status" class="kost-admin-select block w-full">
                        <option value="">Semua Status</option>
                        @foreach ($this->statusOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </x-filament::section>

        {{-- Ringkasan --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['Pemasukan', $this->rupiah($this->summary['income']), 'Pemasukan terverifikasi', 'text-success-600', 'bg-success-50 dark:bg-success-950/30', 'heroicon-o-arrow-trending-up'],
                ['Pengeluaran', $this->rupiah($this->summary['expenses']), 'Total pengeluaran tercatat', 'text-danger-600', 'bg-danger-50 dark:bg-danger-950/30', 'heroicon-o-arrow-trending-down'],
                ['Belum Dibayar', $this->rupiah($this->summary['outstanding']), 'Nilai tagihan aktif', 'text-warning-600', 'bg-warning-50 dark:bg-warning-950/30', 'heroicon-o-clock'],
                ['Tingkat Hunian', $this->summary['occupancy_rate'].'%', 'Kamar terisi saat ini', 'text-primary-600', 'bg-primary-50 dark:bg-primary-950/30', 'heroicon-o-home-modern'],
            ] as [$label, $value, $helper, $color, $iconBackground, $icon])
                <div wire:key="summary-{{ $label }}" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $label }}</p>
                            <p class="mt-2 truncate text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $value }}</p>
                        </div>
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $iconBackground }}">
                            <x-filament::icon :icon="$icon" class="h-5 w-5 {{ $color }}" />
                        </span>
                    </div>
                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">{{ $helper }}</p>
                </div>
            @endforeach
        </div>

        {{-- Tabel --}}
        <x-filament::section
            heading="Data Laporan"
            description="Maksimal 100 data terbaru ditampilkan untuk menjaga performa."
            icon="heroicon-o-table-cells"
        >
            <div wire:loading.flex class="mb-4 items-center gap-2 rounded-lg bg-primary-50 px-3 py-2 text-sm text-primary-700 dark:bg-primary-950/40 dark:text-primary-300">
                <x-filament::loading-indicator class="h-5 w-5" />
                Memuat laporan...
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px] table-auto text-left text-sm">
                        <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-800/60 dark:text-gray-400">
                            @if ($reportType === 'invoices')
                                <tr><th class="w-[18%] px-5 py-3.5 font-semibold">Tagihan</th><th class="w-[20%] px-5 py-3.5 font-semibold">Penghuni</th><th class="w-[12%] px-5 py-3.5 font-semibold">Kamar</th><th class="w-[17%] px-5 py-3.5 font-semibold">Jatuh Tempo</th><th class="w-[18%] px-5 py-3.5 text-right font-semibold">Total</th><th class="w-[15%] px-5 py-3.5 text-center font-semibold">Status</th></tr>
                            @elseif ($reportType === 'payments')
                                <tr><th class="w-[18%] px-5 py-3.5 font-semibold">Pembayaran</th><th class="w-[18%] px-5 py-3.5 font-semibold">Tagihan</th><th class="w-[20%] px-5 py-3.5 font-semibold">Penghuni</th><th class="w-[16%] px-5 py-3.5 font-semibold">Tanggal</th><th class="w-[16%] px-5 py-3.5 text-right font-semibold">Jumlah</th><th class="w-[12%] px-5 py-3.5 text-center font-semibold">Status</th></tr>
                            @elseif ($reportType === 'rooms')
                                <tr><th class="w-[18%] px-5 py-3.5 font-semibold">Kamar</th><th class="w-[18%] px-5 py-3.5 font-semibold">Tipe</th><th class="w-[18%] px-5 py-3.5 text-right font-semibold">Harga</th><th class="w-[31%] px-5 py-3.5 font-semibold">Penghuni Aktif</th><th class="w-[15%] px-5 py-3.5 text-center font-semibold">Status</th></tr>
                            @else
                                <tr><th class="w-[20%] px-5 py-3.5 font-semibold">Penghuni</th><th class="w-[14%] px-5 py-3.5 font-semibold">Kamar</th><th class="w-[20%] px-5 py-3.5 font-semibold">Telepon</th><th class="w-[16%] px-5 py-3.5 font-semibold">Masuk</th><th class="w-[18%] px-5 py-3.5 text-right font-semibold">Harga</th><th class="w-[12%] px-5 py-3.5 text-center font-semibold">Status</th></tr>
                            @endif
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse ($this->records as $record)
                                <tr wire:key="{{ $reportType }}-{{ $record->id }}" class="text-gray-700 transition hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800/40">
                                    @if ($reportType === 'invoices')
                                        <td class="whitespace-nowrap px-5 py-4 font-medium text-gray-950 dark:text-white">{{ $record->invoice_number }}</td><td class="px-5 py-4">{{ $record->tenant?->name }}</td><td class="px-5 py-4">{{ $record->room?->room_number }}</td><td class="whitespace-nowrap px-5 py-4">{{ $record->due_date->format('d M Y') }}</td><td class="whitespace-nowrap px-5 py-4 text-right font-medium">{{ $this->rupiah($record->total_amount) }}</td><td class="px-5 py-4 text-center"><x-filament::badge :color="$record->status->getColor()">{{ $record->status->label() }}</x-filament::badge></td>
                                    @elseif ($reportType === 'payments')
                                        <td class="whitespace-nowrap px-5 py-4 font-medium text-gray-950 dark:text-white">{{ $record->payment_number }}</td><td class="px-5 py-4">{{ $record->invoice?->invoice_number }}</td><td class="px-5 py-4">{{ $record->tenant?->name }}</td><td class="whitespace-nowrap px-5 py-4">{{ $record->paid_at?->format('d M Y') }}</td><td class="whitespace-nowrap px-5 py-4 text-right font-medium">{{ $this->rupiah($record->amount) }}</td><td class="px-5 py-4 text-center"><x-filament::badge :color="$record->status->getColor()">{{ $record->status->label() }}</x-filament::badge></td>
                                    @elseif ($reportType === 'rooms')
                                        <td class="px-5 py-4 font-medium text-gray-950 dark:text-white">{{ $record->room_number }}</td><td class="px-5 py-4">{{ $record->type ?: '-' }}</td><td class="whitespace-nowrap px-5 py-4 text-right">{{ $this->rupiah((int) $record->monthly_price) }}</td><td class="px-5 py-4">{{ $record->tenants->pluck('name')->join(', ') ?: '-' }}</td><td class="px-5 py-4 text-center"><x-filament::badge :color="$record->status->getColor()">{{ $record->status->label() }}</x-filament::badge></td>
                                    @else
                                        <td class="px-5 py-4 font-medium text-gray-950 dark:text-white">{{ $record->name }}</td><td class="px-5 py-4">{{ $record->room?->room_number }}</td><td class="px-5 py-4">{{ $record->phone }}</td><td class="whitespace-nowrap px-5 py-4">{{ $record->move_in_date->format('d M Y') }}</td><td class="whitespace-nowrap px-5 py-4 text-right">{{ $this->rupiah((int) $record->monthly_price) }}</td><td class="px-5 py-4 text-center"><x-filament::badge :color="$record->status->getColor()">{{ $record->status->label() }}</x-filament::badge></td>
                                    @endif
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-5 py-16 text-center text-gray-500 dark:text-gray-400">Tidak ada data untuk filter yang dipilih.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
