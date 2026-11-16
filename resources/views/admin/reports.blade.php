@extends('layouts.admin')
@section('page-title', 'Laporan Terpadu')

@push('head')
<style>
    @keyframes fadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .anim-fade-up { opacity: 0; animation: fadeUp .5s ease-out forwards; }
</style>
@endpush

@section('content')
<div x-data="reportsPage()" x-init="load()" class="space-y-4 sm:space-y-5">
    {{-- Header + filter --}}
    <div class="anim-fade-up flex flex-wrap items-end justify-between gap-3" style="animation-delay:0ms;">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-green-700">Laporan Keuangan</p>
            <h1 class="mt-0.5 text-xl font-bold text-gray-900 sm:text-2xl">Laporan Terpadu</h1>
            <p class="text-xs text-gray-500 sm:text-sm">Online (reservasi) & POS/offline per periode</p>
        </div>
        <div class="flex items-center gap-2 print:hidden">
            <button @click="exportCsv()" :disabled="transactions.length === 0" class="rounded-xl border border-green-200 bg-green-50 px-4 py-2 text-sm font-semibold text-green-800 transition hover:bg-green-100 disabled:opacity-50">⬇️ Export CSV</button>
            <button @click="printReport()" class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">🖨️ Cetak</button>
        </div>
    </div>

    <div class="anim-fade-up rounded-xl border border-gray-200 bg-white p-3 shadow-sm sm:p-4 print:hidden" style="animation-delay:60ms;">
        <div class="flex flex-wrap items-end gap-2 sm:gap-3">
            <div>
                <label for="from" class="text-xs font-semibold text-gray-500">Dari Tanggal</label>
                <input id="from" type="date" x-model="from" class="mt-1 rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
            </div>
            <div>
                <label for="to" class="text-xs font-semibold text-gray-500">Sampai Tanggal</label>
                <input id="to" type="date" x-model="to" class="mt-1 rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
            </div>
            <div class="flex flex-wrap gap-1.5">
                <button type="button" @click="setDate('today')" class="rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-600 hover:border-green-300 hover:text-green-700">Hari Ini</button>
                <button type="button" @click="setDate('week')" class="rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-600 hover:border-green-300 hover:text-green-700">Minggu Ini</button>
                <button type="button" @click="setDate('month')" class="rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-600 hover:border-green-300 hover:text-green-700">Bulan Ini</button>
            </div>
            <button @click="load()" :disabled="loading" class="rounded-xl bg-green-700 px-5 py-2 text-sm font-bold text-white transition hover:bg-green-800 disabled:opacity-60">🔍 Tampilkan</button>
            <span class="text-xs text-gray-400 sm:ml-auto" x-text="'Periode: ' + from + ' s/d ' + to"></span>
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="grid grid-cols-2 gap-2 sm:gap-3 lg:grid-cols-4">
        <div class="anim-fade-up rounded-xl border border-green-200 bg-green-50/70 p-3 shadow-sm sm:p-4" style="animation-delay:120ms;">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-green-700">Total Pendapatan</p>
            <p class="mt-1 text-lg font-extrabold tabular-nums text-green-900 sm:text-2xl" x-text="formatIDR(summary.total_revenue)"></p>
            <p class="text-[11px] text-green-700/80"><span x-text="summary.total_transactions ?? 0"></span> transaksi</p>
        </div>
        <div class="anim-fade-up rounded-xl border border-gray-200 bg-white p-3 shadow-sm sm:p-4" style="animation-delay:170ms;">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-amber-700">Online (Reservasi)</p>
            <p class="mt-1 text-lg font-bold tabular-nums text-amber-800 sm:text-xl" x-text="formatIDR(summary.online_revenue)"></p>
            <p class="text-[11px] text-gray-400"><span x-text="summary.online_transactions ?? 0"></span> transaksi</p>
        </div>
        <div class="anim-fade-up rounded-xl border border-gray-200 bg-white p-3 shadow-sm sm:p-4" style="animation-delay:220ms;">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-fuchsia-700">POS / Offline</p>
            <p class="mt-1 text-lg font-bold tabular-nums text-fuchsia-800 sm:text-xl" x-text="formatIDR(summary.offline_revenue)"></p>
            <p class="text-[11px] text-gray-400"><span x-text="summary.pos_transactions ?? 0"></span> transaksi</p>
        </div>
        <div class="anim-fade-up rounded-xl border border-gray-200 bg-white p-3 shadow-sm sm:p-4" style="animation-delay:270ms;">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-600">Jumlah Transaksi</p>
            <p class="mt-1 text-lg font-bold tabular-nums text-gray-900 sm:text-xl" x-text="(summary.total_transactions ?? 0).toLocaleString('id-ID')"></p>
            <p class="text-[11px] text-gray-400">dalam periode</p>
        </div>
    </div>

    {{-- Trend + breakdown --}}
    <div class="grid grid-cols-1 gap-3 sm:gap-4 xl:grid-cols-3">
        <div class="anim-fade-up flex min-h-0 flex-col rounded-xl border border-gray-200 bg-white p-3 shadow-sm sm:p-4 xl:col-span-2" style="animation-delay:320ms;">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 pb-2.5 sm:pb-3">
                <div>
                    <h2 class="text-sm font-bold text-gray-800 sm:text-base">Tren Pendapatan</h2>
                    <p class="text-[11px] text-gray-500 sm:text-xs">Online vs offline per hari</p>
                </div>
                <div class="flex items-center gap-3 text-[10px] font-medium text-gray-600 sm:text-[11px]">
                    <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-amber-500"></span>Online</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-fuchsia-500"></span>Offline</span>
                </div>
            </div>
            <div class="relative mt-2 min-h-[260px] flex-1 sm:mt-3 sm:min-h-[300px]">
                <canvas x-ref="chart"></canvas>
                <div x-show="loading" class="absolute inset-0 flex items-center justify-center text-sm text-gray-400">Memuat grafik...</div>
            </div>
        </div>

        <div class="anim-fade-up flex min-h-0 flex-col rounded-xl border border-gray-200 bg-white p-3 shadow-sm sm:p-4" style="animation-delay:380ms;">
            <div class="border-b border-gray-100 pb-2.5 sm:pb-3">
                <h2 class="text-sm font-bold text-gray-800 sm:text-base">Rincian Pendapatan</h2>
                <p class="text-[11px] text-gray-500 sm:text-xs">Offline per metode & kasir</p>
            </div>
            <div class="mt-2 rounded-lg border border-amber-100 bg-amber-50/60 p-2.5 sm:mt-3">
                <div class="flex items-center justify-between gap-2"><p class="text-xs font-semibold text-amber-800">Online (Reservasi)</p><p class="text-sm font-bold tabular-nums text-amber-900" x-text="formatIDR(summary.online_revenue)"></p></div>
            </div>
            <div class="mt-2 flex min-h-0 flex-1 flex-col rounded-lg border border-gray-100 bg-gray-50/50 p-2.5 sm:mt-3">
                <div class="flex items-center justify-between gap-2"><p class="text-xs font-semibold text-gray-800">Offline (POS/Kasir)</p><p class="text-sm font-bold tabular-nums text-fuchsia-700" x-text="formatIDR(summary.offline_revenue)"></p></div>
                <div class="mt-2 grid grid-cols-3 gap-1.5 text-[10px] sm:text-[11px]">
                    <template x-for="key in ['cash','qris','transfer']" :key="key">
                        <div class="rounded-md border bg-white p-1.5 sm:p-2" :class="key==='cash'?'border-green-100':(key==='qris'?'border-blue-100':'border-indigo-100')">
                            <p class="font-medium" :class="key==='cash'?'text-green-700':(key==='qris'?'text-blue-700':'text-indigo-700')" x-text="key==='cash'?'Tunai':(key==='qris'?'QRIS':'Transfer')"></p>
                            <p class="mt-0.5 font-bold tabular-nums text-gray-900" x-text="formatIDR(methodMap[key]?.total_amount || 0)"></p>
                            <p class="text-[10px] text-gray-400"><span x-text="methodMap[key]?.transactions || 0"></span> trx</p>
                        </div>
                    </template>
                </div>
                <div class="mt-2 min-h-0 flex-1 overflow-auto">
                    <table class="w-full text-left text-[11px] sm:text-xs">
                        <thead class="sticky top-0 bg-gray-50 text-gray-500"><tr class="border-b border-gray-200"><th class="py-1.5 pr-2 font-semibold">Kasir</th><th class="py-1.5 pr-2 text-right font-semibold">Trx</th><th class="py-1.5 text-right font-semibold">Pendapatan</th></tr></thead>
                        <tbody class="divide-y divide-gray-100 text-gray-700">
                            <template x-if="!cashiers.length"><tr><td colspan="3" class="py-2 text-gray-400">Belum ada transaksi offline.</td></tr></template>
                            <template x-for="c in cashiers" :key="c.user_id">
                                <tr>
                                    <td class="py-1.5 pr-2 font-medium text-gray-800"><span x-text="c.name"></span> <span class="text-gray-400" x-text="c.role ? '('+c.role+')' : ''"></span></td>
                                    <td class="py-1.5 pr-2 text-right tabular-nums" x-text="(c.transactions||0).toLocaleString('id-ID')"></td>
                                    <td class="py-1.5 text-right font-semibold tabular-nums text-fuchsia-700" x-text="formatIDR(c.total_amount)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Transactions table --}}
    <div id="printable" class="anim-fade-up overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm" style="animation-delay:440ms;">
        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 sm:px-5">
            <div>
                <h2 class="text-sm font-bold text-gray-800 sm:text-base">Transaksi Terpadu</h2>
                <p class="text-[11px] text-gray-500 sm:text-xs">Periode <span x-text="from"></span> s/d <span x-text="to"></span> · Online + POS</p>
            </div>
            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600" x-text="transactions.length + ' baris'"></span>
        </div>

        <div x-show="loading" x-cloak class="flex items-center justify-center gap-3 py-14 text-gray-400">
            <svg class="h-6 w-6 animate-spin text-green-700" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
            Memuat laporan...
        </div>
        <div x-show="!loading && transactions.length === 0" x-cloak class="py-14 text-center text-gray-400"><div class="mb-2 text-4xl">📭</div>Tidak ada transaksi pada periode ini.</div>

        <div x-show="!loading && transactions.length > 0" x-cloak class="overflow-x-auto">
            <table class="rtable w-full text-sm min-w-[860px]">
                <thead>
                    <tr class="border-b bg-gray-50/60 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="py-2.5 pl-4 sm:pl-5 pr-4 font-semibold">Tanggal</th>
                        <th class="py-2.5 pr-4 font-semibold">Sumber</th>
                        <th class="py-2.5 pr-4 font-semibold">Referensi</th>
                        <th class="py-2.5 pr-4 font-semibold">Pelanggan/Kasir</th>
                        <th class="py-2.5 pr-4 font-semibold">Deskripsi</th>
                        <th class="py-2.5 pr-4 font-semibold">Metode</th>
                        <th class="py-2.5 pr-4 sm:pr-5 text-right font-semibold">Jumlah</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-for="(t, i) in transactions" :key="i">
                        <tr class="hover:bg-gray-50">
                            <td data-label="Tanggal" class="whitespace-nowrap py-2.5 pl-4 sm:pl-5 pr-4 text-gray-600" x-text="formatDate(t.date)"></td>
                            <td data-label="Sumber" class="py-2.5 pr-4"><span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold" :class="t.source === 'online' ? 'bg-amber-100 text-amber-800' : 'bg-fuchsia-100 text-fuchsia-800'" x-text="t.source === 'online' ? 'ONLINE' : 'POS'"></span></td>
                            <td data-label="Referensi" class="py-2.5 pr-4 font-mono text-xs font-semibold text-gray-700" x-text="t.reference"></td>
                            <td data-label="Pelanggan/Kasir" class="py-2.5 pr-4 text-gray-700" x-text="t.customer ?? '-'"></td>
                            <td data-label="Deskripsi" class="max-w-56 truncate py-2.5 pr-4 text-gray-500" :title="t.description" x-text="t.description ?? '-'"></td>
                            <td data-label="Metode" class="py-2.5 pr-4">
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase" :class="methodBadge(t.method)" x-text="methodLabel(t.method)"></span>
                                <div class="text-[10px] text-gray-400 mt-0.5" x-show="t.provider_ref" x-text="t.provider_ref"></div>
                            </td>
                            <td data-label="Jumlah" class="whitespace-nowrap py-2.5 pr-4 sm:pr-5 text-right font-semibold text-gray-800" x-text="formatIDR(t.amount)"></td>
                        </tr>
                    </template>
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-gray-200 bg-gray-50 font-extrabold">
                        <td colspan="6" class="py-3 pl-4 sm:pl-5 pr-4 text-right text-xs uppercase tracking-wide text-gray-500">Total Periode</td>
                        <td class="py-3 pr-4 sm:pr-5 text-right text-base text-green-800" x-text="formatIDR(summary.total_revenue)"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<style>
