<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Status Reservasi &mdash; SIRESTEDUH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        html, body { height: 100%; }

        /* ===== Animasi bergerak ala robot/chatbot ===== */
        @keyframes robot-float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-7px); }
        }
        @keyframes robot-tilt {
            0%, 100% { transform: rotate(0deg); }
            33% { transform: rotate(-6deg); }
            66% { transform: rotate(6deg); }
        }
        @keyframes qr-scan {
            0% { top: 6%; opacity: 0; }
            12% { opacity: 1; }
            88% { opacity: 1; }
            100% { top: 94%; opacity: 0; }
        }
        @keyframes bubble-in {
            0% { transform: scale(0.6) translateY(8px); opacity: 0; }
            60% { transform: scale(1.06) translateY(-2px); }
            100% { transform: scale(1) translateY(0); opacity: 1; }
        }
        @keyframes anim-rise {
            0% { transform: translateY(16px); opacity: 0; }
            100% { transform: translateY(0); opacity: 1; }
        }
        @keyframes anim-pop {
            0% { transform: scale(0.85); opacity: 0; }
            65% { transform: scale(1.04); }
            100% { transform: scale(1); opacity: 1; }
        }
        @keyframes dot-pulse {
            0% { box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.55); }
            70% { box-shadow: 0 0 0 9px rgba(22, 163, 74, 0); }
            100% { box-shadow: 0 0 0 0 rgba(22, 163, 74, 0); }
        }
        @keyframes glow-soft {
            0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.0); }
            50% { box-shadow: 0 0 22px 2px rgba(16, 185, 129, 0.28); }
        }

        .robot-float { animation: robot-float 2.6s ease-in-out infinite; }
        .robot-face.robot-think { animation: robot-tilt 1.1s ease-in-out infinite; }
        .bubble-in { animation: bubble-in 0.45s cubic-bezier(0.34, 1.56, 0.64, 1) both; }
        .anim-rise { animation: anim-rise 0.5s cubic-bezier(0.22, 1, 0.36, 1) both; }
        .anim-pop { animation: anim-pop 0.55s cubic-bezier(0.34, 1.56, 0.64, 1) both; }
        .qr-scan-line {
            position: absolute;
            left: 10%;
            right: 10%;
            height: 3px;
            border-radius: 999px;
            background: linear-gradient(90deg, transparent, #22c55e 30%, #a3e635 50%, #22c55e 70%, transparent);
            box-shadow: 0 0 12px rgba(34, 197, 94, 0.65);
            animation: qr-scan 2.4s ease-in-out infinite;
            pointer-events: none;
        }
        .step-dot-active { animation: dot-pulse 1.6s ease-out infinite; }
        .glow-soft { animation: glow-soft 2.4s ease-in-out infinite; }

        @media (prefers-reduced-motion: reduce) {
            .robot-float, .robot-face.robot-think, .bubble-in, .anim-rise, .anim-pop,
            .qr-scan-line, .step-dot-active, .glow-soft { animation: none !important; }
        }
    </style>
</head>
<body class="min-h-screen bg-stone-50 text-stone-900 antialiased">

<div class="max-w-xl mx-auto px-4 py-8 sm:py-12" x-data="statusSearch()">
    {{-- ===== Mascot robot + gelembung bicara ===== --}}
    <div class="flex items-center gap-3 mb-7">
        <div class="shrink-0 relative">
            <div class="robot-float w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-gradient-to-br from-green-600 to-teal-600 ring-2 ring-lime-300/50 shadow-lg shadow-green-700/30 flex items-center justify-center p-1.5">
                <div class="robot-face w-full h-full select-none" :class="searching ? 'robot-think' : ''">
                    <x-bot-mascot size="w-full h-full" />
                </div>
            </div>
            <span x-show="searching" class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full bg-lime-400 ring-2 ring-white animate-ping"></span>
        </div>
        <div class="flex-1 min-w-0">
            <template x-for="bubble in [robotMessage]" :key="bubble">
                <div class="bubble-in inline-block max-w-full bg-white border border-green-100 rounded-2xl rounded-bl-md px-4 py-2.5 shadow-sm">
                    <p class="text-sm text-gray-700 leading-snug" x-text="bubble"></p>
                </div>
            </template>
            <div x-show="searching" class="flex gap-1.5 mt-2 ml-1">
                <span class="w-2 h-2 rounded-full bg-green-500 animate-bounce"></span>
                <span class="w-2 h-2 rounded-full bg-green-500 animate-bounce [animation-delay:150ms]"></span>
                <span class="w-2 h-2 rounded-full bg-green-500 animate-bounce [animation-delay:300ms]"></span>
            </div>
        </div>
    </div>

    <div class="text-center mb-6">
        <span class="inline-flex items-center gap-2 text-[10px] sm:text-[11px] font-bold uppercase tracking-[0.16em] text-green-700 bg-white ring-1 ring-green-100 rounded-full px-3 py-1.5 mb-3 shadow-sm">
            <span class="tech-dot w-2 h-2 rounded-full bg-green-500"></span>
            Lacak Order Code
        </span>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-green-900">Status Reservasi</h1>
        <p class="text-gray-500 mt-1 text-sm">Cari order code untuk melihat status pembayaran &amp; check-in</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        {{-- ===== Form pencarian ===== --}}
        <div x-show="!found" x-cloak>
            <div class="mb-4">
                <label for="order_code" class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Order Code *</label>
                <input id="order_code" type="text" x-model="orderCode" @keydown.enter="search()"
                       placeholder="Gunakan order code hasil checkout (mis. SIRES-XXXXX)"
                       class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-green-600 focus:outline-none text-sm">
            </div>
            <button @click="search()"
                    :disabled="!orderCode.trim() || searching"
                    class="w-full bg-green-700 text-white font-semibold py-3 rounded-xl hover:bg-green-800 hover:-translate-y-0.5 active:scale-95 transition-all duration-200 disabled:opacity-50 disabled:translate-y-0 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                <span x-show="!searching">🔍 Cari Status</span>
                <template x-if="searching">
                    <span class="inline-flex items-center gap-1.5">
                        Mencari
                        <span class="w-1.5 h-1.5 rounded-full bg-white animate-bounce"></span>
                        <span class="w-1.5 h-1.5 rounded-full bg-white animate-bounce [animation-delay:150ms]"></span>
                        <span class="w-1.5 h-1.5 rounded-full bg-white animate-bounce [animation-delay:300ms]"></span>
                    </span>
                </template>
            </button>
            <div x-show="error" x-cloak class="mt-4 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm anim-pop" x-text="error"></div>
        </div>

        {{-- ===== Hasil: animasi naik bertahap ala panel chatbot ===== --}}
        <div x-show="found" x-cloak class="space-y-5 border-t border-gray-100 pt-5">

            {{-- Timeline proses bergerak --}}
            <div class="anim-rise" style="animation-delay: 40ms">
                <div class="flex items-center">
                    <template x-for="(step, i) in steps" :key="step.label">
                        <div class="flex-1 flex flex-col items-center relative">
                            <template x-if="i > 0">
                                <div class="absolute top-[13px] left-[-50%] w-full h-0.5 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-green-500 transition-all duration-700"
                                         :style="step.done ? 'width: 100%' : (step.reached ? 'width: 55%' : 'width: 0%')"></div>
                                </div>
                            </template>
                            <div class="relative z-10 w-7 h-7 rounded-full flex items-center justify-center text-[11px] font-bold transition-all duration-300"
                                 :class="step.done ? 'bg-green-600 text-white' : (step.reached ? 'bg-lime-400 text-green-950 step-dot-active' : 'bg-gray-200 text-gray-400')">
                                <span x-text="step.done ? '✓' : (i + 1)"></span>
                            </div>
                            <span class="mt-1.5 text-[10px] sm:text-[11px] font-semibold text-center leading-tight px-1"
                                  :class="step.done || step.reached ? 'text-green-800' : 'text-gray-400'"
                                  x-text="step.label"></span>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Badge status --}}
            <div class="anim-rise flex flex-wrap items-center gap-x-3 gap-y-1.5" style="animation-delay: 120ms">
                <span class="inline-flex items-center gap-2 text-sm px-3 py-1.5 rounded-full" :class="statusBadgeClass">
                    <span class="text-base" x-text="statusEmoji"></span>
                    <span x-text="statusLabel"></span>
                </span>
                <span class="text-xs text-gray-500">Update terakhir: <span class="font-semibold text-gray-700" x-text="statusAtHuman"></span></span>
            </div>

            {{-- Detail pesanan --}}
            <div class="anim-rise grid gap-2.5 text-sm bg-gray-50 border border-gray-100 rounded-xl p-4" style="animation-delay: 200ms">
                <div class="flex justify-between"><span class="text-gray-500">Order Code</span><span class="font-mono font-semibold text-gray-800" x-text="reservation.order_code"></span></div>
                <div class="flex justify-between"><span class="text-gray-500">Nama Tamu</span><span class="text-gray-800 text-right" x-text="reservation.visitor?.name ?? '-'"></span></div>
                <div class="flex justify-between"><span class="text-gray-500">Unit</span><span class="text-gray-800 text-right" x-text="reservation.unit?.name ?? '-'"></span></div>
                <div class="flex justify-between"><span class="text-gray-500">Check-in</span><span class="text-gray-800" x-text="formatDateTime(reservation.check_in_date, reservation.check_in_time)"></span></div>
                <div class="flex justify-between"><span class="text-gray-500">Check-out</span><span class="text-gray-800" x-text="formatDateTime(reservation.check_out_date, reservation.check_out_time)"></span></div>
                <div class="flex justify-between"><span class="text-gray-500">Total</span><span class="font-bold text-green-800" x-text="formatIDR(reservation.total_price)"></span></div>
                <div class="flex justify-between"><span class="text-gray-500">Status Pembayaran</span><span :class="fmtStatusClass(payment?.status)" x-text="paymentLabel"></span></div>
            </div>

            {{-- Bukti pembayaran --}}
            <div x-show="proofUrl" class="anim-rise bg-white border border-gray-200 rounded-xl p-3" style="animation-delay: 280ms">
                <img :src="proofUrl" class="max-w-full h-auto max-h-48 rounded border" alt="Bukti pembayaran">
            </div>

            <template x-if="payment?.status === 'pending'">
                <div class="anim-rise bg-yellow-50 border border-yellow-200 rounded-xl px-4 py-3 text-sm text-yellow-800 glow-soft">
                    ⏳ Menunggu verifikasi pembayaran. Tim kami akan memverifikasi dalam 1x24 jam.
                </div>
            </template>

            <template x-if="payment?.status === 'verified' || reservation?.status === 'paid'">
                <div class="space-y-3">
                    <div class="anim-rise bg-green-50 border border-green-200 rounded-xl px-4 py-3 text-sm text-green-800 glow-soft" style="animation-delay: 320ms">
                        ✅ Pembayaran sudah diverifikasi. E-ticket dikirim ke WhatsApp &amp; QR di bawah bisa langsung dipakai.
                    </div>

                    <template x-if="ticket && !ticket.is_used">
                        <div class="anim-pop relative bg-white border-2 border-dashed border-green-300 rounded-2xl p-4 text-center overflow-hidden" style="animation-delay: 400ms">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-green-700 mb-2">🎟️ E-Ticket &middot; QR Check-in</p>
                            <div class="relative inline-block">
                                <img :src="'/e-ticket/' + ticket.unique_hash_sha256 + '/qr.png'" alt="QR E-Ticket"
                                     class="mx-auto w-44 h-44 bg-white rounded-xl border border-green-200 p-1.5">
                                <span class="qr-scan-line"></span>
                            </div>
                            <p class="text-[11px] text-green-700 mt-2 leading-snug">Tunjukkan QR ini kepada petugas di gerbang check-in.</p>
                            <div class="grid grid-cols-2 gap-2 mt-3">
                                <a :href="'/e-ticket/' + ticket.unique_hash_sha256 + '/qr.png?download=1'"
                                   class="bg-green-700 text-white text-xs font-bold py-2.5 rounded-xl hover:bg-green-800 hover:-translate-y-0.5 active:scale-95 transition-all duration-200">⬇️ Unduh QR</a>
                                <a :href="'/e-ticket/' + ticket.unique_hash_sha256 + '/qr.png'" target="_blank"
                                   class="border border-green-300 text-green-800 text-xs font-bold py-2.5 rounded-xl hover:bg-green-100 hover:-translate-y-0.5 active:scale-95 transition-all duration-200">🔍 Perbesar</a>
                            </div>
                        </div>
                    </template>

                    <template x-if="ticket && ticket.is_used">
                        <div class="anim-rise bg-gray-100 border border-gray-200 rounded-xl px-4 py-3 text-sm text-gray-600" style="animation-delay: 400ms">
                            🎟️ Tiket sudah digunakan untuk check-in.
                        </div>
                    </template>

                    <template x-if="!ticket">
                        <div class="anim-rise bg-yellow-50 border border-yellow-200 rounded-xl px-4 py-3 text-sm text-yellow-800" style="animation-delay: 400ms">
                            ⏳ E-ticket sedang diproses. Muat ulang pencarian beberapa saat lagi.
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>

    <p class="text-center text-xs text-gray-400 mt-8">Belum punya order code? <a href="/checkout" class="text-green-700 underline underline-offset-2">Kembali ke checkout</a></p>
