@extends('layouts.tenant', ['title' => 'Keluhan & Perbaikan'])

@section('content')
<div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(320px,420px)]">
    <section>
        <h1 class="text-3xl font-semibold tracking-tight">Keluhan & Perbaikan</h1>
        <p class="mt-2 text-[#44474e]">Laporkan kerusakan kamar dan pantau prosesnya tanpa perlu mengejar pengelola lewat chat.</p>
        <div class="mt-6 space-y-3">
            @forelse ($maintenanceRequests as $item)
                <article class="rounded-xl border border-[#c5c6cf]/30 bg-white p-5">
                    <div class="flex items-start justify-between gap-4"><div><h2 class="font-semibold">{{ $item->title }}</h2><p class="mt-1 text-sm text-[#44474e]">{{ $item->created_at->locale('id')->translatedFormat('j M Y, H:i') }}</p></div><span class="rounded-md bg-[#e7e2d8] px-2 py-1 text-xs font-semibold uppercase">{{ str_replace('_', ' ', $item->status) }}</span></div>
                    <p class="mt-4 text-sm leading-6 text-[#44474e]">{{ $item->description }}</p>
                    @if ($item->resolution_notes)<p class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800"><strong>Tindak lanjut:</strong> {{ $item->resolution_notes }}</p>@endif
                </article>
            @empty
                <div class="rounded-xl bg-[#f4f4f2] px-6 py-12 text-center text-[#44474e]">Belum ada laporan perbaikan.</div>
            @endforelse
        </div>
        <div class="mt-5">{{ $maintenanceRequests->links() }}</div>
    </section>
    <aside class="h-fit rounded-2xl bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold">Buat laporan</h2>
        <form method="POST" action="{{ route('tenant.maintenance.store') }}" enctype="multipart/form-data" class="mt-5 space-y-4">
            @csrf
            <label class="block"><span class="text-sm font-medium">Masalah</span><input name="title" value="{{ old('title') }}" required maxlength="150" class="mt-2 block w-full rounded-lg border-[#c5c6cf]" placeholder="Contoh: Keran kamar mandi bocor">@error('title')<span class="mt-1 block text-sm text-red-600">{{ $message }}</span>@enderror</label>
            <label class="block"><span class="text-sm font-medium">Detail</span><textarea name="description" required maxlength="3000" rows="5" class="mt-2 block w-full rounded-lg border-[#c5c6cf]" placeholder="Jelaskan lokasi dan kondisi kerusakan">{{ old('description') }}</textarea>@error('description')<span class="mt-1 block text-sm text-red-600">{{ $message }}</span>@enderror</label>
            <label class="block"><span class="text-sm font-medium">Prioritas</span><select name="priority" class="mt-2 block w-full rounded-lg border-[#c5c6cf]"><option value="normal">Normal</option><option value="urgent">Mendesak</option><option value="low">Rendah</option></select></label>
            <label class="block"><span class="text-sm font-medium">Foto (opsional)</span><input name="photo" type="file" accept="image/*" class="mt-2 block w-full text-sm"></label>
            <button class="flex w-full items-center justify-center rounded-lg bg-[#031636] px-4 py-3 font-semibold text-white">Kirim laporan</button>
        </form>
    </aside>
</div>
@endsection
