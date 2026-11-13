@extends('layouts.admin')
@section('page-title', 'Laporan Terpadu')

@section('content')
<div x-data="reportsPage()" x-init="load()">

    {{-- Filter + actions --}}
    <div class="flex flex-wrap items-end justify-between gap-3 mb-5 print:hidden">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label for="from" class="text-xs font-semibold text-gray-500">Dari Tanggal</label>
                <input id="from" type="date" x-model="from" class="mt-1 border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
            </div>
            <div>
                <label for="to" class="text-xs font-semibold text-gray-500">Sampai Tanggal</label>
                <input id="to" type="date" x-model="to" class="mt-1 border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
            </div>
            <button @click="load()" :disabled="loading"
                    class="bg-green-700 text-white text-sm font-bold rounded-xl px-5 py-2.5 hover:bg-green-800 transition">
                🔍 Tampilkan
            </button>
        </div>
        <div class="flex gap-2">
            <button @click="exportCsv()" :disabled="transactions.length === 0"
                    class="text-sm font-semibold text-green-800 bg-green-50 hover:bg-green-100 border border-green-200 rounded-xl px-4 py-2.5 transition">
                ⬇️ Export CSV
            </button>
            <button @click="printReport()" class="text-sm font-semibold text-gray-600 bg-white hover:bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 transition">
                🖨️ Cetak
            </button>
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide">Total Pendapatan</p>
            <p class="text-xl xl:text-2xl font-extrabold text-green-800 mt-1" x-text="formatIDR(summary.total_revenue)"></p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide">Online (Transfer)</p>
            <p class="text-xl xl:text-2xl font-extrabold text-blue-700 mt-1" x-text="formatIDR(summary.online_revenue)"></p>
            <p class="text-[11px] text-gray-400" x-text="(summary.online_transactions ?? 0) + ' transaksi'"></p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide">POS (Walk-in)</p>
            <p class="text-xl xl:text-2xl font-extrabold text-amber-600 mt-1" x-text="formatIDR(summary.offline_revenue)"></p>
            <p class="text-[11px] text-gray-400" x-text="(summary.pos_transactions ?? 0) + ' transaksi'"></p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide">Total Transaksi</p>
            <p class="text-xl xl:text-2xl font-extrabold text-gray-800 mt-1" x-text="summary.total_transactions ?? 0"></p>
            <p class="text-[11px] text-gray-400">dalam periode</p>
        </div>
    </div>

    {{-- Unified table --}}
    <div id="printable" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h2 class="font-bold text-gray-800 hidden print:block">SIRESTEDUH — Laporan Pendapatan Terpadu</h2>
                <h2 class="font-bold text-gray-800 print:hidden">📑 Transaksi Terpadu</h2>
                <p class="text-xs text-gray-400">Periode: <span x-text="from"></span> s/d <span x-text="to"></span> · Online + POS</p>
            </div>
            <span class="text-xs font-bold bg-gray-100 text-gray-600 rounded-full px-3 py-1.5 print:hidden" x-text="transactions.length + ' baris'"></span>
        </div>

        <div x-show="loading" x-cloak class="py-16 flex items-center justify-center gap-3 text-gray-400">
            <svg class="animate-spin h-6 w-6 text-green-700" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            Memuat laporan...
        </div>
        <div x-show="!loading && transactions.length === 0" x-cloak class="py-16 text-center text-gray-400">
            <div class="text-4xl mb-2">📭</div>
            Tidak ada transaksi pada periode ini.
        </div>

        <div x-show="!loading && transactions.length > 0" x-cloak class="overflow-x-auto">
            <table class="rtable w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-400 text-xs uppercase tracking-wide border-b">
                        <th class="pb-3 pr-4 pl-6">Tanggal</th>
                        <th class="pb-3 pr-4">Sumber</th>
                        <th class="pb-3 pr-4">Referensi</th>
                        <th class="pb-3 pr-4">Pelanggan / Kasir</th>
                        <th class="pb-3 pr-4">Deskripsi</th>
                        <th class="pb-3 pr-4">Metode</th>
                        <th class="pb-3 pr-6 text-right">Jumlah</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-for="(t, i) in transactions" :key="i">
                        <tr class="hover:bg-gray-50">
                            <td data-label="Tanggal" class="py-3 pr-4 pl-6 whitespace-nowrap text-gray-600" x-text="formatDate(t.date)"></td>
                            <td data-label="Sumber" class="py-3 pr-4">
                                <span class="text-[11px] font-bold rounded-full px-2.5 py-0.5"
                                      :class="t.source === 'online' ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800'"
                                      x-text="t.source === 'online' ? 'ONLINE' : 'POS'"></span>
                            </td>
                            <td data-label="Referensi" class="py-3 pr-4 font-mono text-xs font-semibold text-gray-700" x-text="t.reference"></td>
                            <td data-label="Pelanggan / Kasir" class="py-3 pr-4 text-gray-700" x-text="t.customer ?? '-'"></td>
                            <td data-label="Deskripsi" class="py-3 pr-4 text-gray-500 max-w-56 truncate" :title="t.description" x-text="t.description ?? '-'"></td>
                            <td data-label="Metode" class="py-3 pr-4 text-xs uppercase text-gray-500" x-text="t.method"></td>
                            <td data-label="Jumlah" class="py-3 pr-6 text-right font-semibold text-gray-800 whitespace-nowrap" x-text="formatIDR(t.amount)"></td>
                        </tr>
                    </template>
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-gray-200 bg-gray-50 font-extrabold">
                        <td colspan="6" class="py-3 pl-6 pr-4 text-right text-gray-600">TOTAL PERIODE</td>
                        <td data-label="Jumlah" class="py-3 pr-6 text-right text-green-800 text-base" x-text="formatIDR(summary.total_revenue)"></td>
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

<script>
function reportsPage() {
    return {
        from: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
        to: new Date().toISOString().split('T')[0],
        summary: {},
        transactions: [],
        loading: true,

        async load() {
            this.loading = true;
            try {
                const res = await fetch(`/api/admin/reports?from=${this.from}&to=${this.to}`, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                this.summary = data.summary || {};
                this.transactions = data.transactions || [];
            } catch (e) {
                this.summary = {};
                this.transactions = [];
            } finally {
                this.loading = false;
            }
        },

        formatIDR(value) {
            return 'Rp ' + Math.round(Number(value) || 0).toLocaleString('id-ID');
        },

        formatDate(iso) {
            if (!iso) return '-';
            return new Date(iso).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        },

        exportCsv() {
            const header = ['Tanggal', 'Sumber', 'Referensi', 'Pelanggan/Kasir', 'Deskripsi', 'Metode', 'Jumlah'];
            const rows = this.transactions.map(t => [
                t.date,
                t.source === 'online' ? 'ONLINE' : 'POS',
                t.reference,
                (t.customer ?? '').replace(/;/g, ','),
                (t.description ?? '').replace(/;/g, ','),
                t.method,
                t.amount,
            ]);
            const csv = [header, ...rows].map(row => row.join(';')).join('\n');
            const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `sireteduh-laporan-${this.from}-${this.to}.csv`;
            a.click();
            URL.revokeObjectURL(url);
        },

        printReport() {
            window.print();
        },
    };
}
</script>
@endsection
