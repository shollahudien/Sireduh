<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kasir — SIRESTEDUH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        @media print {
            @page { margin: 8mm; }
            body * { visibility: hidden !important; }
            #struk-print, #struk-print * { visibility: visible !important; }
            #struk-print {
                position: fixed !important;
                top: 0 !important;
                left: 50% !important;
                transform: translateX(-50%) !important;
                width: 72mm !important;
                max-width: 100% !important;
                max-height: none !important;
                overflow: visible !important;
                margin: 0 !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                border: 0 !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-800 antialiased">

<div class="flex min-h-screen" x-data="posKasir()" x-init="init()">
    {{-- Loket (tanpa sidebar) --}}
    <div class="flex-1 flex flex-col min-h-screen w-full min-w-0 overflow-x-hidden">
        <header class="sticky top-0 z-20">
            <div class="navbar-shimmer h-[3px] {{ ($foodOnly ?? false) ? 'bg-gradient-to-r from-amber-400 via-orange-300 to-amber-400' : 'bg-gradient-to-r from-lime-400 via-emerald-300 to-teal-400' }}"></div>
            <div class="bg-white shadow-sm px-2.5 sm:px-6 py-1.5 sm:py-3 flex items-center justify-between gap-2">
                <a href="{{ url(($foodOnly ?? false) ? '/kasir-makanan' : '/pos') }}" class="group flex items-center gap-2 sm:gap-3 min-w-0">
                    <div class="w-8 h-8 sm:w-9 sm:h-9 {{ ($foodOnly ?? false) ? 'bg-amber-800' : 'bg-green-800' }} rounded-xl flex items-center justify-center text-white text-base sm:text-lg shrink-0 transition-transform duration-300 group-hover:scale-110 group-active:scale-95">{{ ($foodOnly ?? false) ? '🍜' : '🏕️' }}</div>
                    <div class="min-w-0">
                        <h1 class="font-extrabold text-gray-800 text-[13px] sm:text-base leading-tight truncate">SIRESTEDUH{{ ($foodOnly ?? false) ? ' · Kasir Makanan' : '' }}</h1>
                        <p class="hidden sm:block text-[10px] sm:text-xs text-gray-400 truncate">{{ ($foodOnly ?? false) ? 'Khusus penjualan makanan' : 'BUMDes Wisata Teduh' }}</p>
                    </div>
                </a>
                <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                    <span class="hidden sm:inline text-[11px] sm:text-xs text-gray-400" x-text="today"></span>
                    <span class="hidden md:inline-flex items-center gap-1.5 text-[10px] sm:text-xs font-semibold text-gray-600 pl-2 border-l border-gray-200">
                        <span class="w-6 h-6 rounded-full bg-green-700 text-white flex items-center justify-center text-[10px] font-bold">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        {{ auth()->user()->name }}
                    </span>
                    <a href="{{ ($foodOnly ?? false) ? route('kasir.makanan.riwayat') : route('pos.officer.riwayat') }}" class="text-[11px] sm:text-xs font-semibold text-gray-600 border border-gray-200 rounded-lg px-2 sm:px-2.5 py-1.5 hover:bg-gray-50 hover:-translate-y-0.5 hover:border-green-500 active:scale-95 transition-all duration-200" aria-label="Riwayat"><span class="sm:hidden">📋</span><span class="hidden sm:inline">📋 Riwayat</span></a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-[11px] sm:text-xs font-semibold text-red-500 border border-red-200 rounded-lg px-2 sm:px-2.5 py-1.5 hover:bg-red-50 hover:-translate-y-0.5 active:scale-95 transition-all duration-200" aria-label="Keluar"><span class="sm:hidden">🚪</span><span class="hidden sm:inline">🚪 Keluar</span></button>
                    </form>
                </div>
            </div>
        </header>

        <div class="flex flex-col flex-1 min-h-0">
            {{-- ============ LEFT: product grid ============ --}}
            <div class="flex-1 p-2 sm:p-5 overflow-y-auto overflow-x-hidden">
                {{-- Ringkasan hari ini --}}
                <section class="mb-3 grid grid-cols-2 sm:grid-cols-4 gap-2">
                    <div class="rounded-xl border border-green-200 bg-green-50/70 p-2 sm:p-3 shadow-sm min-w-0">
                        <p class="text-[9px] sm:text-[10px] font-semibold uppercase tracking-wide text-green-700 leading-tight">Omzet Hari Ini</p>
                        <p class="mt-0.5 text-[12px] sm:text-sm lg:text-base font-extrabold tabular-nums text-green-900 leading-tight truncate" x-text="formatIDR(todaySummary.total_amount)">Rp 0</p>
                        <p class="text-[9px] sm:text-[10px] text-green-700/80 truncate"><span x-text="todaySummary.total_transactions || 0">0</span> trx</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-white p-2 sm:p-3 shadow-sm min-w-0">
                        <p class="text-[9px] sm:text-[10px] font-semibold uppercase tracking-wide text-gray-600 leading-tight">Tunai</p>
                        <p class="mt-0.5 text-[12px] sm:text-sm lg:text-base font-bold tabular-nums text-gray-900 leading-tight truncate" x-text="formatIDR(todaySummary.cash?.total)">Rp 0</p>
                        <p class="text-[9px] sm:text-[10px] text-gray-400 truncate"><span x-text="todaySummary.cash?.count || 0">0</span> trx</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-white p-2 sm:p-3 shadow-sm min-w-0">
                        <p class="text-[9px] sm:text-[10px] font-semibold uppercase tracking-wide text-blue-700 leading-tight">QRIS</p>
                        <p class="mt-0.5 text-[12px] sm:text-sm lg:text-base font-bold tabular-nums text-gray-900 leading-tight truncate" x-text="formatIDR(todaySummary.qris?.total)">Rp 0</p>
                        <p class="text-[9px] sm:text-[10px] text-gray-400 truncate"><span x-text="todaySummary.qris?.count || 0">0</span> trx</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-white p-2 sm:p-3 shadow-sm min-w-0">
                        <p class="text-[9px] sm:text-[10px] font-semibold uppercase tracking-wide text-indigo-700 leading-tight">Transfer</p>
                        <p class="mt-0.5 text-[12px] sm:text-sm lg:text-base font-bold tabular-nums text-gray-900 leading-tight truncate" x-text="formatIDR(todaySummary.transfer?.total)">Rp 0</p>
                        <p class="text-[9px] sm:text-[10px] text-gray-400 truncate"><span x-text="todaySummary.transfer?.count || 0">0</span> trx</p>
                    </div>
                </section>

                {{-- Search + tabs --}}
                <div class="flex items-center gap-2 mb-2">
                    <div class="relative flex-1 min-w-0">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
                        <input type="text" x-model="search" placeholder="Cari produk..."
                               class="w-full border border-gray-200 rounded-xl pl-9 pr-3 py-2 text-sm focus:ring-2 focus:ring-green-600 focus:outline-none bg-white">
                    </div>
                </div>
                <div class="flex gap-1.5 overflow-x-auto no-scrollbar pb-1.5 mb-3 text-xs sm:text-sm">
                    <template x-for="tab in tabs" :key="tab.value">
                        <button type="button" @click="activeTab = tab.value"
                                :class="activeTab === tab.value ? 'bg-green-700 text-white shadow' : 'bg-white text-gray-600 border border-gray-200 hover:border-green-500'"
                                class="shrink-0 px-3 py-1.5 sm:py-2 rounded-full font-semibold transition"
                                x-text="tab.label"></button>
                    </template>
                </div>

                {{-- Loading / error / empty states --}}
                <div x-show="loading" x-cloak class="flex items-center gap-3 text-gray-400 py-16 justify-center">
                    <svg class="animate-spin h-6 w-6 text-green-700" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Memuat produk &amp; sewa...</span>
                </div>
                <div x-show="loadError && !loading" x-cloak class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-4 text-sm">
                    <span x-text="loadError"></span>
                    <form x-show="authExpired" method="POST" action="{{ route('logout') }}" class="inline">@csrf
                        <button type="submit" class="underline font-semibold">Masuk ulang</button>
                    </form>
                </div>
                <div x-show="!loading && !loadError && items.length === 0" x-cloak class="text-center text-gray-400 py-16">
                    <div class="text-4xl mb-2">📦</div>
                    Tidak ada item pada kategori ini.
                </div>

                {{-- Product cards: foto + nama + harga, format grid POS --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-1.5 sm:gap-3">
                    <template x-for="item in items" :key="item.key">
                        <button type="button" @click="addToCart(item)" :disabled="item.stock <= 0 && !(item.kind === 'unit' && (item.category === 'glamping' || item.category === 'aula'))"
                                :class="item.stock <= 0 && !(item.kind === 'unit' && (item.category === 'glamping' || item.category === 'aula')) ? 'opacity-40 cursor-not-allowed' : 'hover:shadow-lg hover:-translate-y-0.5 cursor-pointer'"
                                class="bg-white rounded-lg sm:rounded-2xl border border-gray-100 overflow-hidden text-left transition flex flex-col shadow-sm w-full min-w-0">
                            {{-- Foto / fallback --}}
                            <div class="relative w-full aspect-[4/3] bg-gray-50 overflow-hidden">
                                <template x-if="item.images.length">
                                    <img :src="item.images[0].url" :alt="item.name" class="w-full h-full object-cover" loading="lazy">
                                </template>
                                <div x-show="!item.images.length" x-cloak class="absolute inset-0 flex items-center justify-center">
                                    <span class="text-3xl sm:text-5xl" x-text="item.emoji"></span>
                                </div>
                                {{-- Badge SEWA/PRODUK --}}
                                <span class="absolute top-1 left-1 sm:top-2 sm:left-2 text-[7px] sm:text-[9px] font-extrabold px-1 sm:px-2 py-0.5 rounded-full shadow"
                                      :class="item.kind === 'unit' ? 'bg-amber-400 text-amber-950' : 'bg-gray-900/80 text-white'"
                                      x-text="item.kind === 'unit' ? 'SEWA' : 'PRODUK'"></span>
                                {{-- Badge stok / paket --}}
                                <span class="absolute top-1 right-1 sm:top-2 sm:right-2 text-[7px] sm:text-[10px] font-bold px-1 sm:px-2 py-0.5 rounded-full shadow max-w-[65%] truncate"
                                      :class="(item.kind === 'unit' && (item.category === 'glamping' || item.category === 'aula')) ? 'bg-green-700 text-white' : (item.stock <= 0 ? 'bg-red-600 text-white' : (item.stock <= 3 ? 'bg-orange-500 text-white' : 'bg-white/90 text-gray-700'))"
                                      x-text="(item.kind === 'unit' && (item.category === 'glamping' || item.category === 'aula')) ? 'Paket' : (item.stock <= 0 ? 'Habis' : item.stock)"></span>
                            </div>
                            {{-- Nama + harga --}}
                            <div class="p-1.5 sm:p-2.5 flex flex-col flex-1 gap-0.5 min-w-0">
                                <h3 class="text-[10px] sm:text-sm font-semibold text-gray-800 leading-tight line-clamp-2" x-text="item.name"></h3>
                                <p class="text-green-700 font-extrabold text-[11px] sm:text-base mt-auto truncate" x-text="formatIDR(item.price)"></p>
                            </div>
                        </button>
                    </template>
                </div>
            </div>

            {{-- ============ Checkout modal ============ --}}
            <div x-show="checkoutOpen" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
                <div class="absolute inset-0 bg-black/50" @click="closeCheckout()"></div>
                <div class="relative bg-white w-full max-w-[100vw] sm:w-[92%] sm:max-w-2xl lg:max-w-4xl rounded-t-3xl sm:rounded-3xl shadow-2xl max-h-[92vh] sm:max-h-[90vh] overflow-hidden flex flex-col">
                    {{-- Handle bar (mobile) --}}
                    <div class="flex justify-center pt-1 sm:hidden shrink-0"><div class="w-10 h-1 bg-gray-200 rounded-full"></div></div>
                    <div class="flex items-center justify-between px-3 sm:px-5 py-2 sm:py-4 border-b border-gray-100 sticky top-0 bg-white sm:rounded-t-3xl z-10 shrink-0">
                        <div class="min-w-0">
                            <h2 class="font-extrabold text-gray-800 text-sm sm:text-base leading-tight">🧾 Rincian Pembayaran</h2>
                            <p class="text-[10px] text-gray-400 leading-tight"><span x-text="cartCount"></span> item · <span x-text="cart.length"></span> jenis</p>
                        </div>
                        <button @click="closeCheckout()" class="p-1 rounded-full hover:bg-gray-100 text-gray-400 text-xl leading-none shrink-0" aria-label="Tutup">×</button>
                    </div>

                    {{-- Isi tengah: single scroll di <lg, split scroll di lg --}}
                    <div class="flex flex-col lg:flex-row items-stretch min-h-0 flex-1 overflow-y-auto lg:overflow-hidden">
                        <div class="w-full min-w-0 px-2.5 sm:px-5 py-2 sm:py-4 space-y-2 sm:space-y-3 lg:flex-1 lg:flex-[1.1] lg:overflow-y-auto lg:min-h-0">
                            {{-- Pencarian produk untuk ditambah (langsung di dalam Rincian) --}}
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">🔍</span>
                                <input type="text" x-model="modalSearch" placeholder="Tambah produk..."
                                       class="w-full border border-gray-200 rounded-xl pl-9 pr-8 py-2 text-sm focus:ring-2 focus:ring-green-600 focus:outline-none bg-gray-50">
                                <button type="button" x-show="modalSearch" @click="modalSearch = ''"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700 text-base leading-none" aria-label="Hapus pencarian">×</button>
                            </div>

                            {{-- Hasil pencarian (quick-add) --}}
                            <div x-show="addableItems.length > 0" x-cloak class="border border-green-100 rounded-xl divide-y divide-gray-100">
                                <template x-for="item in addableItems" :key="item.key">
                                    <div class="flex items-center gap-2 px-2 py-1.5 hover:bg-green-50 transition">
                                        <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg overflow-hidden bg-gray-100 shrink-0 border border-gray-100">
                                            <template x-if="item.images?.length">
                                                <img :src="item.images[0].url" :alt="item.name" class="w-full h-full object-cover">
                                            </template>
                                            <div x-show="!item.images?.length" x-cloak class="w-full h-full flex items-center justify-center text-sm sm:text-lg" x-text="item.emoji"></div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-[11px] sm:text-xs font-semibold text-gray-800 leading-snug truncate" x-text="item.name"></p>
                                            <p class="text-[10px] text-gray-400">Sisa <span x-text="item.stock"></span> · <span class="font-bold text-green-800" x-text="formatIDR(item.price)"></span></p>
                                        </div>
                                        <button type="button" @click="addToCart(item)" :disabled="item.stock <= 0 && !(item.kind === 'unit' && (item.category === 'glamping' || item.category === 'aula'))"
                                                :class="item.stock <= 0 && !(item.kind === 'unit' && (item.category === 'glamping' || item.category === 'aula')) ? 'bg-gray-100 text-gray-300 cursor-not-allowed' : 'bg-green-700 text-white hover:bg-green-800'"
                                                class="shrink-0 rounded-lg px-2.5 py-1 text-[11px] sm:text-xs font-extrabold transition">+</button>
                                    </div>
                                </template>
                            </div>
                            <p x-show="modalSearch.trim() && addableItems.length === 0" x-cloak
                               class="text-center text-xs text-gray-400 py-1.5">Produk tidak ditemukan.</p>

                            {{-- Item (full keranjang) - kartu ringkas responsif/portrait --}}
                            <ul class="space-y-2 max-h-48 sm:max-h-60 lg:max-h-none overflow-y-auto lg:overflow-visible pr-0.5">
                                <template x-for="item in cart" :key="item.key">
                                    <li class="rounded-xl border border-gray-100 bg-white p-2 sm:p-2.5 shadow-sm">
                                        <div class="flex items-start gap-2 sm:gap-2.5">
                                            <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-lg overflow-hidden bg-gray-50 shrink-0 border border-gray-100">
                                                <template x-if="item.images?.length">
                                                    <img :src="item.images[0].url" :alt="item.name" class="w-full h-full object-cover">
                                                </template>
                                                <div x-show="!item.images?.length" x-cloak class="w-full h-full flex items-center justify-center text-lg sm:text-2xl" x-text="item.emoji"></div>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-xs sm:text-sm font-semibold text-gray-800 leading-snug line-clamp-2" x-text="item.name"></p>
                                                <p class="text-[11px] sm:text-xs text-gray-500 mt-0.5" x-text="formatIDR(item.price) + ' / item'"></p>
                                                <p x-show="item.schedule_label" x-cloak class="text-[10px] text-amber-700 font-semibold mt-0.5" x-text="item.schedule_label"></p>
                                                <p class="text-xs sm:text-sm font-extrabold text-green-800 mt-1">Subtotal: <span x-text="formatIDR(item.price * item.qty)"></span></p>
                                            </div>
                                        </div>
                                        <div class="mt-2 pt-2 border-t border-gray-100 flex items-center justify-between gap-2">
                                            <div class="flex items-center border border-gray-200 rounded-lg bg-gray-50">
                                                <button type="button" class="w-9 h-9 sm:w-8 sm:h-8 flex items-center justify-center text-gray-600 hover:text-green-700 text-lg leading-none" @click="decQty(item)" aria-label="Kurangi">−</button>
                                                <span class="w-8 text-center text-sm font-bold" x-text="item.qty"></span>
                                                <button type="button" class="w-9 h-9 sm:w-8 sm:h-8 flex items-center justify-center text-gray-600 hover:text-green-700 text-lg leading-none" @click="incQty(item)" aria-label="Tambah">+</button>
                                            </div>
                                            <button type="button" @click="removeItem(item)" class="h-9 px-3 rounded-lg border border-red-100 bg-red-50 text-red-600 text-xs font-semibold hover:bg-red-100 transition shrink-0" aria-label="Hapus item">Hapus</button>
                                        </div>
                                    </li>
                                </template>
                                <li x-show="cart.length === 0" x-cloak class="py-6 text-center list-none">
                                    <p class="text-sm text-gray-400">Keranjang kosong.</p>
                                </li>
                            </ul>

                            {{-- Checkout error --}}
                            <div x-show="checkoutError" x-cloak class="text-xs text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2" x-text="checkoutError"></div>
                        </div>

                        {{-- Panel pembayaran & kalkulator --}}
                        <aside class="flex w-full lg:w-80 xl:w-96 border-t lg:border-t-0 lg:border-l border-gray-100 bg-slate-50 flex-col p-2 sm:p-4 gap-2 sm:gap-3 shrink-0 lg:overflow-y-auto lg:min-h-0">
                            {{-- Metode + Total ringkas --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-2 items-stretch">
                                <div>
                                    <p class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">💳 Metode</p>
                                    <div class="grid grid-cols-3 gap-1.5">
                                        <template x-for="method in paymentMethods" :key="method.value">
                                            <button type="button" @click="paymentMethod = method.value"
                                                    :class="paymentMethod === method.value ? 'border-green-600 bg-green-50 text-green-800 ring-1 ring-green-500' : 'border-gray-200 text-gray-600 hover:border-green-400 bg-white'"
                                                    class="border rounded-lg py-2 sm:py-1.5 font-semibold transition text-center">
                                                <span class="block text-base sm:text-sm lg:text-base leading-none" x-text="method.icon"></span>
                                                <span class="text-[10px] sm:text-[9px]" x-text="method.label"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                                <div class="bg-white border border-gray-100 rounded-lg px-3 py-2 flex items-center justify-between gap-2 lg:block lg:text-right">
                                    <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Total</p>
                                    <p class="text-base sm:text-lg lg:text-xl font-extrabold text-green-800 leading-tight truncate" x-text="formatIDR(total)"></p>
                                </div>
                            </div>

                            <template x-if="paymentMethod === 'cash'">
                                <div class="space-y-2">
                                    <div class="grid grid-cols-2 gap-1.5 sm:gap-2">
                                        <div class="bg-white border border-slate-200 rounded-lg px-2.5 py-2 min-w-0">
                                            <p class="text-[9px] text-gray-400 uppercase tracking-wider">Diterima</p>
                                            <p class="text-sm sm:text-base font-extrabold text-gray-800 font-mono truncate" x-text="formatIDR(cashTender)"></p>
                                        </div>
                                        <div class="rounded-lg px-2.5 py-2 border min-w-0" :class="kembalian >= 0 ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'">
                                            <p class="text-[9px] uppercase tracking-wider" :class="kembalian >= 0 ? 'text-green-700' : 'text-red-600'">Kembalian</p>
                                            <p class="text-sm sm:text-base font-extrabold font-mono truncate" :class="kembalian >= 0 ? 'text-green-800' : 'text-red-600'" x-text="(kembalian >= 0 ? '' : '− ') + formatIDR(Math.abs(kembalian))"></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <button type="button" @click="setExact()" class="flex-1 bg-teal-50 border border-teal-200 rounded-lg py-2 text-[11px] font-extrabold text-teal-700">Uang Pas</button>
                                        <button type="button" @click="clearCash()" class="px-3 bg-red-50 border border-red-200 rounded-lg py-2 text-[11px] font-extrabold text-red-600">Reset</button>
                                        <button type="button" @click="calcOpen = !calcOpen" class="flex-1 bg-white border border-gray-200 rounded-lg py-2 text-[11px] font-bold text-gray-700" x-text="calcOpen ? 'Tutup' : 'Kalkulator'"></button>
                                    </div>

                                    <div x-show="calcOpen" x-cloak class="space-y-2 pt-0.5">
                                        <div class="grid grid-cols-3 sm:grid-cols-6 lg:grid-cols-3 gap-1.5">
                                            <template x-for="q in quickAmounts" :key="q">
                                                <button type="button" @click="cashInput = String(q)"
                                                        class="bg-white border border-slate-200 rounded-lg py-2 text-[11px] font-bold text-gray-700 hover:border-green-500 active:scale-95 transition"
                                                        x-text="'Rp ' + (q/1000) + 'rb'"></button>
                                            </template>
                                        </div>
                                        <div class="grid grid-cols-4 gap-1.5">
                                            <button type="button" @click="pressKey('7')" class="bg-white rounded-lg py-2 text-sm sm:text-base font-bold text-gray-800 border border-slate-200 active:scale-95">7</button>
                                            <button type="button" @click="pressKey('8')" class="bg-white rounded-lg py-2 text-sm sm:text-base font-bold text-gray-800 border border-slate-200 active:scale-95">8</button>
                                            <button type="button" @click="pressKey('9')" class="bg-white rounded-lg py-2 text-sm sm:text-base font-bold text-gray-800 border border-slate-200 active:scale-95">9</button>
                                            <button type="button" @click="backspace()" class="bg-orange-50 rounded-lg py-2 text-sm sm:text-base font-bold text-orange-600 border border-orange-200 active:scale-95">⌫</button>
                                            <button type="button" @click="pressKey('4')" class="bg-white rounded-lg py-2 text-sm sm:text-base font-bold text-gray-800 border border-slate-200 active:scale-95">4</button>
                                            <button type="button" @click="pressKey('5')" class="bg-white rounded-lg py-2 text-sm sm:text-base font-bold text-gray-800 border border-slate-200 active:scale-95">5</button>
                                            <button type="button" @click="pressKey('6')" class="bg-white rounded-lg py-2 text-sm sm:text-base font-bold text-gray-800 border border-slate-200 active:scale-95">6</button>
                                            <button type="button" @click="clearCash()" class="bg-red-50 rounded-lg py-2 text-xs sm:text-sm font-extrabold text-red-600 border border-red-200 active:scale-95">C</button>
                                            <button type="button" @click="pressKey('1')" class="bg-white rounded-lg py-2 text-sm sm:text-base font-bold text-gray-800 border border-slate-200 active:scale-95">1</button>
                                            <button type="button" @click="pressKey('2')" class="bg-white rounded-lg py-2 text-sm sm:text-base font-bold text-gray-800 border border-slate-200 active:scale-95">2</button>
                                            <button type="button" @click="pressKey('3')" class="bg-white rounded-lg py-2 text-sm sm:text-base font-bold text-gray-800 border border-slate-200 active:scale-95">3</button>
                                            <button type="button" @click="setExact()" class="bg-teal-50 rounded-lg py-2 text-[10px] sm:text-[11px] font-extrabold text-teal-700 border border-teal-200 active:scale-95 leading-tight">PAS</button>
                                            <button type="button" @click="pressKey('0')" class="bg-white rounded-lg py-2 text-sm sm:text-base font-bold text-gray-800 border border-slate-200 active:scale-95">0</button>
                                            <button type="button" @click="pressKey('00')" class="bg-white rounded-lg py-2 text-sm sm:text-base font-bold text-gray-800 border border-slate-200 active:scale-95 col-span-2">00</button>
                                            <button type="button" @click="pressKey('000')" class="bg-white rounded-lg py-2 text-sm sm:text-base font-bold text-gray-800 border border-slate-200 active:scale-95">000</button>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div x-show="paymentMethod !== 'cash'" x-cloak class="flex flex-col items-center justify-center text-center py-3">
                                <div class="text-3xl mb-1" x-text="paymentMethods.find(m => m.value === paymentMethod)?.icon ?? '💳'"></div>
                                <p class="font-bold text-gray-700 text-sm" x-text="paymentMethods.find(m => m.value === paymentMethod)?.label ?? 'Non-Tunai'"></p>
                                <p class="text-[11px] text-gray-400">Tidak perlu kembalian.</p>
                            </div>
                        </aside>
                    </div>

                    {{-- Sticky confirm --}}
                    <div class="sticky bottom-0 bg-white border-t border-gray-100 px-3 sm:px-5 py-2.5 sm:py-4 sm:rounded-b-3xl shrink-0 z-10">
                        <button type="button" @click="checkout()" :disabled="!canCheckout"
                                :class="!canCheckout ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-green-700 text-white hover:bg-green-800 shadow-lg shadow-green-700/20'"
                                class="w-full font-extrabold py-3 sm:py-4 rounded-xl sm:rounded-2xl transition flex items-center justify-center gap-2 text-sm sm:text-base">
                            <svg x-show="sending" x-cloak class="animate-spin h-5 w-5" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span x-text="sending ? 'Memproses...' : '💳 Bayar Sekarang'"></span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- ============ Glamping/Aula schedule modal ============ --}}
            <div x-show="scheduleOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4">
                <div class="absolute inset-0 bg-black/50" @click="closeSchedule()"></div>
                <div class="relative bg-white w-full max-w-lg rounded-2xl sm:rounded-3xl shadow-2xl p-4 sm:p-5 space-y-4 max-h-[92vh] overflow-y-auto">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-extrabold text-gray-800">📅 Jadwal & Paket Sewa</h3>
                            <p class="text-[11px] text-gray-500" x-text="scheduleItem?.name"></p>
                        </div>
                        <button @click="closeSchedule()" class="text-gray-400 text-2xl leading-none">×</button>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Paket</label>
                            <select x-model="scheduleForm.package_id" class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-600 focus:outline-none">
                                <template x-for="pkg in (scheduleItem?.category === 'aula' ? aulaPackages : glampingPackages)" :key="pkg.id">
                                    <option :value="pkg.id" x-text="pkg.name + ' · ' + pkg.duration_hours + ' jam · ' + formatIDR(pkg.price)"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Nama Tamu (opsional)</label>
                            <input type="text" x-model="scheduleForm.name" class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-600 focus:outline-none" placeholder="Walk-in (POS)">
                        </div>
                        <div>
                            <label class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Tanggal Mulai</label>
                            <input type="date" x-model="scheduleForm.date" :min="todayJakarta()" class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-600 focus:outline-none">
                        </div>
                        <div class="rounded-xl border border-green-100 bg-green-50/70 p-3">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-green-800">Jam Mulai (otomatis)</p>
                            <p class="mt-1 text-sm font-semibold text-green-900" x-text="scheduleForm.date === todayJakarta() ? 'Sekarang (saat cek slot/transaksi)' : '08:00 (otomatis)'"></p>
                            <p class="mt-1 text-[11px] text-green-700">Selesai mengikuti durasi paket sistem; cek slot memperhitungkan tenda/glamping yang masih aktif terisi.</p>
                        </div>
                    </div>
                    <p class="text-[11px] text-amber-800 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2">Glamping/aula dipesan per paket & tanggal. Jam mulai otomatis (hari ini = sekarang), durasi sesuai paket, dan hanya bisa jika ada slot/tenda kosong (glamping dicek dari 6 tenda, yang masih aktif/dipakai terhitung terisi).</p>
                    <div x-show="scheduleError" x-cloak class="text-xs text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2" x-text="scheduleError"></div>
                    <div class="flex gap-2">
                        <button type="button" @click="closeSchedule()" class="flex-1 border border-gray-200 rounded-xl py-2.5 font-semibold text-gray-600">Batal</button>
                        <button type="button" @click="confirmSchedule()" :disabled="scheduleChecking" class="flex-1 bg-green-700 text-white rounded-xl py-2.5 font-bold hover:bg-green-800 disabled:opacity-50">
                            <span x-text="scheduleChecking ? 'Mengecek slot...' : 'Cek Slot & Tambah'"></span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- ============ Receipt modal ============ --}}
            <div x-show="showReceipt" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60" @click="closeReceipt()"></div>
                <div class="relative w-full max-w-sm max-h-[92vh] overflow-y-auto rounded-2xl shadow-2xl">
                    <div class="bg-green-700 text-white text-center px-6 pt-5 pb-4">
                        <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center text-2xl mx-auto mb-1.5">✅</div>
                        <h2 class="text-lg font-extrabold">Transaksi Berhasil</h2>
                        <p class="text-green-100 text-[11px] mt-0.5">Struk tersimpan &middot; siap dicetak</p>
                    </div>

                    {{-- Struk (area yang ikut tercetak) --}}
                    <div id="struk-print" x-show="receipt" class="bg-white text-gray-900 px-5 py-4 font-mono text-[11px] leading-relaxed">
                        <div class="text-center">
                            <p class="text-base font-extrabold tracking-widest">SIRESTEDUH</p>
                            <p class="text-[10px]">BUMDes Wisata Teduh</p>
                            <p class="text-[10px] mt-1 tracking-widest">--- STRUK PENJUALAN ---</p>
                        </div>

                        <div class="mt-3 space-y-0.5 text-[10px]">
                            <div class="flex justify-between gap-2"><span class="text-gray-500 shrink-0">No.</span><span class="font-bold" x-text="'#' + receipt?.id"></span></div>
                            <div class="flex justify-between gap-2"><span class="text-gray-500 shrink-0">Tanggal</span><span class="text-right" x-text="formatDate(receipt?.created_at)"></span></div>
                            <div class="flex justify-between gap-2"><span class="text-gray-500 shrink-0">Kasir</span><span class="text-right truncate">{{ auth()->user()->name }}</span></div>
                        </div>

                        <div class="border-t border-dashed border-gray-300 my-2.5"></div>

                        <ul class="space-y-1.5">
                            <template x-for="(d, i) in receipt?.details ?? []" :key="i">
                                <li>
                                    <div class="flex justify-between gap-2">
                                        <span class="truncate"><span class="font-bold" x-text="d.quantity + '×'"></span> <span x-text="d.name"></span></span>
                                        <span class="font-bold shrink-0" x-text="formatIDR(d.subtotal)"></span>
                                    </div>
                                    <p class="text-[9px] text-gray-400 pl-4" x-text="'@ ' + formatIDR(Number(d.subtotal) / Math.max(1, d.quantity))"></p>
                                </li>
                            </template>
                        </ul>

                        <div class="border-t border-dashed border-gray-300 my-2.5"></div>

                        <div class="space-y-0.5">
                            <div class="flex justify-between font-bold"><span>TOTAL</span><span x-text="formatIDR(receipt?.total_amount)"></span></div>
                            <div x-show="receipt?.cash_received != null" class="flex justify-between"><span class="text-gray-500">TUNAI</span><span x-text="formatIDR(receipt?.cash_received)"></span></div>
                            <div x-show="receipt?.change_amount != null" class="flex justify-between"><span class="text-gray-500">KEMBALIAN</span><span class="font-bold" x-text="formatIDR(receipt?.change_amount)"></span></div>
                            <div class="flex justify-between"><span class="text-gray-500">METODE</span><span class="font-bold" x-text="methodLabel(receipt?.payment_method)"></span></div>
                        </div>

                        <div class="border-t border-dashed border-gray-300 my-2.5"></div>
                        <div class="text-center text-[10px] space-y-0.5">
                            <p class="font-bold">Terima kasih atas kunjungan Anda!</p>
                            <p class="text-gray-400">Silakan berkunjung kembali 🌿</p>
                            <p class="text-[8px] text-gray-300">SIRESTEDUH &middot; struk ini bukan bukti pajak</p>
                        </div>
                    </div>

                    <div class="bg-white px-5 pb-5 pt-1 grid grid-cols-2 gap-2">
                        <button type="button" @click="printReceipt()"
                                class="border-2 border-green-700 text-green-800 font-extrabold py-3 rounded-xl hover:bg-green-50 active:scale-95 transition flex items-center justify-center gap-1.5 text-sm">
                            🖨️ Cetak Struk
                        </button>
                        <button type="button" @click="closeReceipt()"
                                class="bg-green-700 text-white font-extrabold py-3 rounded-xl hover:bg-green-800 active:scale-95 transition text-sm">
                            Selesai
                        </button>
                    </div>
                </div>
            </div>

            {{-- Sticky footer: Rincian — selalu terlihat & di tengah saat keranjang berisi --}}
            <div x-show="cart.length > 0" x-cloak
                 class="fixed inset-x-0 bottom-0 z-40 border-t border-gray-200 bg-white/95 backdrop-blur px-3 py-2 sm:py-2.5 flex flex-col items-center justify-center gap-1 text-center shadow-[0_-4px_20px_rgba(0,0,0,0.08)]">
                <div class="min-w-0">
                    <p class="text-[10px] uppercase font-bold text-gray-400 leading-tight">🧾 Rincian &middot; <span x-text="cartCount"></span> item</p>
                    <p class="text-base sm:text-lg font-extrabold text-green-800 leading-tight" x-text="formatIDR(total)"></p>
                </div>
                <button type="button" @click="openCheckout()"
                        class="w-full max-w-md bg-green-700 hover:bg-green-800 text-white font-extrabold text-sm sm:text-base rounded-xl sm:rounded-2xl px-6 py-2.5 sm:py-3.5 shadow-lg shadow-green-700/20 active:scale-95 transition">
                    Rincian &amp; Bayar →
                </button>
            </div>
            {{-- Spacer agar konten tidak tertutup sticky footer --}}
            <div x-show="cart.length > 0" x-cloak class="h-24 sm:h-28 shrink-0"></div>
        </div>
    </div>
</div>

<script>
function posKasir() {
    return {
        products: [],
        units: [],
        cart: [],
        activeTab: 'all',
        search: '',
        paymentMethod: 'cash',
        cashInput: '',
        quickAmounts: [5000, 10000, 20000, 50000, 100000, 200000],
        loading: true,
        loadError: '',
        authExpired: false,
        sending: false,
        checkoutError: '',
        showReceipt: false,
        checkoutOpen: false,
        receipt: null,
        calcOpen: false,
        glampingPackages: [],
        aulaPackages: [],
        scheduleOpen: false,
        scheduleItem: null,
        scheduleForm: { package_id: '', date: '', time: '08:00', name: 'Walk-in (POS)' },
        modalSearch: '',

        todayJakarta() {
            return new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Jakarta' }).format(new Date());
        },

        nowTimeJakarta() {
            return new Date().toLocaleTimeString('en-GB', { timeZone: 'Asia/Jakarta', hour: '2-digit', minute: '2-digit', hour12: false });
        },

        autoScheduleTime(date) {
            return date === this.todayJakarta() ? this.nowTimeJakarta() : '08:00';
        },
        scheduleChecking: false,
        scheduleError: '',
        today: new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }),
        todaySummary: { cash: { count: 0, total: 0 }, qris: { count: 0, total: 0 }, transfer: { count: 0, total: 0 }, total_transactions: 0, total_amount: 0 },

        foodOnly: @json($foodOnly ?? false),

        tabs: (() => {
            const base = [{ value: 'all', label: 'Semua' }, { value: 'makanan', label: 'Makanan' }];
            if (@json($foodOnly ?? false)) {
                return [
                    ...base,
                    { value: 'minuman', label: 'Minuman' },
                    { value: 'produk_umkm', label: 'Produk UMKM' },
                ];
            }

            return [
                ...base,
                { value: 'tiket_masuk', label: 'Tiket Masuk' },
                { value: 'minuman', label: 'Minuman' },
                { value: 'produk_umkm', label: 'Produk UMKM' },
                { value: 'souvenir', label: 'Souvenir' },
                { value: 'baju_merchandise', label: 'Baju & Merch' },
                { value: 'sewa', label: 'Sewa Alat' },
            ];
        })(),

        paymentMethods: [
            { value: 'cash', label: 'Tunai', icon: '💵' },
            { value: 'qris', label: 'QRIS', icon: '📱' },
            { value: 'transfer', label: 'Transfer', icon: '🏦' },
        ],

        async loadTodaySummary() {
            try {
                const d = new Date().toISOString().split('T')[0];
                const res = await fetch(`/api/pos/riwayat?from=${d}&to=${d}`, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                this.todaySummary = data.summary || this.todaySummary;
            } catch (e) {}
        },

        async init() {
            this.authExpired = false;
            await this.loadTodaySummary();
            try {
                const [productsRes, unitsRes] = await Promise.all([
                    fetch('/api/pos/products', { headers: { 'Accept': 'application/json' } }),
                    fetch('/api/pos/units', { headers: { 'Accept': 'application/json' } }),
                ]);

                if (productsRes.status === 401 || productsRes.status === 403 || unitsRes.status === 401 || unitsRes.status === 403) {
                    this.loadError = 'Sesi berakhir atau Anda tidak memiliki akses kasir.';
                    this.authExpired = true;
                    this.loading = false;
                    return;
                }

                const productsData = await productsRes.json();
                const unitsData = await unitsRes.json();

                this.products = productsData.data || [];
                this.units = unitsData.data || [];
                this.glampingPackages = unitsData.glamping_packages || this.glampingPackages || [];
                this.aulaPackages = unitsData.aula_packages || this.aulaPackages || [];
            } catch (e) {
                this.loadError = 'Gagal memuat data. Periksa koneksi Anda.';
            } finally {
                this.loading = false;
            }
        },

        emojiFor(item) {
            if (item.kind === 'unit') {
                return { glamping: '🏕️', aula: '🏛️', alat_wisata: '🥾' }[item.type] ?? '📦';
            }
            return {
                tiket_masuk: '🎫', makanan: '🍜', minuman: '🥤',
                souvenir: '🎁', baju_merchandise: '👕', produk_umkm: '☕',
            }[item.category] ?? '🛍️';
        },

        get items() {
            let list = [
                ...this.products.map(p => ({
                    key: 'p-' + p.id, id: p.id, name: p.name, category: p.category,
                    price: Number(p.price), stock: p.stock, kind: 'product', emoji: this.emojiFor({ kind: 'product', category: p.category }),
                    images: p.images ?? [],
                })),
            ];

            if (!this.foodOnly) {
                list.push(...this.units.map(u => ({
                    key: 'u-' + u.id, id: u.id, name: 'Sewa: ' + u.name, category: u.type,
                    price: Number(u.price), stock: u.stock, kind: 'unit', emoji: this.emojiFor({ kind: 'unit', type: u.type }),
                    images: u.images ?? [],
                })));
            }

            if (this.activeTab === 'sewa') {
                list = list.filter(i => i.kind === 'unit');
            } else if (this.activeTab !== 'all') {
                list = list.filter(i => i.kind === 'product' && i.category === this.activeTab);
            }

            if (this.search.trim()) {
                const q = this.search.trim().toLowerCase();
                list = list.filter(i => i.name.toLowerCase().includes(q));
            }

            return list;
        },

        get cashTender() {
            const n = parseInt((this.cashInput || '').replace(/\D/g, ''), 10);
            return Number.isFinite(n) ? n : 0;
        },

        get kembalian() {
            return this.cashTender - this.total;
        },

        get canCheckout() {
            if (this.cart.length === 0 || this.sending) return false;
            if (this.paymentMethod === 'cash') return this.cashTender >= this.total;
            return true;
        },

        pressKey(k) {
            if (this.cashInput.length >= 11) return;
            this.cashInput = (this.cashInput || '') + k;
        },

        backspace() {
            this.cashInput = (this.cashInput || '').slice(0, -1);
        },

        clearCash() {
            this.cashInput = '';
        },

        setExact() {
            this.cashInput = String(this.total);
        },

        get addableItems() {
            const q = this.modalSearch.trim().toLowerCase();
            if (!q) return [];
            return this.items
                .filter(i => i.name.toLowerCase().includes(q))
                .slice(0, 8);
        },

        get cartCount() {
            return this.cart.reduce((sum, item) => sum + item.qty, 0);
        },

        get total() {
            return this.cart.reduce((sum, item) => sum + item.price * item.qty, 0);
        },

        formatIDR(value) {
            return 'Rp ' + Math.round(Number(value) || 0).toLocaleString('id-ID');
        },

        formatDate(iso) {
            if (!iso) return '';
            return new Date(iso).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
        },

        methodLabel(value) {
            return this.paymentMethods.find(m => m.value === value)?.label ?? value;
        },

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        },

        addToCart(item) {
            if (item.kind === 'unit' && (item.category === 'glamping' || item.category === 'aula')) {
                this.openSchedule(item);
                return;
            }
            if (item.stock <= 0) return;
            const existing = this.cart.find(i => i.key === item.key);
            if (existing) {
                if (existing.qty < item.stock) existing.qty += 1;
            } else {
                this.cart.push({ ...item, qty: 1 });
            }
            this.checkoutError = '';
        },

        openSchedule(item) {
            const today = this.todayJakarta();
            const pkgs = item.category === 'aula' ? this.aulaPackages : this.glampingPackages;
            this.scheduleItem = item;
            this.scheduleForm = {
                package_id: pkgs[0]?.id || '',
                date: today,
                time: this.autoScheduleTime(today),
                name: 'Walk-in (POS)',
            };
            this.scheduleError = '';
            this.scheduleOpen = true;
        },

        closeSchedule() {
            this.scheduleOpen = false;
            this.scheduleItem = null;
            this.scheduleError = '';
        },

        selectedGlampingPackage() {
            const pkgs = this.scheduleItem?.category === 'aula' ? this.aulaPackages : this.glampingPackages;
            return pkgs.find(p => p.id === this.scheduleForm.package_id) || null;
        },

        async confirmSchedule() {
            const item = this.scheduleItem;
            if (!item) return;
            const pkg = this.selectedGlampingPackage();
            if (!pkg || !this.scheduleForm.date) {
                this.scheduleError = 'Pilih paket dan tanggal mulai.';
                return;
            }
            const autoTime = this.autoScheduleTime(this.scheduleForm.date);
            this.scheduleForm.time = autoTime;
            this.scheduleChecking = true;
            this.scheduleError = '';
            try {
                const type = this.scheduleItem?.category === 'aula' ? 'aula' : 'glamping';
                const params = new URLSearchParams({
                    check_in: this.scheduleForm.date,
                    check_in_time: autoTime,
                    package_id: pkg.id,
                    quantity: '1',
                });
                const endpoint = type === 'aula' ? '/api/pos/aula-availability' : '/api/pos/glamping-availability';
                const res = await fetch(`${endpoint}?${params.toString()}`, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (!res.ok || !data.available) {
                    const left = data.available_slots ?? 0;
                    this.scheduleError = data.message || (type === 'glamping'
                        ? `Slot glamping tidak tersedia. Sisa tenda kosong: ${left} dari 6 (tenda aktif/terisi terhitung terpakai).`
                        : `Slot aula tidak tersedia (sisa ${left}). Aula mungkin sedang digunakan.`);
                    return;
                }
                const isToday = this.scheduleForm.date === this.todayJakarta();
                this.cart.push({
                    ...item,
                    key: `${item.key}-${Date.now()}-${Math.random().toString(36).slice(2,6)}`,
                    qty: 1,
                    price: Number(pkg.price),
                    package_id: pkg.id,
                    package_name: pkg.name,
                    package_duration_hours: pkg.duration_hours,
                    check_in_date: this.scheduleForm.date,
                    check_in_time: autoTime,
                    visitor_name: (this.scheduleForm.name || 'Walk-in (POS)').trim() || 'Walk-in (POS)',
                    schedule_label: `${pkg.name} · ${this.scheduleForm.date} · ${isToday ? 'mulai sekarang' : 'mulai 08:00'} (durasi ${pkg.duration_hours} jam)`,
                });
                this.closeSchedule();
                this.checkoutError = '';
            } catch (e) {
                this.scheduleError = 'Gagal mengecek ketersediaan slot.';
            } finally {
                this.scheduleChecking = false;
            }
        },

        incQty(item) {
            if (item.qty < item.stock) item.qty += 1;
        },

        decQty(item) {
            if (item.qty > 1) {
                item.qty -= 1;
            } else {
                this.removeItem(item);
            }
        },

        removeItem(item) {
            this.cart = this.cart.filter(i => i.key !== item.key);
        },

        async checkout() {
            if (!this.canCheckout) return;
            this.sending = true;
            this.checkoutError = '';

            const payload = {
                payment_method: this.paymentMethod,
                items: this.cart.map(item => item.kind === 'product'
                    ? { product_id: item.id, quantity: item.qty }
                    : {
                        unit_id: item.id,
                        quantity: item.qty,
                        package_id: item.package_id || null,
                        check_in_date: item.check_in_date || null,
                        check_in_time: item.check_in_time || null,
                        visitor_name: item.visitor_name || null,
                    }),
            };

            try {
                const res = await fetch('/api/pos/checkout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();

                if (res.ok && data.success) {
                    const isCash = this.paymentMethod === 'cash';
                    this.receipt = {
                        ...data.transaction,
                        cash_received: isCash ? Number(this.cashTender) : null,
                        change_amount: isCash ? Math.max(0, Number(this.kembalian)) : null,
                    };
                    this.cart = [];
                    this.cashInput = '';
                    this.paymentMethod = 'cash';
                    this.showReceipt = true;
                    await this.init();
                } else {
                    this.checkoutError = data.message || 'Checkout gagal. Coba lagi.';
                }
            } catch (e) {
                this.checkoutError = 'Koneksi gagal. Periksa jaringan Anda.';
            } finally {
                this.sending = false;
            }
        },

        openCheckout() {
            if (this.cart.length === 0) return;
            this.checkoutError = '';
            this.modalSearch = '';
            this.calcOpen = false;
            this.checkoutOpen = true;
        },

        closeCheckout() {
            this.checkoutOpen = false;
        },

        closeReceipt() {
            this.showReceipt = false;
            this.receipt = null;
            this.cashInput = '';
        },

        printReceipt() {
            window.print();
        },
    };
}
</script>
</body>
</html>
