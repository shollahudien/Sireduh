<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SIRESTEDUH') &mdash; BUMDes Wisata Teduh</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50 text-gray-800 antialiased" data-layout="public">

{{-- Navbar publik — sticky, animasi halus, responsif HP/tablet/laptop --}}
@php
    $navLink = fn (string $label, string $href, string $prefix) => (object) [
        'label' => $label, 'href' => $href, 'active' => request()->is($prefix),
    ];
    $navItems = [
        $navLink('Beranda', url('/'), '/'),
        $navLink('Reservasi', url('/checkout'), 'checkout'),
        $navLink('Status Pesanan', url('/status'), 'status'),
    ];
@endphp
<nav x-data="{ open: false, scrolled: false }"
     @scroll.window="scrolled = window.scrollY > 10"
     @keydown.escape.window="open = false"
     class="sticky top-0 z-40 text-white transition-all duration-300"
     :class="scrolled ? 'shadow-xl shadow-green-950/30' : 'shadow-md'">

    {{-- Garis aksen gradasi bergerak --}}
    <div class="navbar-shimmer h-[3px] bg-gradient-to-r from-lime-400 via-emerald-300 to-teal-400 from-20% via-50% to-80%"></div>

    <div class="transition-all duration-300"
         :class="scrolled ? 'bg-green-900/85 backdrop-blur-md' : 'bg-green-800'">

        <div class="max-w-7xl mx-auto px-3 sm:px-4 flex items-center justify-between gap-3 h-14 sm:h-16">

            {{-- Logo --}}
            <a href="{{ url('/') }}" class="nav-logo group flex items-center gap-2.5 shrink-0 focus-visible:ring-2 focus-visible:ring-lime-300 rounded-xl">
                <span class="nav-logo-icon w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-lime-400/20 ring-1 ring-lime-300/40 flex items-center justify-center text-lg sm:text-xl transition-transform duration-300 group-hover:scale-110 group-active:scale-95">🏕️</span>
                <span class="leading-tight">
                    <span class="block text-base sm:text-lg font-extrabold tracking-wide">SIRESTEDUH</span>
                    <span class="hidden sm:block text-[10px] uppercase tracking-[0.2em] text-lime-300/90 font-semibold">BUMDes Wisata Teduh</span>
                </span>
            </a>

            {{-- Menu desktop & tablet (>= md) --}}
            <div class="hidden md:flex items-center gap-1 lg:gap-2">
                @foreach ($navItems as $item)
                    <a href="{{ $item->href }}"
                       class="relative group px-3 py-2 text-sm lg:text-[15px] font-semibold rounded-lg transition-all duration-300 active:scale-95 focus-visible:ring-2 focus-visible:ring-lime-300 focus-visible:outline-none {{ $item->active ? 'text-lime-300' : 'text-green-50 hover:text-white hover:-translate-y-0.5' }}">
                        {{ $item->label }}
                        <span class="absolute left-3 right-3 -bottom-0.5 h-0.5 rounded-full bg-lime-300 origin-left transition-transform duration-300 {{ $item->active ? 'scale-x-100' : 'scale-x-0 group-hover:scale-x-100' }}"></span>
                    </a>
                @endforeach
            </div>

            {{-- Tombol hamburger (HP & tablet kecil) --}}
            <button type="button" @click="open = !open" :aria-expanded="open" aria-label="Buka menu"
                    class="md:hidden relative w-11 h-11 rounded-xl bg-white/10 ring-1 ring-white/20 flex flex-col items-center justify-center gap-1.5 active:scale-90 transition-transform duration-200 focus-visible:ring-2 focus-visible:ring-lime-300 focus-visible:outline-none">
                <span class="block w-5 h-0.5 bg-white rounded-full transition-all duration-300"
                      :class="open ? 'rotate-45 translate-y-[3.5px]' : ''"></span>
                <span class="block w-5 h-0.5 bg-white rounded-full transition-all duration-300"
                      :class="open ? '-rotate-45 -translate-y-[3.5px]' : ''"></span>
            </button>
        </div>

        {{-- Panel menu mobile (slide + stagger) --}}
        <div x-show="open" x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="md:hidden bg-green-900/95 backdrop-blur-md border-t border-white/10 shadow-2xl">

            <div class="px-3 py-3 space-y-1">
                @foreach ($navItems as $i => $item)
                    <a href="{{ $item->href }}" @click="open = false"
                       style="transition-delay: {{ $i * 50 }}ms"
                       :class="open ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-1.5'"
                       class="flex items-center justify-between px-4 py-3 rounded-xl text-[15px] font-semibold transition-all duration-300 {{ $item->active ? 'bg-lime-400/15 text-lime-300 ring-1 ring-lime-300/30' : 'text-green-50 hover:bg-white/10 active:bg-white/15' }}">
                        <span>{{ $item->label }}</span>
                        <span class="text-lime-300 text-sm">›</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</nav>

