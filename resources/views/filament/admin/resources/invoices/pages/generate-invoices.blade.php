<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500">Bulan</h3>
                <p class="text-2xl font-semibold">{{ $month }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500">Tahun</h3>
                <p class="text-2xl font-semibold">{{ $year }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500">Penghuni Aktif</h3>
                <p class="text-2xl font-semibold">{{ $totalTenants }} orang</p>
            </div>
        </div>

        @if($result)
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium mb-4">Hasil Pembuatan</h3>
                <ul class="list-disc list-inside space-y-1">
                    <li class="text-green-600">Berhasil: {{ $result['created'] }}</li>
                    <li class="text-yellow-600">Dilewati: {{ $result['skipped'] }}</li>
                    <li class="text-red-600">Gagal: {{ $result['failed'] }}</li>
                </ul>
                <div class="mt-4 max-h-60 overflow-y-auto text-sm text-gray-600">
                    @foreach($result['details'] as $detail)
                        <div>{{ $detail }}</div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
