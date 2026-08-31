<main class="relative flex min-h-screen items-center justify-center overflow-hidden px-6 py-10">
    <a href="{{ route('home') }}" class="fixed left-6 top-6 z-10 flex items-center gap-1.5 rounded-lg border border-[#c5c6cf]/50 bg-white/80 px-3 py-1.5 text-xs font-semibold text-[#44474e] shadow-xs backdrop-blur transition hover:border-[#031636]/30 hover:bg-white hover:text-[#031636] sm:left-8 sm:top-8 sm:px-3.5 sm:py-2 sm:text-sm">
        <span class="material-symbols-outlined text-lg">arrow_back</span>
        <span>Kembali ke Beranda</span>
    </a>

    <div class="pointer-events-none absolute -left-32 -top-32 size-96 rounded-full bg-[#031636]/5"></div>
    <div class="pointer-events-none absolute -bottom-48 -right-40 size-[30rem] rotate-45 rounded-[6rem] bg-[#e9c176]/10"></div>

    <div class="relative flex w-full flex-col items-center">
        <a href="{{ route('home') }}" class="mb-6 flex items-center gap-2 text-[#031636]">
            <span class="material-symbols-outlined text-[28px]">apartment</span>
            <span class="font-semibold">{{ $this->getBusinessName() }}</span>
        </a>

        <section class="w-full max-w-md overflow-hidden rounded-xl border border-[#c5c6cf]/40 bg-white shadow-[0_10px_35px_rgba(3,22,54,0.08)]">
            <div class="flex flex-col p-7 sm:p-8">
                <div class="mb-8">
                    <div class="mb-5 flex size-12 items-center justify-center rounded-xl bg-[#031636]/10 text-[#031636]">
                        <span class="material-symbols-outlined text-[28px]">shield</span>
                    </div>
                    <h1 class="text-3xl font-semibold tracking-tight">Login Pengelola</h1>
                    <p class="mt-2 leading-7 text-[#44474e]">Masuk ke dashboard pengelolaan kost.</p>
                </div>

                <form wire:submit="authenticate" class="flex w-full flex-col gap-5">
                    <label class="flex flex-col gap-2" for="admin-email">
                        <span class="text-sm font-semibold">Alamat Email</span>
                        <div class="relative">
                            <span class="material-symbols-outlined pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-xl text-[#75777f]">mail</span>
                            <input id="admin-email" wire:model="data.email" type="email" autocomplete="email" required autofocus placeholder="nama@email.com" class="h-12 w-full rounded-lg border border-transparent bg-[#f4f4f2] pl-12 pr-4 outline-none transition placeholder:text-[#75777f]/70 focus:border-[#031636]/30 focus:bg-white focus:ring-4 focus:ring-[#031636]/5">
                        </div>
                        @error('data.email')<span class="text-sm font-medium text-[#ba1a1a]">{{ $message }}</span>@enderror
                    </label>

                    <label class="flex flex-col gap-2" for="admin-password">
                        <span class="text-sm font-semibold">Password</span>
                        <div class="relative">
                            <span class="material-symbols-outlined pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-xl text-[#75777f]">lock</span>
                            <input id="admin-password" wire:model="data.password" type="password" autocomplete="current-password" required placeholder="Masukkan password" class="h-12 w-full rounded-lg border border-transparent bg-[#f4f4f2] pl-12 pr-12 outline-none transition placeholder:text-[#75777f]/70 focus:border-[#031636]/30 focus:bg-white focus:ring-4 focus:ring-[#031636]/5">
                            <button type="button" data-password-toggle="admin-password" class="absolute right-3 top-1/2 flex -translate-y-1/2 rounded-md p-1 text-[#75777f] transition hover:text-[#031636]" aria-label="Tampilkan password" aria-pressed="false">
                                <span class="material-symbols-outlined text-xl">visibility</span>
                            </button>
                        </div>
                        @error('data.password')<span class="text-sm font-medium text-[#ba1a1a]">{{ $message }}</span>@enderror
                    </label>

                    <label class="flex cursor-pointer items-center gap-3 text-sm text-[#44474e]">
                        <input wire:model="data.remember" type="checkbox" value="1" class="size-5 rounded border-[#75777f] accent-[#031636]">
                        Ingat saya
                    </label>

                    <button type="submit" class="mt-1 flex h-12 w-full items-center justify-center gap-2 rounded-lg bg-[#031636] text-sm font-semibold text-white shadow-sm transition hover:bg-[#1a2b4c] focus:outline-none focus:ring-4 focus:ring-[#031636]/20">
                        Masuk <span class="material-symbols-outlined text-lg">arrow_forward</span>
                    </button>
                </form>
            </div>

            <div class="border-t border-[#c5c6cf]/40 px-8 py-5 text-center text-sm leading-6 text-[#44474e]">
                Kembali ke portal penghuni?<br>
                <a href="{{ route('login') }}" class="font-semibold text-[#031636] hover:underline">Login sebagai penghuni</a>
            </div>
        </section>

        <div class="mt-7 flex items-center gap-2 text-sm font-semibold text-[#44474e]/70">
            <span class="material-symbols-outlined text-base">lock</span>
            <span>Koneksi login dilindungi</span>
        </div>
    </div>
</main>
