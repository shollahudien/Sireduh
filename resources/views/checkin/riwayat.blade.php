<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Riwayat Penjualan &mdash; SIRESTEDUH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-teal-950 min-h-screen text-gray-100 antialiased">

<div class="min-h-screen flex flex-col" x-data="riwayatPage()" x-init="load()">

    {{-- Header gradient --}}
    <header class="bg-linear-to-r from-teal-800 to-teal-600 text-white shadow-lg shadow-teal-900/20">
        <div class="max-w-5xl mx-auto px-3 sm:px-6 py-3 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-white/15 flex items-center justify-center text-lg sm:text-xl shrink-0">📋</div>
                <div class="min-w-0">
                    <h1 class="font-extrabold text-sm sm:text-lg leading-tight truncate">Riwayat Penjualan</h1>
                    <p class="text-[10px] sm:text-xs text-teal-100/80 truncate">SIRESTEDUH &middot; Petugas Check-in</p>
                </div>
            </div>
            <span class="hidden sm:inline text-[10px] uppercase tracking-widest font-bold bg-white/10 border border-white/20 rounded-full px-3 py-1.5 shrink-0" x-text="periodLabel()"></span>
        </div>
    </header>

    <main class="flex-1 w-full max-w-5xl mx-auto px-3 sm:px-4 py-4 sm:py-6 space-y-4">

        {{-- Filter: periode + metode --}}
        <section class="bg-teal-900/40 border border-teal-800 rounded-2xl p-3 sm:p-4 space-y-3">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-[10px] uppercase font-bold text-teal-400 mr-0.5">Periode</span>
                <button type="button" @click="setPeriod(0)" :class="chipClass(isPeriod(0))">Hari Ini</button>
                <button type="button" @click="setPeriod(6)" :class="chipClass(isPeriod(6))">7 Hari</button>
                <button type="button" @click="setPeriod(29)" :class="chipClass(isPeriod(29))">30 Hari</button>
                <div class="flex items-center gap-1.5 ml-auto">
                    <input type="date" x-model="from" @change="load()" class="bg-teal-950/60 border border-teal-800 rounded-xl px-2 py-1.5 text-xs text-teal-100 focus:outline-none focus:ring-2 focus:ring-teal-400">
                    <span class="text-teal-600 text-xs">s/d</span>
                    <input type="date" x-model="to" @change="load()" class="bg-teal-950/60 border border-teal-800 rounded-xl px-2 py-1.5 text-xs text-teal-100 focus:outline-none focus:ring-2 focus:ring-teal-400">
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2 pt-2.5 border-t border-teal-800">
                <span class="text-[10px] uppercase font-bold text-teal-400 mr-0.5">Metode</span>
                <template x-for="m in ['all', 'cash', 'qris', 'transfer']" :key="m">
                    <button type="button" @click="methodFilter = m"
                            class="text-[10px] sm:text-xs font-bold rounded-full px-3 py-1.5 border transition"
                            :class="methodFilter === m ? methodChipActive(m) : 'bg-teal-950/60 text-teal-300 border-teal-800 hover:border-teal-600 hover:text-teal-100'"
                            x-text="methodChipLabel(m)"></button>
                </template>
            </div>
        </section>

        {{-- Summary cards --}}
        <section class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3">
            <div class="bg-teal-900/40 border border-teal-800 rounded-2xl p-3.5">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="w-8 h-8 rounded-xl bg-teal-800 flex items-center justify-center text-base shrink-0">💵</span>
                    <p class="text-[10px] uppercase font-bold text-teal-400">Tunai</p>
                </div>
                <p class="text-lg sm:text-2xl font-extrabold text-green-400" x-text="formatIDR(summary.cash?.total)"></p>
                <p class="text-[10px] text-teal-500 mt-0.5"><span x-text="summary.cash?.count ?? 0"></span> trx &middot; kotak kasir</p>
            </div>
            <div class="bg-teal-900/40 border border-teal-800 rounded-2xl p-3.5">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="w-8 h-8 rounded-xl bg-teal-800 flex items-center justify-center text-base shrink-0">📱</span>
                    <p class="text-[10px] uppercase font-bold text-teal-400">QRIS</p>
                </div>
                <p class="text-lg sm:text-2xl font-extrabold text-sky-400" x-text="formatIDR(summary.qris?.total)"></p>
                <p class="text-[10px] text-teal-500 mt-0.5"><span x-text="summary.qris?.count ?? 0"></span> trx &middot; masuk bank</p>
            </div>
            <div class="bg-teal-900/40 border border-teal-800 rounded-2xl p-3.5">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="w-8 h-8 rounded-xl bg-teal-800 flex items-center justify-center text-base shrink-0">🏦</span>
                    <p class="text-[10px] uppercase font-bold text-teal-400">Transfer</p>
                </div>
                <p class="text-lg sm:text-2xl font-extrabold text-indigo-400" x-text="formatIDR(summary.transfer?.total)"></p>
                <p class="text-[10px] text-teal-500 mt-0.5"><span x-text="summary.transfer?.count ?? 0"></span> trx &middot; masuk bank</p>
            </div>
            <div class="bg-linear-to-br from-green-700 to-teal-600 rounded-2xl p-3.5 text-white">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="w-8 h-8 rounded-xl bg-white/20 flex items-center justify-center text-base shrink-0">💰</span>
                    <p class="text-[10px] uppercase font-bold text-green-100">Total Pendapatan</p>
                </div>
                <p class="text-lg sm:text-2xl font-extrabold" x-text="formatIDR(summary.total_amount)"></p>
                <p class="text-[10px] text-green-100 mt-0.5"><span x-text="summary.total_transactions ?? 0"></span> transaksi</p>
            </div>
        </section>

        {{-- Grafik harian + Terlaris --}}
        <section class="grid lg:grid-cols-5 gap-2 sm:gap-3">
            <div x-show="!loading && dailyChart.length > 0" x-cloak class="lg:col-span-3 bg-teal-900/40 border border-teal-800 rounded-2xl p-4">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs uppercase font-bold text-teal-400">📊 Tren Harian</p>
                    <span class="text-[10px] text-teal-500" x-text="formatDay(dailyChart[0]?.date) + ' – ' + formatDay(dailyChart[dailyChart.length - 1]?.date)"></span>
                </div>
                <div class="flex items-end gap-1 sm:gap-1.5 h-24">
                    <template x-for="(d, i) in dailyChart" :key="d.date">
                        <div class="flex-1 h-full flex flex-col justify-end min-w-0" :title="formatDay(d.date) + ' · ' + formatIDR(d.total)">
                            <div class="w-full rounded-md bg-linear-to-t from-teal-500 to-teal-300 transition-all"
                                 :class="d.total === maxTotal() ? 'ring-2 ring-teal-300/50' : 'opacity-50'"
                                 :style="'height:' + barHeight(d.total) + '%'"></div>
                        </div>
                    </template>
                </div>
                <div class="flex gap-1 sm:gap-1.5 mt-1.5">
                    <template x-for="(d, i) in dailyChart" :key="'l' + d.date">
                        <div class="flex-1 text-center min-w-0">
                            <p class="text-[8px] sm:text-[9px] truncate"
                               :class="d.total === maxTotal() ? 'font-bold text-teal-200' : 'text-teal-500'"
                               x-show="dailyChart.length <= 10 || i === 0 || i === dailyChart.length - 1"
                               x-text="formatDay(d.date)"></p>
                        </div>
                    </template>
                </div>
            </div>

            <div x-show="!loading && itemsSold.length > 0" x-cloak class="lg:col-span-2 bg-teal-900/40 border border-teal-800 rounded-2xl p-4">
                <p class="text-xs uppercase font-bold text-teal-400 mb-3">🏆 Terlaris</p>
                <div class="space-y-3">
                    <template x-for="(item, i) in itemsSold.slice(0, 5)" :key="i">
                        <div class="flex items-center gap-2.5">
                            <span class="w-7 h-7 shrink-0 rounded-full bg-teal-800 border border-teal-700 flex items-center justify-center text-xs font-bold text-amber-300" x-text="['🥇', '🥈', '🥉'][i] ?? (i + 1)"></span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-xs font-semibold text-teal-100 truncate" x-text="item.name"></p>
                                    <p class="text-[10px] text-teal-500 shrink-0" x-text="'×' + item.qty + ' · ' + formatIDR(item.subtotal)"></p>
                                </div>
                                <div class="h-1.5 bg-teal-950/60 rounded-full mt-1 overflow-hidden">
                                    <div class="h-full bg-linear-to-r from-teal-500 to-teal-300 rounded-full" :style="'width:' + sharePct(item) + '%'"></div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </section>

        {{-- Loading / error --}}
        <div x-show="loading" x-cloak class="flex items-center justify-center gap-3 py-16 text-teal-400">
            <svg class="animate-spin h-6 w-6 text-teal-400" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            Memuat riwayat...
        </div>
        <div x-show="!loading && error" x-cloak class="bg-red-950/50 border border-red-800 rounded-xl px-4 py-3 text-sm text-red-200" x-text="error"></div>

        {{-- Desktop: tabel --}}
        <div x-show="!loading && filtered.length > 0" x-cloak class="hidden md:block bg-teal-900/40 border border-teal-800 rounded-2xl overflow-hidden">
            <div class="px-5 py-3 border-b border-teal-800 flex items-center justify-between">
                <h2 class="font-bold text-teal-100 text-sm">Daftar Transaksi</h2>
                <span class="text-xs font-bold bg-teal-800 text-teal-200 rounded-full px-2.5 py-1" x-text="filtered.length + ' transaksi' + (methodFilter !== 'all' ? ' · ' + methodLabel(methodFilter) : '')"></span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm ">
                    <thead>
                        <tr class="text-left text-xs uppercase text-teal-400 border-b border-teal-800 bg-teal-950/40">
                            <th class="py-2.5 pl-5 pr-4">Waktu</th>
                            <th class="py-2.5 pr-4">Item Terjual</th>
                            <th class="py-2.5 pr-4 text-center">Metode</th>
                            <th class="py-2.5 pr-5 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-teal-800">
                        <template x-for="trx in filtered" :key="trx.id">
                            <tr class="hover:bg-teal-900/60 align-top">
                                <td class="py-3 pl-5 pr-4 whitespace-nowrap">
                                    <p class="text-xs font-semibold text-teal-100" x-text="formatTime(trx.created_at)"></p>
                                    <p class="text-[10px] text-teal-500">#<span x-text="trx.id"></span></p>
                                </td>
                                <td class="py-3 pr-4">
                                    <ul class="space-y-1">
                                        <template x-for="(item, i) in trx.items" :key="i">
                                            <li class="flex items-center gap-1.5 text-xs text-teal-200 leading-snug">
                                                <span x-text="itemIcon(item.kind)"></span>
                                                <span class="font-bold text-teal-300" x-text="item.quantity + '×'"></span>
                                                <span class="truncate" x-text="item.name"></span>
                                                <span class="text-teal-500 shrink-0" x-text="'· ' + formatIDR(item.subtotal)"></span>
                                            </li>
                                        </template>
                                    </ul>
                                </td>
                                <td class="py-3 pr-4 text-center">
                                    <span class="text-[10px] font-bold rounded-full px-2.5 py-1" :class="methodBadge(trx.payment_method)" x-text="methodLabel(trx.payment_method)"></span>
                                </td>
                                <td class="py-3 pr-5 text-right font-extrabold text-teal-100 whitespace-nowrap" x-text="formatIDR(trx.total_amount)"></td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-teal-800 bg-teal-950/60 font-extrabold">
                            <td colspan="3" class="py-3 pl-5 pr-4 text-right text-teal-400 text-xs uppercase tracking-wide" x-text="'Total ' + (methodFilter !== 'all' ? methodLabel(methodFilter) : 'Periode')"></td>
                            <td class="py-3 pr-5 text-right text-green-400 text-base" x-text="formatIDR(filteredTotal)"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Mobile: kartu --}}
        <div x-show="!loading && filtered.length > 0" x-cloak class="md:hidden space-y-2.5">
            <template x-for="trx in filtered" :key="trx.id">
                <div class="bg-teal-900/40 border border-teal-800 rounded-2xl overflow-hidden">
                    <div class="px-4 py-2.5 bg-teal-950/50 border-b border-teal-800 flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-teal-100" x-text="formatTime(trx.created_at)"></p>
                            <p class="text-[10px] text-teal-500">#<span x-text="trx.id"></span></p>
                        </div>
                        <span class="text-[10px] font-bold rounded-full px-2.5 py-1 shrink-0" :class="methodBadge(trx.payment_method)" x-text="methodLabel(trx.payment_method)"></span>
                    </div>
                    <ul class="px-4 py-2.5 space-y-1.5">
                        <template x-for="(item, i) in trx.items" :key="i">
                            <li class="flex items-center gap-1.5 text-xs">
                                <span x-text="itemIcon(item.kind)"></span>
                                <span class="font-bold text-teal-300" x-text="item.quantity + '×'"></span>
                                <span class="text-teal-200 truncate" x-text="item.name"></span>
                                <span class="ml-auto text-teal-500 shrink-0" x-text="formatIDR(item.subtotal)"></span>
                            </li>
                        </template>
                    </ul>
                    <div class="px-4 py-2.5 border-t border-teal-800 flex items-center justify-between">
                        <span class="text-[10px] uppercase font-bold text-teal-400">Total</span>
                        <span class="text-sm font-extrabold text-green-400" x-text="formatIDR(trx.total_amount)"></span>
                    </div>
                </div>
            </template>
        </div>

        {{-- Kosong --}}
        <div x-show="!loading && transactions.length === 0 && !error" x-cloak class="bg-teal-900/40 border border-teal-800 rounded-2xl py-16 text-center">
            <div class="text-5xl mb-2">📭</div>
            <p class="font-semibold text-teal-100">Belum ada penjualan</p>
            <p class="text-sm text-teal-500 mt-1">Belum ada transaksi pada periode <span x-text="formatDay(from)"></span> – <span x-text="formatDay(to)"></span>.</p>
            <p class="text-xs text-teal-500 mt-3">Coba pilih periode 7 atau 30 hari.</p>
        </div>
        <div x-show="!loading && transactions.length > 0 && filtered.length === 0" x-cloak class="bg-teal-900/40 border border-teal-800 rounded-2xl py-10 text-center">
            <div class="text-4xl mb-2">🔍</div>
            <p class="font-semibold text-teal-100 text-sm">Tidak ada transaksi dengan metode ini</p>
            <p class="text-xs text-teal-500 mt-1">Coba ganti filter metode pembayaran.</p>
        </div>

        <p class="text-center text-[10px] text-teal-500 pb-2">Menampilkan transaksi atas nama <span class="font-semibold text-teal-300">{{ auth()->user()->name }}</span> saja.</p>
    </main>

    {{-- Bottom navigation --}}
    @include('checkin.partials.bottomnav')
    <div class="h-16 shrink-0"></div>
