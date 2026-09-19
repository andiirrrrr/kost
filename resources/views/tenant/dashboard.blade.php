@extends('layouts.tenant', ['title' => 'Beranda'])
@section('content')
<div class="flex flex-col gap-8 pb-4">
    <section class="flex items-start justify-between gap-4">
        <div><h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">Selamat datang, {{ $tenant->name }}</h1><p class="mt-1 text-base text-[#44474e]">Kamar {{ $tenant->room?->room_number ?? '—' }}</p></div>
        <div class="flex shrink-0 items-center gap-2 rounded-full bg-[#e8e8e6] px-3 py-2 text-[#44474e] shadow-sm"><span class="size-2 rounded-full bg-[#615e57]"></span><span class="hidden text-xs font-semibold sm:inline">Penghuni {{ $tenant->status->label() }}</span></div>
    </section>
    <section class="overflow-hidden rounded-xl bg-[#031636] p-6 text-white shadow-xl sm:p-8">
        @if ($currentInvoice)
            <div class="flex items-start justify-between gap-4"><div><p class="text-xs font-semibold uppercase tracking-[.1em] text-white/70">Tagihan Bulan Ini</p><p class="mt-1 text-lg">{{ \Carbon\Carbon::create($currentInvoice->period_year, $currentInvoice->period_month)->locale('id')->translatedFormat('F Y') }}</p></div><span class="rounded-full bg-[#ffdea5] px-4 py-2 text-xs font-semibold text-[#261900]">{{ $currentInvoice->status->label() }}</span></div>
            <div class="mt-8"><h2 class="text-3xl font-semibold tracking-tight sm:text-5xl">Rp {{ number_format($currentInvoice->total_amount, 0, ',', '.') }}</h2><p class="mt-3 flex items-center gap-2 text-sm text-white/70 sm:text-base"><span class="material-symbols-outlined text-lg">calendar_today</span>Jatuh tempo: {{ $currentInvoice->due_date->locale('id')->translatedFormat('j F Y') }}</p></div>
            @if (in_array($currentInvoice->status, [\App\Enums\InvoiceStatus::UNPAID, \App\Enums\InvoiceStatus::OVERDUE], true) && ! $hasPendingPayment)
                <a href="{{ route('tenant.payments.create', $currentInvoice) }}" class="mt-10 flex w-full items-center justify-center rounded-lg bg-[#f9f9f7] px-4 py-4 text-sm font-semibold text-[#031636] transition active:scale-[.98]">Ajukan Pembayaran</a>
            @elseif ($hasPendingPayment || $currentInvoice->status === \App\Enums\InvoiceStatus::PENDING)
                <div class="mt-10 flex w-full items-center justify-center rounded-lg bg-white/10 px-4 py-4 text-sm font-semibold">Menunggu Verifikasi</div>
            @else
                <a href="{{ route('tenant.invoices.show', $currentInvoice) }}" class="mt-10 flex w-full items-center justify-center rounded-lg bg-[#f9f9f7] px-4 py-4 text-sm font-semibold text-[#031636]">Lihat Detail Tagihan</a>
            @endif
        @else
            <p class="text-xs font-semibold uppercase tracking-[.1em] text-white/70">Tagihan Bulan Ini</p><h2 class="mt-8 text-3xl font-semibold">Belum ada tagihan</h2><p class="mt-2 text-white/70">Tagihan yang dibuat admin akan tampil di sini.</p>
        @endif
    </section>
    <section><h2 class="mb-4 text-2xl font-medium">Layanan Penghuni</h2><div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        @foreach ([[route('tenant.maintenance.index'), 'build', 'Lapor Perbaikan'], [route('tenant.payments.index'), 'history', 'Riwayat Bayar'], [route('tenant.announcements.index'), 'campaign', 'Pengumuman'], [route('tenant.rules'), 'shield', 'Tata Tertib'], ['https://wa.me/'.preg_replace('/\D+/', '', $contactPhone), 'support_agent', 'Bantuan']] as [$url, $icon, $label])
            <a href="{{ $url }}" @if ($label === 'Bantuan') target="_blank" rel="noopener noreferrer" @endif class="flex min-h-28 flex-col items-start gap-4 rounded-xl bg-[#e7e2d8]/50 p-5 text-[#67645c] transition active:bg-[#e7e2d8]"><span class="material-symbols-outlined text-[#031636]">{{ $icon }}</span><span class="font-medium">{{ $label }}</span></a>
        @endforeach
    </div></section>
    <section><div class="mb-2 flex items-center justify-between gap-4"><h2 class="text-2xl font-medium">Notifikasi Terbaru</h2><a href="{{ route('tenant.notifications') }}" class="shrink-0 text-sm font-semibold text-[#031636]">Lihat Semua</a></div><div class="flex flex-col">
        @forelse ($recentNotifications as $notification)
            <article class="flex items-start gap-4 border-b border-[#c5c6cf]/30 py-4 {{ $notification->read_at ? 'opacity-60' : '' }}"><span class="mt-2 size-2 shrink-0 rounded-full {{ $notification->read_at ? 'bg-transparent' : 'bg-[#031636]' }}"></span><div><h3 class="font-medium">{{ $notification->data['title'] ?? 'Notifikasi' }}</h3><p class="mt-1 text-sm leading-6 text-[#44474e]">{{ $notification->data['message'] ?? '' }}</p>@if ($notification->data['resource_unavailable'] ?? false)<p class="mt-2 text-xs font-semibold text-[#75777f]">Tagihan ini telah dihapus oleh admin</p>@endif<p class="mt-2 text-xs font-semibold text-[#44474e]/50">{{ $notification->created_at->locale('id')->diffForHumans() }}</p></div></article>
        @empty <p class="rounded-xl bg-[#f4f4f2] px-5 py-8 text-center text-sm text-[#44474e]">Belum ada notifikasi.</p> @endforelse
    </div></section>
    <section><div class="mb-4 flex items-center justify-between gap-4"><h2 class="text-2xl font-medium">Pengumuman Kost</h2><a href="{{ route('tenant.announcements.index') }}" class="shrink-0 text-sm font-semibold text-[#031636]">Lihat Semua</a></div><div class="grid gap-3 md:grid-cols-3">
        @forelse ($announcements as $announcement)
            <article class="flex flex-col gap-4 rounded-xl bg-[#f4f4f2] p-6"><div><p class="mb-2 text-xs font-semibold uppercase tracking-wider text-[#44474e]/60">{{ $announcement->published_at?->locale('id')->translatedFormat('j M Y') }}</p><h3 class="text-xl font-medium">{{ $announcement->title }}</h3><p class="mt-2 line-clamp-3 text-sm leading-6 text-[#44474e]">{{ $announcement->content }}</p></div><a href="{{ route('tenant.announcements.index') }}" class="mt-auto text-xs font-semibold uppercase tracking-widest text-[#031636]">Baca Selengkapnya</a></article>
        @empty <p class="rounded-xl bg-[#f4f4f2] px-5 py-8 text-center text-sm text-[#44474e] md:col-span-3">Belum ada pengumuman dari admin.</p> @endforelse
    </div></section>
</div>
@endsection
