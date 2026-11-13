@extends('layouts.admin')
@section('page-title', 'Laporan')

@section('content')
<div x-data="report()" x-init="load()" class="space-y-6">

    {{-- Filter Periode --}}
    <div class="bg-white rounded-xl shadow p-5 flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-xs text-gray-500 mb-1">Dari</label>
            <input type="date" x-model="from" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 outline-none">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Sampai</label>
            <input type="date" x-model="to" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 outline-none">
        </div>
        <button @click="load()" class="bg-green-700 text-white px-5 py-2 rounded-lg text-sm font-semibold hover:bg-green-800 transition">
            Tampilkan
        </button>
    </div>

    {{-- Statistik --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow p-5">
            <p class="text-xs text-gray-400 mb-1">Reservasi</p>
            <p class="text-2xl font-bold text-green-700" x-text="data.total_reservations ?? 0"></p>
        </div>
        <div class="bg-white rounded-xl shadow p-5">
            <p class="text-xs text-gray-400 mb-1">Konfirmasi</p>
            <p class="text-2xl font-bold text-blue-600" x-text="data.confirmed ?? 0"></p>
        </div>
        <div class="bg-white rounded-xl shadow p-5">
            <p class="text-xs text-gray-400 mb-1">Transaksi POS</p>
            <p class="text-2xl font-bold text-indigo-600" x-text="data.pos_transactions ?? 0"></p>
        </div>
        <div class="bg-white rounded-xl shadow p-5">
            <p class="text-xs text-gray-400 mb-1">Total Pendapatan</p>
            <p class="text-xl font-bold text-green-700"
               x-text="'Rp ' + parseInt(data.total_revenue ?? 0).toLocaleString('id-ID')"></p>
        </div>
    </div>

    {{-- Tabel Laporan Harian --}}
    <div class="bg-white rounded-xl shadow p-5">
        <h3 class="font-bold text-gray-700 mb-4">📊 Rekapitulasi Reservasi</h3>
        <table class="rtable w-full text-sm">
            <thead>
                <tr class="text-left text-gray-400 border-b text-xs uppercase">
                    <th class="pb-2 pr-4">Tanggal</th>
                    <th class="pb-2 pr-4">Reservasi</th>
                    <th class="pb-2">Pendapatan</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <template x-for="row in data.daily ?? []" :key="row.date">
                    <tr>
                        <td data-label="Tanggal" class="py-2 pr-4" x-text="row.date"></td>
                        <td data-label="Reservasi" class="py-2 pr-4" x-text="row.count"></td>
                        <td data-label="Pendapatan" class="py-2 font-semibold"
                            x-text="'Rp ' + parseInt(row.revenue).toLocaleString('id-ID')"></td>
                    </tr>
                </template>
                <template x-if="(data.daily ?? []).length === 0">
                    <tr><td colspan="3" class="py-6 text-center text-gray-400">Tidak ada data.</td></tr>
                </template>
            </tbody>
        </table>
    </div>
</div>

<script>
function report() {
    return {
        from: new Date(new Date().setDate(1)).toISOString().split('T')[0],
        to: new Date().toISOString().split('T')[0],
        data: {},
        async load() {
            try {
                const params = new URLSearchParams({ from: this.from, to: this.to });
                const r = await fetch('/admin/reports/data?' + params, {
                    headers: { 'Accept': 'application/json' }
                });
                this.data = await r.json();
            } catch(e) {}
        }
    }
}
</script>
@endsection
