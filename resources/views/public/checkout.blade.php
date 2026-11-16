@extends('layouts.app')

@section('title', 'Checkout')

@section('content')
<div x-data="checkoutPage()" x-init="init()" class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10">

    {{-- ============ SUCCESS STATE ============ --}}
    <div x-show="success" x-cloak class="bg-white rounded-2xl shadow-sm border border-green-100 p-6 sm:p-10 text-center max-w-2xl mx-auto">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center text-3xl mx-auto mb-4">✅</div>
        <h1 class="text-xl sm:text-2xl font-extrabold text-green-900">Pesanan Berhasil Dibuat!</h1>
        <p class="text-gray-500 mt-2 text-sm sm:text-base">Selesaikan pembayaran dan unggah bukti transfer.</p>
        <p class="text-sm text-gray-500 mt-2">Anda akan diarahkan otomatis ke halaman pembayaran...</p>

        <div class="mt-6 bg-green-50 border border-green-200 rounded-xl p-4 text-left inline-block min-w-[16rem] sm:min-w-[20rem]">
            <p class="text-xs uppercase tracking-wider text-green-700 font-semibold">Kode Pesanan</p>
            <p class="text-xl sm:text-2xl font-mono font-extrabold text-green-900 mt-1" x-text="success?.order_code"></p>
            <p class="text-sm text-gray-600 mt-3">Total yang harus dibayar</p>
            <p class="text-xl sm:text-2xl font-bold text-green-800" x-text="formatIDR(lastTotal)"></p>
        </div>

        <div class="mt-6 grid sm:grid-cols-2 gap-3 max-w-lg mx-auto text-left">
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                <p class="text-xs uppercase tracking-wider text-gray-500 font-semibold">Metode Pembayaran</p>
                <p class="text-sm font-bold text-gray-800 mt-1">Transfer ke rekening berikut</p>
                <p class="text-xs text-gray-500 mt-2">Atas Nama</p>
                <p class="text-sm font-bold text-gray-900">{{ config('services.payment.account_name') }}</p>
                <p class="text-xs text-gray-500 mt-2">Nomor Rekening</p>
                <p class="text-lg sm:text-xl font-mono font-extrabold text-gray-900 tracking-wider">{{ config('services.payment.account_number') }}</p>
                <p class="text-xs text-gray-400 mt-1">Bank {{ config('services.payment.bank') }}</p>
            </div>
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                <p class="text-xs uppercase tracking-wider text-gray-500 font-semibold">Konfirmasi &amp; Bantuan</p>
                <p class="text-sm text-gray-700 mt-2">Setelah transfer, unggah bukti dan/atau konfirmasi via WhatsApp:</p>
                <a href="https://wa.me/{{ preg_replace('/\D/', '', config('services.payment.whatsapp')) }}" target="_blank" rel="noopener"
                   class="mt-3 inline-flex items-center gap-2 bg-green-600 text-white font-bold px-4 py-2.5 rounded-xl hover:bg-green-700 transition text-sm w-full justify-center sm:w-auto">
                    💬 WhatsApp {{ config('services.payment.whatsapp') }}
                </a>
                <p class="text-[11px] text-gray-400 mt-3">Simpan <span class="font-mono font-bold">Kode Pesanan</span> untuk cek status.</p>
            </div>
        </div>

        <div class="mt-8 flex flex-col sm:flex-row justify-center gap-3">
            <a :href="'/pembayaran/' + success?.order_code" @click="cancelAutoRedirect()" class="bg-green-700 text-white font-bold px-6 py-3 rounded-xl hover:bg-green-800 transition">Ke Halaman Pembayaran →</a>
            <button type="button" @click="resetAll()" class="bg-white border border-gray-200 text-gray-700 font-semibold px-6 py-3 rounded-xl hover:bg-gray-50 transition">Buat Pesanan Lain</button>
        </div>
    </div>

    {{-- ============ MAIN FLOW ============ --}}
    <div x-show="!success" x-cloak class="space-y-5 sm:space-y-6">

        {{-- Header --}}
        <header class="space-y-1.5">
            <span class="inline-flex items-center gap-2 text-[10px] sm:text-xs font-bold uppercase tracking-[0.16em] text-green-700 bg-green-50 ring-1 ring-green-100 rounded-full px-3 py-1">
                <span class="w-2 h-2 rounded-full bg-green-500"></span>
                Pemesanan Real-time
            </span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-green-950 leading-tight">Checkout</h1>
            <p class="text-sm text-gray-600 leading-snug max-w-2xl">Pilih unit/tiket, isi data pemesan, lalu konfirmasi pesanan.</p>
            <div class="h-1 w-16 sm:w-24 rounded-full bg-gradient-to-r from-lime-400 via-emerald-500 to-green-700"></div>
        </header>

        {{-- Tabs --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-1.5 sm:p-2 grid grid-cols-2 gap-2">
            <button type="button" @click="switchTab('unit')"
                    :class="tab === 'unit' ? 'bg-green-700 text-white shadow-sm' : 'text-gray-600 hover:bg-green-50 hover:text-green-800'"
                    class="py-2.5 sm:py-3 rounded-xl font-bold transition flex items-center justify-center gap-2 text-sm sm:text-base">
                <span>🏕️</span> <span>Unit / Sewa</span>
            </button>
            <button type="button" @click="switchTab('tiket')"
                    :class="tab === 'tiket' ? 'bg-green-700 text-white shadow-sm' : 'text-gray-600 hover:bg-green-50 hover:text-green-800'"
                    class="py-2.5 sm:py-3 rounded-xl font-bold transition flex items-center justify-center gap-2 text-sm sm:text-base">
                <span>🎫</span> <span>Tiket Masuk</span>
            </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start">
            {{-- ============ LEFT COLUMN ============ --}}
            <div class="lg:col-span-7 flex flex-col gap-5">

                {{-- ============ TAB: UNIT / SEWA ============ --}}
                <div x-show="tab === 'unit'" x-cloak class="space-y-5">
                    <section class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-5 space-y-4">
                        <div class="flex items-center justify-between gap-3 pb-2.5 border-b border-gray-100">
                            <h2 class="font-bold text-gray-900 text-sm sm:text-base">Pilih Tanggal &amp; Unit</h2>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs font-semibold text-gray-500">Pilih cepat:</span>
                            <button type="button" @click="setStayToday()" :class="(checkIn === todayStr) ? 'bg-green-700 text-white border-green-700' : 'bg-white text-gray-700 hover:border-green-400 hover:text-green-700'" class="px-3 py-1 rounded-full text-xs font-semibold border border-gray-200 transition">Hari ini</button>
                            <button type="button" @click="setStayTomorrow()" :class="(checkIn === addDaysStr(todayStr, 1)) ? 'bg-green-700 text-white border-green-700' : 'bg-white text-gray-700 hover:border-green-400 hover:text-green-700'" class="px-3 py-1 rounded-full text-xs font-semibold border border-gray-200 transition">Besok</button>
                            <button type="button" @click="setStayWeekend()" class="px-3 py-1 rounded-full text-xs font-semibold border border-gray-200 bg-white text-gray-700 hover:border-green-400 hover:text-green-700 transition">Akhir pekan</button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Check-in *</label>
                                <input type="date" x-model="checkIn" :min="todayStr" @change="onCheckInChange()"
                                       class="w-full border border-gray-200 rounded-xl py-2.5 px-3.5 text-sm focus:ring-2 focus:ring-green-600 focus:border-green-600 focus:outline-none">
                            </div>
                            <div x-show="!isPackagedUnit() || !selectedPackage" x-cloak>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Check-out *</label>
                                <input type="date" x-model="checkOut" :min="minCheckout" @change="ensureDatesValid(); fetchAvailability()"
                                       :class="dateInvalid ? 'border-red-300 focus:ring-red-400 focus:border-red-400' : 'border-gray-200 focus:ring-green-600 focus:border-green-600'"
                                       class="w-full border rounded-xl py-2.5 px-3.5 text-sm focus:ring-2 focus:outline-none">
                            </div>
                            <div x-show="isPackagedUnit() && selectedPackage" x-cloak class="bg-green-50/70 border border-green-100 rounded-xl p-3">
                                <p class="text-[10px] uppercase tracking-wider font-semibold text-green-800">Otomatis</p>
                                <p class="text-sm font-semibold text-green-900 mt-0.5 leading-snug">Mulai saat check-in/scan</p>
                                <p class="text-xs text-green-700 mt-0.5">Durasi <span class="font-semibold" x-text="selectedPackage?.duration_hours"></span> jam (sesuai paket).</p>
                            </div>

                            <div x-show="!isPackagedUnit() || !selectedPackage" x-cloak>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Jam Mulai <span class="text-gray-400 font-normal">(estimasi)</span></label>
                                <input type="time" x-model="checkInTime" @change="ensureDatesValid(); fetchAvailability()"
                                       class="w-full border border-gray-200 rounded-xl py-2.5 px-3.5 text-sm focus:ring-2 focus:ring-green-600 focus:border-green-600 focus:outline-none">
                                <p class="text-[11px] text-gray-400 mt-1">Aktual saat scan.</p>
                            </div>
                            <div x-show="!isPackagedUnit() || !selectedPackage" x-cloak>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Jam Selesai <span class="text-gray-400 font-normal">(estimasi)</span></label>
                                <input type="time" x-model="checkOutTime" @change="ensureDatesValid(); fetchAvailability()"
                                       class="w-full border border-gray-200 rounded-xl py-2.5 px-3.5 text-sm focus:ring-2 focus:ring-green-600 focus:border-green-600 focus:outline-none">
                            </div>
                        </div>

                        <div class="space-y-1">
                            <p x-show="dateInvalid" x-cloak class="text-xs text-red-600 font-medium">Jam/tanggal selesai harus setelah mulai.</p>
                            <p x-show="nights > 0 && !dateInvalid && !isPackagedUnit()" x-cloak class="text-xs text-gray-600">Durasi: <span class="font-bold text-green-800" x-text="nights"></span> malam (ketersediaan sesuai waktu aktual/paket).</p>
                            <p x-show="isPackagedUnit() && selectedPackage" x-cloak class="text-xs text-green-800">Paket: <span class="font-bold" x-text="selectedPackage?.name"></span> · <span class="font-bold" x-text="selectedPackage?.duration_hours"></span> jam, mulai saat scan.</p>
                        </div>

                        <div class="pt-4 border-t border-gray-100">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-xs font-semibold text-gray-500 mr-1">Tipe unit:</span>
                                <template x-for="type in unitTypeFilters" :key="type.value">
                                    <button type="button" @click="setUnitType(type.value)"
                                            :class="unitType === type.value ? 'bg-green-700 text-white border-green-700' : 'bg-white text-gray-600 border-gray-200 hover:border-green-400 hover:text-green-700'"
                                            class="px-3.5 py-1.5 text-xs font-semibold rounded-full border transition"
                                            x-text="type.label"></button>
                                </template>
                            </div>

                            <div class="mt-4">
                                <div x-show="loadingUnits" x-cloak class="flex items-center gap-2 text-gray-400 py-10 justify-center">
                                    <svg class="animate-spin h-5 w-5 text-green-700" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                    <span class="text-sm">Mengecek ketersediaan...</span>
                                </div>
                                <div x-show="unitsError" x-cloak class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm" x-text="unitsError"></div>

                                <div x-show="!loadingUnits && !unitsError" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <template x-for="unit in filteredUnits" :key="unit.id">
                                        <button type="button" @click="selectUnit(unit)"
                                                :disabled="(unit.available_slots ?? 0) <= 0"
                                                :class="[
                                                    (unit.available_slots ?? 0) <= 0 ? 'opacity-60 cursor-not-allowed' : '',
                                                    selectedUnit?.id === unit.id ? 'border-green-600 ring-2 ring-green-200 bg-green-50/70' : 'border-gray-200 hover:border-green-400'
                                                ]"
                                                class="relative border-2 rounded-2xl p-4 text-left transition bg-white">
                                            <div class="flex items-start justify-between gap-2">
                                                <span class="text-2xl" x-text="typeEmoji(unit.type)"></span>
                                                <span class="text-[10px] font-bold uppercase tracking-wide rounded-full px-2 py-0.5 shrink-0"
                                                      :class="unit.available_slots > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                                      x-text="(unit.type === 'glamping' || unit.type === 'aula') ? ((unit.available_slots > 0) ? (unit.available_slots + ' slot tersedia') : 'Penuh/terpakai') : (unit.available_slots > 0 ? (unit.available_slots + ' slot') : 'Penuh')"></span>
                                            </div>
                                            <h3 class="font-bold text-gray-800 mt-2 leading-snug pr-1" x-text="unit.name"></h3>
                                            <p class="text-xs text-gray-500 mt-1">Kapasitas <span x-text="unit.capacity"></span> orang</p>
                                            <p class="text-green-700 font-extrabold mt-3" x-show="unit.type !== 'glamping' && unit.type !== 'aula'" x-text="formatIDR(unit.price)"></p>
                                            <p class="text-xs text-green-800 font-semibold mt-3" x-show="unit.type === 'glamping' || unit.type === 'aula'" x-cloak>Harga mengikuti paket (jam mulai otomatis)</p>
                                        </button>
                                    </template>
                                </div>

                                <template x-if="selectedUnit && (selectedUnit.type === 'glamping' || selectedUnit.type === 'aula')">
                                    <div class="mt-4 border border-green-100 bg-green-50/60 rounded-xl p-4 space-y-3">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <h3 class="text-sm font-bold text-green-900" x-text="selectedUnit.type === 'aula' ? 'Pilih Paket Sewa Aula' : 'Pilih Paket Sewa Glamping'"></h3>
                                            <span class="text-xs font-semibold" :class="(selectedUnit.type === 'glamping' ? glampingRemainingSlots : aulaRemainingSlots) > 0 ? 'text-green-800' : 'text-red-700'"
                                                  x-text="(selectedUnit.type === 'glamping' ? glampingRemainingSlots : aulaRemainingSlots) !== null ? ('Sisa slot: ' + (selectedUnit.type === 'glamping' ? glampingRemainingSlots : aulaRemainingSlots)) : ''"></span>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Paket</label>
                                            <select x-model="selectedPackageId" @change="onPackageChange()" class="w-full border border-green-200 rounded-xl py-2.5 px-3.5 text-sm bg-white focus:ring-2 focus:ring-green-600 focus:border-green-600 focus:outline-none">
                                                <template x-for="pkg in (selectedUnit.type === 'aula' ? aulaPackages : glampingPackages)" :key="pkg.id">
                                                    <option :value="pkg.id" x-text="pkg.name + ' · ' + pkg.duration_hours + ' jam · ' + formatIDR(pkg.price)"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <p class="text-[11px] text-green-800 leading-relaxed">Jam mulai/selesai otomatis mengikuti waktu check-in (scan tiket) dan durasi paket.</p>
                                    </div>
                                </template>

                                <p x-show="!loadingUnits && !unitsError && checkIn && checkOut && !dateInvalid && filteredUnits.length === 0" x-cloak class="text-center text-gray-500 py-10 text-sm bg-gray-50 rounded-xl border border-dashed border-gray-200 mt-3">Tidak ada unit tersedia untuk tipe &amp; tanggal ini.</p>
                                <p x-show="!loadingUnits && !unitsError && (!checkIn || !checkOut)" x-cloak class="text-center text-gray-500 py-10 text-sm bg-gray-50 rounded-xl border border-dashed border-gray-200 mt-3">Pilih tanggal untuk melihat ketersediaan unit.</p>
                            </div>
                        </div>
                    </section>
                </div>

                {{-- ============ TAB: TIKET MASUK ============ --}}
                <div x-show="tab === 'tiket'" x-cloak class="space-y-5">
                    <section class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-6 space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-gray-100">
                            <h2 class="font-bold text-gray-900 text-base sm:text-lg">🎫 Tiket Masuk</h2>
                            <div class="flex items-center gap-2 w-full sm:w-auto">
                                <label for="visit_date" class="text-xs font-semibold text-gray-600 shrink-0">Tanggal:</label>
                                <input id="visit_date" type="date" x-model="checkIn" :min="todayStr" @change="onVisitDateChange()"
                                       class="w-full sm:w-auto border border-gray-200 rounded-xl py-2.5 px-3.5 text-sm focus:ring-2 focus:ring-green-600 focus:border-green-600 focus:outline-none">
                            </div>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 mb-2">Pilih cepat:</p>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" @click="setVisitOffset(0)" :class="(checkIn === todayStr) ? 'bg-green-700 text-white border-green-700' : 'bg-white text-gray-700 hover:border-green-400 hover:text-green-700'" class="px-3.5 py-1.5 rounded-full text-xs font-semibold border border-gray-200 transition">Hari ini</button>
                                <button type="button" @click="setVisitOffset(1)" :class="(checkIn === addDaysStr(todayStr, 1)) ? 'bg-green-700 text-white border-green-700' : 'bg-white text-gray-700 hover:border-green-400 hover:text-green-700'" class="px-3.5 py-1.5 rounded-full text-xs font-semibold border border-gray-200 transition">Besok</button>
                                <button type="button" @click="setVisitWeekend()" class="px-3.5 py-1.5 rounded-full text-xs font-semibold border border-gray-200 bg-white text-gray-700 hover:border-green-400 hover:text-green-700 transition">Akhir pekan ini</button>
                            </div>
                        </div>
                        <p x-show="checkIn" x-cloak class="text-xs text-green-800 bg-green-50 border border-green-100 rounded-lg px-3 py-2">Untuk kunjungan: <span class="font-bold" x-text="formatDateLong(checkIn)"></span> (berlaku 1 hari penuh).</p>

                        <div x-show="loadingTickets" x-cloak class="flex items-center justify-center gap-2 text-gray-400 py-10">
                            <svg class="animate-spin h-5 w-5 text-green-700" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            <span class="text-sm">Memuat tiket...</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" x-show="!loadingTickets">
                            <template x-for="ticket in tickets" :key="ticket.id">
                                <div class="border border-gray-200 rounded-2xl p-4 flex flex-col bg-white hover:border-green-300 hover:shadow-sm transition">
                                    <div class="relative h-28 -mx-4 -mt-4 mb-3 overflow-hidden rounded-t-2xl bg-amber-50 cursor-pointer" @click="openDetail(ticket, 'tiket')" title="Lihat detail tiket">
                                        <template x-if="ticket.images?.length">
                                            <img :src="ticket.images?.[0]?.url" :alt="ticket.name" class="w-full h-full object-cover">
                                        </template>
                                        <div x-show="!ticket.images?.length" x-cloak class="absolute inset-0 flex items-center justify-center text-3xl">🎫</div>
                                        <span x-show="ticket.images?.length > 1" x-cloak class="absolute bottom-1.5 right-1.5 bg-black/55 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full">📷 <span x-text="ticket.images.length"></span></span>
                                    </div>
                                    <h3 class="font-bold text-gray-800 leading-snug cursor-pointer hover:text-green-700" @click="openDetail(ticket, 'tiket')" x-text="ticket.name"></h3>
                                    <button type="button" @click="openDetail(ticket, 'tiket')" class="mt-1 text-xs font-semibold text-green-700 hover:underline self-start">🔍 Detail &amp; Foto</button>
                                    <p class="text-green-700 font-extrabold mt-2 text-base" x-text="formatIDR(ticket.price)"></p>
                                    <p class="text-xs mt-1" :class="ticket.stock > 0 ? 'text-gray-500' : 'text-red-500 font-semibold'" x-text="ticket.stock > 0 ? ('Stok tersedia: ' + ticket.stock) : 'Stok habis'"></p>
                                    <div class="flex items-center justify-between mt-auto pt-4 gap-2">
                                        <div class="flex items-center border border-gray-200 rounded-lg bg-gray-50">
                                            <button type="button" class="w-9 h-9 text-gray-600 hover:text-green-700" @click="decTicketQty(ticket)" aria-label="Kurangi">−</button>
                                            <span class="w-7 text-center font-semibold text-sm" x-text="ticketQty(ticket.id)"></span>
                                            <button type="button" class="w-9 h-9 text-gray-600 hover:text-green-700" @click="incTicketQty(ticket)" aria-label="Tambah">+</button>
                                        </div>
                                        <button type="button" @click="addTicket(ticket)" :disabled="ticket.stock <= 0"
                                                :class="ticket.stock <= 0 ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-green-700 text-white hover:bg-green-800 shadow-sm'"
                                                class="text-xs font-bold px-4 py-2.5 rounded-lg transition">
                                            Tambah
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <p x-show="!loadingTickets && tickets.length === 0" x-cloak class="text-center text-gray-500 py-10 text-sm bg-gray-50 rounded-xl border border-dashed border-gray-200">Belum ada tiket masuk tersedia.</p>
                    </section>
                </div>

                {{-- UMKM / Add-on --}}
                <section class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-6">
                    <div class="flex items-center justify-between mb-4 gap-2 pb-3 border-b border-gray-100">
                        <h2 class="font-bold text-gray-900 text-base sm:text-lg">Tambahan UMKM &amp; Add-on</h2>
                        <span class="text-xs font-medium text-gray-500 bg-gray-100 rounded-full px-2.5 py-1">Opsional</span>
                    </div>
                    <div x-show="loadingAddons" x-cloak class="flex items-center gap-2 text-gray-400 text-sm py-6">
                        <svg class="animate-spin h-4 w-4 text-green-700" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                        <span>Memuat produk UMKM...</span>
                    </div>
                    <div class="flex gap-3 overflow-x-auto pb-2 -mx-1 px-1 snap-x snap-mandatory">
                        <template x-for="addon in addons" :key="addon.id">
                            <div class="snap-start shrink-0 w-40 sm:w-44 border border-gray-200 rounded-2xl p-3 flex flex-col bg-white hover:border-green-300 hover:shadow-sm transition">
                                <div class="relative h-20 -mx-3 -mt-3 mb-2 overflow-hidden rounded-t-2xl bg-amber-50 cursor-pointer" @click="openDetail(addon, 'addon')" title="Lihat detail produk">
                                    <template x-if="addon.images?.length">
                                        <img :src="addon.images?.[0]?.url" :alt="addon.name" class="w-full h-full object-cover">
                                    </template>
                                    <div x-show="!addon.images?.length" x-cloak class="absolute inset-0 flex items-center justify-center text-xl" x-text="categoryEmoji(addon.category)"></div>
                                    <span x-show="addon.images?.length > 1" x-cloak class="absolute bottom-1 right-1 bg-black/55 text-white text-[9px] font-bold px-1.5 py-0.5 rounded-full">📷 <span x-text="addon.images.length"></span></span>
                                </div>
                                <h3 class="text-sm font-semibold text-gray-800 leading-snug line-clamp-2 min-h-[2.5rem] cursor-pointer hover:text-green-700" @click="openDetail(addon, 'addon')" x-text="addon.name"></h3>
                                <button type="button" @click="openDetail(addon, 'addon')" class="mt-0.5 text-[11px] font-semibold text-green-700 hover:underline self-start text-left">🔍 Detail &amp; Foto</button>
                                <p class="text-green-700 font-bold text-sm mt-1.5" x-text="formatIDR(addon.price)"></p>
                                <button type="button" @click="addToCart(addon)" :disabled="addon.stock <= 0"
                                        :class="addon.stock <= 0 ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-green-50 text-green-800 hover:bg-green-100'"
                                        class="mt-2 w-full text-xs font-bold py-2.5 rounded-lg transition">
                                    <span x-text="addon.stock <= 0 ? 'Habis' : '+ Tambah'"></span>
                                </button>
                            </div>
                        </template>
                        <p x-show="!loadingAddons && addons.length === 0" x-cloak class="text-sm text-gray-400 py-6">Belum ada produk UMKM.</p>
                    </div>
                </section>
            </div>

            {{-- ============ RIGHT COLUMN: Ringkasan & Pembayaran ============ --}}
            <aside class="lg:col-span-5 lg:sticky lg:top-6 space-y-4">
                <section class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-6">
                    <div class="flex items-center justify-between gap-3 mb-4 pb-3 border-b border-gray-100">
                        <h2 class="font-extrabold text-lg text-green-900">Ringkasan Pesanan</h2>
                        <span class="text-xs font-semibold bg-green-100 text-green-800 rounded-full px-3 py-1" x-text="itemCount + ' item'"></span>
                    </div>

                    <div x-show="itemCount === 0" x-cloak class="text-center text-gray-500 py-8 px-2">
                        <div class="text-4xl mb-3">🧺</div>
                        <p class="font-semibold text-gray-700">Belum ada item dipilih</p>
                        <p class="text-sm mt-1.5 leading-relaxed text-gray-500">Pilih unit, tiket, atau add-on UMKM untuk melanjutkan pemesanan.</p>
                    </div>

                    <div x-show="itemCount > 0" x-cloak class="space-y-4">
                        <template x-if="selectedUnit">
                            <div class="flex items-start gap-3 bg-green-50 border border-green-100 rounded-xl p-3.5">
                                <span class="text-2xl shrink-0 leading-none mt-0.5" x-text="typeEmoji(selectedUnit.type)"></span>
                                <div class="flex-1 min-w-0">
                                    <p class="font-bold text-gray-800 text-sm leading-snug" x-text="selectedUnit.name"></p>
                                    <p class="text-xs text-gray-600 mt-0.5 leading-relaxed" x-show="!(isPackagedUnit() && selectedPackage)"><span x-text="nights"></span> malam × <span x-text="formatIDR(selectedUnit.price)"></span></p>
                                    <p class="text-xs text-gray-600 mt-0.5 leading-relaxed" x-show="isPackagedUnit() && selectedPackage" x-cloak x-text="selectedPackage ? (selectedPackage.name + ' · ' + selectedPackage.duration_hours + ' jam') : ''"></p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-sm font-bold text-green-800" x-text="formatIDR(unitSubtotal)"></p>
                                    <button type="button" @click="selectedUnit = null" class="text-xs text-red-500 hover:text-red-700 mt-1 font-medium">Hapus</button>
                                </div>
                            </div>
                        </template>

                        <ul class="space-y-3">
                            <template x-for="item in cart" :key="item.id">
                                <li class="flex items-start gap-3 pb-3 border-b border-gray-100 last:border-0 last:pb-0">
                                    <span class="text-xl shrink-0 leading-none mt-0.5" x-text="categoryEmoji(item.category)"></span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-800 leading-snug" x-text="item.name"></p>
                                        <p class="text-xs text-gray-500 mt-0.5" x-text="formatIDR(item.price) + ' / item'"></p>
                                        <div class="flex flex-wrap items-center gap-2 mt-2.5">
                                            <div class="inline-flex items-center border border-gray-200 rounded-lg text-xs bg-gray-50">
                                                <button type="button" class="w-8 h-8 text-gray-600 hover:text-green-700" @click="decCart(item)" aria-label="Kurangi">−</button>
                                                <span class="w-7 text-center font-semibold text-gray-800" x-text="item.qty"></span>
                                                <button type="button" class="w-8 h-8 text-gray-600 hover:text-green-700" @click="incCart(item)" aria-label="Tambah">+</button>
                                            </div>
                                            <button type="button" @click="removeCart(item)" class="text-xs text-red-500 hover:text-red-700 font-semibold px-1">Hapus</button>
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0 pt-0.5">
                                        <p class="text-sm font-bold text-gray-800" x-text="formatIDR(item.price * item.qty)"></p>
                                    </div>
                                </li>
                            </template>
                        </ul>

                        <div class="border-t border-gray-100 pt-4 space-y-2 text-sm">
                            <div class="flex justify-between gap-3 text-gray-600"><span>Subtotal Unit</span><span class="font-medium text-gray-800 tabular-nums" x-text="formatIDR(unitSubtotal)"></span></div>
                            <div class="flex justify-between gap-3 text-gray-600"><span>Subtotal Tiket &amp; Add-on</span><span class="font-medium text-gray-800 tabular-nums" x-text="formatIDR(cartSubtotal)"></span></div>
                            <div class="flex justify-between items-baseline gap-3 pt-3 mt-1 border-t border-dashed border-gray-200">
                                <span class="font-bold text-gray-900">Total Pembayaran</span>
                                <span class="text-xl sm:text-2xl font-extrabold text-green-800 tabular-nums" x-text="formatIDR(grandTotal)"></span>
                            </div>
                        </div>
                    </div>

                    <button type="button" @click="openPayModal()" :disabled="!canOpenPayModal"
                            :class="!canOpenPayModal ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-green-700 text-white hover:bg-green-800 active:bg-green-900 shadow-lg shadow-green-700/20'"
                            class="w-full mt-5 font-bold py-3.5 rounded-xl transition flex items-center justify-center gap-2 text-sm sm:text-base">
                        <span x-text="itemCount > 0 ? 'Lanjut ke Pembayaran — ' + formatIDR(grandTotal) : 'Lanjut ke Pembayaran'"></span>
                    </button>
                    <p x-show="!canOpenPayModal" x-cloak class="text-[11px] text-amber-700 font-medium text-center mt-2.5 leading-relaxed px-1">
                        <span x-text="tab === 'unit' ? ((selectedUnit && (selectedUnit.type === 'glamping' || selectedUnit.type === 'aula')) ? 'Pilih tanggal, paket, dan unit terlebih dahulu.' : 'Pilih tanggal dan unit terlebih dahulu.') : 'Pilih tanggal kunjungan dan item terlebih dahulu.'"></span>
                    </p>
                    <p class="text-[11px] text-gray-500 text-center mt-2 leading-relaxed">Isi data pemesan & pilih metode bayar pada langkah berikutnya.</p>
                </section>
            </aside>
        </div>
    </div>

    {{-- ===== Detail produk publik: galeri foto (mendukung 3+ gambar) ===== --}}
    <div x-show="detailOpen" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4" @keydown.escape.window="closeDetail()">
        <div class="absolute inset-0 bg-black/60" @click="closeDetail()"></div>
        <div x-show="detailOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
             class="relative bg-white w-full sm:max-w-lg sm:rounded-2xl rounded-t-2xl overflow-hidden max-h-[92vh] flex flex-col shadow-2xl">
            <div class="flex items-start justify-between gap-3 px-5 py-4 border-b border-gray-100 shrink-0">
                <div class="min-w-0">
                    <p class="text-[10px] uppercase tracking-wider text-gray-400 font-bold" x-text="categoryLabel(detailProduct?.category)"></p>
                    <h3 class="font-extrabold text-gray-800 leading-snug" x-text="detailProduct?.name ?? ''"></h3>
                </div>
                <button type="button" @click="closeDetail()" aria-label="Tutup detail"
                        class="text-gray-400 hover:text-gray-700 text-2xl leading-none shrink-0">×</button>
            </div>

            <div class="overflow-y-auto">
                <div class="relative bg-amber-50">
                    <div class="relative aspect-[4/3] overflow-hidden flex items-center justify-center">
                        <template x-if="detailImages.length">
                            <img :src="detailImages[detailImageIndex]?.url" :alt="detailProduct?.name"
                                 class="w-full h-full object-contain">
                        </template>
                        <div x-show="detailImages.length === 0" x-cloak class="text-6xl" x-text="categoryEmoji(detailProduct?.category)"></div>
                    </div>

                    <button type="button" x-show="detailImages.length > 1" x-cloak @click.stop="prevDetailImage()" aria-label="Foto sebelumnya"
                            class="absolute left-2 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-white/90 shadow-md flex items-center justify-center text-gray-700 text-xl leading-none hover:bg-white transition">‹</button>
                    <button type="button" x-show="detailImages.length > 1" x-cloak @click.stop="nextDetailImage()" aria-label="Foto berikutnya"
                            class="absolute right-2 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-white/90 shadow-md flex items-center justify-center text-gray-700 text-xl leading-none hover:bg-white transition">›</button>

                    <span x-show="detailImages.length > 1" x-cloak
                          class="absolute top-2 right-2 bg-black/55 text-white text-[11px] font-bold px-2 py-0.5 rounded-full"
                          x-text="(detailImageIndex + 1) + ' / ' + detailImages.length"></span>
                </div>

                <div x-show="detailImages.length > 1" x-cloak class="flex gap-2 overflow-x-auto px-5 py-3 border-b border-gray-100">
                    <template x-for="(img, i) in detailImages" :key="img.id">
                        <button type="button" @click="detailImageIndex = i"
                                :class="i === detailImageIndex ? 'ring-2 ring-green-600 border-transparent opacity-100' : 'border-gray-200 opacity-60 hover:opacity-100'"
                                class="shrink-0 w-16 h-16 rounded-lg overflow-hidden border bg-gray-50 transition">
                            <img :src="img.url" :alt="img.alt || detailProduct?.name" class="w-full h-full object-cover">
                        </button>
                    </template>
                </div>

                <div class="px-5 py-4 space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xl font-extrabold text-green-800" x-text="formatIDR(detailProduct?.price)"></p>
                        <span class="text-xs font-bold rounded-full px-2.5 py-1 shrink-0"
                              :class="(detailProduct?.stock ?? 0) > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                              x-text="(detailProduct?.stock ?? 0) > 0 ? ('Stok: ' + detailProduct.stock) : 'Habis'"></span>
                    </div>

                    <div x-show="detailKind === 'tiket'" class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Jumlah tiket</span>
                        <div class="flex items-center border border-gray-200 rounded-lg">
                            <button type="button" class="px-3 py-1.5 text-gray-500 hover:text-green-700" @click="detailProduct && decTicketQty(detailProduct)">−</button>
                            <span class="w-8 text-center font-semibold" x-text="detailProduct ? ticketQty(detailProduct.id) : 0"></span>
                            <button type="button" class="px-3 py-1.5 text-gray-500 hover:text-green-700" @click="detailProduct && incTicketQty(detailProduct)">+</button>
                        </div>
                    </div>

                    <p x-show="detailImages.length > 1" class="text-xs text-gray-500">📷 <span x-text="detailImages.length"></span> foto tersedia — klik thumbnail atau tombol ‹ › untuk melihat sisanya.</p>
                    <p x-show="detailImages.length === 1" class="text-xs text-gray-500">📷 Hanya 1 foto tersedia untuk produk ini.</p>
                    <p x-show="detailImages.length === 0" x-cloak class="text-xs text-gray-500">Belum ada foto untuk produk ini.</p>
                </div>
            </div>

            <div class="px-5 py-4 border-t border-gray-100 flex gap-2 shrink-0">
                <button type="button" @click="closeDetail()"
                        class="flex-1 text-sm font-semibold text-gray-600 border border-gray-200 rounded-xl py-3 hover:bg-gray-50 transition">Tutup</button>
                <button type="button" @click="detailAdd()" :disabled="(detailProduct?.stock ?? 0) <= 0"
                        :class="(detailProduct?.stock ?? 0) <= 0 ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-green-700 text-white hover:bg-green-800'"
                        class="flex-[2] text-sm font-bold rounded-xl py-3 transition"
                        x-text="(detailProduct?.stock ?? 0) <= 0 ? 'Stok Habis' : 'Tambah ke Keranjang'"></button>
            </div>
        </div>
    </div>

    {{-- Modal Konfirmasi & Data Pemesan --}}
    <div x-show="payModalOpen" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4" @keydown.escape.window="closePayModal()">
        <div class="absolute inset-0 bg-black/60" @click="closePayModal()"></div>
        <div x-show="payModalOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
             class="relative bg-white w-full sm:max-w-lg sm:rounded-2xl rounded-t-2xl overflow-hidden max-h-[92vh] flex flex-col shadow-2xl">
            <div class="flex items-start justify-between gap-3 px-5 py-4 border-b border-gray-100 shrink-0 bg-gray-50/60">
                <div class="min-w-0">
                    <p class="text-[10px] uppercase tracking-wider text-green-700 font-bold">Konfirmasi Pembayaran</p>
                    <h3 class="font-extrabold text-gray-900 leading-snug">Data Pemesan &amp; Ringkasan</h3>
                </div>
                <button type="button" @click="closePayModal()" aria-label="Tutup"
                        class="text-gray-400 hover:text-gray-700 text-2xl leading-none shrink-0" :disabled="sending">×</button>
            </div>

            <div class="overflow-y-auto px-5 py-4 space-y-4">
                <div class="bg-green-50 border border-green-100 rounded-xl p-4 space-y-2">
                    <p class="text-xs font-bold uppercase tracking-wider text-green-800">Ringkasan Pesanan</p>
                    <template x-if="selectedUnit">
                        <div class="flex items-start justify-between gap-3 text-sm">
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-800 leading-snug"><span x-text="typeEmoji(selectedUnit.type)"></span> <span x-text="selectedUnit.name"></span></p>
                                <p class="text-xs text-gray-600 mt-0.5" x-show="!(isPackagedUnit() && selectedPackage)" x-text="nights + ' malam × ' + formatIDR(selectedUnit.price)"></p>
                                <p class="text-xs text-gray-600 mt-0.5" x-show="isPackagedUnit() && selectedPackage" x-cloak x-text="selectedPackage ? (selectedPackage.name + ' · ' + selectedPackage.duration_hours + ' jam') : ''"></p>
                            </div>
                            <span class="font-bold text-gray-800 shrink-0" x-text="formatIDR(unitSubtotal)"></span>
                        </div>
                    </template>
                    <template x-for="item in cart" :key="'m'+item.id">
                        <div class="flex items-start justify-between gap-3 text-sm">
                            <div class="min-w-0">
                                <p class="font-medium text-gray-800 leading-snug" x-text="item.name"></p>
                                <p class="text-xs text-gray-500" x-text="item.qty + ' × ' + formatIDR(item.price)"></p>
                            </div>
                            <span class="font-semibold text-gray-800 shrink-0" x-text="formatIDR(item.price * item.qty)"></span>
                        </div>
                    </template>
                    <div class="border-t border-green-200/70 pt-2 mt-1 space-y-1 text-sm">
                        <div class="flex justify-between text-gray-600"><span>Subtotal Unit</span><span class="tabular-nums" x-text="formatIDR(unitSubtotal)"></span></div>
                        <div class="flex justify-between text-gray-600"><span>Subtotal Tiket &amp; Add-on</span><span class="tabular-nums" x-text="formatIDR(cartSubtotal)"></span></div>
                        <div class="flex justify-between items-baseline pt-1">
                            <span class="font-bold text-gray-900">Total Bayar</span>
                            <span class="text-xl font-extrabold text-green-800 tabular-nums" x-text="formatIDR(grandTotal)"></span>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <h4 class="font-bold text-gray-900 text-sm flex items-center gap-2"><span>👤</span><span>Data Pemesan</span></h4>
                        <span class="text-[10px] font-medium text-amber-700 bg-amber-50 border border-amber-100 rounded-full px-2 py-0.5">Wajib diisi</span>
                    </div>
                    <div class="grid grid-cols-1 gap-3">
                        <div>
                            <label for="m_visitor_name" class="block text-xs font-semibold text-gray-600 mb-1">Nama Lengkap *</label>
                            <input id="m_visitor_name" type="text" x-model="visitorName" placeholder="Budi Santoso"
                                   class="w-full border border-gray-200 rounded-xl py-2.5 px-3.5 text-sm focus:ring-2 focus:ring-green-600 focus:border-green-600 focus:outline-none transition">
                        </div>
                        <div>
                            <label for="m_visitor_whatsapp" class="block text-xs font-semibold text-gray-600 mb-1">No. WhatsApp *</label>
                            <input id="m_visitor_whatsapp" type="tel" inputmode="numeric" x-model="visitorWhatsapp" @input="sanitizeWhatsapp()"
                                   placeholder="081234567890"
                                   class="w-full border rounded-xl py-2.5 px-3.5 text-sm focus:ring-2 focus:outline-none transition"
                                   :class="visitorWhatsapp && !whatsappValid ? 'border-red-300 focus:ring-red-400 focus:border-red-400 bg-red-50/30' : 'border-gray-200 focus:ring-green-600 focus:border-green-600'">
                            <p class="text-[11px] text-gray-500 mt-1 leading-snug" x-show="!visitorWhatsapp || whatsappValid">E-tiket QR dikirim ke nomor ini.</p>
                            <p class="text-[11px] text-red-600 mt-1 font-medium leading-snug" x-show="visitorWhatsapp && !whatsappValid" x-cloak x-text="whatsappError()"></p>
                        </div>
                        <div>
                            <label for="m_visitor_email" class="block text-xs font-semibold text-gray-600 mb-1">Email <span class="text-gray-400 font-normal">(opsional)</span></label>
                            <input id="m_visitor_email" type="email" x-model="visitorEmail" placeholder="nama@email.com"
                                   class="w-full border border-gray-200 rounded-xl py-2.5 px-3.5 text-sm focus:ring-2 focus:ring-green-600 focus:border-green-600 focus:outline-none transition">
                        </div>
                    </div>
                </div>

                <div x-show="errorMessage" x-cloak class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm flex items-start gap-2 leading-relaxed">
                    <span class="shrink-0">⚠️</span><span x-text="errorMessage"></span>
                </div>
                <p class="text-[11px] text-gray-500 leading-relaxed">Setelah ini Anda memilih metode bayar (instan atau transfer manual &amp; unggah bukti). E-tiket QR keluar setelah pembayaran terverifikasi.</p>
            </div>

            <div class="px-5 py-4 border-t border-gray-100 flex flex-col sm:flex-row gap-2 shrink-0 bg-white">
                <button type="button" @click="closePayModal()" :disabled="sending"
                        class="sm:flex-1 text-sm font-semibold text-gray-600 border border-gray-200 rounded-xl py-3 hover:bg-gray-50 transition disabled:opacity-50">Batal</button>
                <button type="button" @click="submit()" :disabled="!canSubmit || sending"
                        :class="!canSubmit || sending ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-green-700 text-white hover:bg-green-800 active:bg-green-900 shadow-lg shadow-green-700/20'"
                        class="sm:flex-[2] font-bold py-3 rounded-xl transition flex items-center justify-center gap-2 text-sm sm:text-base">
                    <svg x-show="sending" x-cloak class="animate-spin h-5 w-5" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span x-text="sending ? 'Memproses...' : 'Bayar Sekarang — ' + formatIDR(grandTotal)"></span>
                </button>
            </div>
            <p x-show="!canSubmit && !sending" x-cloak class="px-5 pb-4 -mt-1 text-[11px] text-amber-700 font-medium text-center leading-relaxed">Lengkapi Nama &amp; nomor WhatsApp yang valid untuk melanjutkan.</p>
        </div>
    </div>

    {{-- Modal checkout lama dipertahankan (tidak dipakai oleh alur utama baru) agar state/fungsi tetap kompatibel --}}
    <div x-show="checkoutOpen" x-cloak class="hidden"></div>
</div>

<script>
function checkoutPage() {
    return {
        tab: 'unit',
        todayStr: '',
        checkIn: '',
        checkOut: '',
        checkInTime: '14:00',
        checkOutTime: '12:00',
        unitType: 'all',
        availableUnits: [],
        selectedUnit: null,
        selectedPackageId: '',
        glampingPackages: [],
        aulaPackages: [],
        glampingRemainingSlots: null,
        aulaRemainingSlots: null,
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
        detailOpen: false,
        detailProduct: null,
        detailKind: 'addon',
        detailImageIndex: 0,
        checkoutOpen: false,
        payModalOpen: false,

        unitTypeFilters: [
            { value: 'all', label: 'Semua' },
            { value: 'glamping', label: 'Glamping' },
            { value: 'aula', label: 'Aula' },
            { value: 'alat_wisata', label: 'Alat Wisata' },
        ],

        init() {
            this.refreshToday();
            this.loadAddons();
            this.preloadPackages();
        },

        async preloadPackages() {
            try {
                this.refreshToday();
                const d = this.todayStr;
                const res = await fetch(`/api/availability?check_in=${d}&check_out=${d}&check_in_time=08:00&check_out_time=09:00`);
                const data = await res.json();
                if (Array.isArray(data.glamping_packages)) this.glampingPackages = data.glamping_packages;
                if (Array.isArray(data.aula_packages)) this.aulaPackages = data.aula_packages;
            } catch (e) {}
        },

        refreshToday() {
            this.todayStr = new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Jakarta' }).format(new Date());
        },

        ensureDatesValid() {
            this.refreshToday();
            if (this.tab === 'unit') {
                if (this.checkIn && this.checkIn < this.todayStr) this.checkIn = this.todayStr;
                if (this.isPackagedUnit() && this.selectedPackageId) {
                    const end = this.computePackageEnd();
                    if (end) { this.checkOut = end.date; this.checkOutTime = end.time; }
                } else {
                    if (!this.checkOut || this.checkOut < this.checkIn) this.checkOut = this.checkIn || '';
                    if (this.checkIn && this.checkOut) {
                        const inDT = this.dateTimeValue(this.checkIn, this.checkInTime);
                        let outDT = this.dateTimeValue(this.checkOut, this.checkOutTime);
                        if (outDT <= inDT) {
                            if (this.checkOut === this.checkIn) {
                                this.checkOutTime = this.addHourStr(this.checkInTime, 1);
                            } else {
                                this.checkOut = this.checkIn;
                                this.checkOutTime = this.addHourStr(this.checkInTime, 1);
                            }
                        }
                    }
                }
            } else {
                if (this.checkIn && this.checkIn < this.todayStr) this.checkIn = this.todayStr;
                this.checkOut = this.checkIn || '';
                this.checkInTime = '00:00';
                this.checkOutTime = '23:59';
            }
        },

        dateTimeValue(d, t) {
            return new Date(`${d}T${(t || '00:00')}:00`).getTime();
        },

        addHourStr(t, h) {
            const [hh, mm] = (t || '00:00').split(':').map(Number);
            const dt = new Date(2000, 0, 1, hh + h, mm || 0);
            const H = String(Math.min(dt.getHours(), 23)).padStart(2, '0');
            const M = String(dt.getMinutes()).padStart(2, '0');
            return `${H}:${M}`;
        },

        dateFromStr(str) {
            const [y, m, d] = str.split('-').map(Number);
            return new Date(y, m - 1, d);
        },

        strFromDate(date) {
            const y = date.getFullYear();
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const d = String(date.getDate()).padStart(2, '0');
            return `${y}-${m}-${d}`;
        },

        addDaysStr(str, days) {
            const dt = this.dateFromStr(str);
            dt.setDate(dt.getDate() + days);
            return this.strFromDate(dt);
        },

        formatDateShort(str) {
            if (!str) return '';
            return this.dateFromStr(str).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
        },

        formatDateLong(str) {
            if (!str) return '';
            return this.dateFromStr(str).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        },

        get minCheckout() {
            return this.checkIn || this.todayStr;
        },

        onCheckInChange() {
            this.ensureDatesValid();
            this.fetchAvailability();
        },

        setStayDates(inStr, outStr) {
            this.checkIn = inStr;
            this.checkOut = outStr;
            this.ensureDatesValid();
            this.fetchAvailability();
        },

        setStayToday() {
            this.setStayDates(this.todayStr, this.addDaysStr(this.todayStr, 1));
        },

        setStayTomorrow() {
            const inStr = this.addDaysStr(this.todayStr, 1);
            this.setStayDates(inStr, this.addDaysStr(inStr, 1));
        },

        setStayWeekend() {
            const base = this.dateFromStr(this.todayStr);
            const day = base.getDay();
            let daysToSat = (6 - day + 7) % 7;
            if (daysToSat === 0 && day === 6) daysToSat = 7;
            const sat = this.addDaysStr(this.todayStr, daysToSat);
            this.setStayDates(sat, this.addDaysStr(sat, 1));
        },

        onVisitDateChange() {
            this.ensureDatesValid();
        },

        setVisitOffset(days) {
            this.refreshToday();
            this.checkIn = this.addDaysStr(this.todayStr, days);
            this.ensureDatesValid();
        },

        setVisitWeekend() {
            const base = this.dateFromStr(this.todayStr);
            const day = base.getDay();
            let daysToSat = (6 - day + 7) % 7;
            if (daysToSat === 0 && day === 6) daysToSat = 7;
            this.setVisitOffset(daysToSat);
        },

        async switchTab(name) {
            this.tab = name;
            this.errorMessage = '';
            if (name === 'tiket') {
                this.checkInTime = '00:00';
                this.checkOutTime = '23:59';
                if (!this.checkIn) {
                    this.setVisitOffset(0);
                } else {
                    this.checkOut = this.checkIn;
                    this.ensureDatesValid();
                }
                if (this.tickets.length === 0) {
                    await this.loadTickets();
                }
            } else {
                if (this.checkInTime === '00:00') this.checkInTime = '14:00';
                if (this.checkOutTime === '23:59') this.checkOutTime = '12:00';
                this.ensureDatesValid();
            }
        },

        setUnitType(value) {
            this.unitType = value;
            if (value === 'glamping' || value === 'aula') {
                if (this.selectedUnit && this.selectedUnit.type !== value) this.selectedUnit = null;
                const pkgs = value === 'aula' ? this.aulaPackages : this.glampingPackages;
                if (!this.selectedPackageId || !pkgs.find(p => p.id === this.selectedPackageId)) {
                    this.selectedPackageId = pkgs[0]?.id || '';
                }
            } else if (value === 'alat_wisata') {
                this.selectedPackageId = '';
            }
            this.ensureDatesValid();
            this.fetchAvailability();
        },

        get filteredUnits() {
            if (this.unitType === 'all') return this.availableUnits;
            return this.availableUnits.filter(u => u.type === this.unitType);
        },

        isPackagedUnit() {
            return this.selectedUnit?.type === 'glamping' || this.selectedUnit?.type === 'aula';
        },

        currentPackages() {
            if (this.selectedUnit?.type === 'aula') return this.aulaPackages;
            if (this.selectedUnit?.type === 'glamping') return this.glampingPackages;
            if (this.unitType === 'aula') return this.aulaPackages;
            if (this.unitType === 'glamping') return this.glampingPackages;
            return [...(this.glampingPackages || []), ...(this.aulaPackages || [])];
        },

        get selectedPackage() {
            return this.currentPackages().find(p => p.id === this.selectedPackageId) || null;
        },

        computePackageEnd() {
            if (!this.checkIn || !this.checkInTime || !this.selectedPackage) return null;
            const start = new Date(`${this.checkIn}T${this.checkInTime}:00`);
            start.setHours(start.getHours() + Number(this.selectedPackage.duration_hours || 0));
            const y = start.getFullYear();
            const m = String(start.getMonth() + 1).padStart(2, '0');
            const d = String(start.getDate()).padStart(2, '0');
            const hh = String(start.getHours()).padStart(2, '0');
            const mm = String(start.getMinutes()).padStart(2, '0');
            return { date: `${y}-${m}-${d}`, time: `${hh}:${mm}` };
        },

        get nights() {
            if (this.isPackagedUnit() && this.selectedPackage) return 1;
            if (!this.checkIn || !this.checkOut) return 0;
            const diff = (new Date(this.checkOut) - new Date(this.checkIn)) / 86400000;
            return diff > 0 ? Math.ceil(diff) : 1;
        },

        get dateInvalid() {
            if (!this.checkIn || !this.checkInTime) return false;
            if (this.tab === 'unit' && this.isPackagedUnit() && this.selectedPackage) return false;
            if (!this.checkOut || !this.checkOutTime) return !!(this.checkIn && this.checkOut);
            return this.dateTimeValue(this.checkOut, this.checkOutTime) <= this.dateTimeValue(this.checkIn, this.checkInTime);
        },

        get unitSubtotal() {
            if (!this.selectedUnit) return 0;
            if (this.isPackagedUnit() && this.selectedPackage) return Number(this.selectedPackage.price);
            if (this.nights <= 0) return 0;
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

        get detailImages() {
            return this.detailProduct?.images ?? [];
        },

        sanitizeWhatsapp() {
            const plus = this.visitorWhatsapp.trim().startsWith('+');
            let digits = this.visitorWhatsapp.replace(/\D/g, '');
            if (digits.length > 16) digits = digits.slice(0, 16);
            this.visitorWhatsapp = (plus ? '+' : '') + digits;
        },

        normalizeWhatsapp(raw) {
            let digits = String(raw ?? '').replace(/\D/g, '');
            if (!digits) return null;
            if (digits.startsWith('62')) {
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

        get canOpenPayModal() {
            this.refreshToday();
            if (this.itemCount <= 0 || this.grandTotal <= 0) return false;
            if (this.tab === 'unit') {
                const packaged = this.isPackagedUnit();
                return !!this.selectedUnit && (!packaged || !!this.selectedPackageId) && !!this.checkIn && !!this.checkOut && !this.dateInvalid && this.checkIn >= this.todayStr;
            }
            return this.cart.length > 0 && !!this.checkIn && this.checkIn >= this.todayStr;
        },

        openPayModal() {
            this.ensureDatesValid();
            if (!this.canOpenPayModal) return;
            this.errorMessage = '';
            this.payModalOpen = true;
        },

        closePayModal() {
            if (this.sending) return;
            this.payModalOpen = false;
        },

        get canSubmit() {
            if (this.tab === 'unit' && this.selectedUnit && (this.selectedUnit.type === 'glamping' || this.selectedUnit.type === 'aula') && this.checkIn) {
                this.ensureDatesValid();
            }
            if (this.sending || !this.visitorName.trim() || !this.whatsappValid) return false;
            if (this.tab === 'unit') {
                const packaged = this.isPackagedUnit();
                return !!this.selectedUnit && (!packaged || !!this.selectedPackageId) && this.unitSubtotal > 0 && !!this.checkIn && !!this.checkOut && !this.dateInvalid && this.checkIn >= this.todayStr;
            }
            return this.cart.length > 0 && !!this.checkIn && this.checkIn >= this.todayStr;
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
            this.ensureDatesValid();
            if (!this.checkIn) return;
            if (this.checkIn < this.todayStr) return;
            const packaged = (this.isPackagedUnit() || this.unitType === 'glamping' || this.unitType === 'aula') && this.selectedPackageId;
            if (!packaged && (!this.checkOut || this.dateInvalid)) return;

            const prevSelected = this.selectedUnit ? { ...this.selectedUnit } : null;
            this.loadingUnits = true;
            this.unitsError = '';
            try {
                const params = new URLSearchParams({
                    check_in: this.checkIn,
                    check_in_time: this.checkInTime || (packaged ? '08:00' : '14:00'),
                });
                if (packaged) {
                    params.set('package_id', this.selectedPackageId);
                    const t = this.selectedUnit?.type || (this.unitType === 'aula' ? 'aula' : (this.unitType === 'glamping' ? 'glamping' : null));
                    if (t) params.set('type', t);
                } else {
                    params.set('check_out', this.checkOut);
                    params.set('check_out_time', this.checkOutTime || '12:00');
                }
                const res = await fetch(`/api/availability?${params.toString()}`);
                const data = await res.json();
                if (!res.ok) {
                    this.unitsError = data.message || 'Gagal memuat ketersediaan.';
                    this.availableUnits = [];
                    this.glampingRemainingSlots = null;
                    this.aulaRemainingSlots = null;
                    if (!(prevSelected && (prevSelected.type === 'glamping' || prevSelected.type === 'aula'))) {
                        this.selectedUnit = null;
                    }
                    return;
                }
                this.availableUnits = data.data || [];
                if (Array.isArray(data.glamping_packages)) this.glampingPackages = data.glamping_packages;
                if (Array.isArray(data.aula_packages)) this.aulaPackages = data.aula_packages;
                if (!Array.isArray(data.glamping_packages) && Array.isArray(data.packages)) this.glampingPackages = data.packages;
                this.glampingRemainingSlots = (data.glamping_remaining_slots ?? null);
                this.aulaRemainingSlots = (data.aula_remaining_slots ?? null);

                if (prevSelected) {
                    const found = this.availableUnits.find(u => u.id === prevSelected.id);
                    if (found) {
                        this.selectedUnit = found;
                    } else if ((prevSelected.type === 'glamping' || prevSelected.type === 'aula') && this.selectedPackageId) {
                        const slots = prevSelected.type === 'glamping' ? this.glampingRemainingSlots : this.aulaRemainingSlots;
                        this.selectedUnit = (slots === null || slots > 0) ? prevSelected : null;
                    } else {
                        this.selectedUnit = null;
                    }
                } else {
                    this.selectedUnit = null;
                }

                if (this.selectedUnit && (this.selectedUnit.type === 'glamping' || this.selectedUnit.type === 'aula')) {
                    const pkgs = this.selectedUnit.type === 'aula' ? this.aulaPackages : this.glampingPackages;
                    if (!this.selectedPackageId || !pkgs.find(p => p.id === this.selectedPackageId)) {
                        this.selectedPackageId = pkgs[0]?.id || '';
                    }
                }
            } catch (e) {
                this.unitsError = 'Gagal memuat ketersediaan. Periksa koneksi Anda.';
                if (!(prevSelected && (prevSelected.type === 'glamping' || prevSelected.type === 'aula') && this.selectedPackageId)) {
                    this.selectedUnit = null;
                }
            } finally {
                this.loadingUnits = false;
            }
        },

        selectUnit(unit) {
            this.selectedUnit = unit;
            this.errorMessage = '';
            if (unit.type === 'glamping' || unit.type === 'aula') {
                const pkgs = unit.type === 'aula' ? this.aulaPackages : this.glampingPackages;
                if (!this.selectedPackageId || !pkgs.find(p => p.id === this.selectedPackageId)) {
                    this.selectedPackageId = pkgs[0]?.id || '';
                }
                if (!this.checkIn) this.checkIn = this.todayStr;
                this.checkInTime = '08:00';
            } else {
                this.selectedPackageId = '';
                if (!this.checkIn) this.checkIn = this.todayStr;
                if (!this.checkInTime) this.checkInTime = '14:00';
            }
            this.ensureDatesValid();
            if (this.checkIn) this.fetchAvailability();
        },

        onPackageChange() {
            this.errorMessage = '';
            if (this.selectedUnit && !this.isPackagedUnit()) this.selectedUnit = null;
            if ((this.selectedUnit?.type === 'glamping' || this.selectedUnit?.type === 'aula' || this.unitType === 'glamping' || this.unitType === 'aula') && this.selectedPackageId) {
                if (!this.checkIn) this.checkIn = this.todayStr;
                this.checkInTime = '08:00';
            }
            this.ensureDatesValid();
            if (this.checkIn) this.fetchAvailability();
        },

        async loadTickets() {
            this.loadingTickets = true;
            try {
                const res = await fetch('/api/products?category=tiket_masuk&type=addon');
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

        openDetail(product, kind = 'addon') {
            this.detailProduct = product;
            this.detailKind = kind;
            this.detailImageIndex = 0;
            this.detailOpen = true;
        },

        closeDetail() {
            this.detailOpen = false;
        },

        prevDetailImage() {
            const n = this.detailImages.length;
            if (!n) return;
            this.detailImageIndex = (this.detailImageIndex - 1 + n) % n;
        },

        nextDetailImage() {
            const n = this.detailImages.length;
            if (!n) return;
            this.detailImageIndex = (this.detailImageIndex + 1) % n;
        },

        detailAdd() {
            if (!this.detailProduct || (this.detailProduct.stock ?? 0) <= 0) return;
            if (this.detailKind === 'tiket') this.addTicket(this.detailProduct);
            else this.addToCart(this.detailProduct);
            this.closeDetail();
        },

        openCheckout() {
            if (this.itemCount === 0) return;
            this.errorMessage = '';
            this.checkoutOpen = true;
        },

        closeCheckout() {
            this.checkoutOpen = false;
        },

        categoryLabel(category) {
            return {
                tiket_masuk: 'Tiket Masuk', makanan: 'Makanan', minuman: 'Minuman',
                souvenir: 'Souvenir', baju_merchandise: 'Baju & Merchandise', produk_umkm: 'Produk UMKM',
            }[category] ?? 'Produk';
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
            this.checkInTime = '14:00';
            this.checkOutTime = '12:00';
            this.unitType = 'all';
            this.availableUnits = [];
            this.selectedUnit = null;
            this.selectedPackageId = '';
            this.tickets = [];
            this.ticketQuantities = {};
            this.cart = [];
            this.visitorName = '';
            this.visitorWhatsapp = '';
            this.detailOpen = false;
            this.detailProduct = null;
            this.detailImageIndex = 0;
            this.checkoutOpen = false;
            this.payModalOpen = false;
            this.visitorEmail = '';
            this.errorMessage = '';
            this.success = null;
        },

        async submit() {
            this.ensureDatesValid();
            if (!this.canSubmit) return;
            this.sending = true;
            this.errorMessage = '';
            this.lastTotal = this.grandTotal;

            const unitCheckOut = this.tab === 'unit' ? this.checkOut : null;
            const payload = {
                visitor_name: this.visitorName,
                visitor_whatsapp: this.normalizeWhatsapp(this.visitorWhatsapp) ?? this.visitorWhatsapp,
                visitor_email: this.visitorEmail || null,
                unit_id: this.tab === 'unit' ? this.selectedUnit?.id ?? null : null,
                package_id: (this.tab === 'unit' && this.isPackagedUnit()) ? (this.selectedPackageId || null) : null,
                check_in_date: this.checkIn,
                check_in_time: this.checkInTime,
                check_out_date: unitCheckOut,
                check_out_time: this.checkOutTime,
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
                    this.checkoutOpen = false;
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    this.redirectTimer = setTimeout(() => {
                        window.location.href = '/pembayaran/' + data.order_code;
                    }, 2500);
                } else {
                    this.errorMessage = data.errors?.visitor_whatsapp?.[0]
                        || (data.errors ? Object.values(data.errors).flat()[0] : null)
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
