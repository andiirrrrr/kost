<x-filament-widgets::widget>
    <section class="flex h-full min-h-80 flex-col overflow-hidden rounded-2xl bg-[var(--kost-navy)] p-6 text-white shadow-sm">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-white/55">Bulan berjalan</p>
                <p class="mt-2 text-sm text-white/70">{{ now()->locale('id')->translatedFormat('F Y') }}</p>
            </div>
            <span class="flex size-10 items-center justify-center rounded-xl bg-white/10">
                <x-filament::icon icon="heroicon-o-banknotes" class="size-5" />
            </span>
        </div>

        <div class="mt-8">
            <p class="text-sm font-medium text-white/65">Pemasukan Bulan Ini</p>
            <p class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Rp {{ number_format($summary['income'], 0, ',', '.') }}</p>
            <p class="mt-3 text-xs leading-5 text-white/55">Pembayaran yang telah diverifikasi.</p>
        </div>

        <div class="mt-auto border-t border-white/10 pt-5">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <p class="text-xs text-white/50">Nilai tagihan bulan ini</p>
                    <p class="mt-1 text-lg font-semibold">Rp {{ number_format($summary['invoice_total'], 0, ',', '.') }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-white/50">Pengeluaran</p>
                    <p class="mt-1 text-sm font-semibold">Rp {{ number_format($summary['expenses'], 0, ',', '.') }}</p>
                </div>
            </div>
        </div>
    </section>
</x-filament-widgets::widget>
