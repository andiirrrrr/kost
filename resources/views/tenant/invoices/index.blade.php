@extends('layouts.tenant', ['title' => 'Tagihan Saya'])
@section('content')
<div class="flex flex-col gap-6">
    <header><h1 class="text-3xl font-semibold tracking-tight">Tagihan Saya</h1><p class="mt-2 text-[#44474e]">Kelola pembayaran properti Anda.</p></header>
    <nav class="-mx-5 flex gap-2 overflow-x-auto px-5 pb-1 sm:mx-0 sm:px-0" aria-label="Filter tagihan">
        @foreach (['' => 'Semua', 'unpaid' => 'Belum Bayar', 'pending' => 'Menunggu', 'paid' => 'Lunas', 'overdue' => 'Terlambat'] as $value => $label)
            <a href="{{ route('tenant.invoices.index', $value ? ['status' => $value] : []) }}" class="shrink-0 rounded-full px-4 py-2 text-sm font-semibold tracking-wide transition active:scale-95 {{ $filter === $value ? 'bg-[#031636] text-white' : 'bg-[#eeeeec] text-[#44474e] hover:bg-[#e8e8e6]' }}">{{ $label }}</a>
        @endforeach
    </nav>
    <div class="grid gap-4 lg:grid-cols-2">
        @forelse ($invoices as $invoice)
            <article class="overflow-hidden rounded-xl border border-[#c5c6cf]/30 bg-white">
                <div class="flex items-start justify-between gap-4 p-5">
                    <div><h2 class="text-xl font-medium">{{ \Carbon\Carbon::create($invoice->period_year, $invoice->period_month)->locale('id')->translatedFormat('F Y') }}</h2><p class="mt-1 text-xs font-semibold tracking-wider text-[#44474e]">{{ $invoice->invoice_number }}</p></div>
                    <span class="rounded-md bg-[#e8e8e6] px-2 py-1 text-[11px] font-semibold uppercase tracking-wider text-[#44474e]">{{ $invoice->status->label() }}</span>
                </div>
                <div class="flex items-center justify-between bg-[#f4f4f2] px-5 py-4">
                    <div><p class="font-semibold">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</p><p class="mt-1 flex items-center gap-1 text-xs font-semibold {{ $invoice->status === \App\Enums\InvoiceStatus::OVERDUE ? 'text-[#ba1a1a]' : 'text-[#44474e]' }}"><span class="material-symbols-outlined text-sm">calendar_today</span>Jatuh tempo: {{ $invoice->due_date->locale('id')->translatedFormat('j M Y') }}</p></div>
                </div>
                <div class="p-5"><a href="{{ route('tenant.invoices.show', $invoice) }}" class="flex w-full items-center justify-center rounded-lg {{ in_array($invoice->status, [\App\Enums\InvoiceStatus::UNPAID, \App\Enums\InvoiceStatus::OVERDUE], true) ? 'bg-[#031636] text-white' : 'border border-[#c5c6cf] text-[#031636]' }} px-4 py-3 text-xs font-semibold uppercase tracking-wider transition active:scale-[.98]">{{ in_array($invoice->status, [\App\Enums\InvoiceStatus::UNPAID, \App\Enums\InvoiceStatus::OVERDUE], true) ? 'Lihat Detail & Bayar' : 'Lihat Detail' }}</a></div>
            </article>
        @empty
            <div class="rounded-xl bg-[#f4f4f2] px-6 py-14 text-center lg:col-span-2"><span class="material-symbols-outlined text-4xl text-[#75777f]">receipt_long</span><p class="mt-3 font-medium">Belum ada tagihan untuk Anda.</p><p class="mt-1 text-sm text-[#44474e]">Tagihan yang diterbitkan admin akan tampil di halaman ini.</p></div>
        @endforelse
    </div>
    {{ $invoices->links() }}
</div>
@endsection
