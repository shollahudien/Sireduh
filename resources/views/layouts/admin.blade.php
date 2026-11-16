<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') &mdash; SIRESTEDUH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none !important;}</style>
    @stack('head')
</head>
<body class="bg-slate-100/80 text-gray-800 antialiased" data-layout="admin">

@if(\App\Models\Setting::maintenanceEnabled())
    <div class="bg-yellow-400 text-yellow-950 text-center text-[11px] sm:text-xs font-extrabold py-1.5 px-4 uppercase tracking-widest">
        🛠️ MAINTENANCE AKTIF — Publik & staf melihat halaman perbaikan
        <a href="{{ route('admin.maintenance') }}" class="underline">Nonaktifkan →</a>
    </div>
@endif



@php
    $menuGroups = [
        [
            'label' => 'Utama',
            'items' => [
                ['route' => 'admin.dashboard', 'pattern' => 'admin.dashboard', 'icon' => '📊', 'label' => 'Dashboard'],
                ['route' => 'admin.reservations.index', 'pattern' => 'admin.reservations.*', 'icon' => '📋', 'label' => 'Reservasi'],
                ['route' => 'admin.verification', 'pattern' => 'admin.verification', 'icon' => '💳', 'label' => 'Verifikasi Pembayaran'],
            ],
        ],
        [
            'label' => 'Manajemen',
            'items' => [
                ['route' => 'admin.units', 'pattern' => 'admin.units', 'icon' => '🏕️', 'label' => 'Unit Sewa'],
                ['route' => 'admin.products', 'pattern' => 'admin.products', 'icon' => '🛍️', 'label' => 'Produk'],
                ['route' => 'admin.reports.index', 'pattern' => 'admin.reports.*', 'icon' => '📈', 'label' => 'Laporan'],
                ['route' => 'admin.users', 'pattern' => 'admin.users', 'icon' => '👥', 'label' => 'Pengguna'],
            ],
        ],
        [
            'label' => 'Sistem',
            'items' => [
                ['route' => 'admin.maintenance', 'pattern' => 'admin.maintenance', 'icon' => '🛠️', 'label' => 'Maintenance'],
                ['route' => 'admin.logs', 'pattern' => 'admin.logs', 'icon' => '🪵', 'label' => 'Log Aplikasi'],
                ['route' => 'admin.activity', 'pattern' => 'admin.activity', 'icon' => '🗂️', 'label' => 'Aktivitas'],
            ],
        ],
    ];
@endphp

<style>
    .sidebar-collapsed .botm-bubble{display:none !important;}
</style>

