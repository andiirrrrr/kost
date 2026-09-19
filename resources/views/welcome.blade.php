<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $settings['tagline'] }}">
    <title>{{ $settings['business_name'] }} — Hunian Nyaman</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#f9f9f7] font-sans text-[#1a1c1b] antialiased">
@php
    $phone = preg_replace('/\D/', '', $settings['contact_phone']);
    $whatsAppPhone = str_starts_with($phone, '0') ? '62'.substr($phone, 1) : $phone;
    $whatsAppUrl = 'https://wa.me/'.$whatsAppPhone;
    $heroFallback = 'https://images.unsplash.com/photo-1600566753086-00f18fb6b3ea?auto=format&fit=crop&w=1800&q=85';
    $aboutFallback = 'https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?auto=format&fit=crop&w=1200&q=85';
    $roomFallbacks = [
        'https://images.unsplash.com/photo-1598928506311-c55ded91a20c?auto=format&fit=crop&w=900&q=85',
        'https://images.unsplash.com/photo-1616594039964-ae9021a400a0?auto=format&fit=crop&w=900&q=85',
        'https://images.unsplash.com/photo-1615874694520-474822394e73?auto=format&fit=crop&w=900&q=85',
    ];
@endphp

<header class="fixed inset-x-0 top-0 z-50 border-b border-black/5 bg-[#f9f9f7]/90 backdrop-blur-md">
    <div class="mx-auto flex h-20 max-w-7xl items-center justify-between gap-4 px-5 lg:px-10">
        <a href="#beranda" class="flex min-w-0 items-center gap-2 text-[#031636]"><span class="material-symbols-outlined text-[32px]">apartment</span><span class="truncate text-lg font-semibold tracking-tight sm:text-xl">{{ $settings['business_name'] }}</span></a>
        <nav class="hidden items-center gap-7 lg:flex" aria-label="Navigasi utama">
            @foreach(['beranda' => 'Beranda', 'tentang' => 'Tentang', 'kamar' => 'Kamar', 'fasilitas' => 'Fasilitas', 'lokasi' => 'Lokasi', 'kontak' => 'Kontak'] as $id => $label)
                <a href="#{{ $id }}" class="text-sm font-semibold text-[#44474e] transition hover:text-[#031636]">{{ $label }}</a>
            @endforeach
        </nav>
        <div class="flex shrink-0 items-center gap-3">
            <a href="{{ route('login') }}" class="hidden rounded-lg bg-[#e7e2d8] px-4 py-2 text-sm font-semibold text-[#1d1c16] transition hover:bg-[#cac6bd] lg:inline-flex">Login Penghuni</a>
            <a href="/admin" class="hidden rounded-lg bg-[#031636] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#1a2b4c] lg:inline-flex">Login Admin</a>
            <button type="button" data-mobile-menu-button class="inline-flex size-11 items-center justify-center rounded-xl border border-[#c5c6cf]/60 bg-white text-[#031636] shadow-sm transition active:scale-95 lg:hidden" aria-label="Buka menu" aria-controls="mobile-navigation" aria-expanded="false"><span class="material-symbols-outlined">menu</span></button>
        </div>
    </div>
</header>

