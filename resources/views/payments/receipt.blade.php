<!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Kwitansi {{ $payment->payment_number }}</title>@vite('resources/css/app.css')</head>
<body class="bg-slate-100 p-4 text-slate-900 sm:p-10">
<main class="mx-auto max-w-2xl rounded-2xl bg-white p-7 shadow-sm sm:p-10">
    <header class="flex items-start justify-between gap-5 border-b pb-6"><div><p class="text-sm font-semibold uppercase tracking-widest text-slate-500">Kwitansi Pembayaran</p><h1 class="mt-2 text-2xl font-bold">{{ \App\Models\Setting::get('business_name', 'Kost') }}</h1></div><p class="font-mono text-sm">{{ $payment->payment_number }}</p></header>
    <dl class="mt-7 grid grid-cols-2 gap-x-6 gap-y-5 text-sm"><div><dt class="text-slate-500">Diterima dari</dt><dd class="mt-1 font-semibold">{{ $payment->tenant?->name }}</dd></div><div><dt class="text-slate-500">Kamar</dt><dd class="mt-1 font-semibold">{{ $payment->tenant?->room?->room_number }}</dd></div><div><dt class="text-slate-500">Untuk tagihan</dt><dd class="mt-1 font-semibold">{{ $payment->invoice?->invoice_number }}</dd></div><div><dt class="text-slate-500">Tanggal bayar</dt><dd class="mt-1 font-semibold">{{ $payment->paid_at?->locale('id')->translatedFormat('j F Y, H:i') }}</dd></div></dl>
    <div class="my-8 rounded-xl bg-[#031636] p-6 text-white"><p class="text-sm text-white/70">Jumlah diterima</p><p class="mt-2 text-3xl font-bold">Rp {{ number_format($payment->amount, 0, ',', '.') }}</p></div>
    <p class="text-sm text-slate-500">Status: <strong class="text-emerald-700">Terverifikasi</strong></p>
    <button onclick="window.print()" class="mt-8 w-full rounded-lg bg-[#031636] px-4 py-3 font-semibold text-white print:hidden">Cetak / Simpan PDF</button>
</main>
</body></html>