</div>

<script>
function riwayatPage() {
    return {
        from: new Date().toISOString().split('T')[0],
        to: new Date().toISOString().split('T')[0],
        summary: {},
        transactions: [],
        itemsSold: [],
        loading: true,
        error: '',
        methodFilter: 'all',

        async load() {
            this.loading = true;
            this.error = '';
            try {
                const res = await fetch(`/api/pos/riwayat?from=${this.from}&to=${this.to}`, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message ?? 'Gagal memuat riwayat.');
                this.summary = data.summary || {};
                this.transactions = data.transactions || [];
                this.itemsSold = data.items_sold || [];
            } catch (e) {
                this.error = e.message || 'Gagal memuat riwayat.';
            } finally {
                this.loading = false;
            }
        },

        setPeriod(daysBack) {
            const iso = (d) => d.toISOString().split('T')[0];
            const now = new Date();
            const past = new Date();
            past.setDate(past.getDate() - daysBack);
            this.from = iso(past);
            this.to = iso(now);
            this.load();
        },

        isPeriod(daysBack) {
            const iso = (d) => d.toISOString().split('T')[0];
            const now = new Date();
            const past = new Date();
            past.setDate(past.getDate() - daysBack);
            return this.from === iso(past) && this.to === iso(now);
        },

        periodLabel() {
            return formatDay(this.from) + ' – ' + formatDay(this.to);
        },

        get filtered() {
            if (this.methodFilter === 'all') return this.transactions;
            return this.transactions.filter((t) => t.payment_method === this.methodFilter);
        },

        get filteredTotal() {
            return this.filtered.reduce((s, t) => s + Number(t.total_amount || 0), 0);
        },

        get dailyChart() {
            const map = {};
            for (const t of this.transactions) {
                const d = (t.created_at || '').slice(0, 10);
                if (!d) continue;
                map[d] = (map[d] || 0) + Number(t.total_amount || 0);
            }
            return Object.keys(map).sort().map((d) => ({ date: d, total: map[d] }));
        },

        maxTotal() {
            return Math.max(1, ...this.dailyChart.map((d) => d.total));
        },

        barHeight(total) {
            return Math.max(8, Math.round((total / this.maxTotal()) * 100));
        },

        sharePct(item) {
            const max = Math.max(1, ...this.itemsSold.map((x) => Number(x.subtotal) || 0));
            return Math.max(4, Math.round((Number(item.subtotal) || 0) / max * 100));
        },

        formatIDR(value) {
            return 'Rp ' + Math.round(Number(value) || 0).toLocaleString('id-ID');
        },

        formatTime(iso) {
            if (!iso) return '-';
            return new Date(iso).toLocaleString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
        },

        methodLabel(m) {
            return { cash: 'TUNAI', qris: 'QRIS', transfer: 'TRANSFER' }[m] ?? m.toUpperCase();
        },

        methodBadge(m) {
            return {
                cash: 'bg-green-800 text-green-200',
                qris: 'bg-sky-800 text-sky-200',
                transfer: 'bg-indigo-800 text-indigo-200',
            }[m] ?? 'bg-teal-800 text-teal-200';
        },

        methodChipLabel(m) {
            return { all: 'Semua', cash: '💵 Tunai', qris: '📱 QRIS', transfer: '🏦 Transfer' }[m] ?? m;
        },

        methodChipActive(m) {
            return {
                all: 'bg-teal-100 text-teal-900 border-teal-100',
                cash: 'bg-green-500 text-white border-green-500',
                qris: 'bg-sky-500 text-white border-sky-500',
                transfer: 'bg-indigo-500 text-white border-indigo-500',
            }[m] ?? 'bg-teal-100 text-teal-900 border-teal-100';
        },

        chipClass(active) {
            return active
                ? 'bg-teal-400 text-teal-950 border-teal-400'
                : 'bg-teal-950/60 text-teal-300 border-teal-800 hover:border-teal-600 hover:text-teal-100';
        },

        itemIcon(kind) {
            return kind === 'sewa' ? '🏕️' : '🛍️';
        },
    };
}

function formatDay(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr + 'T00:00:00').toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
}
</script>
</body>
</html>
