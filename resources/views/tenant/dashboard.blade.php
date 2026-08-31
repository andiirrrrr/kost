@extends('layouts.tenant', ['title' => 'Dashboard'])

@section('content')
<div class="flex flex-col gap-6">
    <section class="flex items-start justify-between gap-4">
        <div><p class="text-sm text-slate-500">Halo,</p><h1 class="text-2xl font-bold tracking-tight sm:text-3xl">{{ $tenant->name }}</h1><p class="mt-1 text-sm text-slate-500">Kamar {{ $tenant->room?->room_number ?? '—' }} · Penghuni Aktif</p></div>
    </section>

    <section class="overflow-hidden rounded-3xl bg-slate-950 p-6 text-white shadow-lg sm:p-8">
        <p class="text-xs font-bold uppercase tracking-[.2em] text-amber-300">Tagihan terbaru</p>
        @if ($currentInvoice)
            <div class="mt-5 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                <div><p class="text-3xl font-bold sm:text-4xl">Rp {{ number_format($currentInvoice->total_amount, 0, ',', '.') }}</p><p class="mt-2 text-sm text-slate-300">{{ \Carbon\Carbon::create($currentInvoice->period_year, $currentInvoice->period_month)->locale('id')->translatedFormat('F Y') }}</p></div>
                <div class="flex flex-col gap-2 sm:text-right"><span class="text-xs text-slate-400">Jatuh tempo {{ $currentInvoice->due_date->locale('id')->translatedFormat('d F Y') }}</span><x-status-badge :status="$currentInvoice->status" class="self-start sm:self-end" /></div>
            </div>
            @if (in_array($currentInvoice->status, [\App\Enums\InvoiceStatus::UNPAID, \App\Enums\InvoiceStatus::OVERDUE], true) && ! $hasPendingPayment)
                <a href="{{ route('tenant.payments.create', $currentInvoice) }}" class="mt-6 inline-flex w-full justify-center rounded-xl bg-amber-400 px-4 py-3 font-bold text-slate-950 sm:w-auto">Ajukan Pembayaran</a>
            @elseif ($hasPendingPayment || $currentInvoice->status === \App\Enums\InvoiceStatus::PENDING)
                <p class="mt-6 rounded-xl bg-sky-400/15 px-4 py-3 text-sm font-semibold text-sky-200">Menunggu Verifikasi</p>
            @endif
        @else
            <p class="mt-5 text-slate-300">Belum ada tagihan untuk Anda.</p>
        @endif
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"><div class="flex items-center justify-between"><h2 class="font-bold">Tagihan terakhir</h2><a href="{{ route('tenant.invoices.index') }}" class="text-sm font-semibold text-amber-700">Lihat semua</a></div><div class="mt-4 flex flex-col divide-y divide-slate-100">@forelse($recentInvoices as $invoice)<a href="{{ route('tenant.invoices.show', $invoice) }}" class="flex items-center justify-between gap-3 py-3"><div><p class="font-semibold">{{ $invoice->invoice_number }}</p><p class="text-xs text-slate-500">{{ sprintf('%02d/%d', $invoice->period_month, $invoice->period_year) }}</p></div><x-status-badge :status="$invoice->status" /></a>@empty<p class="py-8 text-center text-sm text-slate-500">Belum ada tagihan untuk Anda.</p>@endforelse</div></section>
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"><div class="flex items-center justify-between"><h2 class="font-bold">Notifikasi terbaru</h2><a href="{{ route('tenant.notifications') }}" class="text-sm font-semibold text-amber-700">Lihat semua</a></div><div class="mt-4 flex flex-col divide-y divide-slate-100">@forelse($recentNotifications as $notification)<div class="py-3"><p class="font-semibold">{{ $notification->data['title'] ?? 'Notifikasi' }}</p><p class="mt-1 text-sm text-slate-500">{{ $notification->data['message'] ?? '' }}</p></div>@empty<p class="py-8 text-center text-sm text-slate-500">Semua sudah dibaca.</p>@endforelse</div></section>
    </div>
    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"><div class="flex items-center justify-between"><h2 class="font-bold">Pengumuman terbaru</h2><a href="{{ route('tenant.announcements.index') }}" class="text-sm font-semibold text-amber-700">Lihat semua</a></div><div class="mt-4 grid gap-3 sm:grid-cols-3">@forelse($announcements as $announcement)<article class="rounded-2xl bg-slate-50 p-4"><p class="text-xs text-slate-500">{{ $announcement->published_at?->locale('id')->translatedFormat('d M Y') }}</p><h3 class="mt-2 font-bold">{{ $announcement->title }}</h3><p class="mt-2 line-clamp-3 text-sm leading-6 text-slate-600">{{ $announcement->content }}</p></article>@empty<p class="col-span-full py-8 text-center text-sm text-slate-500">Belum ada pengumuman.</p>@endforelse</div></section>
</div>
@endsection
