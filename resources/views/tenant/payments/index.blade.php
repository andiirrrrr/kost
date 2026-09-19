@extends('layouts.tenant', ['title' => 'Riwayat Pembayaran'])

@section('content')
<div class="flex flex-col gap-6">
    <header><h1 class="text-3xl font-semibold tracking-tight">Riwayat Pembayaran</h1><p class="mt-2 text-[#44474e]">Pantau bukti pembayaran dan status verifikasinya.</p></header>
    <div class="grid gap-4 lg:grid-cols-2">
        @forelse ($payments as $payment)
            <article x-data="{ proofOpen: false }" x-on:keydown.escape.window="proofOpen = false" class="overflow-hidden rounded-xl border border-[#c5c6cf]/30 bg-white">
                <div class="flex items-start justify-between gap-3 p-5"><div><h2 class="font-semibold">{{ $payment->payment_number }}</h2><p class="mt-1 text-sm text-[#44474e]">{{ $payment->invoice?->invoice_number }} · {{ \Carbon\Carbon::create($payment->invoice?->period_year, $payment->invoice?->period_month)->locale('id')->translatedFormat('F Y') }}</p></div><span class="rounded-md bg-[#e8e8e6] px-2 py-1 text-[11px] font-semibold uppercase tracking-wider text-[#44474e]">{{ $payment->status->label() }}</span></div>
                <div class="flex items-end justify-between bg-[#f4f4f2] px-5 py-4"><p class="text-sm text-[#44474e]">{{ $payment->paid_at?->locale('id')->translatedFormat('j M Y, H:i') }}</p><p class="text-lg font-semibold">Rp {{ number_format($payment->amount, 0, ',', '.') }}</p></div>
                @if ($payment->status === \App\Enums\PaymentStatus::REJECTED)<p class="mx-5 mt-4 rounded-lg bg-[#ffdad6] px-4 py-3 text-sm text-[#93000a]"><strong>Alasan:</strong> {{ $payment->rejection_reason }}</p>@endif
                @php
                    $canRetry = $payment->status === \App\Enums\PaymentStatus::REJECTED
                        && $payment->invoice
                        && in_array($payment->invoice->status, [\App\Enums\InvoiceStatus::UNPAID, \App\Enums\InvoiceStatus::OVERDUE], true)
                        && ! $payment->invoice->payments->contains('status', \App\Enums\PaymentStatus::PENDING);
                @endphp
                @if ($payment->proof || $canRetry || $payment->status === \App\Enums\PaymentStatus::VERIFIED)
                    <div class="grid gap-2 p-5 {{ $payment->proof && $canRetry ? 'sm:grid-cols-2' : '' }}">
                        @if ($payment->proof)<button type="button" x-on:click="proofOpen = true" class="flex items-center justify-center gap-2 rounded-lg border border-[#c5c6cf] px-4 py-3 text-sm font-semibold text-[#031636]"><span class="material-symbols-outlined text-lg">visibility</span>Lihat Bukti</button>@endif
                        @if ($payment->status === \App\Enums\PaymentStatus::VERIFIED)<a href="{{ route('payments.receipt', $payment) }}" class="flex items-center justify-center gap-2 rounded-lg border border-[#c5c6cf] px-4 py-3 text-sm font-semibold text-[#031636]"><span class="material-symbols-outlined text-lg">print</span>Kwitansi</a>@endif
                        @if ($canRetry)<a href="{{ route('tenant.payments.create', $payment->invoice) }}" class="flex items-center justify-center gap-2 rounded-lg bg-[#031636] px-4 py-3 text-sm font-semibold text-white"><span class="material-symbols-outlined text-lg">refresh</span>Bayar Ulang</a>@endif
                    </div>
                @endif
                @if ($payment->proof)
                    <template x-teleport="body">
                        <div x-cloak x-show="proofOpen" x-transition.opacity class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 p-4" role="dialog" aria-modal="true" aria-label="Bukti pembayaran {{ $payment->payment_number }}">
                            <div x-on:click.outside="proofOpen = false" class="flex max-h-[94vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                                <header class="flex items-center justify-between border-b border-[#c5c6cf]/50 px-5 py-4"><div><h3 class="font-semibold">Bukti Pembayaran</h3><p class="mt-1 text-xs text-[#44474e]">{{ $payment->payment_number }}</p></div><button type="button" x-on:click="proofOpen = false" class="flex size-10 items-center justify-center rounded-full text-[#44474e] hover:bg-[#eeeeec]" aria-label="Tutup pratinjau"><span class="material-symbols-outlined">close</span></button></header>
                                <div class="overflow-y-auto p-4 sm:p-5"><template x-if="proofOpen"><x-payment-proof-preview :payment="$payment" /></template></div>
                            </div>
                        </div>
                    </template>
                @endif
            </article>
        @empty
            <div class="rounded-xl bg-[#f4f4f2] px-6 py-14 text-center lg:col-span-2"><span class="material-symbols-outlined text-4xl text-[#75777f]">payments</span><p class="mt-3 font-medium">Belum ada riwayat pembayaran.</p></div>
        @endforelse
    </div>
    {{ $payments->links() }}
</div>
@endsection