{{-- Flash Messages --}}
@if(session('success'))
    <div class="max-w-7xl mx-auto mt-4 px-4">
        <div class="bg-green-100 border border-green-400 text-green-800 rounded px-4 py-3">
            {{ session('success') }}
        </div>
    </div>
@endif
@if(session('error'))
    <div class="max-w-7xl mx-auto mt-4 px-4">
        <div class="bg-red-100 border border-red-400 text-red-800 rounded px-4 py-3">
            {{ session('error') }}
        </div>
    </div>
@endif

<main class="max-w-7xl mx-auto px-4 py-8">
    @yield('content')
</main>

{{-- Footer — navigasi sekunder + akses masuk/panel staf --}}
<footer class="bg-green-900 text-green-200 mt-12 relative overflow-hidden">
    <div class="navbar-shimmer h-[3px] bg-gradient-to-r from-lime-400 via-emerald-300 to-teal-400 opacity-70"></div>

    <div class="max-w-7xl mx-auto px-4 py-10 grid gap-8 sm:grid-cols-3">
        {{-- Brand --}}
        <div>
            <p class="flex items-center gap-2 text-lg font-extrabold text-white">
                <span class="w-9 h-9 rounded-xl bg-lime-400/20 ring-1 ring-lime-300/40 flex items-center justify-center text-lg">🏕️</span>
                SIRESTEDUH
            </p>
            <p class="text-sm text-green-300/80 mt-2 leading-relaxed">Sistem Informasi Reservasi Terpadu<br>BUMDes Wisata Teduh Glamping</p>
        </div>

        {{-- Menu publik --}}
        <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-lime-300/90 mb-3">Jelajahi</p>
            <ul class="space-y-2 text-sm">
                <li><a href="{{ url('/') }}" class="inline-flex items-center gap-2 hover:text-white hover:translate-x-1 transition-all duration-200">🏠 Beranda</a></li>
                <li><a href="{{ url('/checkout') }}" class="inline-flex items-center gap-2 hover:text-white hover:translate-x-1 transition-all duration-200">🎟️ Reservasi &amp; Tiket</a></li>
                <li><a href="{{ url('/status') }}" class="inline-flex items-center gap-2 hover:text-white hover:translate-x-1 transition-all duration-200">📋 Status Pesanan</a></li>
            </ul>
        </div>

        {{-- Akses staf: login / panel sesuai role --}}
        <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-lime-300/90 mb-3">Untuk Staf</p>
            @auth
                <ul class="space-y-2 text-sm">
                    @if (auth()->user()->role === 'admin')
                        <li><a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-2 hover:text-white hover:translate-x-1 transition-all duration-200">🛠️ Dashboard Admin</a></li>
                    @elseif (auth()->user()->role === 'kasir')
                        <li><a href="{{ route('pos.officer.index') }}" class="inline-flex items-center gap-2 hover:text-white hover:translate-x-1 transition-all duration-200">🧾 POS Kasir</a></li>
                    @elseif (auth()->user()->role === 'kasir_makanan')
                        <li><a href="{{ route('kasir.makanan.index') }}" class="inline-flex items-center gap-2 hover:text-white hover:translate-x-1 transition-all duration-200">🍜 Kasir Makanan</a></li>
                    @elseif (auth()->user()->role === 'checkin')
                        <li><a href="{{ route('checkin.officer.index') }}" class="inline-flex items-center gap-2 hover:text-white hover:translate-x-1 transition-all duration-200">🎫 Check-In</a></li>
                    @endif
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 text-green-300 hover:text-red-300 hover:translate-x-1 transition-all duration-200">🚪 Keluar</button>
                        </form>
                    </li>
                </ul>
            @else
                <a href="{{ route('login') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-lime-400 text-green-950 text-sm font-bold shadow-lg shadow-lime-400/20 hover:bg-lime-300 hover:shadow-lime-300/40 hover:scale-105 active:scale-95 transition-all duration-300">
                    🔐 Masuk
                </a>
                <p class="text-xs text-green-300/70 mt-2">Untuk petugas &amp; admin</p>
            @endauth
        </div>
    </div>

    <div class="border-t border-white/10">
        <p class="max-w-7xl mx-auto px-4 py-4 text-center text-xs text-green-300/70">
            &copy; {{ date('Y') }} BUMDes Wisata Teduh Glamping &mdash; SIRESTEDUH
        </p>
    </div>
</footer>

@include('components.chatbot')
</body>
</html>
