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
    <div class="flex-1 flex flex-col min-h-screen">
        <header class="sticky top-0 z-20">
            <div class="navbar-shimmer h-[3px] {{ ($foodOnly ?? false) ? 'bg-gradient-to-r from-amber-400 via-orange-300 to-amber-400' : 'bg-gradient-to-r from-lime-400 via-emerald-300 to-teal-400' }}"></div>
            <div class="bg-white shadow-sm px-3 sm:px-6 py-2 sm:py-3 flex items-center justify-between gap-2">
                <a href="{{ url(($foodOnly ?? false) ? '/kasir-makanan' : '/pos') }}" class="group flex items-center gap-2 sm:gap-3 min-w-0">
                    <div class="w-9 h-9 {{ ($foodOnly ?? false) ? 'bg-amber-800' : 'bg-green-800' }} rounded-xl flex items-center justify-center text-white text-lg shrink-0 transition-transform duration-300 group-hover:scale-110 group-active:scale-95">{{ ($foodOnly ?? false) ? '🍜' : '🏕️' }}</div>
                    <div class="min-w-0">
                        <h1 class="font-extrabold text-gray-800 text-sm sm:text-base truncate">SIRESTEDUH{{ ($foodOnly ?? false) ? ' · Kasir Makanan' : '' }}</h1>
                        <p class="text-[10px] sm:text-xs text-gray-400 truncate">{{ ($foodOnly ?? false) ? 'Khusus penjualan makanan' : 'BUMDes Wisata Teduh' }}</p>
                    </div>
                </a>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="hidden sm:inline text-[11px] sm:text-xs text-gray-400" x-text="today"></span>
                    <span class="hidden md:inline-flex items-center gap-1.5 text-[10px] sm:text-xs font-semibold text-gray-600 pl-2 border-l border-gray-200">
                        <span class="w-6 h-6 rounded-full bg-green-700 text-white flex items-center justify-center text-[10px] font-bold">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        {{ auth()->user()->name }}
                    </span>
                    <a href="{{ ($foodOnly ?? false) ? route('kasir.makanan.riwayat') : route('pos.officer.riwayat') }}" class="text-[11px] sm:text-xs font-semibold text-gray-600 border border-gray-200 rounded-lg px-2.5 py-1.5 hover:bg-gray-50 hover:-translate-y-0.5 hover:border-green-500 active:scale-95 transition-all duration-200">📋 Riwayat</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-[11px] sm:text-xs font-semibold text-red-500 border border-red-200 rounded-lg px-2.5 py-1.5 hover:bg-red-50 hover:-translate-y-0.5 active:scale-95 transition-all duration-200">🚪 Keluar</button>
                    </form>
                </div>
            </div>
        </header>

        <div class="flex flex-col flex-1 min-h-0">
            {{-- ============ LEFT: product grid ============ --}}
            <div class="flex-1 p-4 sm:p-5 overflow-y-auto">
                {{-- Search + tabs --}}
                <div class="flex items-center gap-2 mb-3">
                    <div class="relative flex-1">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
                        <input type="text" x-model="search" placeholder="Cari produk / layanan..."
                               class="w-full border border-gray-200 rounded-xl pl-9 pr-3 py-2.5 text-sm focus:ring-2 focus:ring-green-600 focus:outline-none bg-white">
                    </div>
                </div>
                <div class="flex gap-1.5 overflow-x-auto pb-2 mb-4 text-xs sm:text-sm">
                    <template x-for="tab in tabs" :key="tab.value">
                        <button type="button" @click="activeTab = tab.value"
                                :class="activeTab === tab.value ? 'bg-green-700 text-white shadow' : 'bg-white text-gray-600 border border-gray-200 hover:border-green-500'"
                                class="shrink-0 px-3.5 py-2 rounded-full font-semibold transition"
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
                    <a href="{{ route('login') }}" class="underline font-semibold">Masuk ulang</a>
                </div>
                <div x-show="!loading && !loadError && items.length === 0" x-cloak class="text-center text-gray-400 py-16">
                    <div class="text-4xl mb-2">📦</div>
                    Tidak ada item pada kategori ini.
                </div>

                {{-- Product cards: foto + nama + harga, format grid POS --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3">
                    <template x-for="item in items" :key="item.key">
                        <button type="button" @click="addToCart(item)" :disabled="item.stock <= 0"
                                :class="item.stock <= 0 ? 'opacity-40 cursor-not-allowed' : 'hover:shadow-lg hover:-translate-y-0.5 cursor-pointer'"
                                class="bg-white rounded-2xl border border-gray-100 overflow-hidden text-left transition flex flex-col shadow-sm">
                            {{-- Foto / fallback --}}
                            <div class="relative w-full aspect-[4/3] bg-gray-50 overflow-hidden">
                                <template x-if="item.images.length">
                                    <img :src="item.images[0].url" :alt="item.name" class="w-full h-full object-cover" loading="lazy">
                                </template>
                                <div x-show="!item.images.length" x-cloak class="absolute inset-0 flex items-center justify-center">
                                    <span class="text-5xl" x-text="item.emoji"></span>
                                </div>
                                {{-- Badge SEWA/PRODUK --}}
                                <span class="absolute top-2 left-2 text-[9px] font-extrabold px-2 py-0.5 rounded-full shadow"
                                      :class="item.kind === 'unit' ? 'bg-amber-400 text-amber-950' : 'bg-gray-900/80 text-white'"
                                      x-text="item.kind === 'unit' ? 'SEWA' : 'PRODUK'"></span>
                                {{-- Badge stok --}}
                                <span class="absolute top-2 right-2 text-[10px] font-bold px-2 py-0.5 rounded-full shadow"
                                      :class="item.stock <= 0 ? 'bg-red-600 text-white' : (item.stock <= 3 ? 'bg-orange-500 text-white' : 'bg-white/90 text-gray-700')"
                                      x-text="item.stock <= 0 ? 'Habis' : item.stock + ' pcs'"></span>
                            </div>
                            {{-- Nama + harga --}}
                            <div class="p-2.5 flex flex-col flex-1 gap-0.5">
                                <h3 class="text-xs sm:text-sm font-semibold text-gray-800 leading-snug line-clamp-2 min-h-10" x-text="item.name"></h3>
                                <p class="text-green-700 font-extrabold text-sm sm:text-base mt-auto" x-text="formatIDR(item.price)"></p>
                            </div>
                        </button>
                    </template>
                </div>
            </div>

    {{-- ============ Checkout modal (bottom sheet di HP, centered di tablet/PC) ============ --}}
    <div x-show="checkoutOpen" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4">
        <div class="absolute inset-0 bg-black/50" @click="closeCheckout()"></div>
        <div class="relative bg-white w-full sm:max-w-2xl lg:max-w-4xl rounded-t-3xl sm:rounded-3xl shadow-2xl max-h-[94vh] overflow-y-auto lg:overflow-hidden flex flex-col">
            {{-- Handle bar (mobile) --}}
            <div class="flex justify-center pt-2 sm:hidden"><div class="w-12 h-1.5 bg-gray-200 rounded-full"></div></div>
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 sticky top-0 bg-white sm:rounded-t-3xl">
                <div>
                    <h2 class="font-extrabold text-gray-800">🧾 Rincian Pembayaran</h2>
                    <p class="text-[10px] text-gray-400"><span x-text="cartCount"></span> item · <span x-text="cart.length"></span> jenis</p>
                </div>
                <button @click="closeCheckout()" class="p-2 rounded-full hover:bg-gray-100 text-gray-400 text-2xl leading-none" aria-label="Tutup">×</button>
            </div>

            <div class="flex items-stretch min-h-0 max-h-[72vh] lg:max-h-[78vh] flex-1">
                <div class="flex-1 overflow-y-auto px-5 py-4 space-y-4 min-w-0">
                {{-- Pencarian produk untuk ditambah (langsung di dalam Rincian) --}}
                <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
                    <input type="text" x-model="modalSearch" placeholder="Tambah produk lainnya..."
                           class="w-full border border-gray-200 rounded-xl pl-10 pr-10 py-2.5 text-sm focus:ring-2 focus:ring-green-600 focus:outline-none bg-gray-50">
                    <button type="button" x-show="modalSearch" @click="modalSearch = ''"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700 text-lg leading-none" aria-label="Hapus pencarian">×</button>
                </div>

                {{-- Hasil pencarian (quick-add) --}}
                <div x-show="addableItems.length > 0" x-cloak class="border border-green-100 rounded-2xl divide-y divide-gray-100 max-h-44 overflow-y-auto">
                    <template x-for="item in addableItems" :key="item.key">
                        <div class="flex items-center gap-2.5 px-2.5 py-2 hover:bg-green-50 transition">
                            <div class="w-10 h-10 rounded-lg overflow-hidden bg-gray-100 shrink-0 border border-gray-100">
                                <template x-if="item.images?.length">
                                    <img :src="item.images[0].url" :alt="item.name" class="w-full h-full object-cover">
                                </template>
                                <div x-show="!item.images?.length" x-cloak class="w-full h-full flex items-center justify-center text-lg" x-text="item.emoji"></div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-gray-800 leading-snug" x-text="item.name"></p>
                                <p class="text-[10px] text-gray-400">Sisa <span x-text="item.stock"></span> · <span class="font-bold text-green-800" x-text="formatIDR(item.price)"></span></p>
                            </div>
                            <button type="button" @click="addToCart(item)" :disabled="item.stock <= 0"
                                    :class="item.stock <= 0 ? 'bg-gray-100 text-gray-300 cursor-not-allowed' : 'bg-green-700 text-white hover:bg-green-800'"
                                    class="shrink-0 rounded-lg px-3 py-1.5 text-xs font-extrabold transition">+ Tambah</button>
                        </div>
                    </template>
                </div>
                <p x-show="modalSearch.trim() && addableItems.length === 0" x-cloak
                   class="text-center text-xs text-gray-400 py-2">Produk tidak ditemukan.</p>

                {{-- Item (full keranjang) --}}
                <ul class="divide-y divide-gray-100 max-h-56 overflow-y-auto">
                    <template x-for="item in cart" :key="item.key">
                        <li class="py-2.5 flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl overflow-hidden bg-gray-50 shrink-0 border border-gray-100">
                                <template x-if="item.images?.length">
                                    <img :src="item.images[0].url" :alt="item.name" class="w-full h-full object-cover">
                                </template>
                                <div x-show="!item.images?.length" x-cloak class="w-full h-full flex items-center justify-center text-2xl" x-text="item.emoji"></div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-800 leading-snug" x-text="item.name"></p>
                                <p class="text-xs text-gray-400" x-text="formatIDR(item.price)"></p>
                            </div>
                            <div class="flex items-center border border-gray-200 rounded-lg shrink-0">
                                <button type="button" class="px-2 py-1 text-gray-500 hover:text-green-700" @click="decQty(item)">−</button>
                                <span class="w-7 text-center text-sm font-bold" x-text="item.qty"></span>
                                <button type="button" class="px-2 py-1 text-gray-500 hover:text-green-700" @click="incQty(item)">+</button>
                            </div>
                            <button type="button" @click="removeItem(item)" class="text-gray-300 hover:text-red-500 transition shrink-0" aria-label="Hapus">✕</button>
                        </li>
                    </template>
                    <li x-show="cart.length === 0" x-cloak class="py-10 text-center">
                        <div class="text-4xl mb-2">🛒</div>
                        <p class="text-sm text-gray-400">Keranjang kosong.</p>
                    </li>
                </ul>

                {{-- Checkout error --}}
                <div x-show="checkoutError" x-cloak class="text-xs text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2" x-text="checkoutError"></div>
                </div>

                {{-- Kalkulator side panel (desktop/tablet landscape) --}}
                <aside class="flex w-52 sm:w-64 md:w-72 lg:w-80 xl:w-96 border-l border-gray-100 bg-slate-50 flex-col p-2 sm:p-3 md:p-4 gap-3 overflow-y-auto">
                    {{-- Metode pembayaran --}}
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1.5">💳 Metode</p>
                        <div class="grid grid-cols-3 gap-1.5 sm:gap-2">
                            <template x-for="method in paymentMethods" :key="method.value">
                                <button type="button" @click="paymentMethod = method.value"
                                        :class="paymentMethod === method.value ? 'border-green-600 bg-green-50 text-green-800 ring-2 ring-green-500' : 'border-gray-200 text-gray-600 hover:border-green-400'"
                                        class="border rounded-xl py-1.5 sm:py-2.5 font-semibold transition text-center">
                                    <span class="block text-lg sm:text-2xl" x-text="method.icon"></span>
                                    <span class="text-[9px] sm:text-[11px]" x-text="method.label"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Total --}}
                    <div class="flex items-baseline justify-between border-t border-gray-200 pt-2 sm:pt-3">
                        <span class="text-[10px] sm:text-xs font-semibold text-gray-500 uppercase tracking-wide">Total</span>
                        <span class="text-base sm:text-2xl font-extrabold text-green-800" x-text="formatIDR(total)"></span>
                    </div>

                    <div class="border-t border-gray-200 pt-2 sm:pt-3">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1.5 sm:mb-2">💰 Kalkulator Kembalian</p>

                    <template x-if="paymentMethod === 'cash'">
                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between">
                                <p class="text-[11px] font-bold uppercase tracking-widest text-gray-500">Tunai</p>
                                <button type="button" @click="cashInput = ''" class="text-[10px] font-bold text-red-400 hover:text-red-600">Reset</button>
                            </div>

                            <div class="bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-right shadow-inner">
                                <p class="text-[9px] text-gray-400 uppercase tracking-wider">Uang Diterima</p>
                                <p class="text-xl font-extrabold text-gray-800 font-mono truncate" x-text="formatIDR(cashTender)"></p>
                            </div>

                            <div class="grid grid-cols-6 gap-1.5">
                                <template x-for="q in quickAmounts" :key="q">
                                    <button type="button" @click="cashInput = String(q)"
                                            class="bg-white border border-slate-200 rounded-lg py-1.5 text-[11px] font-bold text-gray-700 hover:border-green-500 hover:text-green-800 active:scale-95 transition"
                                            x-text="'Rp ' + (q/1000) + 'rb'"></button>
                                </template>
                            </div>

                            <div class="grid grid-cols-4 gap-1.5">
                                <button type="button" @click="pressKey('7')" class="bg-white rounded-xl py-2.5 text-lg font-bold text-gray-800 hover:bg-slate-100 border border-slate-200 active:scale-95 transition">7</button>
                                <button type="button" @click="pressKey('8')" class="bg-white rounded-xl py-2.5 text-lg font-bold text-gray-800 hover:bg-slate-100 border border-slate-200 active:scale-95 transition">8</button>
                                <button type="button" @click="pressKey('9')" class="bg-white rounded-xl py-2.5 text-lg font-bold text-gray-800 hover:bg-slate-100 border border-slate-200 active:scale-95 transition">9</button>
                                <button type="button" @click="backspace()" class="bg-orange-50 rounded-xl py-2.5 text-lg font-bold text-orange-600 hover:bg-orange-100 border border-orange-200 active:scale-95 transition">⌫</button>
                                <button type="button" @click="pressKey('4')" class="bg-white rounded-xl py-2.5 text-lg font-bold text-gray-800 hover:bg-slate-100 border border-slate-200 active:scale-95 transition">4</button>
                                <button type="button" @click="pressKey('5')" class="bg-white rounded-xl py-2.5 text-lg font-bold text-gray-800 hover:bg-slate-100 border border-slate-200 active:scale-95 transition">5</button>
                                <button type="button" @click="pressKey('6')" class="bg-white rounded-xl py-2.5 text-lg font-bold text-gray-800 hover:bg-slate-100 border border-slate-200 active:scale-95 transition">6</button>
                                <button type="button" @click="clearCash()" class="bg-red-50 rounded-xl py-2.5 text-sm font-extrabold text-red-600 hover:bg-red-100 border border-red-200 active:scale-95 transition">C</button>
                                <button type="button" @click="pressKey('1')" class="bg-white rounded-xl py-2.5 text-lg font-bold text-gray-800 hover:bg-slate-100 border border-slate-200 active:scale-95 transition">1</button>
                                <button type="button" @click="pressKey('2')" class="bg-white rounded-xl py-2.5 text-lg font-bold text-gray-800 hover:bg-slate-100 border border-slate-200 active:scale-95 transition">2</button>
                                <button type="button" @click="pressKey('3')" class="bg-white rounded-xl py-2.5 text-lg font-bold text-gray-800 hover:bg-slate-100 border border-slate-200 active:scale-95 transition">3</button>
                                <button type="button" @click="setExact()" class="bg-teal-50 rounded-xl py-2.5 text-[11px] font-extrabold text-teal-700 hover:bg-teal-100 border border-teal-200 active:scale-95 transition leading-tight">UANG<br>PAS</button>
                                <button type="button" @click="pressKey('0')" class="bg-white rounded-xl py-2.5 text-lg font-bold text-gray-800 hover:bg-slate-100 border border-slate-200 active:scale-95 transition">0</button>
                                <button type="button" @click="pressKey('00')" class="bg-white rounded-xl py-2.5 text-lg font-bold text-gray-800 hover:bg-slate-100 border border-slate-200 active:scale-95 transition col-span-2">00</button>
                                <button type="button" @click="pressKey('000')" class="bg-white rounded-xl py-2.5 text-lg font-bold text-gray-800 hover:bg-slate-100 border border-slate-200 active:scale-95 transition">000</button>
                            </div>

                            <div class="flex items-baseline justify-between rounded-xl px-3 py-2 border"
                                 :class="kembalian >= 0 ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'">
                                <span class="text-[10px] font-bold uppercase tracking-widest" :class="kembalian >= 0 ? 'text-green-700' : 'text-red-600'">Kembalian</span>
                                <span class="text-lg font-extrabold font-mono" :class="kembalian >= 0 ? 'text-green-800' : 'text-red-600'"
                                      x-text="(kembalian >= 0 ? '' : '− ') + formatIDR(Math.abs(kembalian))"></span>
                            </div>
                        </div>
                    </template>

                    <div x-show="paymentMethod !== 'cash'" x-cloak class="flex-1 flex flex-col items-center justify-center text-center py-10">
                        <div class="text-6xl mb-3" x-text="paymentMethods.find(m => m.value === paymentMethod)?.icon ?? '💳'"></div>
                        <p class="font-extrabold text-gray-700 text-lg" x-text="paymentMethods.find(m => m.value === paymentMethod)?.label ?? 'Non-Tunai'"></p>
                        <p class="text-xs text-gray-400 mt-1">Tidak perlu kembalian — langsung konfirmasi bayar.</p>
                    </div>
                    </div>
                </aside>
            </div>

            {{-- Sticky confirm --}}
            <div class="sticky bottom-0 bg-white border-t border-gray-100 px-5 py-4 sm:rounded-b-3xl">
                <button type="button" @click="checkout()" :disabled="!canCheckout"
                        :class="!canCheckout ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-green-700 text-white hover:bg-green-800 shadow-lg shadow-green-700/20'"
                        class="w-full font-extrabold py-4 rounded-2xl transition flex items-center justify-center gap-2 text-base">
                    <svg x-show="sending" x-cloak class="animate-spin h-5 w-5" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span x-text="sending ? 'Memproses...' : '💳 Bayar Sekarang'"></span>
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

    {{-- Sticky footer: Rincian — selalu tampak saat keranjang berisi (HP/tablet). Di desktop, kolom keranjang kanan sudah selalu terlihat. --}}
    <div x-show="cart.length > 0" x-cloak
         class="fixed inset-x-0 bottom-0 z-40 border-t border-gray-200 bg-white/95 backdrop-blur px-3 py-2 flex items-center gap-3 shadow-[0_-4px_20px_rgba(0,0,0,0.08)]">
        <div class="min-w-0 flex-1">
            <p class="text-[10px] uppercase font-bold text-gray-400 leading-tight">🧾 Rincian &middot; <span x-text="cartCount"></span> item</p>
            <p class="text-lg font-extrabold text-green-800 leading-tight truncate" x-text="formatIDR(total)"></p>
        </div>
        <button type="button" @click="openCheckout()"
                class="shrink-0 bg-green-700 hover:bg-green-800 text-white font-extrabold text-sm sm:text-base rounded-2xl px-5 sm:px-6 py-3.5 shadow-lg shadow-green-700/20 active:scale-95 transition">
            Rincian &amp; Bayar →
        </button>
    </div>
    {{-- Spacer agar konten tidak tertutup sticky footer --}}
    <div x-show="cart.length > 0" x-cloak class="h-16 shrink-0"></div>
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
        sending: false,
        checkoutError: '',
        showReceipt: false,
        checkoutOpen: false,
        receipt: null,
        today: new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }),

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

        async init() {
            try {
                const [productsRes, unitsRes] = await Promise.all([
                    fetch('/api/pos/products', { headers: { 'Accept': 'application/json' } }),
                    fetch('/api/pos/units', { headers: { 'Accept': 'application/json' } }),
                ]);

                if (productsRes.status === 401 || productsRes.status === 403 || unitsRes.status === 401 || unitsRes.status === 403) {
                    this.loadError = 'Sesi berakhir atau Anda tidak memiliki akses kasir.';
                    this.loading = false;
                    return;
                }

                const productsData = await productsRes.json();
                const unitsData = await unitsRes.json();

                this.products = productsData.data || [];
                this.units = unitsData.data || [];
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

        modalSearch: '',

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
            if (item.stock <= 0) return;
            const existing = this.cart.find(i => i.key === item.key);
            if (existing) {
                if (existing.qty < item.stock) existing.qty += 1;
            } else {
                this.cart.push({ ...item, qty: 1 });
            }
            this.checkoutError = '';
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
                    : { unit_id: item.id, quantity: item.qty }),
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
                    this.checkoutOpen = false;
                    this.showReceipt = true;
                    // Refresh stok lokal setelah transaksi
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
