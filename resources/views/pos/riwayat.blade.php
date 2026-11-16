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
<body class="bg-gray-100 text-gray-800 antialiased">

<div class="min-h-screen flex flex-col" x-data="riwayatPage()" x-init="load()">

    <header class="bg-white border-b border-gray-200 sticky top-0 z-20">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 py-3 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                <a href="{{ auth()->user()->role === 'kasir_makanan' ? route('kasir.makanan.index') : route('pos.officer.index') }}"
                   class="w-9 h-9 rounded-lg border border-gray-200 bg-white flex items-center justify-center text-gray-600 hover:bg-gray-50 hover:text-green-700 transition shrink-0" aria-label="Kembali">←</a>
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-green-700">Riwayat Operasional</p>
                    <h1 class="font-bold text-sm sm:text-lg text-gray-900 leading-tight truncate">Riwayat Penjualan</h1>
                    <p class="text-[10px] sm:text-xs text-gray-500 truncate">{{ auth()->user()->name }} &middot; {{ auth()->user()->role === 'kasir_makanan' ? 'Kasir Makanan' : 'Kasir' }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2 text-xs font-medium shrink-0">
                <span class="hidden sm:inline rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-gray-600" x-text="periodLabel()"></span>
                <span class="inline-flex items-center gap-1.5 rounded-full border border-green-200 bg-green-50 px-2.5 py-1 text-green-800"><span class="h-1.5 w-1.5 rounded-full bg-green-500"></span> Aktif</span>
            </div>
        </div>
    </header>

    <main class="flex-1 w-full max-w-7xl mx-auto px-3 sm:px-6 py-4 sm:py-6 space-y-4">

        {{-- Filter --}}
        <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 sm:p-4 space-y-3">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-[10px] uppercase font-bold text-gray-400 mr-0.5">Periode</span>
                <button type="button" @click="setPeriod(0)" class="text-[10px] sm:text-xs font-bold rounded-full px-3 py-1.5 border transition" :class="chipClass(isPeriod(0))">Hari Ini</button>
                <button type="button" @click="setPeriod(6)" class="text-[10px] sm:text-xs font-bold rounded-full px-3 py-1.5 border transition" :class="chipClass(isPeriod(6))">7 Hari</button>
                <button type="button" @click="setPeriod(29)" class="text-[10px] sm:text-xs font-bold rounded-full px-3 py-1.5 border transition" :class="chipClass(isPeriod(29))">30 Hari</button>
                <div class="flex items-center gap-1.5 w-full sm:w-auto sm:ml-auto">
                    <input type="date" x-model="from" @change="load()" class="min-w-0 flex-1 sm:flex-none bg-gray-50 border border-gray-200 rounded-lg px-2 py-1.5 text-xs text-gray-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                    <span class="text-gray-300 text-xs shrink-0">s/d</span>
                    <input type="date" x-model="to" @change="load()" class="min-w-0 flex-1 sm:flex-none bg-gray-50 border border-gray-200 rounded-lg px-2 py-1.5 text-xs text-gray-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2 pt-2.5 border-t border-gray-100">
                <span class="text-[10px] uppercase font-bold text-gray-400 mr-0.5">Metode</span>
                <template x-for="m in ['all', 'cash', 'qris', 'transfer']" :key="m">
                    <button type="button" @click="methodFilter = m"
                            class="text-[10px] sm:text-xs font-bold rounded-full px-3 py-1.5 border transition"
                            :class="methodFilter === m ? methodChipActive(m) : 'bg-white text-gray-500 border-gray-200 hover:border-gray-300 hover:text-gray-700'"
                            x-text="methodChipLabel(m)"></button>
                </template>
            </div>
        </section>

        {{-- Summary cards --}}
        <section class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3">
            <div class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm sm:p-4">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-[11px] font-medium text-gray-500 sm:text-xs">Tunai</p>
                        <p class="mt-1 text-lg font-bold tabular-nums text-green-800 sm:text-xl" x-text="formatIDR(summary.cash?.total)"></p>
                        <p class="text-[10px] text-gray-400 mt-0.5"><span x-text="summary.cash?.count ?? 0"></span> trx · kotak kasir</p>
                    </div>
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-green-50 text-base ring-1 ring-green-100 sm:h-10 sm:w-10">💵</span>
                </div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm sm:p-4">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-[11px] font-medium text-gray-500 sm:text-xs">QRIS</p>
                        <p class="mt-1 text-lg font-bold tabular-nums text-blue-700 sm:text-xl" x-text="formatIDR(summary.qris?.total)"></p>
                        <p class="text-[10px] text-gray-400 mt-0.5"><span x-text="summary.qris?.count ?? 0"></span> trx · masuk bank</p>
                    </div>
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-base ring-1 ring-blue-100 sm:h-10 sm:w-10">📱</span>
                </div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm sm:p-4">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-[11px] font-medium text-gray-500 sm:text-xs">Transfer</p>
                        <p class="mt-1 text-lg font-bold tabular-nums text-indigo-700 sm:text-xl" x-text="formatIDR(summary.transfer?.total)"></p>
                        <p class="text-[10px] text-gray-400 mt-0.5"><span x-text="summary.transfer?.count ?? 0"></span> trx · masuk bank</p>
                    </div>
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-base ring-1 ring-indigo-100 sm:h-10 sm:w-10">🏦</span>
                </div>
            </div>
            <div class="rounded-xl border border-green-200 bg-green-50/70 p-3 shadow-sm sm:p-4">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-[11px] font-medium text-green-700 sm:text-xs">Total Pendapatan</p>
                        <p class="mt-1 text-lg font-extrabold tabular-nums text-green-900 sm:text-xl" x-text="formatIDR(summary.total_amount)"></p>
                        <p class="text-[10px] text-green-700/80 mt-0.5"><span x-text="summary.total_transactions ?? 0"></span> transaksi</p>
                    </div>
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-base ring-1 ring-green-100 sm:h-10 sm:w-10">💰</span>
                </div>
            </div>
        </section>

        {{-- Tren + Terlaris --}}
        <section class="grid lg:grid-cols-5 gap-3 sm:gap-4">
            <div class="lg:col-span-3 bg-white rounded-xl shadow-sm border border-gray-200 p-3 sm:p-4 flex flex-col min-h-0">
                <div class="flex shrink-0 flex-wrap items-center justify-between gap-2 border-b border-gray-100 pb-2.5 sm:pb-3">
                    <div>
                        <h2 class="text-sm font-bold text-gray-800 sm:text-base">Tren Harian</h2>
                        <p class="text-[11px] text-gray-500 sm:text-xs">Pendapatan per hari pada periode terpilih</p>
                    </div>
                    <span class="text-[10px] sm:text-[11px] font-medium text-gray-500" x-show="dailyChart.length" x-text="dailyChart.length ? (formatDay(dailyChart[0].date) + ' – ' + formatDay(dailyChart[dailyChart.length - 1].date)) : ''"></span>
                </div>
                <div class="relative mt-2 min-h-[220px] flex-1 sm:mt-3 sm:min-h-[260px]">
                    <canvas x-ref="chart" x-show="!loading && dailyChart.length > 0"></canvas>
                    <div x-show="!loading && dailyChart.length === 0" class="absolute inset-0 flex items-center justify-center text-sm text-gray-400">Belum ada data tren pada periode ini.</div>
                    <div x-show="loading" class="absolute inset-0 flex items-center justify-center text-sm text-gray-400">Memuat...</div>
                </div>
            </div>

            <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 p-3 sm:p-4 flex flex-col min-h-0">
                <div class="flex shrink-0 items-center justify-between gap-2 border-b border-gray-100 pb-2.5 sm:pb-3">
                    <div>
                        <h2 class="text-sm font-bold text-gray-800 sm:text-base">Item Terlaris</h2>
                        <p class="text-[11px] text-gray-500 sm:text-xs">Berdasarkan nilai penjualan</p>
                    </div>
                </div>
                <div class="mt-2 flex-1 space-y-3 sm:mt-3" x-show="!loading && itemsSold.length > 0">
                    <template x-for="(item, i) in itemsSold.slice(0, 6)" :key="i">
                        <div class="flex items-center gap-2.5">
                            <span class="w-7 h-7 shrink-0 rounded-full border border-amber-100 bg-amber-50 flex items-center justify-center text-xs font-bold text-amber-700" x-text="['🥇', '🥈', '🥉'][i] ?? (i + 1)"></span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-xs font-semibold text-gray-800 truncate" x-text="item.name"></p>
                                    <p class="text-[10px] text-gray-500 shrink-0" x-text="'×' + item.qty + ' · ' + formatIDR(item.subtotal)"></p>
                                </div>
                                <div class="h-1.5 bg-gray-100 rounded-full mt-1 overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-green-600 to-emerald-400 rounded-full" :style="'width:' + sharePct(item) + '%'"></div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                <div x-show="!loading && itemsSold.length === 0" class="mt-3 text-sm text-gray-400">Belum ada item terjual pada periode ini.</div>
            </div>
        </section>

        {{-- Loading / error --}}
        <div x-show="loading" x-cloak class="flex items-center justify-center gap-3 py-10 text-gray-400">
            <svg class="animate-spin h-6 w-6 text-green-700" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            Memuat riwayat...
        </div>
        <div x-show="!loading && error" x-cloak class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700" x-text="error"></div>

        {{-- Daftar transaksi --}}
        <section x-show="!loading && filtered.length > 0" x-cloak class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-4 sm:px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-bold text-gray-800 text-sm sm:text-base">Daftar Transaksi</h2>
                <span class="text-xs font-semibold bg-gray-100 text-gray-600 rounded-full px-2.5 py-1" x-text="filtered.length + ' transaksi' + (methodFilter !== 'all' ? ' · ' + methodLabel(methodFilter) : '')"></span>
            </div>

            <div class="overflow-x-auto">
                <table class="rtable w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-gray-400 border-b border-gray-100 bg-gray-50/60">
                            <th class="py-2.5 pl-4 sm:pl-5 pr-4 font-semibold">Waktu</th>
                            <th class="py-2.5 pr-4 font-semibold">Item Terjual</th>
                            <th class="py-2.5 pr-4 text-center font-semibold">Metode</th>
                            <th class="py-2.5 pr-4 sm:pr-5 text-right font-semibold">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="trx in filtered" :key="trx.id">
                            <tr class="hover:bg-gray-50 align-top">
                                <td class="py-3 pl-4 sm:pl-5 pr-4 sm:whitespace-nowrap" data-label="Waktu">
                                    <div class="text-right sm:text-left">
                                        <p class="text-xs font-semibold text-gray-700" x-text="formatTime(trx.created_at)"></p>
                                        <p class="text-[10px] text-gray-400">#<span x-text="trx.id"></span></p>
                                    </div>
                                </td>
                                <td class="py-3 pr-4" data-label="Item Terjual">
                                    <ul class="space-y-1 w-full sm:w-auto">
                                        <template x-for="(item, i) in trx.items" :key="i">
                                            <li class="flex flex-wrap items-start sm:items-center gap-x-1.5 gap-y-0.5 text-xs text-gray-600 leading-snug justify-end sm:justify-start text-right sm:text-left">
                                                <span x-text="itemIcon(item.kind)"></span>
                                                <span class="font-bold text-gray-700" x-text="item.quantity + '×'"></span>
                                                <span class="break-words sm:truncate max-w-[14rem]" x-text="item.name"></span>
                                                <span class="text-gray-400 shrink-0" x-text="'· ' + formatIDR(item.subtotal)"></span>
                                            </li>
                                        </template>
                                    </ul>
                                </td>
                                <td class="py-3 pr-4 text-center" data-label="Metode">
                                    <span class="text-[10px] font-bold rounded-full px-2.5 py-1" :class="methodBadge(trx.payment_method)" x-text="methodLabel(trx.payment_method)"></span>
                                </td>
                                <td class="py-3 pr-4 sm:pr-5 text-right font-extrabold text-gray-800 sm:whitespace-nowrap" data-label="Total" x-text="formatIDR(trx.total_amount)"></td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-gray-200 bg-gray-50 font-extrabold">
                            <td colspan="4" class="py-3 px-4 sm:px-5">
                                <div class="flex items-center justify-between gap-3 w-full">
                                    <span class="text-gray-500 text-xs uppercase tracking-wide" x-text="'Total ' + (methodFilter !== 'all' ? methodLabel(methodFilter) : 'Periode')"></span>
                                    <span class="text-green-800 text-base" x-text="formatIDR(filteredTotal)"></span>
                                </div>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>

        {{-- Kosong --}}
        <div x-show="!loading && transactions.length === 0 && !error" x-cloak class="bg-white rounded-xl shadow-sm border border-gray-200 py-16 text-center">
            <div class="text-5xl mb-2">📭</div>
            <p class="font-semibold text-gray-700">Belum ada penjualan</p>
            <p class="text-sm text-gray-400 mt-1">Belum ada transaksi pada periode <span x-text="formatDay(from)"></span> – <span x-text="formatDay(to)"></span>.</p>
            <p class="text-xs text-gray-400 mt-3">Coba pilih periode 7 atau 30 hari.</p>
        </div>
        <div x-show="!loading && transactions.length > 0 && filtered.length === 0" x-cloak class="bg-white rounded-xl shadow-sm border border-gray-200 py-10 text-center">
            <div class="text-4xl mb-2">🔍</div>
            <p class="font-semibold text-gray-700 text-sm">Tidak ada transaksi dengan metode ini</p>
            <p class="text-xs text-gray-400 mt-1">Coba ganti filter metode pembayaran.</p>
        </div>

        <p class="text-center text-[10px] text-gray-400 pb-2">Menampilkan transaksi atas nama <span class="font-semibold">{{ auth()->user()->name }}</span> saja.</p>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
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
        chart: null,

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
                this.$nextTick(() => this.renderChart());
            } catch (e) {
                this.error = e.message || 'Gagal memuat riwayat.';
            } finally {
                this.loading = false;
            }
        },

        renderChart() {
            const canvas = this.$refs.chart;
            if (!canvas) return;
            if (this.chart) {
                this.chart.destroy();
                this.chart = null;
            }
            if (!this.dailyChart.length) return;
            const ctx = canvas.getContext('2d');
            this.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: this.dailyChart.map((d) => formatDay(d.date)),
                    datasets: [{
                        label: 'Pendapatan',
                        data: this.dailyChart.map((d) => d.total),
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16,185,129,0.10)',
                        tension: 0.35,
                        fill: true,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#111827',
                            titleColor: '#f9fafb',
                            bodyColor: '#e5e7eb',
                            padding: 10,
                            cornerRadius: 8,
                            callbacks: {
                                label: (ctx) => ' Rp ' + Math.round(ctx.parsed.y || 0).toLocaleString('id-ID'),
                            },
                        },
                    },
                    scales: {
                        x: { ticks: { color: '#6b7280', font: { size: 10 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 10 }, grid: { color: 'rgba(17,24,39,0.06)' } },
                        y: { beginAtZero: true, ticks: { color: '#6b7280', font: { size: 10 }, callback: (v) => 'Rp ' + Math.round(v).toLocaleString('id-ID') }, grid: { color: 'rgba(17,24,39,0.06)' } },
                    },
                },
            });
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
            return { cash: 'TUNAI', qris: 'QRIS', transfer: 'TRANSFER' }[m] ?? String(m).toUpperCase();
        },

        methodBadge(m) {
            return {
                cash: 'bg-green-100 text-green-800',
                qris: 'bg-blue-100 text-blue-800',
                transfer: 'bg-indigo-100 text-indigo-800',
            }[m] ?? 'bg-gray-100 text-gray-600';
        },

        methodChipLabel(m) {
            return { all: 'Semua', cash: '💵 Tunai', qris: '📱 QRIS', transfer: '🏦 Transfer' }[m] ?? m;
        },

        methodChipActive(m) {
            return {
                all: 'bg-gray-800 text-white border-gray-800',
                cash: 'bg-green-700 text-white border-green-700',
                qris: 'bg-blue-600 text-white border-blue-600',
                transfer: 'bg-indigo-600 text-white border-indigo-600',
            }[m] ?? 'bg-gray-800 text-white border-gray-800';
        },

        chipClass(active) {
            return active
                ? 'bg-green-700 text-white border-green-700'
                : 'bg-white text-gray-600 border-gray-200 hover:border-green-300 hover:text-green-700';
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