</div>

<script>
function statusSearch() {
    return {
        orderCode: '',
        searching: false,
        error: '',
        found: false,
        reservation: {},
        payment: null,
        ticket: null,
        proofUrl: '',

        async search() {
            this.searching = true;
            this.error = '';
            try {
                const res = await fetch('/status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ order_code: this.orderCode }),
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok && data.reservation) {
                    this.found = true;
                    this.reservation = data.reservation;
                    this.payment = data.payment || null;
                    this.ticket = data.ticket || null;
                    this.proofUrl = data.proof_url || '';
                } else {
                    this.error = data.message || 'Order code tidak ditemukan.';
                    this.found = false;
                }
            } catch (e) {
                this.error = 'Gagal mencari. Periksa koneksi Anda.';
            } finally {
                this.searching = false;
            }
        },

        // ===== Gelembung bicara robot (kontekstual) =====
        get robotMessage() {
            if (this.searching) return 'Sebentar, saya cek data reservasimu dulu…';
            if (!this.found) return 'Hai! 👋 Masukkan kode order-mu ya, contoh: SIRES-XXXXXX';
            const p = this.payment?.status ?? null;
            if (!p) return 'Ayo lanjut bayar & unggah buktinya di halaman pembayaran ya! 💪';
            if (p === 'pending') return 'Bukti transfer sudah diterima. Admin akan verifikasi maksimal 1×24 jam ⏳';
            if (this.ticket && this.ticket.is_used) return 'Tiketmu sudah dipakai check-in. Selamat menikmati wisata! 🎊';
            if (this.ticket) return 'Yeay! 🎉 Tiket e-QR siap — tunjukkan ke petugas di gerbang ya!';
            return 'Pembayaran sah! E-tiket sedang diproses sebentar lagi ya ⚙️';
        },

        // ===== Timeline proses: done / reached (aktif berdenyut) =====
        get steps() {
            const p = this.payment?.status ?? null;
            const paid = p === 'verified' || this.reservation?.status === 'paid';
            const used = this.ticket?.is_used === true || this.reservation?.status === 'checked_in';

            return [
                { label: 'Reservasi', done: true, reached: true },
                { label: 'Pembayaran', done: p !== null, reached: p === null },
                { label: 'Verifikasi', done: paid, reached: p === 'pending' },
                { label: 'E-Tiket', done: used, reached: paid && !used },
            ];
        },

        get statusLabel() {
            const s = this.reservation?.status ?? '';
            if (s === 'paid') return 'Lunas — e-tiket siap';
            if (s === 'waiting_verification') return 'Menunggu verifikasi';
            if (s === 'pending') return 'Menunggu pembayaran';
            if (s === 'cancelled') return 'Dibatalkan';
            if (s === 'confirmed') return 'Terverifikasi & siap check-in';
            if (s === 'checked_in') return 'Sudah check-in';
            if (s === 'checked_out') return 'Sudah check-out';
            return 'Menunggu konfirmasi';
        },

        get statusEmoji() {
            const s = this.reservation?.status ?? '';
            if (s === 'paid') return '✅';
            if (s === 'waiting_verification') return '⏳';
            if (s === 'pending') return '💳';
            if (s === 'cancelled') return '❌';
            if (s === 'checked_in') return '✅';
            if (s === 'checked_out') return '🚶';
            if (s === 'confirmed') return '⏳';
            return '⏸️';
        },

        get statusBadgeClass() {
            const s = this.reservation?.status ?? '';
            if (s === 'paid') return 'bg-green-100 text-green-700';
            if (s === 'waiting_verification') return 'bg-yellow-100 text-yellow-800';
            if (s === 'pending') return 'bg-blue-100 text-blue-700';
            if (s === 'cancelled') return 'bg-red-100 text-red-700';
            if (s === 'checked_in') return 'bg-green-100 text-green-700';
            if (s === 'checked_out') return 'bg-indigo-100 text-indigo-700';
            if (s === 'confirmed') return 'bg-yellow-100 text-yellow-700';
            return 'bg-gray-100 text-gray-600';
        },

        fmtStatusClass(status) {
            if (status === 'verified') return 'text-green-700 font-semibold';
            if (status === 'pending') return 'text-yellow-700 font-semibold';
            return 'text-gray-600';
        },

        get paymentLabel() {
            const s = this.payment?.status ?? '';
            if (s === 'verified') return 'Sudah diverifikasi';
            if (s === 'pending') return 'Menunggu verifikasi';
            return s;
        },

        formatDate(iso) {
            if (!iso) return '-';
            return new Date(iso).toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' });
        },

        formatIDR(value) {
            return 'Rp ' + Math.round(Number(value) || 0).toLocaleString('id-ID');
        },
    };
}
</script>

@include('components.chatbot')
</body>
</html>
