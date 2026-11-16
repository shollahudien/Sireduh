<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Jual Tiket &mdash; SIRESTEDUH</title>
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
                margin: 0 !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                border: 0 !important;
            }
        }
    </style>
</head>
<body class="bg-teal-950 min-h-screen text-gray-100 antialiased">

<div class="min-h-screen flex flex-col" x-data="tiketPos()" x-init="init()">

    {{-- Top bar --}}
    <header class="bg-teal-900 border-b border-teal-800 px-3 sm:px-6 py-2 sm:py-3 flex items-center justify-between gap-2 sticky top-0 z-30">
        <div class="flex items-center gap-2 sm:gap-3 min-w-0">
            <div class="w-9 h-9 sm:w-10 sm:h-10 bg-teal-500 text-teal-950 rounded-xl flex items-center justify-center text-lg sm:text-xl shrink-0">🎟️</div>
            <div class="min-w-0">
                <p class="font-extrabold leading-tight truncate">Jual Tiket Masuk</p>
                <p class="text-[10px] sm:text-xs text-teal-300 uppercase tracking-widest truncate">SIRESTEDUH &middot; Petugas Check-in</p>
            </div>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <span class="hidden sm:inline text-[11px] sm:text-xs text-teal-200">{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-[11px] sm:text-xs font-semibold text-red-300 border border-red-900 rounded-lg px-2.5 py-1.5 hover:bg-red-950 transition">Keluar</button>
            </form>
        </div>
    </header>

    <main class="flex-1 w-full max-w-5xl mx-auto px-3 sm:px-6 py-4 sm:py-6 pb-28 lg:pb-6">

        {{-- Ringkasan penjualan tiket hari ini --}}
        <section class="mb-4 grid grid-cols-2 gap-2 sm:grid-cols-4 sm:gap-3">
            <div class="rounded-xl border border-teal-700/60 bg-teal-900/50 p-2.5 shadow-sm sm:p-3">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-teal-300">Omzet Hari Ini</p>
                <p class="mt-0.5 text-sm font-extrabold tabular-nums text-green-400 sm:text-base" x-text="formatIDR(todaySummary.total_amount)">Rp 0</p>
                <p class="text-[10px] text-teal-400"><span x-text="todaySummary.total_transactions || 0">0</span> transaksi</p>
            </div>
            <div class="rounded-xl border border-teal-800 bg-teal-900/40 p-2.5 shadow-sm sm:p-3">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-teal-300">Tiket Terjual</p>
                <p class="mt-0.5 text-sm font-extrabold tabular-nums text-white sm:text-base" x-text="ticketsSoldToday">0</p>
                <p class="text-[10px] text-teal-500">hari ini</p>
            </div>
            <div class="rounded-xl border border-teal-800 bg-teal-900/40 p-2.5 shadow-sm sm:p-3">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-green-300">Tunai</p>
                <p class="mt-0.5 text-sm font-bold tabular-nums text-white sm:text-base" x-text="formatIDR(todaySummary.cash?.total)">Rp 0</p>
                <p class="text-[10px] text-teal-500"><span x-text="todaySummary.cash?.count || 0">0</span> trx</p>
            </div>
            <div class="rounded-xl border border-teal-800 bg-teal-900/40 p-2.5 shadow-sm sm:p-3">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-sky-300">QRIS</p>
                <p class="mt-0.5 text-sm font-bold tabular-nums text-white sm:text-base" x-text="formatIDR(todaySummary.qris?.total)">Rp 0</p>
                <p class="text-[10px] text-teal-500"><span x-text="todaySummary.qris?.count || 0">0</span> trx</p>
            </div>
        </section>

        {{-- Error --}}
        <div x-show="errorMessage" x-cloak class="bg-red-950/50 border border-red-800 rounded-xl px-4 py-3 text-sm text-red-200 mb-4" x-text="errorMessage"></div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            {{-- Kolom kiri: Daftar tiket --}}
            <section class="lg:col-span-8 space-y-3 sm:space-y-4">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-sm sm:text-base font-extrabold text-teal-100">Pilih Tiket Masuk</h2>
                    <span class="text-[10px] sm:text-xs text-teal-400" x-show="tickets.length" x-text="tickets.length + ' jenis tiket'"></span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
                    <template x-for="ticket in tickets" :key="ticket.id">
                        <div class="bg-teal-900/40 border border-teal-800 rounded-2xl p-3.5 sm:p-4 flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
                            <div class="flex items-start gap-3 sm:gap-4 min-w-0 flex-1">
                                <div class="w-12 h-12 sm:w-16 sm:h-16 rounded-2xl bg-teal-800/50 border border-teal-700 flex items-center justify-center text-2xl sm:text-4xl shrink-0">🎫</div>
                                <div class="min-w-0 flex-1">
                                    <h3 class="font-extrabold text-white leading-snug break-words" x-text="ticket.name"></h3>
                                    <p class="text-[10px] sm:text-xs text-teal-300 mt-0.5">Sisa stok: <span x-text="ticket.stock"></span></p>
                                    <p class="text-base sm:text-lg font-extrabold text-green-400 mt-1" x-text="formatIDR(ticket.price)"></p>
                                </div>
                            </div>
                            <div class="shrink-0 flex items-center justify-between sm:justify-center sm:flex-col gap-2 pt-2 sm:pt-0 border-t border-teal-800/60 sm:border-0">
                                <div class="flex items-center border border-teal-800 rounded-xl bg-teal-950/60">
                                    <button type="button" class="w-10 h-10 text-xl text-teal-200 hover:text-white disabled:opacity-40" @click="decQty(ticket)" :disabled="qtyOf(ticket.id) === 0">−</button>
                                    <span class="w-10 text-center text-lg font-extrabold text-white" x-text="qtyOf(ticket.id)"></span>
                                    <button type="button" class="w-10 h-10 text-xl text-teal-200 hover:text-white disabled:opacity-40" @click="incQty(ticket)" :disabled="ticket.stock <= 0 || qtyOf(ticket.id) >= ticket.stock">+</button>
                                </div>
                                <button type="button" @click="addTicket(ticket)" :disabled="ticket.stock <= 0"
                                        class="text-[11px] font-bold text-teal-950 bg-teal-400 hover:bg-teal-300 rounded-lg px-4 py-2 sm:py-1.5 transition disabled:opacity-40 disabled:cursor-not-allowed whitespace-nowrap"
                                        x-text="ticket.stock <= 0 ? 'Habis' : '+ Tambah'"></button>
                            </div>
                        </div>
                    </template>
                </div>

                <p x-show="loading" x-cloak class="text-center text-teal-400 py-10">Memuat tiket...</p>
                <p x-show="!loading && !loadFailed && tickets.length === 0" x-cloak class="text-center text-teal-500 py-16">Tiket masuk habis.</p>
            </section>

            {{-- Kolom kanan: Panel ringkasan (desktop) --}}
            <aside class="hidden lg:block lg:col-span-4">
                <div class="sticky top-6 bg-teal-900/50 border border-teal-800 rounded-2xl p-4 shadow-xl">
                    <div class="flex items-center justify-between gap-2 mb-3 pb-3 border-b border-teal-800">
                        <h2 class="font-extrabold text-white">Ringkasan Pesanan</h2>
                        <span class="text-xs font-bold bg-teal-800 text-teal-100 rounded-full px-2.5 py-1" x-text="cartCount + ' tiket'"></span>
                    </div>

                    <div x-show="cartCount === 0" class="text-center text-teal-400 py-8">
                        <div class="text-4xl mb-2">🧾</div>
                        <p class="font-semibold text-teal-200">Belum ada tiket dipilih</p>
                        <p class="text-xs mt-1">Pilih tiket di kiri untuk memulai transaksi.</p>
                    </div>

                    <div x-show="cartCount > 0" x-cloak class="space-y-3">
                        <ul class="space-y-2 max-h-64 overflow-y-auto pr-1">
                            <template x-for="item in Object.values(cart)" :key="item.id">
                                <li class="flex items-start justify-between gap-3 bg-teal-950/40 border border-teal-800 rounded-xl p-3">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-white leading-snug break-words" x-text="item.name"></p>
                                        <p class="text-xs text-teal-300 mt-0.5" x-text="item.qty + ' × ' + formatIDR(item.price)"></p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <p class="text-sm font-bold text-green-400" x-text="formatIDR(item.qty * item.price)"></p>
                                        <div class="mt-1 flex items-center gap-1 justify-end">
                                            <button type="button" class="w-6 h-6 rounded bg-teal-800 text-teal-100 hover:bg-teal-700" @click="decQty(item)">−</button>
                                            <button type="button" class="w-6 h-6 rounded bg-teal-800 text-teal-100 hover:bg-teal-700" @click="incQty(item)" :disabled="item.qty >= item.stock">+</button>
                                        </div>
                                    </div>
                                </li>
                            </template>
                        </ul>

                        <div class="border-t border-teal-800 pt-3">
                            <p class="text-[11px] uppercase font-bold text-teal-300">Total Pembayaran</p>
                            <p class="text-2xl font-extrabold text-green-400" x-text="formatIDR(total)"></p>
                            <p class="text-xs text-teal-400 mt-0.5"><span x-text="cartCount"></span> tiket dipilih</p>
                        </div>

                        <div class="grid grid-cols-1 gap-2 pt-1">
                            <button type="button" @click="pay('cash')" :disabled="sending || cartCount === 0"
                                    class="w-full bg-green-600 text-white font-extrabold rounded-xl py-3.5 hover:bg-green-500 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                                <span>💵</span><span x-text="sending ? 'Memproses...' : 'Bayar Tunai'"></span>
                            </button>
                            <button type="button" @click="pay('qris')" :disabled="sending || cartCount === 0"
                                    class="w-full bg-blue-600 text-white font-extrabold rounded-xl py-3.5 hover:bg-blue-500 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                                <span>📱</span><span x-text="sending ? 'Memproses...' : 'Bayar QRIS'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        {{-- Sticky bottom (mobile): total + cash/qris --}}
        <div x-show="cartCount > 0" x-cloak
             class="fixed inset-x-0 bottom-16 z-40 bg-teal-900 border-t border-teal-800 shadow-[0_-4px_20px_rgba(0,0,0,0.4)] px-3 sm:px-4 py-2 flex items-center gap-2 lg:hidden">
            <div class="min-w-0 flex-1">
                <p class="text-[10px] uppercase font-bold text-teal-300">Total &middot; <span x-text="cartCount"></span> tiket</p>
                <p class="text-lg sm:text-2xl font-extrabold text-green-400" x-text="formatIDR(total)"></p>
            </div>
            <button type="button" @click="pay('cash')" :disabled="sending"
                    class="shrink-0 bg-green-600 text-white font-extrabold rounded-xl sm:rounded-2xl px-3 sm:px-5 py-3 sm:py-3.5 hover:bg-green-500 transition flex flex-col items-center leading-tight disabled:opacity-50">
                <span class="text-lg">💵</span>
                <span class="text-[9px] sm:text-[10px] uppercase tracking-wide">TUNAI</span>
            </button>
            <button type="button" @click="pay('qris')" :disabled="sending"
                    class="shrink-0 bg-blue-600 text-white font-extrabold rounded-xl sm:rounded-2xl px-3 sm:px-5 py-3 sm:py-3.5 hover:bg-blue-500 transition flex flex-col items-center leading-tight disabled:opacity-50">
                <span class="text-lg">📱</span>
                <span class="text-[9px] sm:text-[10px] uppercase tracking-wide">QRIS</span>
            </button>
        </div>
    </main>

    {{-- Struk sukses (modal overlay) --}}
    <div x-show="receipt" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4">
        <div class="absolute inset-0 bg-teal-950/70 backdrop-blur-sm" @click="closeReceipt()"></div>
        <div class="relative w-full max-w-sm rounded-2xl overflow-hidden border border-green-700/40 shadow-2xl">
            <div class="bg-green-700 text-white text-center px-6 pt-4 pb-3">
                <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center text-2xl mx-auto mb-1.5">✅</div>
                <h2 class="text-lg font-extrabold">Transaksi Berhasil</h2>
                <p class="text-green-100 text-[11px] mt-0.5">Struk tersimpan &middot; siap dicetak</p>
            </div>

            {{-- Struk (area yang ikut tercetak) --}}
            <div id="struk-print" x-show="receipt" class="bg-white text-gray-900 px-5 py-4 font-mono text-[11px] leading-relaxed">
                <div class="text-center">
                    <p class="text-base font-extrabold tracking-widest">SIRESTEDUH</p>
                    <p class="text-[10px]">BUMDes Wisata Teduh</p>
                    <p class="text-[10px] mt-1 tracking-widest">--- STRUK TIKET MASUK ---</p>
                </div>

                <div class="mt-3 space-y-0.5 text-[10px]">
                    <div class="flex justify-between gap-2"><span class="text-gray-500 shrink-0">No.</span><span class="font-bold" x-text="'#' + receipt?.id"></span></div>
                    <div class="flex justify-between gap-2"><span class="text-gray-500 shrink-0">Tanggal</span><span class="text-right" x-text="formatDate(receipt?.created_at)"></span></div>
                    <div class="flex justify-between gap-2"><span class="text-gray-500 shrink-0">Petugas</span><span class="text-right truncate">{{ auth()->user()->name }}</span></div>
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
                    <div class="flex justify-between"><span class="text-gray-500">BAYAR</span><span x-text="formatIDR(receipt?.total_amount)"></span></div>
                    <div class="flex justify-between"><span class="text-gray-500">METODE</span><span class="font-bold" x-text="receipt?.payment_method === 'cash' ? 'TUNAI' : 'QRIS'"></span></div>
                </div>

                <div class="border-t border-dashed border-gray-300 my-2.5"></div>
                <div class="text-center text-[10px] space-y-0.5">
                    <p class="font-bold">Terima kasih atas kunjungan Anda!</p>
                    <p class="text-gray-400">Selamat menikmati wisata 🏕️</p>
                    <p class="text-[8px] text-gray-300">SIRESTEDUH &middot; struk ini bukan bukti pajak</p>
                </div>
            </div>

            <div class="bg-white px-5 pb-4 pt-1 grid grid-cols-2 gap-2">
                <button type="button" @click="printReceipt()"
                        class="border-2 border-teal-600 text-teal-800 font-extrabold py-3 rounded-xl hover:bg-teal-50 active:scale-95 transition flex items-center justify-center gap-1.5 text-sm">
                    🖨️ Cetak Struk
                </button>
                <button type="button" @click="closeReceipt()"
                        class="bg-teal-600 text-white font-extrabold py-3 rounded-xl hover:bg-teal-500 active:scale-95 transition text-sm">
                    Selesai
                </button>
            </div>
        </div>
    </div>

    {{-- Bottom navigation --}}
    @include('checkin.partials.bottomnav')
    <div class="h-16 shrink-0"></div>
</div>

<script>
function tiketPos() {
    return {
        tickets: [],
        loading: true,
        loadFailed: false,
        cart: {},
        cartCount: 0,
        total: 0,
        sending: false,
        errorMessage: '',
        receipt: null,
        todaySummary: { cash: { count: 0, total: 0 }, qris: { count: 0, total: 0 }, transfer: { count: 0, total: 0 }, total_transactions: 0, total_amount: 0 },
        ticketsSoldToday: 0,

        async init() {
            await Promise.all([this.loadTickets(), this.loadTodaySummary()]);
        },

        async loadTodaySummary() {
            try {
                const d = new Date().toISOString().split('T')[0];
                const res = await fetch(`/api/pos/riwayat?from=${d}&to=${d}`, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                this.todaySummary = data.summary || this.todaySummary;
                this.ticketsSoldToday = (data.items_sold || []).reduce((s, i) => s + Number(i.qty || 0), 0);
            } catch (e) {}
        },

        async loadTickets(silent = false) {
            if (!silent) this.loading = true;
            this.loadFailed = false;
            try {
                const res = await fetch('/api/pos/products', { headers: { 'Accept': 'application/json' } });
                if (res.status === 401 || res.status === 403) {
                    this.loadFailed = true;
                    this.errorMessage = 'Sesi Anda berakhir. Silakan keluar lalu masuk kembali.';
                    return;
                }
                if (!res.ok) throw new Error(String(res.status));
                const data = await res.json();
                this.tickets = (data.data ?? []).filter(p => p.category === 'tiket_masuk');
                this.errorMessage = '';
            } catch (e) {
                this.loadFailed = true;
                this.errorMessage = 'Gagal memuat tiket.';
            } finally {
                this.loading = false;
            }
        },

        qtyOf(id) { return this.cart[id]?.qty ?? 0; },

        addTicket(ticket) {
            if (ticket.stock <= 0) return;
            const cur = this.cart[ticket.id];
            if (cur) {
                if (cur.qty < ticket.stock) cur.qty += 1;
            } else {
                this.cart[ticket.id] = { id: ticket.id, name: ticket.name, price: Number(ticket.price), qty: 1, stock: ticket.stock };
            }
            this.recalc();
        },

        incQty(ticket) { this.addTicket(ticket); },

        decQty(ticket) {
            const cur = this.cart[ticket.id];
            if (!cur) return;
            if (cur.qty > 1) cur.qty -= 1; else delete this.cart[ticket.id];
            this.recalc();
        },

        recalc() {
            const items = Object.values(this.cart);
            this.cartCount = items.reduce((s, i) => s + i.qty, 0);
            this.total = items.reduce((s, i) => s + i.qty * i.price, 0);
        },

        formatIDR(value) {
            return 'Rp ' + Math.round(Number(value) || 0).toLocaleString('id-ID');
        },

        async pay(method) {
            const items = Object.values(this.cart).map(i => ({ product_id: i.id, quantity: i.qty }));
            if (items.length === 0 || this.sending) return;
            this.sending = true;
            this.errorMessage = '';

            try {
                const res = await fetch('/api/pos/checkout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                    },
                    body: JSON.stringify({ payment_method: method, items }),
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.receipt = data.transaction;
                    this.cart = {};
                    this.recalc();
                    await Promise.all([this.loadTickets(true), this.loadTodaySummary()]);
                } else {
                    this.errorMessage = data.message ?? 'Checkout gagal.';
                }
            } catch (e) {
                this.errorMessage = 'Koneksi gagal. Periksa jaringan Anda.';
            } finally {
                this.sending = false;
            }
        },

        closeReceipt() { this.receipt = null; },

        printReceipt() {
            window.print();
        },

        formatDate(iso) {
            if (!iso) return '-';
            return new Date(iso).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        },
    };
}
</script>
</body>
</html>
