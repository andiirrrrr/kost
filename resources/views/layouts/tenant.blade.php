<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Portal Penghuni' }} — {{ $businessName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-[100dvh] bg-[#f9f9f7] text-[#1a1c1b] antialiased">
    <div x-data="{ shown: false, type: 'success', message: '', timeout: null, show(detail) { this.type = detail.type; this.message = detail.message; this.shown = true; clearTimeout(this.timeout); this.timeout = setTimeout(() => this.shown = false, 5000); } }" x-on:tenant-toast.window="show($event.detail)" x-show="shown" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-4 opacity-0" x-transition:enter-end="translate-x-0 opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0 opacity-100" x-transition:leave-end="translate-x-4 opacity-0" class="fixed right-5 top-[4.25rem] z-[70] w-[calc(100vw-2.5rem)] max-w-sm" :role="type === 'success' ? 'status' : 'alert'" aria-live="polite">
        <div class="flex items-start gap-3 rounded-xl border px-4 py-3 shadow-lg" :class="type === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-red-200 bg-red-50 text-[#ba1a1a]'">
            <span class="material-symbols-outlined text-xl" x-text="type === 'success' ? 'check_circle' : 'error'"></span>
            <p class="flex-1 text-sm font-medium" x-text="message"></p>
            <button type="button" @click="shown = false" class="-mr-1 -mt-1 flex size-7 items-center justify-center rounded-md hover:bg-black/5" aria-label="Tutup notifikasi"><span class="material-symbols-outlined text-lg">close</span></button>
        </div>
    </div>
    <header class="fixed inset-x-0 top-0 z-50 bg-[#f9f9f7]/80 pt-safe shadow-[0_1px_8px_rgba(0,0,0,0.04)] backdrop-blur-xl">
        <div class="mx-auto flex h-16 max-w-[1200px] items-center justify-between px-5 sm:px-8 lg:px-10">
            <a href="{{ route('tenant.dashboard') }}" class="max-w-48 truncate text-xl font-semibold uppercase tracking-tight text-[#031636]">{{ $businessName }}</a>
            <nav class="hidden items-center gap-1 md:flex">
                @foreach ([['tenant.dashboard', 'Beranda'], ['tenant.invoices.index', 'Tagihan'], ['tenant.payments.index', 'Riwayat Bayar'], ['tenant.maintenance.index', 'Perbaikan'], ['tenant.announcements.index', 'Pengumuman'], ['tenant.rules', 'Tata Tertib'], ['tenant.profile', 'Profil']] as [$routeName, $label])
                    <a href="{{ route($routeName) }}" class="rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs($routeName) || ($routeName === 'tenant.payments.index' && request()->routeIs('tenant.payments.*')) ? 'bg-[#e7e2d8] text-[#031636]' : 'text-[#44474e] hover:bg-[#eeeeec]' }}">{{ $label }}</a>
                @endforeach
            </nav>
            <div class="flex items-center gap-2">
                <livewire:tenant.notification-badge />
                <a href="{{ route('tenant.profile') }}" class="flex size-10 items-center justify-center rounded-full bg-[#e7e2d8] text-[#67645c] md:hidden" aria-label="Profil"><span class="material-symbols-outlined text-xl">person</span></a>
            </div>
        </div>
    </header>
    <main class="mx-auto max-w-[1200px] px-5 pb-28 pt-24 sm:px-8 lg:px-10">
        @if (session('success'))<div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
        {{ $slot ?? '' }}
        @yield('content')
    </main>
    <nav class="fixed inset-x-0 bottom-0 z-50 bg-[#f9f9f7]/90 pb-safe shadow-[0_-1px_8px_rgba(0,0,0,0.04)] backdrop-blur-xl md:hidden">
        <div class="mx-auto grid h-16 max-w-md grid-cols-5 px-2">
            @foreach ([['tenant.dashboard', 'home', 'Beranda'], ['tenant.invoices.index', 'receipt_long', 'Tagihan'], ['tenant.payments.index', 'history', 'Riwayat'], ['tenant.maintenance.index', 'build', 'Perbaikan'], ['tenant.profile', 'person', 'Profil']] as [$routeName, $icon, $label])
                <a href="{{ route($routeName) }}" class="flex min-w-16 flex-col items-center justify-center gap-1 text-[10px] font-semibold uppercase tracking-wider {{ request()->routeIs($routeName) || ($routeName === 'tenant.payments.index' && request()->routeIs('tenant.payments.*')) ? 'text-[#031636]' : 'text-[#44474e]' }}"><span class="material-symbols-outlined text-2xl">{{ $icon }}</span>{{ $label }}</a>
            @endforeach
        </div>
    </nav>
    @livewireScripts
</body>
</html>
