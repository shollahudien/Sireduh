@extends('layouts.admin')
@section('page-title', 'Dashboard')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
@endpush

@section('content')
<div x-data="dashboardPage()" x-init="init()">

    {{-- Stat cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-2">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Pendapatan</p>
                <span class="text-xl">💰</span>
            </div>
            <p class="text-2xl xl:text-3xl font-extrabold text-green-800 mt-2" x-text="formatIDR(stats.total_revenue)"></p>
            <p class="text-xs text-gray-400 mt-1">Online + Walk-in POS</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Online (Transfer)</p>
                <span class="text-xl">🔵</span>
            </div>
            <p class="text-2xl xl:text-3xl font-extrabold text-blue-700 mt-2" x-text="formatIDR(stats.online_revenue)"></p>
            <p class="text-xs text-gray-400 mt-1">Reservasi &amp; tiket terverifikasi</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">POS (Walk-in)</p>
                <span class="text-xl">🟠</span>
            </div>
            <p class="text-2xl xl:text-3xl font-extrabold text-amber-600 mt-2" x-text="formatIDR(stats.offline_revenue)"></p>
            <p class="text-xs text-gray-400 mt-1">Transaksi kasir loket</p>
        </div>
    </div>
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        <a href="{{ route('admin.verification') }}" class="bg-yellow-50 rounded-2xl border border-yellow-200 p-5 hover:border-yellow-400 transition group">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-yellow-700 uppercase tracking-wide">Menunggu Verifikasi</p>
                <span class="text-xl">⏳</span>
            </div>
            <p class="text-2xl xl:text-3xl font-extrabold text-yellow-700 mt-2" x-text="stats.pending_payments ?? 0"></p>
            <p class="text-xs text-yellow-700/80 mt-1 group-hover:underline">Lihat bukti pembayaran →</p>
        </a>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Pengunjung Hari Ini</p>
                <span class="text-xl">🎟️</span>
            </div>
            <p class="text-2xl xl:text-3xl font-extrabold text-gray-800 mt-2" x-text="stats.visitors_today ?? 0"></p>
            <p class="text-xs text-gray-400 mt-1">Tiket terpakai (scan gate)</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Reservasi</p>
                <span class="text-xl">📋</span>
            </div>
            <p class="text-2xl xl:text-3xl font-extrabold text-gray-800 mt-2" x-text="stats.total_reservations ?? 0"></p>
            <p class="text-xs text-gray-400 mt-1">Sepanjang waktu</p>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6 items-start">
        {{-- Chart: online vs offline revenue (7 hari) --}}
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-bold text-gray-800">Pendapatan 7 Hari Terakhir</h2>
                <span class="text-xs text-gray-400">Online vs POS</span>
            </div>
            <div x-show="loading" x-cloak class="h-64 flex items-center justify-center text-gray-400 text-sm">Memuat grafik...</div>
            <canvas x-show="!loading" x-cloak id="revenueChart" height="220"></canvas>
        </div>

        {{-- Pending payments panel --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-bold text-gray-800">🔔 Pembayaran Masuk</h2>
                <span class="text-xs font-bold bg-red-100 text-red-700 rounded-full px-2.5 py-0.5" x-text="pending.count + ' pending'"></span>
            </div>
            <div x-show="pending.latest.length === 0" x-cloak class="text-sm text-gray-400 text-center py-8">
                Tidak ada pembayaran menunggu verifikasi.
            </div>
            <ul class="divide-y divide-gray-100">
                <template x-for="item in pending.latest" :key="item.id">
                    <li class="py-3">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm font-mono font-bold text-gray-800" x-text="item.order_code"></p>
                            <span class="text-[11px] text-gray-400" x-text="item.created_at_human"></span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5 truncate" x-text="item.visitor_name + ' · ' + item.unit_name"></p>
                        <div class="flex items-center justify-between mt-1.5">
                            <p class="text-sm font-bold text-green-800" x-text="formatIDR(item.total_amount)"></p>
                            <a href="{{ route('admin.verification') }}" class="text-[11px] font-bold text-yellow-800 bg-yellow-100 hover:bg-yellow-200 rounded-full px-3 py-1 transition">Verifikasi</a>
                        </div>
                    </li>
                </template>
            </ul>
            <a href="{{ route('admin.verification') }}" class="block text-center text-xs font-bold text-green-800 bg-green-50 hover:bg-green-100 rounded-xl py-2.5 mt-3 transition">
                Buka Halaman Verifikasi →
            </a>
        </div>
    </div>

</div>

<script>
function dashboardPage() {
    return {
        stats: {},
        pending: { count: 0, latest: [] },
        loading: true,
        chart: null,

        async init() {
            await Promise.all([this.fetchStats(), this.fetchPending()]);
            setInterval(() => this.fetchPending(), 10000);
        },

        async fetchStats() {
            try {
                const res = await fetch('/api/admin/dashboard-stats', { headers: { 'Accept': 'application/json' } });
                this.stats = await res.json();
                this.loading = false;
                this.renderChart();
            } catch (e) {
                this.loading = false;
            }
        },

        async fetchPending() {
            try {
                const res = await fetch('/api/admin/notifications', { headers: { 'Accept': 'application/json' } });
                if (res.ok) this.pending = await res.json();
            } catch (e) {}
        },

        renderChart() {
            const ctx = document.getElementById('revenueChart');
            if (!ctx || !this.stats.revenue_series) return;

            if (this.chart) {
                this.chart.destroy();
            }

            this.chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: this.stats.revenue_series.map(s => s.label),
                    datasets: [
                        { label: 'Online', data: this.stats.revenue_series.map(s => s.online), backgroundColor: '#15803d', borderRadius: 6 },
                        { label: 'POS', data: this.stats.revenue_series.map(s => s.offline), backgroundColor: '#f59e0b', borderRadius: 6 },
                    ],
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'bottom' } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { callback: (value) => 'Rp ' + (value / 1000).toLocaleString('id-ID') + 'rb' },
                        },
                    },
                },
            });
        },

        formatIDR(value) {
            return 'Rp ' + Math.round(Number(value) || 0).toLocaleString('id-ID');
        },
    };
}
</script>
@endsection
