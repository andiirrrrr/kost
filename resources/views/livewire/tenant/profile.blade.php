<div class="mx-auto flex max-w-3xl flex-col gap-8">
    <header class="flex flex-col items-center gap-3 text-center">
        <div class="flex size-24 items-center justify-center rounded-full bg-[#e7e2d8] text-[#031636] shadow-sm"><span class="material-symbols-outlined text-5xl">person</span></div>
        <div><h1 class="text-3xl font-semibold tracking-tight">{{ $tenant->name }}</h1><p class="mt-1 text-[#44474e]">Kamar {{ $tenant->room?->room_number ?? '—' }} · Bergabung {{ $tenant->move_in_date ? \Carbon\Carbon::parse($tenant->move_in_date)->locale('id')->translatedFormat('M Y') : '—' }}</p></div>
    </header>
    <section>
        <h2 class="text-2xl font-medium">Detail Hunian</h2><p class="mt-2 text-[#44474e]">Informasi profil penghuni dan unit yang ditempati.</p>
        <div class="mt-4 overflow-hidden rounded-xl border border-[#e2e3e1]/70 bg-white shadow-[0_4px_24px_rgba(26,43,76,0.04)]">
            <div class="flex flex-col gap-4 p-6">
                <div><p class="text-xs font-semibold uppercase tracking-wider text-[#44474e]">Nama Lengkap</p><p class="mt-1 text-lg font-medium">{{ $tenant->name }}</p></div>
                <div class="h-px bg-[#e2e3e1]/60"></div>
                <form wire:submit="updateContact" class="flex flex-col gap-4">
                    <label><span class="text-xs font-semibold uppercase tracking-wider text-[#44474e]">Nomor HP</span><input wire:model="phone" x-on:input="$el.value = $el.value.replace(/\D/g, '')" type="tel" inputmode="numeric" autocomplete="tel" class="mt-1 w-full border-0 border-b border-[#e2e3e1] bg-transparent px-0 py-2 text-lg outline-none focus:border-[#031636] focus:ring-0">@error('phone')<span class="mt-1 block text-sm text-[#ba1a1a]">{{ $message }}</span>@enderror</label>
                    <label><span class="text-xs font-semibold uppercase tracking-wider text-[#44474e]">Email</span><input wire:model="email" type="email" class="mt-1 w-full border-0 border-b border-[#e2e3e1] bg-transparent px-0 py-2 text-lg outline-none focus:border-[#031636] focus:ring-0">@error('email')<span class="mt-1 block text-sm text-[#ba1a1a]">{{ $message }}</span>@enderror</label>
                    <button class="self-start rounded-lg bg-[#031636] px-5 py-3 text-sm font-semibold text-white">Simpan Kontak</button>
                </form>
                <dl class="grid grid-cols-2 gap-6 border-t border-[#e2e3e1] pt-6">
                    @foreach ([['No. Kamar', $tenant->room?->room_number ?? '—'], ['Tipe', $tenant->room?->type ?? '—'], ['Harga Sewa', 'Rp '.number_format($tenant->monthly_price, 0, ',', '.')], ['Siklus Tagihan', 'Tanggal '.$tenant->move_in_date->day]] as [$label, $value])
                        <div><dt class="text-xs font-semibold uppercase tracking-wider text-[#44474e]">{{ $label }}</dt><dd class="mt-1 text-lg {{ $label === 'No. Kamar' ? 'font-semibold text-[#031636]' : '' }}">{{ $value }}</dd></div>
                    @endforeach
                </dl>
            </div>
        </div>
    </section>
    <section>
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-medium">Tata Tertib Kost</h2>
            <a href="{{ route('tenant.rules') }}" class="text-xs font-semibold uppercase tracking-wider text-[#031636] hover:underline">Lihat Lengkap</a>
        </div>
        <a href="{{ route('tenant.rules') }}" class="mt-4 flex items-center justify-between rounded-xl bg-white p-5 border border-[#e2e3e1]/80 shadow-[0_4px_24px_rgba(26,43,76,0.04)] transition active:scale-[.98]">
            <div class="flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-full bg-[#e7e2d8] text-[#031636]">
                    <span class="material-symbols-outlined">shield</span>
                </span>
                <div>
                    <span class="block font-medium text-[#031636]">Aturan & Perjanjian Sewa</span>
                    <span class="block text-xs text-[#44474e]">Pedoman keamanan, ketertiban, dan kenyamanan bersama</span>
                </div>
            </div>
            <span class="material-symbols-outlined text-[#44474e]">chevron_right</span>
        </a>
    </section>
    <section x-data="{ open: false }">
        <h2 class="text-2xl font-medium">Keamanan</h2>
        <button type="button" @click="open = ! open" class="mt-4 flex w-full items-center justify-between rounded-xl bg-[#e7e2d8] p-4 text-left transition active:scale-[.98]"><span class="flex items-center gap-3"><span class="flex size-10 items-center justify-center rounded-full bg-white text-[#031636]"><span class="material-symbols-outlined">lock</span></span><span><span class="block font-medium">Ganti Password</span><span class="block text-xs text-[#44474e]">Gunakan minimal 12 karakter</span></span></span><span class="material-symbols-outlined text-[#44474e]" x-text="open ? 'expand_less' : 'chevron_right'"></span></button>
        <form x-show="open" x-collapse wire:submit="updatePassword" class="mt-3 flex flex-col gap-4 rounded-xl bg-[#f4f4f2] p-5">
            <label class="flex flex-col gap-2"><span class="text-sm font-semibold">Password Saat Ini</span><input wire:model="currentPassword" type="password" class="rounded-lg border border-[#c5c6cf] bg-white px-4 py-3">@error('currentPassword')<span class="text-sm text-[#ba1a1a]">{{ $message }}</span>@enderror</label>
            <label class="flex flex-col gap-2"><span class="text-sm font-semibold">Password Baru</span><input wire:model="password" type="password" class="rounded-lg border border-[#c5c6cf] bg-white px-4 py-3">@error('password')<span class="text-sm text-[#ba1a1a]">{{ $message }}</span>@enderror</label>
            <label class="flex flex-col gap-2"><span class="text-sm font-semibold">Konfirmasi Password</span><input wire:model="passwordConfirmation" type="password" class="rounded-lg border border-[#c5c6cf] bg-white px-4 py-3"></label>
            <button class="rounded-lg bg-[#031636] px-4 py-3 font-semibold text-white">Ganti Password</button>
        </form>
    </section>
    <form method="POST" action="{{ route('logout') }}">@csrf<button class="flex w-full items-center justify-center gap-2 py-4 text-sm font-semibold uppercase tracking-wider text-[#44474e] transition hover:text-[#ba1a1a]"><span class="material-symbols-outlined text-xl">logout</span>Keluar Akun</button></form>

</div>
