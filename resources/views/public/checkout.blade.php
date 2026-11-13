@extends('layouts.app')

@section('title', 'Checkout')

@section('content')
<div x-data="checkoutPage()" x-init="init()">

    {{-- Success panel --}}
    <div x-show="success" x-cloak class="max-w-2xl mx-auto">
        <div class="bg-white rounded-2xl shadow border border-green-100 p-8 md:p-12 text-center">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center text-4xl mx-auto mb-6">✅</div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-green-900 mb-2">Pesanan Berhasil Dibuat!</h1>
            <p class="text-gray-500 mb-6">Simpan kode pesanan berikut. Anda akan diarahkan ke pembayaran transfer.</p>
            <div class="bg-green-50 border border-green-200 rounded-xl py-4 px-6 inline-block mb-6">
                <p class="text-xs uppercase tracking-widest text-green-700 font-semibold">Order Code</p>
                <p class="text-2xl md:text-3xl font-mono font-bold text-green-900" x-text="success?.order_code"></p>
            </div>
            <div class="text-sm text-gray-600 mb-8">
                <span class="font-semibold">Total:</span>
                <span x-text="formatIDR(lastTotal)"></span>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a :href="'/pembayaran/' + success?.order_code"
                   class="bg-green-700 text-white font-bold px-8 py-3.5 rounded-xl hover:bg-green-800 transition shadow-lg shadow-green-700/20 inline-flex items-center justify-center gap-2">
                    Lanjut ke Pembayaran →
                </a>
                <button @click="resetAll()" class="border border-gray-200 text-gray-600 font-semibold px-8 py-3.5 rounded-xl hover:bg-gray-50 transition">
                    Buat Pesanan Lagi
                </button>
            </div>
            <p class="text-xs text-gray-400 mt-4">
                Dialihkan otomatis ke halaman pembayaran dalam beberapa detik…
                <button type="button" @click="cancelAutoRedirect()" class="text-green-700 font-semibold hover:underline">Batalkan</button>
            </p>
        </div>
    </div>

    {{-- Main layout --}}
    <div x-show="!success" x-cloak>
        {{-- Page header --}}
        <div class="mb-6">
            <h1 class="text-2xl md:text-3xl font-extrabold text-green-900">Checkout</h1>
            <p class="text-gray-500 mt-1">Sewa glamping / aula / alat wisata, atau beli Tiket Masuk saja.</p>
        </div>

        <div class="grid lg:grid-cols-3 gap-6 items-start">

            {{-- ============ LEFT: form ============ --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Tabs --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-2 grid grid-cols-2 gap-2">
                    <button type="button" @click="tab = 'unit'"
                        :class="tab === 'unit' ? 'bg-green-700 text-white shadow' : 'text-gray-600 hover:bg-green-50'"
                        class="px-4 py-3 rounded-xl font-semibold transition flex flex-col items-center gap-0.5">
                        <span>🏕️ Sewa Unit</span>
                        <span class="text-xs font-normal opacity-80">Glamping / Aula / Alat</span>
                    </button>
                    <button type="button" @click="switchTab('tiket')"
                        :class="tab === 'tiket' ? 'bg-green-700 text-white shadow' : 'text-gray-600 hover:bg-green-50'"
                        class="px-4 py-3 rounded-xl font-semibold transition flex flex-col items-center gap-0.5">
                        <span>🎫 Tiket Masuk Saja</span>
                        <span class="text-xs font-normal opacity-80">Tanpa menginap</span>
                    </button>
                </div>

                {{-- Error banner --}}
                <div x-show="errorMessage" x-cloak class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm flex items-start gap-2">
                    <span>⚠️</span><span x-text="errorMessage"></span>
                </div>

                {{-- ===== TAB: Sewa Unit ===== --}}
                <div x-show="tab === 'unit'" x-cloak class="space-y-5">

                    {{-- Date selection --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Tanggal Menginap</label>
                        <div class="grid grid-cols-2 gap-3 mt-2">
                            <div>
                                <label for="check_in" class="text-xs text-gray-400">Check-in</label>
                                <input id="check_in" type="date" x-model="checkIn" :min="todayStr" @change="fetchAvailability()"
                                       class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-green-600 focus:outline-none">
                            </div>
                            <div>
                                <label for="check_out" class="text-xs text-gray-400">Check-out</label>
                                <input id="check_out" type="date" x-model="checkOut" :min="checkIn || todayStr" @change="fetchAvailability()"
                                       class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-green-600 focus:outline-none">
                            </div>
                        </div>
                        <p x-show="dateInvalid" x-cloak class="text-xs text-red-600 mt-2">Tanggal check-out harus setelah check-in.</p>
                        <p x-show="nights > 0" x-cloak class="text-xs text-green-700 mt-2 font-medium">
                            <span x-text="nights"></span> malam
                        </p>
                    </div>

                    {{-- Availability results --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                        <div class="flex items-center justify-between gap-3 flex-wrap">
                            <h2 class="font-bold text-gray-800">Unit Tersedia</h2>
                            <div class="flex gap-1.5 text-xs">
                                <template x-for="f in unitTypeFilters" :key="f.value">
                                    <button type="button" @click="unitType = f.value"
                                        :class="unitType === f.value ? 'bg-green-700 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                                        class="px-3 py-1.5 rounded-full font-medium transition" x-text="f.label"></button>
                                </template>
                            </div>
                        </div>

                        {{-- Loading spinner --}}
                        <div x-show="loadingUnits" x-cloak class="flex items-center gap-3 text-gray-400 py-10 justify-center">
                            <svg class="animate-spin h-6 w-6 text-green-700" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>Memeriksa ketersediaan...</span>
                        </div>

                        {{-- Error state --}}
                        <div x-show="unitsError && !loadingUnits" x-cloak class="text-center text-red-600 text-sm py-8" x-text="unitsError"></div>

                        {{-- Prompt to pick dates first --}}
                        <div x-show="!loadingUnits && (!checkIn || !checkOut) && !unitsError" x-cloak class="text-center text-gray-400 py-10">
                            <div class="text-4xl mb-2">📅</div>
                            Pilih tanggal check-in &amp; check-out untuk melihat unit tersedia.
                        </div>

                        {{-- Empty state --}}
                        <div x-show="!loadingUnits && filteredUnits.length === 0 && checkIn && checkOut && !unitsError" x-cloak
                             class="text-center text-gray-400 py-10">
                            <div class="text-4xl mb-2">😔</div>
                            Tidak ada unit tersedia untuk tanggal &amp; tipe ini.
                        </div>

                        {{-- Unit cards --}}
                        <div class="grid sm:grid-cols-2 gap-4 mt-4">
                            <template x-for="unit in filteredUnits" :key="unit.id">
                                <button type="button" @click="selectUnit(unit)"
                                    :class="selectedUnit?.id === unit.id ? 'ring-2 ring-green-600 border-green-600 bg-green-50' : 'border-gray-200 hover:border-green-400'"
                                    class="text-left border rounded-2xl p-4 transition bg-white relative">
                                    <span x-show="selectedUnit?.id === unit.id" x-cloak
                                          class="absolute top-3 right-3 bg-green-600 text-white text-xs rounded-full w-6 h-6 flex items-center justify-center">✓</span>
                                    <div class="relative h-28 -mx-4 -mt-4 mb-3 overflow-hidden rounded-t-2xl bg-green-50">
                                        <template x-if="unit.images?.length">
                                            <img :src="unit.images?.[0]?.url" :alt="unit.name" class="w-full h-full object-cover">
                                        </template>
                                        <div x-show="!unit.images?.length" x-cloak class="absolute inset-0 flex items-center justify-center text-4xl" x-text="typeEmoji(unit.type)"></div>
                                        <span class="absolute top-2 left-2 text-[10px] font-bold bg-black/55 text-white rounded-full px-2 py-0.5" x-text="'Sisa ' + unit.stock"></span>
                                    </div>
                                    <h3 class="font-bold text-gray-800 leading-snug" x-text="unit.name"></h3>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        <span x-text="typeLabel(unit.type)"></span> · Kapasitas <span x-text="unit.capacity"></span> orang · Sisa <span x-text="unit.stock"></span>
                                    </p>
                                    <p class="text-green-700 font-bold mt-2">
                                        <span x-text="formatIDR(unit.price)"></span><span class="text-xs font-medium text-gray-400"> / malam</span>
                                    </p>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- ===== TAB: Tiket Masuk Saja ===== --}}
                <div x-show="tab === 'tiket'" x-cloak class="space-y-5">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                        <div class="flex items-center justify-between">
                            <h2 class="font-bold text-gray-800">Tiket Masuk</h2>
                            <div class="text-xs text-gray-400">Berlaku sesuai tanggal kunjungan</div>
                        </div>

                        <div class="mt-3">
                            <label for="visit_date" class="text-xs text-gray-400">Tanggal Kunjungan</label>
                            <input id="visit_date" type="date" x-model="checkIn" :min="todayStr" @change="checkOut = checkIn"
                                   class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-green-600 focus:outline-none">
                        </div>

                        <div x-show="loadingTickets" x-cloak class="flex items-center gap-3 text-gray-400 py-8 justify-center">
                            <svg class="animate-spin h-6 w-6 text-green-700" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>Memuat tiket...</span>
                        </div>

                        <div class="grid sm:grid-cols-2 gap-4 mt-4">
                            <template x-for="ticket in tickets" :key="ticket.id">
                                <div class="border border-gray-200 rounded-2xl p-4 hover:border-green-400 transition bg-white">
                                    <div class="relative h-24 -mx-4 -mt-4 mb-2 overflow-hidden rounded-t-2xl bg-amber-50">
                                    <template x-if="ticket.images?.length">
                                        <img :src="ticket.images?.[0]?.url" :alt="ticket.name" class="w-full h-full object-cover">
                                    </template>
                                    <div x-show="!ticket.images?.length" x-cloak class="absolute inset-0 flex items-center justify-center text-4xl">🎫</div>
                                </div>
                                    <h3 class="font-bold text-gray-800 leading-snug" x-text="ticket.name"></h3>
                                    <p class="text-xs text-gray-400 mt-0.5">Sisa stok: <span x-text="ticket.stock"></span></p>
                                    <p class="text-green-700 font-bold mt-2" x-text="formatIDR(ticket.price)"></p>
                                    <div class="flex items-center justify-between mt-3">
                                        <div class="flex items-center border border-gray-200 rounded-lg">
                                            <button type="button" class="px-3 py-1.5 text-gray-500 hover:text-green-700" @click="decTicketQty(ticket)">−</button>
                                            <span class="w-8 text-center font-semibold" x-text="ticketQty(ticket.id)"></span>
                                            <button type="button" class="px-3 py-1.5 text-gray-500 hover:text-green-700" @click="incTicketQty(ticket)">+</button>
                                        </div>
                                        <button type="button" @click="addTicket(ticket)"
                                                class="bg-green-700 text-white text-sm font-semibold px-4 py-2 rounded-lg hover:bg-green-800 transition">
                                            Tambah
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- ===== UMKM / Add-on strip (semua tab) ===== --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="font-bold text-gray-800">Tambahan UMKM &amp; Add-on</h2>
                        <span class="text-xs text-gray-400">Opsional</span>
                    </div>
                    <div x-show="loadingAddons" x-cloak class="text-gray-400 text-sm py-4">Memuat produk UMKM...</div>
                    <div class="flex gap-3 overflow-x-auto pb-2 -mx-1 px-1">
                        <template x-for="addon in addons" :key="addon.id">
                            <div class="shrink-0 w-40 border border-gray-200 rounded-2xl p-3 flex flex-col">
                                <div class="relative h-20 -mx-3 -mt-3 mb-2 overflow-hidden rounded-t-2xl bg-amber-50">
                                    <template x-if="addon.images?.length">
                                        <img :src="addon.images?.[0]?.url" :alt="addon.name" class="w-full h-full object-cover">
                                    </template>
                                    <div x-show="!addon.images?.length" x-cloak class="absolute inset-0 flex items-center justify-center text-xl" x-text="categoryEmoji(addon.category)"></div>
                                </div>
                                <h3 class="text-sm font-semibold text-gray-800 leading-snug line-clamp-2 min-h-10" x-text="addon.name"></h3>
                                <p class="text-green-700 font-bold text-sm mt-1.5" x-text="formatIDR(addon.price)"></p>
                                <button type="button" @click="addToCart(addon)"
                                        :disabled="addon.stock <= 0"
                                        :class="addon.stock <= 0 ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-green-50 text-green-800 hover:bg-green-100'"
                                        class="mt-auto pt-2 mx-auto w-full text-xs font-bold py-1.5 rounded-lg transition">
                                    <span x-text="addon.stock <= 0 ? 'Habis' : '+ Tambah'"></span>
                                </button>
                            </div>
                        </template>
                        <p x-show="!loadingAddons && addons.length === 0" x-cloak class="text-sm text-gray-400 py-4">Belum ada produk UMKM.</p>
                    </div>
                </div>

                {{-- ===== Visitor form ===== --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <h2 class="font-bold text-gray-800 mb-3">Data Pemesan</h2>
                    <div class="grid sm:grid-cols-2 gap-3">
                        <div class="sm:col-span-2">
                            <label for="visitor_name" class="text-xs text-gray-400">Nama Lengkap *</label>
                            <input id="visitor_name" type="text" x-model="visitorName" placeholder="Budi Santoso" required
                                   class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-green-600 focus:outline-none">
                        </div>
                        <div>
                            <label for="visitor_whatsapp" class="text-xs text-gray-400">No. WhatsApp *</label>
                            <input id="visitor_whatsapp" type="tel" inputmode="numeric" x-model="visitorWhatsapp" @input="sanitizeWhatsapp()"
                                   placeholder="081234567890" required
                                   class="mt-1 w-full border rounded-xl px-3 py-2.5 focus:ring-2 focus:outline-none"
                                   :class="visitorWhatsapp && !whatsappValid ? 'border-red-300 focus:ring-red-400' : 'border-gray-200 focus:ring-green-600'">
                            <p class="text-[11px] text-gray-400 mt-1" x-show="!visitorWhatsapp || whatsappValid">E-tiket QR dikirim ke nomor ini.</p>
                            <p class="text-[11px] text-red-600 mt-1 font-medium" x-show="visitorWhatsapp && !whatsappValid" x-cloak x-text="whatsappError()"></p>
                        </div>
                        <div>
                            <label for="visitor_email" class="text-xs text-gray-400">Email <span class="text-gray-300">(opsional)</span></label>
                            <input id="visitor_email" type="email" x-model="visitorEmail" placeholder="nama@email.com"
                                   class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-green-600 focus:outline-none">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============ RIGHT: order summary ============ --}}
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 lg:sticky lg:top-6">
                    <h2 class="font-extrabold text-lg text-green-900 flex items-center justify-between">
                        Ringkasan
                        <span class="text-xs font-semibold bg-green-100 text-green-800 rounded-full px-3 py-1" x-text="itemCount + ' item'"></span>
                    </h2>

                    {{-- Empty state --}}
                    <div x-show="!selectedUnit && cart.length === 0" x-cloak class="text-center text-gray-400 py-10">
                        <div class="text-4xl mb-2">🧾</div>
                        Belum ada item.<br>
                        <span class="text-sm">Pilih unit / tiket, lalu tambahkan add-on.</span>
                    </div>

                    {{-- Selected unit line --}}
                    <template x-if="selectedUnit">
                        <div class="mt-4 flex items-start gap-3 bg-green-50 border border-green-100 rounded-xl p-3">
                            <span class="text-2xl" x-text="typeEmoji(selectedUnit.type)"></span>
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-gray-800 text-sm leading-snug" x-text="selectedUnit.name"></p>
                                <p class="text-xs text-gray-500 mt-0.5"><span x-text="nights"></span> malam × <span x-text="formatIDR(selectedUnit.price)"></span></p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold text-green-800" x-text="formatIDR(unitSubtotal)"></p>
                                <button type="button" @click="selectedUnit = null" class="text-xs text-red-500 hover:text-red-700 mt-1">Hapus</button>
                            </div>
                        </div>
                    </template>

                    {{-- Cart items --}}
                    <ul class="mt-3 divide-y divide-gray-100">
                        <template x-for="item in cart" :key="item.id">
                            <li class="py-3 flex items-center gap-3">
                                <span class="text-xl" x-text="categoryEmoji(item.category)"></span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-800 leading-snug" x-text="item.name"></p>
                                    <p class="text-xs text-gray-400" x-text="formatIDR(item.price)"></p>
                                </div>
                                <div class="flex items-center border border-gray-200 rounded-lg text-xs">
                                    <button type="button" class="px-2 py-1 text-gray-500 hover:text-green-700" @click="decCart(item)">−</button>
                                    <span class="w-6 text-center font-semibold" x-text="item.qty"></span>
                                    <button type="button" class="px-2 py-1 text-gray-500 hover:text-green-700" @click="incCart(item)">+</button>
                                </div>
                                <button type="button" @click="removeCart(item)" class="text-gray-300 hover:text-red-500 transition" aria-label="Hapus">✕</button>
                            </li>
                        </template>
                    </ul>

                    {{-- Totals --}}
                    <div x-show="selectedUnit || cart.length > 0" x-cloak class="border-t border-gray-100 mt-2 pt-4 space-y-1.5 text-sm">
                        <div class="flex justify-between text-gray-500"><span>Unit</span><span x-text="formatIDR(unitSubtotal)"></span></div>
                        <div class="flex justify-between text-gray-500"><span>Tiket &amp; Add-on</span><span x-text="formatIDR(cartSubtotal)"></span></div>
                        <div class="flex justify-between items-baseline pt-2 border-t border-dashed border-gray-200">
                            <span class="font-bold text-gray-800">Total</span>
                            <span class="text-xl font-extrabold text-green-800" x-text="formatIDR(grandTotal)"></span>
                        </div>
                    </div>

                    {{-- Submit --}}
                    <button type="button" @click="submit()" :disabled="!canSubmit || sending"
                        :class="!canSubmit || sending ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-green-700 text-white hover:bg-green-800 shadow-lg shadow-green-700/20'"
                        class="w-full mt-4 font-bold py-3.5 rounded-xl transition flex items-center justify-center gap-2">
                        <svg x-show="sending" x-cloak class="animate-spin h-5 w-5" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span x-text="sending ? 'Memproses...' : 'Pesan Sekarang'"></span>
                    </button>
                    <p class="text-[11px] text-gray-400 text-center mt-2">Pembayaran diverifikasi admin. E-tiket dikirim via WhatsApp setelah pembayaran disetujui.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function checkoutPage() {
    return {
        tab: 'unit',
        todayStr: '',
        checkIn: '',
        checkOut: '',
        unitType: 'all',
        availableUnits: [],
        selectedUnit: null,
        loadingUnits: false,
        unitsError: '',
        tickets: [],
        ticketQuantities: {},
        loadingTickets: false,
        addons: [],
        loadingAddons: false,
        cart: [],
        visitorName: '',
        visitorWhatsapp: '',
        visitorEmail: '',
        sending: false,
        errorMessage: '',
        success: null,
        lastTotal: 0,
        redirectTimer: null,

        unitTypeFilters: [
            { value: 'all', label: 'Semua' },
            { value: 'glamping', label: 'Glamping' },
            { value: 'aula', label: 'Aula' },
            { value: 'alat_wisata', label: 'Alat Wisata' },
        ],

        init() {
            this.todayStr = new Date().toISOString().split('T')[0];
            this.loadAddons();
        },

        async switchTab(name) {
            this.tab = name;
            this.errorMessage = '';
            if (name === 'tiket' && this.tickets.length === 0) {
                await this.loadTickets();
            }
        },

        get filteredUnits() {
            if (this.unitType === 'all') return this.availableUnits;
            return this.availableUnits.filter(u => u.type === this.unitType);
        },

        get nights() {
            if (!this.checkIn || !this.checkOut) return 0;
            const diff = (new Date(this.checkOut) - new Date(this.checkIn)) / 86400000;
            return diff > 0 ? Math.ceil(diff) : 0;
        },

        get dateInvalid() {
            return !!this.checkIn && !!this.checkOut && new Date(this.checkOut) <= new Date(this.checkIn);
        },

        get unitSubtotal() {
            if (!this.selectedUnit || this.nights <= 0) return 0;
            return Number(this.selectedUnit.price) * this.nights;
        },

        get cartSubtotal() {
            return this.cart.reduce((sum, item) => sum + Number(item.price) * item.qty, 0);
        },

        get grandTotal() {
            return this.unitSubtotal + this.cartSubtotal;
        },

        get itemCount() {
            return (this.selectedUnit ? 1 : 0) + this.cart.reduce((sum, item) => sum + item.qty, 0);
        },

        // ===== Validasi nomor WhatsApp (mirror App\Rules\WhatsAppNumber) =====
        sanitizeWhatsapp() {
            // Hanya digit; '+' hanya boleh di awal. Maksimal 16 digit.
            const plus = this.visitorWhatsapp.trim().startsWith('+');
            let digits = this.visitorWhatsapp.replace(/\D/g, '');
            if (digits.length > 16) digits = digits.slice(0, 16);
            this.visitorWhatsapp = (plus ? '+' : '') + digits;
        },

        normalizeWhatsapp(raw) {
            let digits = String(raw ?? '').replace(/\D/g, '');
            if (!digits) return null;
            if (digits.startsWith('62')) {
                // Sudah format internasional
            } else if (digits.startsWith('0')) {
                digits = '62' + digits.slice(1);
            } else if (digits.startsWith('8')) {
                digits = '62' + digits;
            }
            if (!digits.startsWith('628')) return null;
            if (digits.length < 10 || digits.length > 15) return null;
            return digits;
        },

        get whatsappValid() {
            return this.normalizeWhatsapp(this.visitorWhatsapp) !== null;
        },

        whatsappError() {
            const digits = this.visitorWhatsapp.replace(/\D/g, '');
            if (!digits) return 'Nomor WhatsApp wajib diisi (contoh: 081234567890).';
            if (!digits.startsWith('0') && !digits.startsWith('62') && !digits.startsWith('8')) {
                return 'Nomor WhatsApp harus diawali 08 atau 62 (contoh: 081234567890).';
            }
            return 'Nomor WhatsApp tidak valid. Gunakan 10–15 digit angka, diawali 08 atau 62 (contoh: 081234567890).';
        },

        get canSubmit() {
            if (this.sending || !this.visitorName.trim() || !this.whatsappValid) return false;
            if (this.tab === 'unit') {
                return !!this.selectedUnit && this.nights > 0;
            }
            return this.cart.length > 0 && !!this.checkIn;
        },

        typeEmoji(type) {
            return { glamping: '🏕️', aula: '🏛️', alat_wisata: '🥾' }[type] ?? '📦';
        },

        typeLabel(type) {
            return { glamping: 'Glamping', aula: 'Aula', alat_wisata: 'Alat Wisata' }[type] ?? type;
        },

        categoryEmoji(category) {
            return {
                tiket_masuk: '🎫', makanan: '🍜', minuman: '🥤',
                souvenir: '🎁', baju_merchandise: '👕', produk_umkm: '☕',
            }[category] ?? '🛍️';
        },

        formatIDR(value) {
            return 'Rp ' + Math.round(Number(value) || 0).toLocaleString('id-ID');
        },

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        },

        async fetchAvailability() {
            if (!this.checkIn || !this.checkOut || this.dateInvalid) return;
            this.loadingUnits = true;
            this.unitsError = '';
            try {
                const res = await fetch(`/api/availability?check_in=${this.checkIn}&check_out=${this.checkOut}`);
                const data = await res.json();
                this.availableUnits = data.data || [];
                this.selectedUnit = null;
            } catch (e) {
                this.unitsError = 'Gagal memuat ketersediaan. Periksa koneksi Anda.';
            } finally {
                this.loadingUnits = false;
            }
        },

        selectUnit(unit) {
            this.selectedUnit = unit;
            this.errorMessage = '';
        },

        async loadTickets() {
            this.loadingTickets = true;
            try {
                const res = await fetch('/api/products?category=tiket_masuk');
                const data = await res.json();
                this.tickets = data.data || [];
                this.tickets.forEach(t => { this.ticketQuantities[t.id] = 1; });
            } catch (e) {
                this.errorMessage = 'Gagal memuat Tiket Masuk.';
            } finally {
                this.loadingTickets = false;
            }
        },

        async loadAddons() {
            this.loadingAddons = true;
            try {
                const res = await fetch('/api/products?type=addon');
                const data = await res.json();
                this.addons = (data.data || []).filter(p => p.category !== 'tiket_masuk');
            } catch (e) {
                // Add-on bersifat opsional; gagal load tidak memblokir checkout
            } finally {
                this.loadingAddons = false;
            }
        },

        ticketQty(id) {
            return this.ticketQuantities[id] ?? 1;
        },

        incTicketQty(ticket) {
            const current = this.ticketQty(ticket.id);
            if (current < (ticket.stock || 1)) this.ticketQuantities[ticket.id] = current + 1;
        },

        decTicketQty(ticket) {
            const current = this.ticketQty(ticket.id);
            if (current > 1) this.ticketQuantities[ticket.id] = current - 1;
        },

        addTicket(ticket) {
            const qty = this.ticketQty(ticket.id);
            const existing = this.cart.find(i => i.id === ticket.id);
            if (existing) {
                existing.qty = Math.min(existing.qty + qty, ticket.stock);
            } else {
                this.cart.push({ id: ticket.id, name: ticket.name, price: Number(ticket.price), qty, category: 'tiket_masuk' });
            }
            this.errorMessage = '';
        },

        addToCart(product) {
            const existing = this.cart.find(i => i.id === product.id);
            if (existing) {
                if (existing.qty < product.stock) existing.qty += 1;
            } else {
                this.cart.push({ id: product.id, name: product.name, price: Number(product.price), qty: 1, category: product.category });
            }
            this.errorMessage = '';
        },

        incCart(item) {
            const max = item.category === 'tiket_masuk'
                ? this.tickets.find(t => t.id === item.id)?.stock ?? 99
                : this.addons.find(a => a.id === item.id)?.stock ?? 99;
            if (item.qty < max) item.qty += 1;
        },

        decCart(item) {
            if (item.qty > 1) item.qty -= 1;
        },

        removeCart(item) {
            this.cart = this.cart.filter(i => i.id !== item.id);
        },

        cancelAutoRedirect() {
            if (this.redirectTimer) {
                clearTimeout(this.redirectTimer);
                this.redirectTimer = null;
            }
        },

        resetAll() {
            this.cancelAutoRedirect();
            this.tab = 'unit';
            this.checkIn = '';
            this.checkOut = '';
            this.unitType = 'all';
            this.availableUnits = [];
            this.selectedUnit = null;
            this.tickets = [];
            this.ticketQuantities = {};
            this.cart = [];
            this.visitorName = '';
            this.visitorWhatsapp = '';
            this.visitorEmail = '';
            this.errorMessage = '';
            this.success = null;
        },

        async submit() {
            if (!this.canSubmit) return;
            this.sending = true;
            this.errorMessage = '';
            this.lastTotal = this.grandTotal;

            const unitCheckOut = this.tab === 'unit' ? this.checkOut : this.checkIn;
            const payload = {
                visitor_name: this.visitorName,
                visitor_whatsapp: this.normalizeWhatsapp(this.visitorWhatsapp) ?? this.visitorWhatsapp,
                visitor_email: this.visitorEmail || null,
                unit_id: this.tab === 'unit' ? this.selectedUnit?.id ?? null : null,
                check_in_date: this.checkIn,
                check_out_date: unitCheckOut,
                addons: this.cart.map(item => ({ product_id: item.id, quantity: item.qty })),
                total_price: this.grandTotal,
            };

            try {
                const res = await fetch('/api/reservations', {
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
                    this.success = data;
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    this.redirectTimer = setTimeout(() => {
                        window.location.href = '/pembayaran/' + data.order_code;
                    }, 2500);
                } else {
                    this.errorMessage = data.errors?.visitor_whatsapp?.[0]
                        || data.message
                        || 'Gagal membuat pesanan. Coba lagi.';
                }
            } catch (e) {
                this.errorMessage = 'Koneksi gagal. Periksa jaringan Anda.';
            } finally {
                this.sending = false;
            }
        },
    };
}
</script>
@endsection