<div class="min-h-screen" x-data="adminNotifications()" x-init="init()">

    {{-- Global Toast (Alpine bound) --}}
    <div x-show="toast.message" x-cloak x-transition
         class="fixed top-4 left-1/2 -translate-x-1/2 z-[70] w-full max-w-md px-4 text-center pointer-events-none">
        <div class="pointer-events-auto relative rounded-xl px-4 py-3 pr-10 text-sm border shadow-lg"
             :class="toast.isError ? 'bg-red-50 border-red-300 text-red-800' : 'bg-emerald-50 border-emerald-300 text-emerald-800'">
            <span x-text="toast.message"></span>
            <button type="button" @click="toast = { message: '', isError: false }"
                    class="absolute right-2 top-1/2 -translate-y-1/2 text-current text-xl leading-none w-8 h-8 rounded-full opacity-60 hover:opacity-100"
                    aria-label="Tutup">×</button>
        </div>
    </div>

    {{-- Overlay (mobile/tablet) --}}
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
         x-transition:enter="transition-opacity ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-slate-950/50 backdrop-blur-[2px] z-30 lg:hidden" aria-hidden="true"></div>

    {{-- Sidebar: drawer full di mobile, fixed/collapsible di desktop --}}
    <aside :class="[
               sidebarOpen ? 'translate-x-0' : '-translate-x-full',
               sidebarHidden ? 'lg:-translate-x-full lg:invisible' : 'lg:translate-x-0',
               collapsed ? 'lg:w-20' : 'lg:w-64',
               (collapsed && !sidebarOpen) ? 'sidebar-collapsed' : ''
           ]"
           class="w-72 bg-gradient-to-b from-emerald-950 via-[#063729] to-emerald-950 text-white flex flex-col fixed inset-y-0 left-0 z-40 shadow-2xl shadow-emerald-950/40 border-r border-emerald-400/15 transition-[transform,width,visibility] duration-300 ease-in-out bot-bubble-hover">
        <div class="h-px bg-gradient-to-r from-transparent via-lime-300/40 to-transparent shrink-0"></div>
        <div class="pointer-events-none absolute inset-x-0 top-0 h-24 bg-gradient-to-b from-white/[0.04] to-transparent"></div>

        {{-- Brand --}}
        <div class="relative h-16 px-4 border-b border-white/10 flex items-center justify-between gap-3 shrink-0">
            <div class="flex items-center gap-3 min-w-0" :class="(collapsed && !sidebarOpen) ? 'w-full justify-center' : ''">
                <div class="shrink-0 bg-white/10 backdrop-blur-md ring-1 ring-lime-300/30 rounded-xl p-1 shadow-inner">
                    <x-bot-mascot size="w-8 h-8" bubble="Halo, {{ auth()->user()->name }}! 👋" />
                </div>
                <div class="min-w-0 leading-tight" x-show="!collapsed || sidebarOpen" x-cloak>
                    <p class="text-base sm:text-lg font-extrabold tracking-wide bg-gradient-to-r from-white via-lime-100 to-emerald-100 bg-clip-text text-transparent">SIRESTEDUH</p>
                    <p class="text-[10px] text-emerald-200/80 leading-snug tracking-wide">BUMDes Wisata Teduh</p>
                </div>
            </div>
            <button type="button" @click="sidebarOpen = false" x-show="sidebarOpen" x-cloak class="lg:hidden p-2 rounded-xl text-emerald-100/80 hover:text-white hover:bg-white/10 shrink-0 transition" aria-label="Tutup menu">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 py-3 overflow-y-auto overflow-x-hidden" :class="(collapsed && !sidebarOpen) ? 'px-2' : 'px-3'">
            @foreach($menuGroups as $group)
                <div class="mb-3">
                    <p class="px-3 mb-1.5 text-[10px] uppercase tracking-widest text-emerald-300/50 font-bold"
                       x-show="!collapsed || sidebarOpen" x-cloak>{{ $group['label'] }}</p>
                    <div class="space-y-1">
                        @foreach($group['items'] as $item)
                            @php $active = request()->routeIs($item['pattern']); @endphp
                            <a href="{{ route($item['route']) }}" @click="sidebarOpen = false"
                               title="{{ $item['label'] }}"
                               :class="(collapsed && !sidebarOpen) ? 'justify-center px-0' : 'justify-start px-3 gap-3'"
                               class="group relative flex items-center py-2.5 rounded-xl transition-all duration-200 ease-out border-l-2
                                      {{ $active
                                          ? 'bg-gradient-to-r from-lime-400/20 to-emerald-400/5 border-lime-400 text-white shadow-[0_0_18px_rgba(163,230,53,0.12)]'
                                          : 'border-transparent text-emerald-100/90 hover:bg-white/5 hover:text-white hover:translate-x-1' }}">
                                <span class="flex items-center justify-center w-9 h-9 rounded-lg text-base shrink-0 transition-all duration-200
                                             {{ $active ? 'bg-lime-400/15 ring-1 ring-lime-300/25 shadow-[0_0_12px_rgba(163,230,53,0.15)]' : 'bg-white/5 group-hover:bg-white/10' }}">{{ $item['icon'] }}</span>
                                <span class="truncate text-sm font-medium tracking-wide" x-show="!collapsed || sidebarOpen" x-cloak>{{ $item['label'] }}</span>
                                @if($active)
                                    <span class="ml-auto mr-1 w-1.5 h-1.5 rounded-full bg-lime-400 shadow-[0_0_10px_rgba(163,230,53,0.9)] shrink-0"
                                          x-show="!collapsed || sidebarOpen" x-cloak></span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>

        {{-- Footer / Profile --}}
        <div class="mt-auto bg-black/25 border-t border-white/10 p-3">
            <button type="button" @click="profileOpen = true"
                    class="w-full flex items-center gap-3 rounded-xl px-2 py-2 hover:bg-white/5 transition text-left"
                    :class="(collapsed && !sidebarOpen) ? 'justify-center' : ''" title="Lihat profil">
                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-lime-300 to-emerald-400 text-emerald-950 flex items-center justify-center font-extrabold text-sm shrink-0 ring-2 ring-lime-300/40 shadow-lg">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                <div class="min-w-0 flex-1" x-show="!collapsed || sidebarOpen" x-cloak>
                    <p class="text-sm font-semibold truncate text-white">{{ auth()->user()->name }}</p>
                    <p class="text-[10px] uppercase tracking-widest text-lime-300/90 font-bold">Super Admin</p>
                </div>
            </button>
            <div class="mt-2 grid gap-2" :class="(collapsed && !sidebarOpen) ? 'grid-cols-1' : 'grid-cols-2'">
                <a href="{{ url('/') }}" title="Lihat Situs Publik"
                   :class="(collapsed && !sidebarOpen) ? 'justify-center px-0' : 'justify-center gap-1.5 px-2'"
                   class="flex items-center text-[11px] font-semibold py-2 rounded-xl bg-white/10 hover:bg-white/15 ring-1 ring-white/10 transition-all duration-200">
                    <span>🌐</span><span x-show="!collapsed || sidebarOpen" x-cloak>Situs</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Keluar"
                            :class="(collapsed && !sidebarOpen) ? 'justify-center px-0' : 'justify-center gap-1.5 px-2'"
                            class="w-full flex items-center text-[11px] font-semibold py-2 rounded-xl text-red-100 hover:text-white bg-red-500/10 hover:bg-red-500/20 ring-1 ring-red-400/20 hover:ring-red-400/40 transition-all duration-200">
                        <span>🚪</span><span x-show="!collapsed || sidebarOpen" x-cloak>Keluar</span>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Main wrapper --}}
    <div :class="sidebarHidden ? 'lg:ml-0' : (collapsed ? 'lg:ml-20' : 'lg:ml-64')" class="flex-1 flex flex-col min-h-screen transition-[margin] duration-300 ease-in-out">

        {{-- Navbar / Header --}}
        <header class="h-16 bg-white/85 backdrop-blur-xl border-b border-slate-200/80 px-4 sm:px-6 flex items-center justify-between gap-3 sticky top-0 z-20 shadow-sm shadow-slate-200/40">
            {{-- Left --}}
            <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                <button type="button" @click="sidebarOpen = true"
                        class="lg:hidden shrink-0 inline-flex items-center justify-center w-9 h-9 rounded-xl text-slate-600 hover:bg-slate-100 hover:text-emerald-700 transition"
                        aria-label="Buka menu">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <div class="min-w-0">
                    <h1 class="text-sm sm:text-base font-bold text-slate-800 truncate leading-tight">@yield('page-title', 'Dashboard')</h1>
                    <p class="text-[10px] sm:text-xs text-slate-400 truncate">Admin / @yield('page-title', 'Dashboard') · {{ now()->translatedFormat('l, d F Y') }}</p>
                </div>
            </div>

            {{-- Right action cluster --}}
            <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                {{-- Search (Ctrl/⌘ K) --}}
                <div x-data="adminSearch()" @click.outside="close()" @keydown.escape.window="close()" class="relative">
                    <button type="button" @click="show()"
                            class="inline-flex items-center justify-center sm:justify-start gap-2 w-9 h-9 sm:w-auto sm:h-auto sm:px-3 sm:py-1.5 rounded-full bg-slate-100/80 hover:bg-white border border-slate-200/80 text-slate-500 hover:text-emerald-700 hover:border-emerald-300 hover:shadow-sm active:scale-95 transition-all duration-200"
                            aria-label="Cari apa saja (Ctrl+K)" title="Cari (Ctrl+K)">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" />
                        </svg>
                        <span class="hidden sm:inline text-xs font-medium">Cari apa saja…</span>
                        <kbd x-text="(navigator.platform && navigator.platform.includes('Mac') ? '⌘' : 'Ctrl') + ' K'"
                             class="hidden md:inline-flex text-[10px] font-bold text-slate-500 bg-white border border-slate-200 rounded px-1.5 py-0.5">Ctrl K</kbd>
                    </button>

                    {{-- Overlay + panel palette --}}
                    <div x-show="open" x-cloak class="fixed inset-0 z-[60]">
                        <div x-show="open" x-transition.opacity.duration.200ms
                             class="absolute inset-0 bg-emerald-950/60 backdrop-blur-sm" @click="close()"></div>

                        <div x-show="open"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute left-1/2 top-[9vh] -translate-x-1/2 w-full max-w-xl px-4">
                            <div class="bg-white rounded-2xl shadow-2xl border border-emerald-100 overflow-hidden">
                                <div class="flex items-center gap-2.5 px-4 py-3 border-b border-gray-100">
                                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" />
                                    </svg>
                                    <input x-ref="input" x-model="term" type="text" @input="onInput()"
                                           @keydown.arrow-down.prevent="move(1)"
                                           @keydown.arrow-up.prevent="move(-1)"
                                           @keydown.enter.prevent="go()"
                                           placeholder="Cari reservasi, unit, produk, pengguna, log…"
                                           class="w-full text-sm font-medium text-gray-800 placeholder-gray-400 outline-none bg-transparent"
                                           autocomplete="off" spellcheck="false">
                                    <span x-show="loading" x-cloak class="w-4 h-4 border-2 border-emerald-200 border-t-emerald-600 rounded-full animate-spin shrink-0"></span>
                                    <kbd class="shrink-0 text-[10px] font-bold text-gray-400 bg-gray-100 border border-gray-200 rounded px-1.5 py-0.5">Esc</kbd>
                                </div>

                                <div x-ref="list" class="max-h-[55vh] overflow-y-auto overscroll-contain">
                                    <template x-if="term.trim().length < 2">
                                        <div class="px-4 py-5">
                                            <div class="flex items-start gap-3 rounded-xl bg-emerald-50/80 ring-1 ring-emerald-100 px-3.5 py-3">
                                                <span class="text-xl shrink-0">🤖</span>
                                                <p class="text-xs text-emerald-800/80 leading-relaxed">Ketik minimal 2 huruf untuk mencari <b>apa saja</b> — reservasi, unit sewa, produk, pengguna, pembayaran tertunda, hingga log aktivitas.</p>
                                            </div>
                                            <p class="mt-4 mb-2 text-[10px] font-bold uppercase tracking-widest text-gray-400">Pintasan</p>
                                            <div class="grid grid-cols-2 gap-2">
                                                <a href="{{ route('admin.reservations.index') }}" @click="close()" class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl border border-gray-100 text-sm font-semibold text-gray-700 hover:border-emerald-300 hover:bg-emerald-50/60 hover:-translate-y-0.5 active:scale-95 transition-all duration-200">📋 Reservasi</a>
                                                <a href="{{ route('admin.verification') }}" @click="close()" class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl border border-gray-100 text-sm font-semibold text-gray-700 hover:border-emerald-300 hover:bg-emerald-50/60 hover:-translate-y-0.5 active:scale-95 transition-all duration-200">💳 Verifikasi</a>
                                                <a href="{{ route('admin.users') }}" @click="close()" class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl border border-gray-100 text-sm font-semibold text-gray-700 hover:border-emerald-300 hover:bg-emerald-50/60 hover:-translate-y-0.5 active:scale-95 transition-all duration-200">👥 Pengguna</a>
                                                <a href="{{ route('admin.activity') }}" @click="close()" class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl border border-gray-100 text-sm font-semibold text-gray-700 hover:border-emerald-300 hover:bg-emerald-50/60 hover:-translate-y-0.5 active:scale-95 transition-all duration-200">🗂️ Aktivitas</a>
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="term.trim().length >= 2 && !loading && flat.length === 0">
                                        <div class="px-4 py-8 text-center">
                                            <p class="text-3xl">🤖</p>
                                            <p class="text-sm font-semibold text-gray-600 mt-2">Tidak ada hasil untuk &ldquo;<span x-text="term"></span>&rdquo;</p>
                                            <p class="text-xs text-gray-400 mt-1">Coba kata kunci lain, mis. kode pesanan atau nama tamu.</p>
                                        </div>
                                    </template>

                                    <template x-for="(group, gi) in groups" :key="group.key">
                                        <div class="py-1" :style="'animation: palette-group-in .25s ease-out both; animation-delay: ' + (gi * 60) + 'ms'">
                                            <p class="px-4 pt-2.5 pb-1 text-[10px] font-bold uppercase tracking-widest text-gray-400 flex items-center gap-1.5">
                                                <span class="text-sm" x-text="group.icon"></span><span x-text="group.label"></span>
                                            </p>
                                            <template x-for="item in group.items" :key="item._i">
                                                <a :href="item.url" @click="close()" @mouseenter="active = item._i"
                                                   :data-active="item._i === active"
                                                   class="flex items-center gap-3 px-4 py-2.5 transition-colors duration-100"
                                                   :class="item._i === active ? 'bg-emerald-50 text-emerald-900 shadow-[inset_2px_0_0_0_#84cc16]' : 'text-gray-700 hover:bg-gray-50'">
                                                    <span class="w-7 h-7 rounded-lg bg-emerald-100/70 ring-1 ring-emerald-100 flex items-center justify-center text-xs shrink-0" x-text="group.icon"></span>
                                                    <span class="min-w-0 flex-1">
                                                        <span class="block text-sm font-semibold truncate" x-text="item.title"></span>
                                                        <span class="block text-xs text-gray-500 truncate" x-text="item.subtitle"></span>
                                                    </span>
                                                    <span class="text-gray-300 text-xs shrink-0" x-show="item._i === active">↵</span>
                                                </a>
                                            </template>
                                        </div>
                                    </template>
                                </div>

                                <div class="flex items-center justify-between gap-2 px-4 py-2 border-t border-gray-100 bg-gray-50/70 text-[10px] text-gray-400 font-medium">
                                    <span class="flex items-center gap-3"><span>↑↓ navigasi</span><span>↵ buka</span><span>esc tutup</span></span>
                                    <span x-show="total > 0" x-cloak class="font-bold text-emerald-700" x-text="total + ' hasil'"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Desktop: hide sidebar --}}
                <button type="button" @click="toggleSidebarHidden()"
                        :title="sidebarHidden ? 'Tampilkan sidebar' : 'Sembunyikan sidebar'"
                        :aria-label="sidebarHidden ? 'Tampilkan sidebar' : 'Sembunyikan sidebar'"
                        :class="sidebarHidden ? 'text-emerald-700 bg-emerald-50 ring-1 ring-emerald-200' : 'text-slate-500 hover:bg-slate-100 hover:text-emerald-700'"
                        class="hidden lg:inline-flex items-center justify-center w-9 h-9 rounded-xl transition active:scale-95">
                    <svg x-show="!sidebarHidden" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <rect x="3" y="5" width="18" height="14" rx="2" />
                        <path stroke-linecap="round" d="M9 5v14" />
                    </svg>
                    <svg x-show="sidebarHidden" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <rect x="3" y="5" width="18" height="14" rx="2" stroke-dasharray="3 2" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 12h6m0 0-2-2m2 2-2 2" />
                    </svg>
                </button>

                {{-- Desktop: collapse sidebar --}}
                <button type="button" @click="toggleCollapse()" x-show="!sidebarHidden"
                        :title="collapsed ? 'Perbesar sidebar' : 'Perkecil sidebar'"
                        :aria-label="collapsed ? 'Perbesar sidebar' : 'Perkecil sidebar'"
                        :class="collapsed ? 'text-emerald-700 bg-emerald-50 ring-1 ring-emerald-200' : 'text-slate-500 hover:bg-slate-100 hover:text-emerald-700'"
                        class="hidden lg:inline-flex items-center justify-center w-9 h-9 rounded-xl transition active:scale-95">
                    <svg x-show="!collapsed" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5 5-5M18 17l-5-5 5-5" />
                    </svg>
                    <svg x-show="collapsed" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 7l5 5-5 5" />
                    </svg>
                </button>

                {{-- Sound toggle --}}
                <button type="button" @click="toggleSound()"
                        :title="soundOn ? 'Matikan suara notifikasi' : 'Nyalakan suara notifikasi'"
                        :aria-label="soundOn ? 'Matikan suara notifikasi' : 'Nyalakan suara notifikasi'"
                        class="inline-flex items-center justify-center w-9 h-9 rounded-xl text-lg leading-none hover:bg-slate-100 active:scale-95 transition"
                        :class="soundOn ? 'text-emerald-700' : 'text-slate-300 hover:text-slate-500'">
                    <span x-text="soundOn ? '🔊' : '🔇'">🔊</span>
                </button>

                {{-- Notification bell --}}
                <div class="relative" @click.outside="bellOpen = false">
                    <button type="button" @click="bellOpen = !bellOpen"
                            class="relative inline-flex items-center justify-center w-9 h-9 rounded-xl text-slate-500 hover:bg-slate-100 hover:text-emerald-700 transition"
                            aria-label="Notifikasi pembayaran">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <span x-show="count > 0" x-cloak class="absolute -top-0.5 -right-0.5 flex items-center justify-center">
                            <span class="absolute inline-flex h-5 w-5 animate-ping rounded-full bg-red-400 opacity-75"></span>
                            <span class="relative bg-red-600 text-white text-[10px] font-bold rounded-full min-w-5 h-5 px-1 flex items-center justify-center ring-2 ring-white"
                                  x-text="count > 99 ? '99+' : count"></span>
                        </span>
                    </button>

                    <div x-show="bellOpen" x-cloak x-transition
                         class="absolute right-0 mt-2 w-80 max-w-[calc(100vw-2rem)] bg-white rounded-xl shadow-xl border border-slate-100 overflow-hidden z-30">
                        <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-100 bg-gray-50">
                            <p class="text-xs font-bold text-gray-600 uppercase tracking-wide">Pembayaran Masuk</p>
                            <span class="text-[10px] font-bold bg-red-100 text-red-700 rounded-full px-2 py-0.5" x-text="count + ' pending'"></span>
                        </div>
                        <div class="max-h-80 overflow-y-auto divide-y divide-gray-50">
                            <template x-if="latest.length === 0">
                                <p class="text-sm text-gray-400 text-center py-6">Tidak ada pembayaran menunggu verifikasi.</p>
                            </template>
                            <template x-for="item in latest" :key="item.id">
                                <div class="px-4 py-3 hover:bg-gray-50">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="text-sm font-mono font-bold text-gray-800" x-text="item.order_code"></p>
                                        <p class="text-xs text-gray-400" x-text="item.created_at_human"></p>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-0.5 truncate" x-text="item.visitor_name"></p>
                                    <p class="text-xs font-semibold text-emerald-800 mt-0.5" x-text="'Rp ' + Number(item.total_amount).toLocaleString('id-ID')"></p>
                                </div>
                            </template>
                        </div>
                        <a href="{{ route('admin.verification') }}"
                           class="block text-center text-xs font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 py-2.5 transition">
                            Verifikasi Sekarang →
                        </a>
                    </div>
                </div>

                <span class="hidden sm:block h-6 w-px bg-slate-200 mx-0.5"></span>

                {{-- Profile badge --}}
                <div class="relative" @click.outside="profileOpen = false">
                    <button type="button" @click="profileOpen = !profileOpen"
                            class="flex items-center gap-2 sm:pl-1 rounded-xl hover:bg-slate-100/80 px-1.5 py-1 transition"
                            title="Lihat profil" aria-label="Profil admin">
                        <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-gradient-to-br from-emerald-600 to-lime-500 text-white flex items-center justify-center text-xs sm:text-sm font-bold ring-2 ring-emerald-100 shadow-sm">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <div class="hidden md:block leading-tight text-left">
                            <p class="text-sm font-semibold text-slate-800 max-w-[140px] truncate">{{ auth()->user()->name }}</p>
                            <span class="inline-flex items-center text-[10px] font-extrabold uppercase tracking-widest text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-full px-2 py-0.5">Super Admin</span>
                        </div>
                    </button>

                    <div x-show="profileOpen" x-cloak x-transition
                         class="absolute right-0 mt-2 w-72 max-w-[calc(100vw-2rem)] bg-white rounded-xl shadow-xl border border-slate-200 overflow-hidden z-40">
                        <div class="px-4 py-3 bg-gradient-to-r from-emerald-50 to-white border-b border-slate-100 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-emerald-600 to-lime-500 text-white flex items-center justify-center font-extrabold ring-2 ring-emerald-100 shrink-0">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-slate-800 truncate">{{ auth()->user()->name }}</p>
                                <p class="text-[11px] text-slate-500 truncate">{{ auth()->user()->email }}</p>
                            </div>
                        </div>
                        <div class="px-4 py-3 space-y-2 text-xs">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-slate-500">Role</span>
                                <span class="font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-full px-2 py-0.5 uppercase tracking-wide">{{ auth()->user()->role ?? 'admin' }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-slate-500">User ID</span>
                                <span class="font-semibold text-slate-700">#{{ auth()->id() }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-slate-500">Terdaftar</span>
                                <span class="font-semibold text-slate-700">{{ optional(auth()->user()->created_at)->translatedFormat('d M Y') ?? '-' }}</span>
                            </div>
                        </div>
                        <div class="px-4 py-2.5 border-t border-slate-100 bg-slate-50/70 flex items-center justify-between gap-2">
                            <a href="{{ route('admin.users') }}" class="text-[11px] font-semibold text-emerald-700 hover:text-emerald-800 hover:underline">Kelola akun</a>
                            <button type="button" @click="profileOpen = false" class="text-[11px] font-semibold text-slate-500 hover:text-slate-700">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        {{-- Toast pembayaran baru (otomatis) --}}
        <div x-show="newPayment" x-cloak x-transition
             class="fixed top-20 right-4 z-50 w-80 max-w-[calc(100vw-2rem)]">
            <a href="{{ route('admin.verification') }}"
               class="block bg-white rounded-2xl shadow-2xl border border-emerald-200 overflow-hidden hover:shadow-emerald-200/40 hover:ring-2 hover:ring-emerald-500 transition">
                <div class="bg-emerald-700 text-white px-4 py-2 flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-widest">🔔 Pembayaran Baru</p>
                    <button type="button" @click.prevent.stop="dismissToast()" class="text-white/80 hover:text-white text-lg leading-none px-1" aria-label="Tutup">×</button>
                </div>
                <div class="px-4 py-3">
                    <p class="font-mono font-bold text-gray-900" x-text="newPayment?.order_code"></p>
                    <p class="text-xs text-gray-500 truncate" x-text="newPayment?.visitor_name"></p>
                    <p class="text-sm font-extrabold text-emerald-800 mt-1" x-text="'Rp ' + Number(newPayment?.total_amount ?? 0).toLocaleString('id-ID')"></p>
                    <p class="text-[11px] text-emerald-800 font-semibold mt-2 border-t border-emerald-100 pt-2">Ketuk untuk Verifikasi →</p>
                </div>
            </a>
        </div>

        {{-- Flash messages --}}
        @if(session('success'))
            <div class="mx-4 sm:mx-6 mt-4 bg-emerald-50 border border-emerald-300 text-emerald-800 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mx-4 sm:mx-6 mt-4 bg-red-50 border border-red-300 text-red-800 rounded-xl px-4 py-3 text-sm">{{ session('error') }}</div>
        @endif
        @if(session('info'))
            <div class="mx-4 sm:mx-6 mt-4 bg-blue-50 border border-blue-300 text-blue-800 rounded-xl px-4 py-3 text-sm">{{ session('info') }}</div>
        @endif

        <main class="flex-1 p-4 sm:p-6 lg:p-8">
            @yield('content')
        </main>
    </div>


</div>

<script>
function adminNotifications() {
    return {
        sidebarOpen: false,
        count: 0,
        latest: [],
        bellOpen: false,
        newPayment: null,
        prevCount: null,
        soundOn: localStorage.getItem('admin_sound') !== 'off',
        collapsed: localStorage.getItem('admin_sidebar_collapsed') === '1',
        sidebarHidden: localStorage.getItem('admin_sidebar_hidden') === '1',
        toast: { message: '', isError: false },
        profileOpen: false,
        init() {
            this.load();
            setInterval(() => this.load(), 15000);
        },
        toggleCollapse() {
            this.collapsed = !this.collapsed;
            localStorage.setItem('admin_sidebar_collapsed', this.collapsed ? '1' : '0');
        },
        toggleSidebarHidden() {
            this.sidebarHidden = !this.sidebarHidden;
            localStorage.setItem('admin_sidebar_hidden', this.sidebarHidden ? '1' : '0');
        },
        async load() {
            try {
                const res = await fetch('{{ url('/api/admin/notifications') }}', {
                    headers: { 'Accept': 'application/json' },
                });
                if (res.ok) {
                    const data = await res.json();
                    const next = data.count ?? 0;

                    if (this.prevCount !== null && next > this.prevCount) {
                        const newest = (data.latest ?? [])[0];
                        if (newest) {
                            this.showNewPaymentToast(newest);
                        }
                    }
                    this.prevCount = next;
                    this.count = next;
                    this.latest = data.latest ?? [];
                }
            } catch (e) {
                // silent
            }
        },
        showNewPaymentToast(item) {
            this.newPayment = item;
            this.playChime();
            setTimeout(() => { this.newPayment = null; }, 8000);
        },
        showToast(message, isError = false) {
            this.toast = { message, isError };
            setTimeout(() => { this.toast = { message: '', isError: false }; }, 4000);
        },
        dismissToast() {
            this.newPayment = null;
        },
        toggleSound() {
            this.soundOn = !this.soundOn;
            localStorage.setItem('admin_sound', this.soundOn ? 'on' : 'off');
            if (this.soundOn) {
                this.playChime();
            }
        },
        playChime() {
            if (!this.soundOn) return;
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                if (ctx.state === 'suspended') {
                    ctx.resume();
                }
                const now = ctx.currentTime;
                [[880, 0, 0.22], [1318.5, 0.16, 0.5]].forEach(([freq, delay, dur]) => {
                    const o = ctx.createOscillator();
                    const g = ctx.createGain();
                    o.type = 'sine';
                    o.frequency.value = freq;
                    g.gain.setValueAtTime(0.001, now + delay);
                    g.gain.exponentialRampToValueAtTime(0.09, now + delay + 0.02);
                    g.gain.exponentialRampToValueAtTime(0.001, now + delay + dur);
                    o.connect(g);
                    g.connect(ctx.destination);
                    o.start(now + delay);
                    o.stop(now + delay + dur);
                });
            } catch (e) {
                // blocked
            }
        },
    };
}

