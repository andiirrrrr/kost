<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lupa Password — {{ $settings['business_name'] ?? 'Kost' }}</title>
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
            <span class="font-semibold">{{ $settings['business_name'] ?? 'Kost' }}</span>
        </a>

        <section class="w-full max-w-md overflow-hidden rounded-xl border border-[#c5c6cf]/40 bg-white shadow-[0_10px_35px_rgba(3,22,54,0.08)]">
            <div class="flex flex-col p-7 sm:p-8">
                <div class="mb-6">
                    <div class="mb-5 flex size-12 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                        <span class="material-symbols-outlined text-2xl">lock_reset</span>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-[#1a1c1b]">Lupa Password Penghuni</h1>
                    <p class="mt-2 text-sm leading-6 text-[#44474e]">
                        Untuk menjaga keamanan akun Anda, permohonan reset password dilakukan langsung oleh pengelola kost melalui sistem admin.
                    </p>
                </div>

                <div class="rounded-xl border border-amber-200/60 bg-amber-50/60 p-4 text-xs leading-5 text-amber-900">
                    <span class="font-bold">Prosedur Reset:</span> Pengelola akan mereset password akun Anda kembali ke <strong>password default</strong>. Setelah berhasil login, Anda dapat langsung mengubah password baru di menu profil penghuni.
                </div>

                <div class="mt-6 flex flex-col gap-3">
                    <a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener noreferrer" class="flex h-12 w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 active:scale-[0.99]">
                        <span class="material-symbols-outlined text-xl">chat</span>
                        <span>Hubungi Pengelola via WhatsApp</span>
                    </a>

                    <a href="{{ route('login') }}" class="mt-1 flex items-center justify-center gap-1.5 py-1 text-xs font-semibold text-[#75777f] transition hover:text-[#031636] sm:text-sm">
                        <span class="material-symbols-outlined text-base">arrow_back</span>
                        <span>Kembali ke Halaman Login</span>
                    </a>
                </div>
            </div>

            <div class="border-t border-[#c5c6cf]/40 px-8 py-4 text-center text-xs text-[#75777f]">
                Layanan bantuan penghuni kost {{ $settings['business_name'] ?? '' }}
            </div>
        </section>

        <div class="mt-7 flex items-center gap-2 text-sm font-semibold text-[#44474e]/70">
            <span class="material-symbols-outlined text-base">lock</span>
            <span>Koneksi aman terverifikasi</span>
        </div>
    </div>
</main>
</body>
</html>