<div data-mobile-menu id="mobile-navigation" class="pointer-events-none fixed inset-0 z-[60] lg:hidden" aria-hidden="true">
    <button type="button" data-mobile-menu-backdrop class="absolute inset-0 bg-[#031636]/45 opacity-0 backdrop-blur-sm transition-opacity duration-300" aria-label="Tutup menu"></button>
    <aside data-mobile-menu-panel class="absolute inset-y-0 right-0 flex w-[min(88vw,24rem)] translate-x-full flex-col bg-[#f9f9f7] shadow-2xl transition-transform duration-300 ease-out" aria-label="Navigasi seluler">
        <div class="flex items-center justify-between border-b border-[#c5c6cf]/40 px-5 py-5">
            <a href="#beranda" class="flex min-w-0 items-center gap-3 text-[#031636]">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-[#031636] text-white"><span class="material-symbols-outlined">apartment</span></span>
                <span class="min-w-0"><span class="block truncate font-semibold">{{ $settings['business_name'] }}</span><span class="block text-xs text-[#75777f]">Hunian nyaman Anda</span></span>
            </a>
            <button type="button" data-mobile-menu-close class="flex size-10 shrink-0 items-center justify-center rounded-full text-[#44474e] transition hover:bg-[#eeeeec]" aria-label="Tutup menu"><span class="material-symbols-outlined">close</span></button>
        </div>

        <nav class="flex-1 overflow-y-auto px-4 py-6">
            <p class="px-3 text-xs font-semibold uppercase tracking-[0.18em] text-[#75777f]">Jelajahi</p>
            <div class="mt-3 space-y-1">
                @foreach(['beranda' => ['home', 'Beranda'], 'tentang' => ['info', 'Tentang'], 'kamar' => ['bed', 'Kamar'], 'fasilitas' => ['verified', 'Fasilitas'], 'lokasi' => ['location_on', 'Lokasi'], 'kontak' => ['call', 'Kontak']] as $id => [$icon, $label])
                    <a href="#{{ $id }}" class="flex items-center gap-4 rounded-xl px-3 py-3 text-sm font-semibold text-[#44474e] transition hover:bg-[#e7e2d8]/60 hover:text-[#031636]"><span class="material-symbols-outlined text-[22px] text-[#031636]">{{ $icon }}</span>{{ $label }}<span class="material-symbols-outlined ml-auto text-lg text-[#a3a4aa]"></span></a>
                @endforeach
            </div>
        </nav>

        <div class="border-t border-[#c5c6cf]/40 bg-white/70 p-5 pb-[max(1.25rem,env(safe-area-inset-bottom))]">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[0.18em] text-[#75777f]">Akses portal</p>
            <div class="grid gap-3">
                <a href="{{ route('login') }}" class="flex items-center gap-3 rounded-xl border border-[#c5c6cf]/60 bg-white px-4 py-3.5 font-semibold text-[#031636] shadow-sm transition active:scale-[.98]"><span class="flex size-9 items-center justify-center rounded-lg bg-[#e7e2d8]"><span class="material-symbols-outlined text-xl">person</span></span>Login Penghuni<span class="material-symbols-outlined ml-auto text-xl">arrow_forward</span></a>
                <a href="/admin" class="flex items-center gap-3 rounded-xl bg-[#031636] px-4 py-3.5 font-semibold text-white shadow-lg shadow-[#031636]/20 transition active:scale-[.98]"><span class="flex size-9 items-center justify-center rounded-lg bg-white/10"><span class="material-symbols-outlined text-xl">admin_panel_settings</span></span>Login Admin<span class="material-symbols-outlined ml-auto text-xl">arrow_forward</span></a>
            </div>
        </div>
    </aside>
</div>

<main class="pt-20">
    <section id="beranda" class="relative flex min-h-[calc(100vh-5rem)] scroll-mt-20 items-center overflow-hidden bg-[#f4f4f2]">
        <div class="absolute inset-0"><div class="h-full w-full bg-cover bg-center opacity-35 mix-blend-multiply" style="background-image: url('{{ $settings['hero_image_url'] ?: $heroFallback }}')"></div><div class="absolute inset-0 bg-gradient-to-r from-[#f9f9f7] via-[#f9f9f7]/90 to-[#f9f9f7]/20"></div></div>
        <div class="relative mx-auto grid w-full max-w-7xl items-center gap-10 px-5 py-16 lg:grid-cols-12 lg:px-10 lg:py-20">
            <div class="flex flex-col gap-7 lg:col-span-7">
                <div class="inline-flex items-center gap-2 self-start rounded-full bg-[#031636]/5 px-4 py-2 text-sm font-semibold uppercase tracking-widest text-[#031636]"><span class="material-symbols-outlined text-base">apartment</span>{{ $settings['hero_eyebrow'] }}</div>
                <h1 class="text-4xl font-semibold leading-tight tracking-tight sm:text-5xl lg:text-6xl">{{ $settings['hero_title'] }}<br><span class="font-serif italic text-[#031636]">{{ $settings['hero_emphasis'] }}</span></h1>
                <p class="max-w-2xl text-lg leading-8 text-[#44474e]">{{ $settings['tagline'] }}</p>
                <div class="flex flex-col gap-4 sm:flex-row"><a href="#kamar" class="rounded-xl bg-[#031636] px-8 py-4 text-center text-sm font-semibold text-white shadow-lg shadow-[#031636]/20 transition hover:bg-[#1a2b4c]">Lihat Kamar</a><a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener noreferrer" class="rounded-xl border-2 border-[#031636] px-8 py-4 text-center text-sm font-semibold text-[#031636] transition hover:bg-[#031636]/5">Hubungi Pengelola</a></div>
                <div class="flex flex-wrap gap-x-6 gap-y-3 border-t border-[#c5c6cf]/50 pt-7 text-sm font-semibold text-[#44474e]">@foreach($settings['hero_benefits'] as $benefit)<div class="flex items-center gap-2"><span class="material-symbols-outlined text-xl text-[#031636]">{{ $benefit['icon'] }}</span>{{ $benefit['label'] }}</div>@endforeach</div>
            </div>
            <div class="relative hidden h-[580px] overflow-hidden rounded-2xl shadow-2xl shadow-[#031636]/10 lg:col-span-5 lg:block"><img src="{{ $settings['hero_image_url'] ?: $heroFallback }}" alt="Interior {{ $settings['business_name'] }}" class="h-full w-full object-cover transition duration-700 hover:scale-105"></div>
        </div>
    </section>

    <section id="fasilitas" class="scroll-mt-20 bg-[#f9f9f7] py-20"><div class="mx-auto max-w-7xl px-5 lg:px-10"><div class="mx-auto mb-10 max-w-2xl text-center"><h2 class="text-3xl font-semibold tracking-tight">Kenapa Memilih Kost Ini?</h2></div><div class="grid gap-8 md:grid-cols-3">@foreach($settings['advantages'] as $advantage)<article class="flex flex-col items-center gap-2 rounded-2xl bg-white p-8 text-center shadow-sm transition hover:-translate-y-1 hover:shadow-md"><div class="mb-2 flex size-16 items-center justify-center rounded-2xl bg-[#031636]/10"><span class="material-symbols-outlined text-[32px] text-[#031636]">{{ $advantage['icon'] }}</span></div><h3 class="text-xl font-semibold">{{ $advantage['title'] }}</h3><p class="leading-7 text-[#44474e]">{{ $advantage['description'] }}</p></article>@endforeach</div></div></section>

    <section id="tentang" class="scroll-mt-20 bg-[#f4f4f2] py-20"><div class="mx-auto grid max-w-7xl items-center gap-10 px-5 lg:grid-cols-2 lg:px-10"><div class="relative aspect-[4/5] overflow-hidden rounded-2xl shadow-xl shadow-[#031636]/5"><img src="{{ $settings['about_image_url'] ?: $aboutFallback }}" alt="Tentang {{ $settings['business_name'] }}" class="h-full w-full object-cover"></div><div class="flex flex-col gap-7 lg:pl-10"><div class="flex flex-col gap-2"><span class="text-sm font-semibold uppercase tracking-widest text-[#031636]">Tentang Kami</span><h2 class="text-3xl font-semibold tracking-tight">{{ $settings['about_title'] }}</h2><p class="leading-7 text-[#44474e]">{{ $settings['about_description'] }}</p></div><div class="flex flex-col gap-7 border-l-2 border-[#031636]/20 pl-5">@foreach($settings['about_points'] as $point)<div class="flex flex-col gap-2"><h3 class="text-xl font-medium">{{ $point['title'] }}</h3><p class="leading-7 text-[#44474e]">{{ $point['description'] }}</p></div>@endforeach</div></div></div></section>

    <section id="kamar" class="scroll-mt-20 bg-[#f9f9f7] py-20"><div class="mx-auto max-w-7xl px-5 lg:px-10"><div class="mb-10 flex flex-col justify-between gap-4 sm:flex-row sm:items-end"><div><span class="mb-2 block text-sm font-semibold uppercase tracking-widest text-[#031636]">Koleksi Hunian</span><h2 class="text-3xl font-semibold tracking-tight">Pilihan Kamar</h2></div><a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-1 text-sm font-semibold text-[#031636]">Tanyakan Ketersediaan <span class="material-symbols-outlined text-xl">arrow_forward</span></a></div>
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @forelse($roomCategories as $category)
                @php
                    $isAvailable = $category->available_rooms_count > 0;
                    $categoryImage = $category->landing_image ? asset('storage/'.$category->landing_image) : $roomFallbacks[$loop->index % count($roomFallbacks)];
                    $minimumPrice = $category->rooms->min('monthly_price') ?? $category->base_monthly_price;
                @endphp
                <article class="group flex h-full flex-col overflow-hidden rounded-xl bg-white shadow-sm transition hover:shadow-lg {{ $isAvailable ? '' : 'opacity-85' }}">
                    <div class="relative aspect-[3/2] overflow-hidden">
                        <img src="{{ $categoryImage }}" alt="Kamar kategori {{ $category->name }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105 {{ $isAvailable ? '' : 'grayscale-[30%]' }}">
                        <span class="absolute left-4 top-4 flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-bold shadow-sm backdrop-blur-sm {{ $isAvailable ? 'bg-emerald-50/95 text-emerald-700' : 'bg-[#f9f9f7]/95 text-[#44474e]' }}">
                            <span class="size-2 rounded-full {{ $isAvailable ? 'bg-emerald-500' : 'bg-[#75777f]' }}"></span>
                            {{ $isAvailable ? 'Sisa '.$category->available_rooms_count.' kamar' : 'Penuh' }}
                        </span>
                    </div>
                    <div class="flex grow flex-col p-5">
                        <p class="text-sm font-medium text-[#75777f]">{{ $category->listed_rooms_count }} unit dalam kategori ini</p>
                        <h3 class="mt-1 text-xl font-semibold">{{ $category->name }}</h3>
                        @if($minimumPrice)
                            <p class="mt-1 text-lg font-semibold text-[#031636]">Mulai Rp{{ number_format($minimumPrice, 0, ',', '.') }} <span class="text-sm font-normal text-[#44474e]">/ bulan</span></p>
                        @endif
                        <p class="mt-3 text-sm leading-6 text-[#44474e]">{{ $category->description ?: 'Kamar nyaman dengan fasilitas yang mendukung kebutuhan harian Anda.' }}</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach(($category->facilities ?? []) as $facility)
                                <span class="rounded-full bg-[#eeeeec] px-3 py-1 text-xs font-medium text-[#44474e]">{{ $facility }}</span>
                            @endforeach
                        </div>
                        <a href="{{ $isAvailable ? $whatsAppUrl : '#kamar' }}" target="{{ $isAvailable ? '_blank' : '_self' }}" rel="noopener noreferrer" class="mt-auto pt-6">
                            <span class="block rounded-lg px-4 py-3 text-center text-sm font-semibold {{ $isAvailable ? 'bg-[#e7e2d8] text-[#1d1c16] transition hover:bg-[#cac6bd]' : 'cursor-not-allowed bg-[#eeeeec] text-[#75777f]' }}">{{ $isAvailable ? 'Tanyakan Ketersediaan' : 'Saat Ini Penuh' }}</span>
                        </a>
                    </div>
                </article>
            @empty
                <div class="col-span-full rounded-2xl border border-dashed border-[#c5c6cf] bg-white py-14 text-center"><p class="font-semibold">Belum ada kategori kamar untuk ditampilkan.</p><p class="mt-2 text-sm text-[#44474e]">Silakan hubungi pengelola untuk informasi ketersediaan.</p></div>
            @endforelse
        </div>
    </div></section>

    <section class="bg-[#f4f4f2] py-20"><div class="mx-auto max-w-7xl px-5 lg:px-10"><div class="mx-auto mb-10 max-w-2xl text-center"><h2 class="text-3xl font-semibold tracking-tight">Akses Mudah untuk Penghuni & Pengelola</h2><p class="mt-2 text-[#44474e]">Platform terintegrasi yang memudahkan operasional hunian Anda.</p></div><div class="grid gap-8 md:grid-cols-2"><article class="relative flex min-h-72 flex-col justify-between overflow-hidden rounded-2xl bg-white p-8 shadow-sm"><span class="material-symbols-outlined absolute right-6 top-4 text-[120px] text-[#031636]/5">person</span><div class="relative"><div class="mb-4 flex size-12 items-center justify-center rounded-xl bg-[#031636]/10"><span class="material-symbols-outlined text-[#031636]">vpn_key</span></div><h3 class="text-2xl font-medium">Portal Penghuni</h3><p class="mt-2 max-w-sm leading-7 text-[#44474e]">Lihat tagihan, riwayat pembayaran, pengumuman, dan notifikasi kost dalam satu tempat.</p></div><a href="{{ route('login') }}" class="relative mt-6 flex self-start items-center gap-2 rounded-lg bg-[#e7e2d8] px-6 py-3 text-sm font-semibold text-[#1d1c16]">Masuk sebagai Penghuni <span class="material-symbols-outlined text-lg">arrow_forward</span></a></article><article class="relative flex min-h-72 flex-col justify-between overflow-hidden rounded-2xl bg-[#031636] p-8 text-white shadow-sm"><span class="material-symbols-outlined absolute right-6 top-4 text-[120px] text-white/10">admin_panel_settings</span><div class="relative"><div class="mb-4 flex size-12 items-center justify-center rounded-xl bg-white/20"><span class="material-symbols-outlined">dashboard</span></div><h3 class="text-2xl font-medium">Dashboard Pengelola</h3><p class="mt-2 max-w-sm leading-7 text-white/75">Kelola kamar, penghuni, pembayaran, laporan, dan operasional kost melalui satu dashboard.</p></div><a href="/admin" class="relative mt-6 flex self-start items-center gap-2 rounded-lg bg-white px-6 py-3 text-sm font-semibold text-[#031636]">Masuk sebagai Admin <span class="material-symbols-outlined text-lg">arrow_forward</span></a></article></div></div></section>

    <section id="lokasi" class="scroll-mt-20 bg-[#f9f9f7] py-20">
        <div class="mx-auto max-w-7xl px-5 lg:px-10">
            <div class="mb-10">
                <span class="mb-2 block text-sm font-semibold uppercase tracking-widest text-[#031636]">Lokasi Strategis</span>
                <h2 class="text-3xl font-semibold tracking-tight">Akses Mudah ke Pusat Kota</h2>
            </div>
            <div class="grid items-center gap-8 lg:grid-cols-2">
                @if(!empty($settings['maps_embed_url']))
                    <div class="relative aspect-video w-full overflow-hidden rounded-2xl border border-[#c5c6cf]/50 shadow-sm lg:aspect-square">
                        <iframe
                            src="{{ $settings['maps_embed_url'] }}"
                            class="h-full w-full border-0"
                            allowfullscreen=""
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            title="Peta Lokasi {{ $settings['business_name'] }}"
                        ></iframe>
                    </div>
                @else
                    <div class="relative flex aspect-video items-center justify-center overflow-hidden rounded-2xl border border-[#c5c6cf]/50 bg-[#e2e3e1] lg:aspect-square">
                        <div class="absolute inset-0 opacity-20 [background-image:radial-gradient(#031636_1px,transparent_1px)] [background-size:20px_20px]"></div>
                        <div class="relative flex flex-col items-center gap-2">
                            <span class="material-symbols-outlined text-5xl text-[#031636]">location_on</span>
                            <span class="text-sm font-semibold text-[#44474e]">Lokasi {{ $settings['business_name'] }}</span>
                        </div>
                    </div>
                @endif
                <div class="flex flex-col gap-7 lg:pl-8">
                    <div>
                        <h3 class="text-xl font-medium">Alamat</h3>
                        <p class="mt-2 text-lg leading-8 text-[#44474e]">{{ $settings['address'] }}</p>
                    </div>
                    <div>
                        <h3 class="text-xl font-medium">Landmark Terdekat</h3>
                        <div class="mt-4 flex flex-col gap-3">
                            @foreach($settings['landmarks'] as $landmark)
                                <div class="flex items-center gap-3 text-[#44474e]">
                                    <span class="material-symbols-outlined text-[#031636]">{{ $landmark['icon'] }}</span>
                                    <span>{{ $landmark['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @if($settings['map_url'])
                        <a href="{{ $settings['map_url'] }}" target="_blank" rel="noopener noreferrer" class="flex self-start items-center gap-2 rounded-xl border-2 border-[#031636] px-8 py-3 text-sm font-semibold text-[#031636] transition hover:bg-[#031636]/5">
                            <span class="material-symbols-outlined text-xl">map</span>Buka di Google Maps
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section id="kontak" class="scroll-mt-20 bg-[#f4f4f2] py-20"><div class="mx-auto max-w-7xl px-5 lg:px-10"><div class="mx-auto mb-10 max-w-2xl text-center"><span class="mb-2 block text-sm font-semibold uppercase tracking-widest text-[#031636]">Hubungi Kami</span><h2 class="text-3xl font-semibold tracking-tight">Siap Membantu Anda Menemukan Hunian Terbaik</h2></div><div class="grid gap-6 md:grid-cols-3">@foreach([['call', 'WhatsApp', $settings['contact_phone']], ['mail', 'Email', $settings['contact_email']], ['schedule', 'Jam Operasional', $settings['operating_hours']]] as [$icon, $title, $value])<article class="flex flex-col items-center gap-2 rounded-2xl bg-white p-8 text-center"><div class="mb-2 flex size-12 items-center justify-center rounded-full bg-[#031636]/10"><span class="material-symbols-outlined text-[#031636]">{{ $icon }}</span></div><h3 class="text-lg font-medium">{{ $title }}</h3><p class="text-[#44474e]">{{ $value }}</p></article>@endforeach</div><div class="mt-8 flex justify-center"><a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-3 rounded-xl bg-[#031636] px-10 py-4 text-sm font-semibold text-white shadow-lg shadow-[#031636]/20"><span class="material-symbols-outlined">chat</span>Hubungi via WhatsApp</a></div></div></section>
</main>

<footer class="border-t border-[#c5c6cf]/40 bg-[#f4f4f2] pb-8 pt-16"><div class="mx-auto max-w-7xl px-5 lg:px-10"><div class="grid gap-8 pb-12 md:grid-cols-4"><div><div class="mb-4 flex items-center gap-2 text-[#031636]"><span class="material-symbols-outlined">apartment</span><span class="text-xl font-semibold">{{ $settings['business_name'] }}</span></div><p class="leading-7 text-[#44474e]">Hunian nyaman dengan pengelolaan profesional, memberikan rasa tenang di setiap langkah Anda.</p></div><div><h3 class="mb-4 text-lg font-medium">Properti</h3><ul class="flex flex-col gap-2 text-[#44474e]"><li><a href="#tentang">Tentang Kami</a></li><li><a href="#kamar">Pilihan Kamar</a></li><li><a href="#fasilitas">Fasilitas</a></li></ul></div><div><h3 class="mb-4 text-lg font-medium">Portal</h3><ul class="flex flex-col gap-2 text-[#44474e]"><li><a href="{{ route('login') }}">Login Penghuni</a></li><li><a href="/admin">Login Admin</a></li></ul></div><div><h3 class="mb-4 text-lg font-medium">Kontak</h3><ul class="flex flex-col gap-3 text-[#44474e]"><li class="flex gap-2"><span class="material-symbols-outlined text-lg">location_on</span>{{ $settings['address'] }}</li><li class="flex gap-2"><span class="material-symbols-outlined text-lg">call</span>{{ $settings['contact_phone'] }}</li><li class="flex gap-2"><span class="material-symbols-outlined text-lg">mail</span>{{ $settings['contact_email'] }}</li></ul></div></div><div class="flex flex-col items-center justify-between gap-4 border-t border-[#c5c6cf] pt-6 text-sm text-[#44474e] sm:flex-row"><span>© {{ date('Y') }} {{ $settings['business_name'] }}. Hak cipta dilindungi.</span><a href="#beranda" class="flex items-center gap-1">Kembali ke atas <span class="material-symbols-outlined text-lg">arrow_upward</span></a></div></div></footer>
</body>
</html>
