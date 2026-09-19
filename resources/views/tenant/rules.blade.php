@extends('layouts.tenant', ['title' => 'Tata Tertib Kost'])

@section('content')
<div class="mx-auto flex max-w-4xl flex-col gap-8 pb-8">
    <!-- Header -->
    <header class="flex flex-col gap-2">
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-[#e7e2d8] px-3 py-1 text-xs font-semibold text-[#031636]">
                <span class="material-symbols-outlined text-sm">shield</span>
                Kenyamanan & Ketertiban
            </span>
        </div>
        <h1 class="text-3xl font-semibold tracking-tight text-[#031636] sm:text-4xl">Tata Tertib & Ketentuan Kost</h1>
        <p class="text-sm leading-relaxed text-[#44474e] sm:text-base">
            Pedoman bersama untuk menciptakan hunian yang aman, nyaman, bersih, dan saling menghargai di {{ $businessName }}.
        </p>
    </header>

    <!-- Ringkasan Unit & Kontrak Sewa -->
    <section class="overflow-hidden rounded-2xl border border-[#e2e3e1]/80 bg-white p-6 shadow-[0_4px_24px_rgba(26,43,76,0.04)] sm:p-8">
        <div class="flex flex-col gap-6">
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-[#e2e3e1]/60 pb-5">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#44474e]">Unit Anda</span>
                    <h2 class="text-xl font-bold text-[#031636]">Kamar {{ $tenant->room?->room_number ?? '—' }}</h2>
                </div>
                <div class="flex items-center gap-2 rounded-full bg-emerald-50 px-3.5 py-1.5 text-xs font-semibold text-emerald-800 border border-emerald-200">
                    <span class="size-2 rounded-full bg-emerald-600"></span>
                    Status: Penghuni Aktif
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="rounded-xl bg-[#f9f9f7] p-4">
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#44474e]">Mulai Masuk</span>
                    <p class="mt-1 text-sm font-semibold text-[#1a1c1b]">
                        {{ $tenant->move_in_date ? \Carbon\Carbon::parse($tenant->move_in_date)->locale('id')->translatedFormat('d M Y') : '—' }}
                    </p>
                </div>
                <div class="rounded-xl bg-[#f9f9f7] p-4">
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#44474e]">Jatuh Tempo</span>
                    <p class="mt-1 text-sm font-semibold text-[#1a1c1b]">
                        Setiap tgl {{ $tenant->due_day ?? $tenant->move_in_date?->day ?? '5' }}
                    </p>
                </div>
                <div class="rounded-xl bg-[#f9f9f7] p-4">
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#44474e]">Biaya Sewa</span>
                    <p class="mt-1 text-sm font-semibold text-[#031636]">
                        Rp {{ number_format($tenant->monthly_price, 0, ',', '.') }}/bln
                    </p>
                </div>
                <div class="rounded-xl bg-[#f9f9f7] p-4">
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#44474e]">Deposit Jaminan</span>
                    <p class="mt-1 text-sm font-semibold {{ ($contract?->deposit_amount ?? 0) > 0 ? 'text-emerald-700' : 'text-[#44474e]' }}">
                        @if(($contract?->deposit_amount ?? 0) > 0)
                            Rp {{ number_format($contract->deposit_amount, 0, ',', '.') }}
                        @else
                            Tidak Ada
                        @endif
                    </p>
                </div>
            </div>

            @if($contract)
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-[#f4f4f2] px-4 py-3 text-xs text-[#44474e]">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-base text-[#031636]">description</span>
                        <span>Nomor Kontrak: <strong class="text-[#031636]">{{ $contract->contract_number }}</strong></span>
                    </div>
                    @if($contract->starts_at && $contract->ends_at)
                        <span>Masa Kontrak: {{ $contract->starts_at->locale('id')->translatedFormat('d M Y') }} s/d {{ $contract->ends_at->locale('id')->translatedFormat('d M Y') }}</span>
                    @endif
                </div>
            @endif
        </div>
    </section>

    <!-- Daftar Poin Tata Tertib Per Kategori -->
    <div class="flex flex-col gap-6">
        @forelse($categories as $category)
            <article class="overflow-hidden rounded-2xl border border-[#e2e3e1]/80 bg-white p-6 shadow-[0_4px_24px_rgba(26,43,76,0.04)] sm:p-7">
                <div class="flex items-center gap-3 border-b border-[#e2e3e1]/60 pb-4">
                    <div class="flex size-10 items-center justify-center rounded-xl bg-[#e7e2d8] text-[#031636]">
                        <span class="material-symbols-outlined text-2xl">{{ $category['icon'] ?? 'shield' }}</span>
                    </div>
                    <h2 class="text-xl font-semibold text-[#031636]">{{ $category['category'] }}</h2>
                </div>

                <ul class="mt-5 space-y-3.5">
                    @foreach($category['rules'] as $index => $rule)
                        <li class="flex items-start gap-3 text-sm leading-relaxed text-[#1a1c1b]">
                            <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-[#f4f4f2] text-xs font-bold text-[#031636]">
                                {{ $index + 1 }}
                            </span>
                            <span class="pt-0.5">{{ is_array($rule) ? ($rule['text'] ?? '') : $rule }}</span>
                        </li>
                    @endforeach
                </ul>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-[#c5c6cf] bg-white p-12 text-center">
                <span class="material-symbols-outlined text-4xl text-[#75777f]">info</span>
                <p class="mt-2 text-sm text-[#44474e]">Tata tertib belum diatur oleh pengelola kost.</p>
            </div>
        @endforelse
    </div>

    <!-- Catatan & Konfirmasi -->
    <section class="rounded-2xl bg-[#e7e2d8]/60 p-6 text-[#1a1c1b] sm:p-8">
        <div class="flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
                <span class="material-symbols-outlined text-2xl text-[#031636]">check_circle</span>
                <div>
                    <h3 class="font-semibold text-[#031636]">Aturan Berlaku untuk Seluruh Penghuni</h3>
                    <p class="mt-1 text-xs text-[#44474e]">
                        Dengan menempati kamar di {{ $businessName }}, penghuni dianggap telah membaca, memahami, dan menyetujui seluruh tata tertib di atas.
                    </p>
                </div>
            </div>
            <a href="{{ route('tenant.dashboard') }}" class="shrink-0 rounded-lg bg-[#031636] px-5 py-2.5 text-xs font-semibold uppercase tracking-wider text-white transition active:scale-95">
                Kembali ke Beranda
            </a>
        </div>
    </section>
</div>
@endsection
