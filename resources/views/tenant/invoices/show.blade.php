@extends('layouts.tenant', ['title' => $invoice->invoice_number])
@section('content')
<div class="mx-auto max-w-2xl">
    <a href="{{ route('tenant.invoices.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-[#031636]"><span class="material-symbols-outlined text-lg">arrow_back</span>Semua tagihan</a>
    <section class="mt-5 overflow-hidden rounded-xl border border-[#c5c6cf]/30 bg-white">
        <header class="flex items-start justify-between gap-4 p-6"><div><p class="text-xs font-semibold tracking-wider text-[#44474e]">{{ $invoice->invoice_number }}</p><h1 class="mt-2 text-2xl font-semibold">{{ \Carbon\Carbon::create($invoice->period_year, $invoice->period_month)->locale('id')->translatedFormat('F Y') }}</h1></div><span class="rounded-md bg-[#e8e8e6] px-2 py-1 text-[11px] font-semibold uppercase tracking-wider text-[#44474e]">{{ $invoice->status->label() }}</span></header>
        <dl class="flex flex-col gap-3 bg-[#f4f4f2] p-6 text-sm">
            @foreach ([['Sewa', $invoice->base_amount], ['Listrik', $invoice->electricity_amount], ['Air', $invoice->water_amount], ['Lainnya', $invoice->other_amount], ['Diskon', -$invoice->discount_amount]] as [$label, $amount])
                <div class="flex justify-between gap-4"><dt class="text-[#44474e]">{{ $label }}</dt><dd class="font-semibold">{{ $amount < 0 ? '-' : '' }}Rp {{ number_format(abs($amount), 0, ',', '.') }}</dd></div>
            @endforeach
            <div class="mt-2 flex justify-between border-t border-[#c5c6cf]/50 pt-4 text-lg"><dt class="font-semibold">Total</dt><dd class="font-semibold">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</dd></div>
        </dl>
        <div class="p-6"><p class="flex items-center gap-2 text-sm text-[#44474e]"><span class="material-symbols-outlined text-lg">calendar_today</span>Jatuh tempo: <strong>{{ $invoice->due_date->locale('id')->translatedFormat('j F Y') }}</strong></p>
            @if (in_array($invoice->status, [\App\Enums\InvoiceStatus::UNPAID, \App\Enums\InvoiceStatus::OVERDUE], true) && ! $invoice->payments->contains('status', \App\Enums\PaymentStatus::PENDING))
                <a href="{{ route('tenant.payments.create', $invoice) }}" class="mt-6 flex justify-center rounded-xl bg-[#031636] px-4 py-4 font-semibold text-white">Ajukan Pembayaran</a>
            @elseif ($invoice->status === \App\Enums\InvoiceStatus::PENDING || $invoice->payments->contains('status', \App\Enums\PaymentStatus::PENDING))
                <p class="mt-6 rounded-xl bg-[#e7e2d8] px-4 py-4 text-center text-sm font-semibold text-[#67645c]">Pembayaran sedang diverifikasi admin</p>
            @endif
            @if ($verifiedPayment)
                <a href="{{ route('payments.receipt', $verifiedPayment) }}" class="mt-6 flex items-center justify-center gap-2 rounded-xl border border-[#c5c6cf] px-4 py-4 font-semibold text-[#031636]" target="_blank" rel="noopener">
                    <span class="material-symbols-outlined">print</span>
                    Unduh Kwitansi
                </a>
            @endif
        </div>
    </section>
</div>
@endsection
