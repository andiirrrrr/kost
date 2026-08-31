<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Portal Penghuni' }} — {{ $businessName }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
            <a href="{{ route('tenant.dashboard') }}" class="font-bold tracking-tight">{{ $businessName }}</a>
            <nav class="hidden items-center gap-1 md:flex">
                @foreach ([
                    ['tenant.dashboard', 'Dashboard'], ['tenant.invoices.index', 'Tagihan'],
                    ['tenant.payments.index', 'Pembayaran'], ['tenant.notifications', 'Notifikasi'],
                    ['tenant.announcements.index', 'Pengumuman'], ['tenant.profile', 'Profil'],
                ] as [$routeName, $label])
                    <a href="{{ route($routeName) }}" class="rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs($routeName) ? 'bg-amber-50 text-amber-800' : 'text-slate-600 hover:bg-slate-100' }}">{{ $label }}</a>
                @endforeach
            </nav>
            <a href="{{ route('tenant.notifications') }}" class="relative rounded-full p-2 text-slate-600 hover:bg-slate-100" aria-label="Notifikasi">
                <span class="text-xl">🔔</span>
                @if ($unreadNotificationCount > 0)
                    <span class="absolute right-0 top-0 min-w-5 rounded-full bg-red-600 px-1 text-center text-xs font-bold leading-5 text-white">{{ min($unreadNotificationCount, 99) }}</span>
                @endif
            </a>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-6 pb-28 sm:px-6 md:pb-10">
        @if (session('success'))
            <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white px-2 pb-[max(.5rem,env(safe-area-inset-bottom))] pt-2 md:hidden">
        <div class="mx-auto grid max-w-md grid-cols-5">
            @foreach ([
                ['tenant.dashboard', '⌂', 'Home'], ['tenant.invoices.index', '▤', 'Tagihan'],
                ['tenant.payments.index', '＋', 'Bayar'], ['tenant.notifications', '●', 'Notifikasi'],
                ['tenant.profile', '○', 'Profil'],
            ] as [$routeName, $icon, $label])
                <a href="{{ route($routeName) }}" class="flex flex-col items-center gap-1 rounded-xl py-2 text-[11px] font-semibold {{ request()->routeIs($routeName) ? 'bg-amber-50 text-amber-800' : 'text-slate-500' }}"><span class="text-lg leading-none">{{ $icon }}</span>{{ $label }}</a>
            @endforeach
        </div>
    </nav>
    @livewireScripts
</body>
</html>