function adminSearch() {
    return {
        open: false,
        term: '',
        loading: false,
        groups: [],
        flat: [],
        total: 0,
        active: 0,
        _debounce: null,
        _lastSearch: null,

        init() {
            window.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
                    e.preventDefault();
                    this.open ? this.close() : this.show();
                }
            });
        },

        show() {
            this.open = true;
            this.$nextTick(() => this.$refs.input?.focus());
            if (this.term.trim().length >= 2) {
                this.search();
            }
        },

        close() {
            this.open = false;
        },

        onInput() {
            clearTimeout(this._debounce);
            this._debounce = setTimeout(() => this.search(), 300);
        },

        async search() {
            const q = this.term.trim();
            clearTimeout(this._debounce);

            if (q.length < 2) {
                this.groups = [];
                this.flat = [];
                this.total = 0;
                this.loading = false;
                return;
            }

            this.loading = true;
            try {
                if (this._lastSearch && this._lastSearch.term === q) {
                    this.groups = this._lastSearch.groups;
                    this.flat = this._lastSearch.flat;
                    this.total = this._lastSearch.total;
                    this.loading = false;
                    return;
                }

                const res = await fetch('{{ route('api.admin.search') }}?q=' + encodeURIComponent(q), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });
                const data = res.ok ? await res.json() : { groups: [], total: 0 };

                let idx = 0;
                this.groups = (data.groups || []).map((group) => ({
                    ...group,
                    items: group.items.map((item) => ({ ...item, _i: idx++ })),
                }));
                this.flat = this.groups.flatMap((group) => group.items);
                this.total = data.total || 0;
                this.active = 0;
                this._lastSearch = { term: q, groups: this.groups, flat: this.flat, total: this.total };
            } catch (e) {
                this.groups = [];
                this.flat = [];
                this.total = 0;
            } finally {
                this.loading = false;
            }
        },

        move(step) {
            if (!this.flat.length) {
                return;
            }
            this.active = (this.active + step + this.flat.length) % this.flat.length;
            this.$nextTick(() => {
                this.$refs.list?.querySelector('[data-active="true"]')?.scrollIntoView({ block: 'nearest' });
            });
        },

        go() {
            const item = this.flat[this.active];
            if (item?.url) {
                window.location = item.url;
            }
        },
    };
}
</script>
@stack('scripts')
</body>
</html>
