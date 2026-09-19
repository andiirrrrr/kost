<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-2xl border border-[var(--kost-border)] bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-[var(--kost-text-muted)]">Bulan</p>
                        <p class="mt-2 text-2xl font-semibold tracking-tight text-[var(--kost-text)]">{{ \Carbon\CarbonImmutable::create(null, $month)->locale('id')->translatedFormat('F') }}</p>
                    </div>
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-[var(--kost-surface-muted)] text-[var(--kost-navy)]">
                        <x-filament::icon icon="heroicon-o-calendar-days" class="size-5" />
                    </span>
                </div>
            </div>
            <div class="rounded-2xl border border-[var(--kost-border)] bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-[var(--kost-text-muted)]">Tahun</p>
                        <p class="mt-2 text-2xl font-semibold tracking-tight text-[var(--kost-text)]">{{ $year }}</p>
                    </div>
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-[var(--kost-surface-muted)] text-[var(--kost-navy)]">
                        <x-filament::icon icon="heroicon-o-clock" class="size-5" />
                    </span>
                </div>
            </div>
            <div class="rounded-2xl border border-[var(--kost-border)] bg-white p-5 shadow-sm sm:col-span-2 sm:p-6 xl:col-span-1">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-[var(--kost-text-muted)]">Penghuni Aktif</p>
                        <p class="mt-2 text-2xl font-semibold tracking-tight text-[var(--kost-text)]">{{ $totalTenants }} orang</p>
                    </div>
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-[var(--kost-surface-muted)] text-[var(--kost-navy)]">
                        <x-filament::icon icon="heroicon-o-users" class="size-5" />
                    </span>
                </div>
            </div>
        </div>

        @if ($result)
            <x-filament::section heading="Hasil Pembuatan" description="Ringkasan pemrosesan tagihan untuk periode yang dipilih." icon="heroicon-o-clipboard-document-check">
                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="rounded-xl bg-success-50 p-4 text-success-700">
                        <p class="text-sm font-medium">Berhasil</p>
                        <p class="mt-1 text-2xl font-semibold">{{ $result['created'] }}</p>
                    </div>
                    <div class="rounded-xl bg-warning-50 p-4 text-warning-700">
                        <p class="text-sm font-medium">Dilewati</p>
                        <p class="mt-1 text-2xl font-semibold">{{ $result['skipped'] }}</p>
                    </div>
                    <div class="rounded-xl bg-danger-50 p-4 text-danger-700">
                        <p class="text-sm font-medium">Gagal</p>
                        <p class="mt-1 text-2xl font-semibold">{{ $result['failed'] }}</p>
                    </div>
                </div>

                @if ($result['details'] !== [])
                    <div class="mt-5 max-h-64 overflow-y-auto rounded-xl border border-[var(--kost-border)] bg-[var(--kost-surface-muted)] p-4 text-sm leading-6 text-[var(--kost-text-muted)]">
                        @foreach ($result['details'] as $detail)
                            <p wire:key="invoice-result-{{ $loop->index }}">{{ $detail }}</p>
                        @endforeach
                    </div>
                @endif
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
