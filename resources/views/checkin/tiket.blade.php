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

    <main class="flex-1 w-full max-w-3xl mx-auto px-3 sm:px-4 py-4 sm:py-6">

        {{-- Struk sukses --}}
        <div x-show="receipt" x-cloak class="mb-4 max-w-sm mx-auto rounded-2xl overflow-hidden border border-green-700/40 shadow-xl">
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

        {{-- Error --}}
        <div x-show="errorMessage" x-cloak class="bg-red-950/50 border border-red-800 rounded-xl px-4 py-3 text-sm text-red-200 mb-4" x-text="errorMessage"></div>

        {{-- Ticket list --}}
        <div class="grid gap-3">
            <template x-for="ticket in tickets" :key="ticket.id">
                <div class="bg-teal-900/40 border border-teal-800 rounded-2xl p-4 sm:p-5 flex items-center gap-4">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-teal-800/50 border border-teal-700 flex items-center justify-center text-4xl sm:text-5xl shrink-0">🎫</div>
                    <div class="min-w-0 flex-1">
                        <h3 class="font-extrabold text-white leading-snug" x-text="ticket.name"></h3>
                        <p class="text-[10px] sm:text-xs text-teal-300">Sisa stok: <span x-text="ticket.stock"></span></p>
                        <p class="text-lg sm:text-xl font-extrabold text-green-400 mt-1" x-text="formatIDR(ticket.price)"></p>
                    </div>
                    <div class="shrink-0 flex flex-col items-center gap-1.5">
                        <div class="flex items-center border border-teal-800 rounded-xl bg-teal-950/60">
                            <button type="button" class="w-10 h-10 text-xl text-teal-200 hover:text-white" @click="decQty(ticket)">−</button>
                            <span class="w-10 text-center text-lg font-extrabold text-white" x-text="qtyOf(ticket.id)"></span>
                            <button type="button" class="w-10 h-10 text-xl text-teal-200 hover:text-white" @click="incQty(ticket)">+</button>
                        </div>
                        <button type="button" @click="addTicket(ticket)" class="text-[11px] font-bold text-teal-950 bg-teal-400 hover:bg-teal-300 rounded-lg px-4 py-1.5 transition">+ Tambah</button>
                    </div>
                </div>
            </template>
            <p x-show="!loading && tickets.length === 0" x-cloak class="text-center text-teal-500 py-16">Tiket masuk habis.</p>
        </div>

        {{-- Sticky bottom: total + cash/qris --}}
        <div x-show="cartCount > 0" x-cloak
             class="fixed inset-x-0 bottom-16 z-40 bg-teal-900 border-t border-teal-800 shadow-[0_-4px_20px_rgba(0,0,0,0.4)] px-3 sm:px-4 py-2 flex items-center gap-2">
            <div class="min-w-0 flex-1">
                <p class="text-[10px] uppercase font-bold text-teal-300">Total &middot; <span x-text="cartCount"></span> tiket</p>
                <p class="text-lg sm:text-2xl font-extrabold text-green-400" x-text="formatIDR(total)"></p>
            </div>
            <button type="button" @click="pay('cash')" :disabled="sending"
                    class="shrink-0 bg-green-600 text-white font-extrabold rounded-xl sm:rounded-2xl px-3 sm:px-5 py-3 sm:py-3.5 hover:bg-green-500 transition flex flex-col items-center leading-tight">
                <span class="text-lg">💵</span>
                <span class="text-[9px] sm:text-[10px] uppercase tracking-wide">TUNAI</span>
            </button>
            <button type="button" @click="pay('qris')" :disabled="sending"
                    class="shrink-0 bg-blue-600 text-white font-extrabold rounded-xl sm:rounded-2xl px-3 sm:px-5 py-3 sm:py-3.5 hover:bg-blue-500 transition flex flex-col items-center leading-tight">
                <span class="text-lg">📱</span>
                <span class="text-[9px] sm:text-[10px] uppercase tracking-wide">QRIS</span>
            </button>
        </div>
        <div x-show="cartCount > 0" x-cloak class="h-20 shrink-0"></div>
    </main>

    {{-- Bottom navigation --}}
    @include('checkin.partials.bottomnav')
    <div class="h-16 shrink-0"></div>
</div>

<script>
function tiketPos() {
    return {
        tickets: [],
        loading: true,
        cart: {},
        cartCount: 0,
        total: 0,
        sending: false,
        errorMessage: '',
        receipt: null,

        async init() {
            try {
                const res = await fetch('/api/pos/products', { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                this.tickets = (data.data ?? []).filter(p => p.category === 'tiket_masuk');
            } catch (e) {
                this.errorMessage = 'Gagal memuat tiket.';
            } finally {
                this.loading = false;
            }
        },

        qtyOf(id) { return this.cart[id]?.qty ?? 0; },

        addTicket(ticket) {
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
