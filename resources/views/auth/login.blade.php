<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Penghuni — {{ $settings['business_name'] }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f9f9f7] text-[#1a1c1b] antialiased">
@php
    $phone = preg_replace('/\D/', '', $settings['contact_phone'] ?? '');
    $whatsAppPhone = str_starts_with($phone, '0') ? '62'.substr($phone, 1) : $phone;
    $whatsAppMessage = 'Halo pengelola ' . ($settings['business_name'] ?? 'Kost') . ', saya lupa password akun penghuni saya. Mohon bantuan untuk reset password ke default. Terima kasih.';
    $whatsAppUrl = 'https://wa.me/' . $whatsAppPhone . '?text=' . urlencode($whatsAppMessage);
@endphp

<main class="relative flex min-h-screen items-center justify-center overflow-hidden px-6 py-10">

    <div class="pointer-events-none absolute -left-32 -top-32 size-96 rounded-full bg-[#031636]/5"></div>
    <div class="pointer-events-none absolute -bottom-48 -right-40 size-[30rem] rotate-45 rounded-[6rem] bg-[#e9c176]/10"></div>

    <div class="relative flex w-full flex-col items-center">
        <a href="{{ route('home') }}" class="mb-6 flex items-center gap-2 text-[#031636]">
            <span class="material-symbols-outlined text-[28px]">apartment</span>
            <span class="font-semibold">{{ $settings['business_name'] }}</span>
        </a>

        <section class="w-full max-w-md overflow-hidden rounded-xl border border-[#c5c6cf]/40 bg-white shadow-[0_10px_35px_rgba(3,22,54,0.08)]">
            <div class="flex flex-col p-7 sm:p-8">
                <div class="mb-8">
                    <div class="mb-5 flex size-12 items-center justify-center rounded-xl bg-[#031636]/10 text-[#031636]"><span class="material-symbols-outlined text-[28px]">person</span></div>
                    <h1 class="text-3xl font-semibold tracking-tight">Login Penghuni</h1>
                    <p class="mt-2 leading-7 text-[#44474e]">Akses tagihan, pembayaran, notifikasi, dan informasi kost Anda.</p>
                </div>

                <form method="POST" action="{{ route('login') }}" class="flex w-full flex-col gap-5">
                    @csrf
                    <label class="flex flex-col gap-2" for="tenant-email">
                        <span class="text-sm font-semibold">Alamat Email</span>
                        <div class="relative">
                            <span class="material-symbols-outlined pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-xl text-[#75777f]">mail</span>
                            <input id="tenant-email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus placeholder="nama@email.com" class="h-12 w-full rounded-lg border border-transparent bg-[#f4f4f2] pl-12 pr-4 outline-none transition placeholder:text-[#75777f]/70 focus:border-[#031636]/30 focus:bg-white focus:ring-4 focus:ring-[#031636]/5">
                        </div>
                        @error('email')<span class="text-sm font-medium text-[#ba1a1a]">{{ $message }}</span>@enderror
                    </label>

                    <label class="flex flex-col gap-2" for="tenant-password">
                        <span class="text-sm font-semibold">Password</span>
                        <div class="relative">
                            <span class="material-symbols-outlined pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-xl text-[#75777f]">lock</span>
                            <input id="tenant-password" name="password" type="password" autocomplete="current-password" required placeholder="Masukkan password" class="h-12 w-full rounded-lg border border-transparent bg-[#f4f4f2] pl-12 pr-12 outline-none transition placeholder:text-[#75777f]/70 focus:border-[#031636]/30 focus:bg-white focus:ring-4 focus:ring-[#031636]/5">
                            <button type="button" data-password-toggle="tenant-password" class="absolute right-3 top-1/2 flex -translate-y-1/2 rounded-md p-1 text-[#75777f] transition hover:text-[#031636]" aria-label="Tampilkan password" aria-pressed="false"><span class="material-symbols-outlined text-xl">visibility</span></button>
                        </div>
                        @error('password')<span class="text-sm font-medium text-[#ba1a1a]">{{ $message }}</span>@enderror
                        <button type="button" data-modal-open="forgot-password-modal" class="text-right text-sm font-semibold text-[#031636] transition hover:underline">Lupa password?</button>
                    </label>

                    <label class="flex cursor-pointer items-center gap-3 text-sm text-[#44474e]">
                        <input name="remember" type="checkbox" value="1" @checked(old('remember')) class="size-5 rounded border-[#75777f] accent-[#031636]">
                        Ingat saya
                    </label>

                    <button type="submit" class="mt-1 flex h-12 w-full items-center justify-center gap-2 rounded-lg bg-[#031636] text-sm font-semibold text-white shadow-sm transition hover:bg-[#1a2b4c] focus:outline-none focus:ring-4 focus:ring-[#031636]/20">
                        Masuk <span class="material-symbols-outlined text-lg">arrow_forward</span>
                    </button>

                    <a href="{{ route('home') }}" class="mt-1 flex items-center justify-center gap-1.5 py-1 text-xs font-semibold text-[#75777f] transition hover:text-[#031636] sm:text-sm">
                        <span class="material-symbols-outlined text-base">arrow_back</span>
                        <span>Kembali ke Beranda</span>
                    </a>
                </form>
            </div>

            <div class="border-t border-[#c5c6cf]/40 px-8 py-5 text-center text-sm leading-6 text-[#44474e]">
                Belum memiliki akun?<br>
                <a href="https://wa.me/{{ $whatsAppPhone }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-[#031636] hover:underline">Hubungi pengelola kost</a>
            </div>
        </section>

        <div class="mt-7 flex items-center gap-2 text-sm font-semibold text-[#44474e]/70">
            <span class="material-symbols-outlined text-base">lock</span>
            <span>Koneksi login dilindungi</span>
        </div>
    </div>

    <!-- Modal Lupa Password -->
    <div id="forgot-password-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4 backdrop-blur-xs" role="dialog" aria-modal="true" aria-labelledby="modal-forgot-title">
        <div class="w-full max-w-md rounded-2xl border border-[#c5c6cf]/40 bg-white p-6 shadow-2xl sm:p-8">
            <div class="mb-4 flex size-12 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                <span class="material-symbols-outlined text-2xl">lock_reset</span>
            </div>
            <h2 id="modal-forgot-title" class="text-xl font-bold tracking-tight text-[#1a1c1b]">Lupa Password?</h2>
            <p class="mt-2 text-sm leading-6 text-[#44474e]">
                Untuk menjaga keamanan akun hunian Anda, pengaturan ulang password dilakukan langsung oleh pengelola kost melalui sistem admin.
            </p>
            <div class="mt-4 rounded-xl border border-amber-200/60 bg-amber-50/60 p-3.5 text-xs leading-5 text-amber-900">
                <span class="font-bold">Prosedur Reset:</span> Pengelola akan mereset password akun Anda ke <strong>password default</strong>. Setelah berhasil masuk, Anda dapat langsung mengubah password baru di menu profil penghuni.
            </div>
            <div class="mt-6 flex flex-col gap-2.5">
                <a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener noreferrer" class="flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 active:scale-[0.99]">
                    <span class="material-symbols-outlined text-xl">chat</span>
                    <span>Hubungi Pengelola via WhatsApp</span>
                </a>
                <button type="button" data-modal-close="forgot-password-modal" class="flex h-11 w-full items-center justify-center rounded-xl border border-[#c5c6cf]/50 text-sm font-semibold text-[#44474e] transition hover:bg-[#f4f4f2] active:scale-[0.99]">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</main>

<script>
    document.querySelectorAll('[data-modal-open]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = document.getElementById(btn.dataset.modalOpen);
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        });
    });
    document.querySelectorAll('[data-modal-close]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = document.getElementById(btn.dataset.modalClose);
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        });
    });
    const forgotModal = document.getElementById('forgot-password-modal');
    if (forgotModal) {
        forgotModal.addEventListener('click', (e) => {
            if (e.target === forgotModal) {
                forgotModal.classList.add('hidden');
                forgotModal.classList.remove('flex');
            }
        });
    }
</script>
</body>
</html>