@media print {
    body * { visibility: hidden; }
    #printable, #printable * { visibility: visible; }
    #printable { position: absolute; top: 0; left: 0; right: 0; border: none; box-shadow: none; }
    aside, header, .print\\:hidden { display: none !important; }
    table { font-size: 11px; }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
function reportsPage() {
    return {
        from: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
        to: new Date().toISOString().split('T')[0],
        summary: {},
        transactions: [],
        daily: { labels: [], online_revenue: [], offline_revenue: [] },
        methodMap: { cash: { total_amount: 0, transactions: 0 }, qris: { total_amount: 0, transactions: 0 }, transfer: { total_amount: 0, transactions: 0 } },
        cashiers: [],
        loading: true,
        chart: null,

        async load() {
            this.loading = true;
            try {
                const res = await fetch(`/api/admin/reports?from=${this.from}&to=${this.to}`, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                this.summary = data.summary || {};
                this.transactions = data.transactions || [];
                this.daily = data.daily || { labels: [], online_revenue: [], offline_revenue: [] };
                const methods = Array.isArray(data.offline_by_method) ? data.offline_by_method : [];
                const map = { cash: { total_amount: 0, transactions: 0 }, qris: { total_amount: 0, transactions: 0 }, transfer: { total_amount: 0, transactions: 0 } };
                methods.forEach((m) => { map[m.method] = m; });
                this.methodMap = map;
                this.cashiers = Array.isArray(data.offline_by_cashier) ? data.offline_by_cashier : [];
                this.$nextTick(() => this.renderChart());
            } catch (e) {
                this.summary = {}; this.transactions = []; this.cashiers = [];
            } finally {
                this.loading = false;
            }
        },

        renderChart() {
            const canvas = this.$refs.chart;
            if (!canvas) return;
            if (this.chart) { this.chart.destroy(); this.chart = null; }
            const labels = this.daily.labels || [];
            const ctx = canvas.getContext('2d');
            this.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        { label: 'Online', data: this.daily.online_revenue || [], borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,0.08)', tension: 0.35, fill: true, pointRadius: 2, pointHoverRadius: 4 },
                        { label: 'Offline', data: this.daily.offline_revenue || [], borderColor: '#d946ef', backgroundColor: 'rgba(217,70,239,0.08)', tension: 0.35, fill: true, pointRadius: 2, pointHoverRadius: 4 },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 900, easing: 'easeOutQuart' },
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { display: false }, tooltip: { backgroundColor: '#111827', titleColor: '#f9fafb', bodyColor: '#e5e7eb', padding: 10, cornerRadius: 8 } },
                    scales: {
                        x: { ticks: { color: '#6b7280', font: { size: 10 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 10 }, grid: { color: 'rgba(17,24,39,0.06)' } },
                        y: { beginAtZero: true, ticks: { color: '#6b7280', font: { size: 10 }, callback: (v) => 'Rp ' + Math.round(v).toLocaleString('id-ID') }, grid: { color: 'rgba(17,24,39,0.06)' } },
                    },
                },
            });
            let offset = 0;
            const flow = () => {
                if (!this.chart) return;
                offset -= 0.6;
                this.chart.data.datasets.forEach((d) => { d.borderDash = [6, 4]; d.borderDashOffset = offset; });
                this.chart.update('none');
                requestAnimationFrame(flow);
            };
            requestAnimationFrame(flow);
        },

        setDate(period) {
            const today = new Date();
            const from = new Date(today);
            const to = new Date(today);
            if (period === 'today') {
                // today
            } else if (period === 'week') {
                const day = today.getDay() || 7;
                from.setDate(today.getDate() - day + 1);
                to.setDate(from.getDate() + 6);
            } else if (period === 'month') {
                from.setDate(1);
                to.setMonth(today.getMonth() + 1, 0);
            }
            const iso = (d) => d.toISOString().split('T')[0];
            this.from = iso(from);
            this.to = iso(to);
            this.load();
        },

        formatIDR(value) { return 'Rp ' + Math.round(Number(value) || 0).toLocaleString('id-ID'); },
        formatDate(iso) { if (!iso) return '-'; return new Date(iso + 'T00:00:00').toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }); },
        methodLabel(m) { const map = { cash: 'Tunai', qris: 'QRIS', transfer: 'Transfer', manual_transfer: 'Transfer', gopay: 'GoPay', shopeepay: 'ShopeePay', bank_transfer: 'VA', echannel: 'VA', bca_va: 'BCA VA', bni_va: 'BNI VA', bri_va: 'BRI VA', permata_va: 'Permata VA', other_qris: 'QRIS' }; return map[m] ?? (m ? String(m).replace(/_/g,' ').toUpperCase() : '-'); },
        methodBadge(m) { return { cash: 'bg-green-100 text-green-800', qris: 'bg-blue-100 text-blue-800', transfer: 'bg-indigo-100 text-indigo-800' }[m] ?? 'bg-gray-100 text-gray-600'; },

        exportCsv() {
            const header = ['Tanggal', 'Sumber', 'Referensi', 'Pelanggan/Kasir', 'Deskripsi', 'Metode', 'Jumlah'];
            const rows = this.transactions.map(t => [t.date, t.source === 'online' ? 'ONLINE' : 'POS', t.reference, (t.customer ?? '').replace(/;/g, ','), (t.description ?? '').replace(/;/g, ','), t.method, t.amount]);
            const csv = [header, ...rows].map(row => row.join(';')).join('\n');
            const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url; a.download = `sireteduh-laporan-${this.from}-${this.to}.csv`; a.click();
            URL.revokeObjectURL(url);
        },
        printReport() { window.print(); },
    };
}
</script>
@endsection
