@extends('layouts.app')

@section('title', 'Pembayaran')

@section('content')
@php
    $payment = config('services.payment');
@endphp

<div x-data="paymentPage(@js($orderCode))" x-init="load()" class="max-w-4xl mx-auto">

    {{-- Loading --}}
    <div x-show="loading" x-cloak class="flex items-center justify-center gap-3 text-gray-400 py-24">
        <svg class="animate-spin h-8 w-8 text-green-700" viewBox="0 0 24 24" fill="none">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
        </svg>
        <span>Memuat detail pesanan...</span>
    </div>

    {{-- Error / order not found --}}
    <div x-show="!loading && loadError" x-cloak class="bg-red-50 border border-red-200 rounded-2xl p-8 text-center">
        <div class="text-5xl mb-3">🔍</div>
        <h1 class="text-xl font-bold text-red-800">Order Tidak Ditemukan</h1>
        <p class="text-sm text-red-600 mt-1" x-text="loadError"></p>
        <a href="{{ url('/') }}" class="inline-block mt-6 bg-green-700 text-white font-semibold px-6 py-2.5 rounded-xl hover:bg-green-800 transition">Kembali ke Beranda</a>
    </div>

    {{-- Content --}}
    <div x-show="!loading && !loadError" x-cloak>
        {{-- Header --}}
        <div class="mb-6">
            <span class="inline-flex items-center gap-2 text-[10px] sm:text-[11px] font-bold uppercase tracking-[0.16em] text-green-700 bg-green-50 ring-1 ring-green-100 rounded-full px-3 py-1.5 mb-3">
                <span class="tech-dot w-2 h-2 rounded-full bg-green-500"></span>
                Pembayaran Aman
            </span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-green-900">Pembayaran</h1>
            <p class="text-gray-500 mt-1 text-sm sm:text-base">Selesaikan transfer sebelum timer reservasi berakhir.</p>
            <div class="h-1 w-24 sm:w-32 mt-3 rounded-full bg-gradient-to-r from-lime-400 via-emerald-400 to-teal-400"></div>
        </div>

        {{-- Status banner --}}
        <div x-show="status === 'paid' || status === 'confirmed'" x-cloak class="bg-green-50 border border-green-300 rounded-2xl px-5 py-4 mb-6 flex items-start gap-3">
            <span class="text-2xl">🎉</span>
            <div>
                <p class="font-bold text-green-900">Pembayaran Terverifikasi!</p>
                <p class="text-sm text-green-800">E-tiket akan dikirim ke WhatsApp Anda. Simpan order code ini untuk check-in: <span class="font-mono font-bold" x-text="reservation?.order_code"></span></p>
            </div>
        </div>
        <div x-show="status === 'waiting_verification'" x-cloak class="bg-blue-50 border border-blue-300 rounded-2xl px-5 py-4 mb-6 flex items-start gap-3">
            <span class="text-2xl">⏳</span>
            <div>
                <p class="font-bold text-blue-900">Bukti pembayaran sudah dikirim</p>
                <p class="text-sm text-blue-800">Admin sedang memverifikasi. E-tiket dikirim ke WhatsApp setelah disetujui.</p>
            </div>
        </div>
        <div x-show="status === 'cancelled'" x-cloak class="bg-red-50 border border-red-300 rounded-2xl px-5 py-4 mb-6 flex items-start gap-3">
            <span class="text-2xl">❌</span>
            <div>
                <p class="font-bold text-red-900">Reservasi Dibatalkan</p>
                <p class="text-sm text-red-800">Silakan buat pesanan baru dari halaman utama.</p>
            </div>
        </div>

        <div class="grid lg:grid-cols-5 gap-6 items-start">

            {{-- Ringkasan pesanan --}}
            <div class="lg:col-span-3 space-y-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <h2 class="font-bold text-gray-800 flex items-center justify-between">
                        Ringkasan Pesanan
                        <span class="text-[10px] font-bold uppercase tracking-widest rounded-full px-3 py-1"
                              :class="statusBadge(status)" x-text="statusLabel(status)"></span>
                    </h2>

                    <div class="mt-4 bg-green-50 border border-green-100 rounded-xl px-4 py-3 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-[10px] uppercase tracking-widest text-green-700 font-semibold">Order Code</p>
                            <p class="text-xl sm:text-2xl font-mono font-extrabold text-green-900 truncate" x-text="reservation?.order_code"></p>
                        </div>
                        <button @click="copyCode()" class="shrink-0 border border-green-200 bg-white rounded-lg px-3 py-2 text-xs font-semibold text-green-800 hover:bg-green-100 transition">
                            <span x-text="copied ? '✓ Tersalin' : '📋 Salin'"></span>
                        </button>
                    </div>

                    <dl class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                        <div>
                            <dt class="text-[10px] uppercase text-gray-400 font-semibold">Nama</dt>
                            <dd class="font-semibold text-gray-800" x-text="reservation?.visitor_name"></dd>
                        </div>
                        <div>
                            <dt class="text-[10px] uppercase text-gray-400 font-semibold">WhatsApp</dt>
                            <dd class="text-gray-800" x-text="formatWa(reservation?.visitor_whatsapp)"></dd>
                        </div>
                        <div>
                            <dt class="text-[10px] uppercase text-gray-400 font-semibold">Check-in</dt>
                            <dd class="text-gray-800" x-text="(reservation?.check_in_date || '') + (reservation?.check_in_time ? ' ' + String(reservation.check_in_time).slice(0,5) : '')"></dd>
                        </div>
                        <div>
                            <dt class="text-[10px] uppercase text-gray-400 font-semibold">Check-out</dt>
                            <dd class="text-gray-800" x-text="(reservation?.check_out_date || '') + (reservation?.check_out_time ? ' ' + String(reservation.check_out_time).slice(0,5) : '')"></dd>
                        </div>
                    </dl>

                    <div class="mt-4 border-t border-gray-100 pt-4">
                        <div class="flex justify-between text-sm text-gray-500">
                            <span x-text="reservation?.unit_name ?? 'Tiket Masuk'"></span>
                            <span></span>
                        </div>
                        <ul class="mt-2 divide-y divide-gray-100 text-sm">
                            <template x-for="addon in reservation?.addons ?? []" :key="addon.product_name">
                                <li class="py-2 flex justify-between gap-3">
                                    <span class="text-gray-600"><span x-text="addon.quantity"></span>× <span x-text="addon.product_name"></span></span>
                                    <span class="text-gray-800" x-text="formatIDR(addon.price * addon.quantity)"></span>
                                </li>
                            </template>
                        </ul>
                        <div class="flex justify-between items-baseline border-t border-dashed border-gray-200 pt-3 mt-2">
                            <span class="font-bold text-gray-800">TOTAL</span>
                            <span class="text-2xl font-extrabold text-green-800" x-text="formatIDR(reservation?.total_price)"></span>
                        </div>
                    </div>
                </div>

                {{-- Metode: Manual (selalu) --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <h2 class="font-bold text-gray-800">🏦 Transfer Manual</h2>
                            <p class="text-xs text-gray-400 mt-1">Transfer ke rekening lalu unggah bukti (diverifikasi admin). Metode ini selalu tersedia.</p>
                        </div>
                        <span x-show="payMode==='manual'" x-cloak class="text-[10px] font-bold uppercase tracking-widest bg-green-100 text-green-800 rounded-full px-2.5 py-1">Dipilih</span>
                    </div>
                    <div class="mt-3 bg-gray-50 border border-gray-200 rounded-xl p-4 flex items-center gap-4">
                        <div class="w-16 h-16 bg-blue-600 text-white rounded-xl flex items-center justify-center font-extrabold text-lg" x-text="(manualInfo.bank||'BANK').slice(0,4).toUpperCase()"></div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs text-gray-400" x-text="manualInfo.bank"></p>
                            <p class="text-lg sm:text-xl font-mono font-extrabold text-gray-900 tracking-wider" x-text="manualInfo.account_number"></p>
                            <p class="text-xs text-gray-500">a.n. <span x-text="manualInfo.account_name"></span></p>
                        </div>
                        <button @click="copyAccount(manualInfo.account_number)" class="shrink-0 border border-gray-200 bg-white rounded-lg px-3 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-50 transition">
                            <span x-text="copiedAccount ? '✓' : 'Salin'"></span>
                        </button>
                    </div>
                    <p class="text-xs text-gray-400 mt-3">Konfirmasi manual via WhatsApp: <a :href="'https://wa.me/' + String(manualInfo.whatsapp||'').replace(/\D/g,'')" target="_blank" rel="noopener" class="text-green-700 font-semibold hover:underline" x-text="manualInfo.whatsapp"></a> setelah transfer.</p>
                </div>

                {{-- Metode tambahan: Instan (Midtrans) --}}
                <div class="bg-white rounded-2xl shadow-sm border p-5" :class="gateway.enabled ? 'border-green-200' : 'border-dashed border-gray-200 opacity-90'">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <h2 class="font-bold text-gray-800">⚡ Pembayaran Instan <span class="text-xs font-semibold text-gray-400">(Midtrans)</span></h2>
                            <p class="text-xs mt-1" :class="gateway.enabled ? 'text-gray-500' : 'text-amber-700'">
                                <span x-text="gateway.enabled ? 'Bayar otomatis via QRIS, e-wallet, atau Virtual Account. Status terkonfirmasi otomatis & e-ticket langsung dikirim.' : 'Metode tambahan — belum aktif. Akan tersedia otomatis saat Midtrans diaktifkan (tanpa menghapus transfer manual).'"></span>
                            </p>
                        </div>
                        <span x-show="gateway.enabled && payMode==='instant'" x-cloak class="text-[10px] font-bold uppercase tracking-widest bg-green-100 text-green-800 rounded-full px-2.5 py-1">Dipilih</span>
                        <span x-show="!gateway.enabled" x-cloak class="text-[10px] font-bold uppercase tracking-widest bg-amber-100 text-amber-800 rounded-full px-2.5 py-1">Segera</span>
                    </div>
                    <ul class="mt-3 text-sm text-gray-700 space-y-1">
                        <li>• QRIS (semua e-wallet & mobile banking)</li>
                        <li>• GoPay / ShopeePay</li>
                        <li>• Virtual Account (BCA, BNI, BRI, Permata)</li>
                    </ul>
                </div>
            </div>

            {{-- Aksi pembayaran --}}
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 lg:sticky lg:top-6">
                    <div x-show="!isPaid() && status !== 'cancelled'" x-cloak class="mb-4">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Pilih Metode Pembayaran</p>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" @click="payMode='manual'" :class="payMode==='manual' ? 'bg-green-700 text-white ring-2 ring-green-200' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'" class="text-xs font-bold rounded-xl px-3 py-2.5 transition">🏦 Transfer Manual</button>
                            <button type="button" @click="gateway.enabled && (payMode='instant')" :disabled="!gateway.enabled"
                                    :class="!gateway.enabled ? 'bg-gray-50 text-gray-400 cursor-not-allowed border border-dashed border-gray-200' : (payMode==='instant' ? 'bg-green-700 text-white ring-2 ring-green-200' : 'bg-gray-100 text-gray-700 hover:bg-gray-200')"
                                    class="text-xs font-bold rounded-xl px-3 py-2.5 transition">
                                <span x-text="gateway.enabled ? '⚡ Instan (Midtrans)' : '⚡ Instan (segera aktif)'"></span>
                            </button>
                        </div>
                        <p x-show="!gateway.enabled" x-cloak class="text-[11px] text-amber-700 mt-2">Pembayaran instan (QRIS/e-wallet/VA) akan tersedia saat Midtrans diaktifkan. Anda tetap bisa bayar via transfer manual sekarang.</p>
                    </div>

                    {{-- Instant --}}
                    <div x-show="payMode === 'instant' && gateway.enabled && status === 'pending'" x-cloak>
                        <h2 class="font-bold text-gray-800">Bayar Otomatis</h2>
                        <p class="text-xs text-gray-400 mt-1">QRIS, GoPay, ShopeePay, atau Virtual Account. Konfirmasi instan.</p>
                        <div x-show="payError" x-cloak class="mt-3 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                            <p class="text-xs text-red-600" x-text="payError"></p>
                            <button type="button" @click="payMode='manual'" class="mt-2 text-xs font-bold text-green-800 underline">Gunakan Transfer Manual (fallback)</button>
                        </div>
                        <button @click="payInstant()" :disabled="paying"
                                :class="paying ? 'bg-gray-200 text-gray-400 cursor-wait' : 'bg-green-700 text-white hover:bg-green-800 shadow-lg shadow-green-700/20'"
                                class="w-full mt-4 font-bold py-3.5 rounded-xl transition flex items-center justify-center gap-2">
                            <svg x-show="paying" x-cloak class="animate-spin h-5 w-5" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span x-text="paying ? 'Memproses...' : '⚡ Bayar Sekarang (QRIS/VA/E-Wallet)'"></span>
                        </button>
                        <p class="text-[11px] text-gray-400 mt-3 text-center">Setelah bayar, status diperbarui otomatis (muat ulang halaman bila perlu).</p>
                    </div>

                    {{-- Manual upload --}}
                    <div x-show="payMode === 'manual' && status === 'pending'" x-cloak>
                        <h2 class="font-bold text-gray-800">📤 Upload Bukti Transfer Manual</h2>
                        <p class="text-xs text-gray-400 mt-1">Transfer manual tetap tersedia. Format JPG/PNG, maksimal 2 MB (diverifikasi admin).</p>

                        <div class="mt-4 border-2 border-dashed border-gray-200 rounded-2xl overflow-hidden" :class="filePreview ? 'border-solid' : ''">
                            <template x-if="filePreview">
                                <img :src="filePreview" alt="Bukti transfer" class="w-full max-h-56 object-contain bg-gray-50">
                            </template>
                            <template x-if="!filePreview">
                                <label class="flex flex-col items-center justify-center gap-2 py-10 cursor-pointer hover:bg-gray-50 transition">
                                    <span class="text-4xl">🧾</span>
                                    <span class="text-sm font-semibold text-gray-600">Klik untuk pilih foto bukti</span>
                                    <span class="text-xs text-gray-400">atau seret file ke sini</span>
                                    <input type="file" accept="image/*" class="sr-only" @change="pickFile($event)">
                                </label>
                            </template>
                        </div>

                        <div x-show="filePreview" x-cloak class="mt-3 flex items-center justify-between gap-2 bg-gray-50 border border-gray-200 rounded-xl px-3 py-2">
                            <span class="text-xs text-gray-600 truncate" x-text="fileName"></span>
                            <button @click="clearFile()" class="text-xs font-semibold text-red-500 hover:text-red-700">Ganti</button>
                        </div>

                        <p x-show="uploadError" x-cloak class="text-xs text-red-600 mt-3 bg-red-50 border border-red-200 rounded-lg px-3 py-2" x-text="uploadError"></p>

                        <button @click="upload()" :disabled="!selectedFile || uploading"
                                :class="!selectedFile || uploading ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-green-700 text-white hover:bg-green-800 shadow-lg shadow-green-700/20'"
                                class="w-full mt-4 font-bold py-3.5 rounded-xl transition flex items-center justify-center gap-2">
                            <svg x-show="uploading" x-cloak class="animate-spin h-5 w-5" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span x-text="uploading ? 'Mengirim...' : '📤 Kirim Bukti Sekarang'"></span>
                        </button>
                    </div>

                    <div x-show="status === 'waiting_verification'" x-cloak class="mt-2 text-center bg-blue-50 border border-blue-200 rounded-2xl px-5 py-8">
                        <div class="text-5xl mb-3">⏳</div>
                        <p class="font-bold text-blue-900">Menunggu Verifikasi / Konfirmasi</p>
                        <p class="text-sm text-blue-700 mt-1">Jika bayar otomatis, status akan terkonfirmasi sendiri. Jika transfer manual, admin akan memverifikasi bukti Anda. E-tiket dikirim ke WhatsApp <span class="font-semibold" x-text="formatWa(reservation?.visitor_whatsapp)"></span>.</p>
                        <a href="{{ url('/') }}" class="inline-block mt-5 bg-blue-700 text-white font-semibold px-6 py-2.5 rounded-xl hover:bg-blue-800 transition">Kembali ke Beranda</a>
                    </div>
                    <div x-show="status === 'paid' || status === 'confirmed'" x-cloak class="mt-2 text-center bg-green-50 border border-green-200 rounded-2xl px-5 py-8">
                        <div class="text-5xl mb-3">🎫</div>
                        <p class="font-bold text-green-900">Pembayaran Berhasil!</p>
                        <p class="text-sm text-green-800 mt-1">Terkonfirmasi otomatis. E-tiket sudah/akan segera dikirim ke WhatsApp.</p>
                        <a href="{{ url('/') }}" class="inline-block mt-5 bg-green-700 text-white font-semibold px-6 py-2.5 rounded-xl hover:bg-green-800 transition">Kembali ke Beranda</a>
                    </div>
                    <div x-show="status === 'expired' || status === 'failed'" x-cloak class="mt-2 text-center bg-red-50 border border-red-200 rounded-2xl px-5 py-8">
                        <div class="text-5xl mb-3">⚠️</div>
                        <p class="font-bold text-red-900">Pembayaran Belum Berhasil / Kedaluwarsa</p>
                        <p class="text-sm text-red-700 mt-1">Pembayaran otomatis kadaluarsa atau gagal/gangguan. Anda tetap dapat melanjutkan dengan <b>Transfer Manual</b> (unggah bukti) sebagai fallback.</p>
                        <button @click="status='pending'; payMode='manual'" class="inline-block mt-5 bg-green-700 text-white font-semibold px-6 py-2.5 rounded-xl hover:bg-green-800 transition">Gunakan Transfer Manual</button>
                        <button x-show="gateway.enabled" x-cloak @click="status='pending'; payMode='instant'" class="inline-block mt-5 ml-2 bg-white border border-gray-200 text-gray-700 font-semibold px-6 py-2.5 rounded-xl hover:bg-gray-50 transition">Coba Instan Lagi</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function paymentPage(orderCode) {
    return {
        orderCode,
        reservation: null,
        payment: null,
        status: '',
        loading: true,
        loadError: '',
        selectedFile: null,
        fileName: '',
        filePreview: null,
        uploading: false,
        uploadError: '',
        copied: false,
        copiedAccount: false,
        gateway: { enabled: false, client_key: '', snap_js_url: '', methods: [] },
        manualInfo: { bank: @js($payment['bank']), account_number: @js($payment['account_number']), account_name: @js($payment['account_name']), whatsapp: @js($payment['whatsapp']) },
        payMode: 'instant',
        paying: false,
        payError: '',
        snapLoaded: false,

        async load() {
            this.loading = true;
            this.loadError = '';
            try {
                const res = await fetch('/api/reservations/' + encodeURIComponent(this.orderCode) + '/payment', { headers: { 'Accept': 'application/json' } });
                const data = await res.json();

                if (res.ok && data.success) {
                    this.reservation = data.reservation;
                    this.payment = data.payment;
                    this.status = data.reservation?.status ?? 'pending';
                    this.gateway = data.gateway || this.gateway;
                    if (data.manual) this.manualInfo = data.manual;
                    this.payMode = this.payment?.method === 'gateway' && this.gateway.enabled ? 'instant' : 'manual';
                } else {
                    this.loadError = data.message ?? 'Pesanan tidak ditemukan.';
                }
            } catch (e) {
                this.loadError = 'Gagal terhubung ke server.';
            } finally {
                this.loading = false;
            }
        },

        statusLabel(status) {
            return {
                pending: 'Menunggu Pembayaran',
                waiting_verification: 'Menunggu Verifikasi',
                paid: 'Lunas',
                confirmed: 'Terverifikasi',
                cancelled: 'Dibatalkan',
            }[status] ?? status;
        },

        statusBadge(status) {
            return {
                pending: 'bg-yellow-100 text-yellow-800',
                waiting_verification: 'bg-blue-100 text-blue-800',
                paid: 'bg-green-100 text-green-800',
                confirmed: 'bg-green-100 text-green-800',
                cancelled: 'bg-red-100 text-red-800',
            }[status] ?? 'bg-gray-100 text-gray-600';
        },

        ensureSnap() {
            if (!this.gateway.enabled || !this.gateway.client_key) return false;
            if (window.snap && this.snapLoaded) return true;
            if (document.getElementById('midtrans-snap')) { this.snapLoaded = !!window.snap; return this.snapLoaded; }
            const s = document.createElement('script');
            s.id = 'midtrans-snap';
            s.src = this.gateway.snap_js_url || 'https://app.sandbox.midtrans.com/snap/snap.js';
            s.setAttribute('data-client-key', this.gateway.client_key);
            s.onload = () => { this.snapLoaded = true; };
            document.head.appendChild(s);
            this.snapLoaded = !!window.snap;
            return true;
        },

        async payInstant() {
            if (!this.gateway.enabled) { this.payError = 'Pembayaran otomatis belum dikonfigurasi. Gunakan transfer manual.'; return; }
            this.paying = true; this.payError = '';
            try {
                const res = await fetch('/api/payments/midtrans/create', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken() },
                    body: JSON.stringify({ order_code: this.reservation?.order_code || this.orderCode }),
                });
                const data = await res.json();
                if (!res.ok || !data.success || !data.snap_token) {
                    this.payError = data.message || 'Gagal memulai pembayaran otomatis.';
                    return;
                }
                this.ensureSnap();
                const open = () => window.snap.pay(data.snap_token, {
                    onSuccess: () => this.load(),
                    onPending: () => this.load(),
                    onError: () => { this.payError = 'Pembayaran gagal. Silakan coba lagi.'; },
                    onClose: () => this.load(),
                });
                if (window.snap) open(); else setTimeout(open, 800);
            } catch (e) {
                this.payError = 'Koneksi gagal. Coba lagi.';
            } finally { this.paying = false; }
        },

        isPaid() { return ['paid','confirmed'].includes(this.status); },
        isWaitingManual() { return this.status === 'waiting_verification' && (this.payment?.method || 'manual_transfer') === 'manual_transfer'; },

        formatIDR(value) {
            return 'Rp ' + Math.round(Number(value) || 0).toLocaleString('id-ID');
        },

        formatWa(raw) {
            // Nomor tersimpan 628… → tampilkan 08… agar familiar
            const digits = String(raw ?? '').replace(/\D/g, '');
            if (digits.startsWith('62')) return '0' + digits.slice(2);
            return String(raw ?? '');
        },

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        },

        async copyText(text) {
            try {
                await navigator.clipboard.writeText(text);
                return true;
            } catch (e) {
                const el = document.createElement('textarea');
                el.value = text;
                document.body.appendChild(el);
                el.select();
                try { document.execCommand('copy'); return true; } catch (_) { return false; }
                finally { document.body.removeChild(el); }
            }
        },

        async copyCode() {
            this.copied = await this.copyText(this.reservation?.order_code ?? this.orderCode);
            setTimeout(() => { this.copied = false; }, 2000);
        },

        async copyAccount(number) {
            this.copiedAccount = await this.copyText(number);
            setTimeout(() => { this.copiedAccount = false; }, 2000);
        },

        pickFile(event) {
            const file = event.target.files[0];
            if (!file) return;
            if (file.size > 2 * 1024 * 1024) {
                this.uploadError = 'Ukuran file maksimal 2 MB.';
                return;
            }
            if (!file.type.startsWith('image/')) {
                this.uploadError = 'Hanya file gambar yang diizinkan.';
                return;
            }
            this.selectedFile = file;
            this.fileName = file.name;
            this.uploadError = '';
            const reader = new FileReader();
            reader.onload = (e) => { this.filePreview = e.target.result; };
            reader.readAsDataURL(file);
        },

        clearFile() {
            this.selectedFile = null;
            this.fileName = '';
            this.filePreview = null;
            this.uploadError = '';
        },

        async upload() {
            if (!this.selectedFile || !this.reservation) return;
            this.uploading = true;
            this.uploadError = '';

            const form = new FormData();
            form.append('order_code', this.reservation.order_code ?? this.orderCode);
            form.append('proof_image', this.selectedFile);

            try {
                const res = await fetch('/api/payments/upload', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                    body: form,
                });
                const data = await res.json();

                if (res.ok && data.success) {
                    this.status = 'waiting_verification';
                    this.payment = { ...(this.payment||{}), method: 'manual_transfer', status: data.status ?? 'pending' };
                    this.payMode = 'manual';
                    this.clearFile();
                } else {
                    this.uploadError = data.message ?? 'Gagal mengirim bukti. Coba lagi.';
                }
            } catch (e) {
                this.uploadError = 'Koneksi gagal. Periksa jaringan Anda.';
            } finally {
                this.uploading = false;
            }
        },
    };
}
</script>
@endsection
