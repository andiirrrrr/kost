<div class="mx-auto max-w-2xl">
    <a href="{{ route('tenant.invoices.show', $invoice) }}" class="text-sm font-semibold text-amber-700">← Detail tagihan</a>
    <div class="mt-5 flex flex-col gap-5">
        <section class="rounded-3xl bg-slate-950 p-6 text-white"><p class="text-sm text-slate-400">Total tagihan {{ $invoice->invoice_number }}</p><p class="mt-2 text-3xl font-bold">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</p></section>
        <section class="rounded-3xl border border-amber-200 bg-amber-50 p-5"><p class="text-xs font-bold uppercase tracking-widest text-amber-700">Tujuan Pembayaran</p><dl class="mt-4 grid gap-3 text-sm"><div><dt class="text-slate-500">Bank</dt><dd class="font-bold">{{ $bankName }}</dd></div><div><dt class="text-slate-500">Nomor Rekening</dt><dd class="font-mono text-lg font-bold">{{ $bankAccountNumber }}</dd></div><div><dt class="text-slate-500">Atas Nama</dt><dd class="font-bold">{{ $bankAccountHolder }}</dd></div></dl></section>
        <form wire:submit="submit" class="flex flex-col gap-5 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
            <h1 class="text-xl font-bold">Ajukan Pembayaran</h1>
            <label class="flex flex-col gap-2"><span class="text-sm font-semibold">Metode Pembayaran</span><select wire:model="paymentMethod" class="rounded-xl border border-slate-300 px-4 py-3"><option value="bank_transfer">Transfer Bank</option></select>@error('paymentMethod')<span class="text-sm text-red-600">{{ $message }}</span>@enderror</label>
            <label class="flex flex-col gap-2"><span class="text-sm font-semibold">Tanggal Transfer</span><input wire:model="paidAt" type="datetime-local" class="rounded-xl border border-slate-300 px-4 py-3">@error('paidAt')<span class="text-sm text-red-600">{{ $message }}</span>@enderror</label>
            <label class="flex flex-col gap-2"><span class="text-sm font-semibold">Bukti Pembayaran</span><input wire:model="proof" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf" class="block w-full rounded-xl border border-dashed border-slate-300 p-4 text-sm"><span class="text-xs text-slate-500">JPG, PNG, WEBP, atau PDF. Maksimal 5 MB.</span>@error('proof')<span class="text-sm text-red-600">{{ $message }}</span>@enderror</label>
            <div wire:loading wire:target="proof" class="text-sm text-slate-500">Mengunggah bukti…</div>
            @if ($proof && str_starts_with((string) $proof->getMimeType(), 'image/'))<img src="{{ $proof->temporaryUrl() }}" alt="Pratinjau bukti" class="max-h-72 w-full rounded-2xl object-contain">@endif
            <button wire:loading.attr="disabled" class="rounded-xl bg-slate-950 px-4 py-3 font-bold text-white disabled:opacity-50"><span wire:loading.remove wire:target="submit">Kirim Pembayaran</span><span wire:loading wire:target="submit">Mengirim…</span></button>
        </form>
    </div>
</div>
