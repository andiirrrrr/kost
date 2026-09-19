<x-filament-panels::page>
    <div class="grid gap-6 xl:grid-cols-2">
        <x-filament::section heading="Checklist Siap Operasional" description="Urutan singkat agar aplikasi cepat dipakai.">
            <ol class="space-y-4 text-sm text-gray-700">
                <li><strong>1. Buat kategori kamar</strong><br><span class="text-gray-500">Isi fasilitas, harga dasar, dan foto landing page.</span></li>
                <li><strong>2. Masukkan nomor kamar</strong><br><span class="text-gray-500">Hubungkan setiap kamar ke kategori yang sesuai.</span></li>
                <li><strong>3. Tambahkan metode pembayaran</strong><br><span class="text-gray-500">Rekening atau e-wallet yang dilihat penghuni.</span></li>
                <li><strong>4. Masukkan penghuni</strong><br><span class="text-gray-500">Gunakan form atau impor banyak data sekaligus.</span></li>
            </ol>
        </x-filament::section>
        <x-filament::section heading="Impor Excel" description="Unggah file .xlsx, periksa pratinjau, lalu simpan data kamar dan penghuni.">
            <div class="space-y-5">
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                    <p class="text-sm text-gray-600 dark:text-gray-300">Gunakan susunan kolom yang benar agar data dapat divalidasi otomatis.</p>
                    <x-filament::button tag="a" :href="route('admin.data.template')" color="gray" icon="heroicon-o-arrow-down-tray">Unduh Template Excel</x-filament::button>
                </div>

                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-gray-950 dark:text-white">File Excel</span>
                    <input type="file" wire:model="spreadsheet" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                </label>
                <p wire:loading wire:target="spreadsheet" class="text-sm text-gray-500">Membaca file...</p>
                @error('spreadsheet')<p class="text-sm text-danger-600">{{ $message }}</p>@enderror

                <div class="flex flex-wrap gap-3">
                    <x-filament::button wire:click="preview" wire:loading.attr="disabled" icon="heroicon-o-eye">Periksa & Pratinjau</x-filament::button>
                    @if ($previewRows !== [] && $previewErrors === [])
                        <x-filament::button wire:click="import" wire:loading.attr="disabled" color="success" icon="heroicon-o-check">Impor Data</x-filament::button>
                    @endif
                </div>

                @if ($previewErrors !== [])
                    <div class="rounded-xl border border-danger-200 bg-danger-50 p-4 dark:border-danger-900 dark:bg-danger-950/30">
                        <p class="mb-2 text-sm font-semibold text-danger-700 dark:text-danger-300">Data belum dapat diimpor:</p>
                        <ul class="list-disc space-y-1 pl-5 text-sm text-danger-700 dark:text-danger-300">
                            @foreach ($previewErrors as $error)
                                <li wire:key="preview-error-{{ $loop->index }}">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($previewRows !== [])
                    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                        <table class="w-full min-w-[760px] text-left text-sm">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                <tr><th class="px-4 py-3">Kamar</th><th class="px-4 py-3">Kategori</th><th class="px-4 py-3">Harga</th><th class="px-4 py-3">Penghuni</th><th class="px-4 py-3">Telepon</th><th class="px-4 py-3">Tanggal Masuk</th></tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach ($previewRows as $row)
                                    <tr wire:key="preview-room-{{ $loop->index }}-{{ $row['nomor_kamar'] }}"><td class="px-4 py-3 font-medium">{{ $row['nomor_kamar'] }}</td><td class="px-4 py-3">{{ $row['kategori'] }}</td><td class="px-4 py-3">Rp {{ number_format($row['harga_bulanan'], 0, ',', '.') }}</td><td class="px-4 py-3">{{ $row['nama_penghuni'] ?: '—' }}</td><td class="px-4 py-3">{{ $row['telepon'] ?: '—' }}</td><td class="px-4 py-3">{{ $row['tanggal_masuk'] ?: '—' }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="text-xs text-gray-500">Pratinjau menampilkan maksimal 20 baris pertama.</p>
                @endif
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
