@extends('layouts.admin')
@section('page-title', 'Dashboard')

@push('head')
<style>
    @media (min-width: 1280px) {
        html, body { height: 100%; overflow: hidden; }
        body > div.min-h-screen { height: 100vh !important; overflow: hidden; }
        main { overflow: hidden !important; min-height: 0; }
    }
    @media (max-width: 1279.98px) {
        main { min-height: 0; }
    }
</style>
@endpush

@section('content')
<style>
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .anim-fade-up { opacity: 0; animation: fadeUp .55s ease-out forwards; }
</style>
<div class="flex h-full min-h-0 flex-col gap-3 sm:gap-4 overflow-y-auto overflow-x-hidden xl:overflow-hidden">
    <div class="anim-fade-up flex shrink-0 flex-wrap items-end justify-between gap-3" style="animation-delay: 0ms;">
        <div class="min-w-0">
            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-green-700">Ringkasan Operasional</p>
            <h1 class="mt-0.5 text-xl font-bold text-gray-900 sm:text-2xl">Dashboard</h1>
            <p class="mt-0.5 text-xs text-gray-500 sm:text-sm">SIRESTEDUH · BUMDes Wisata Teduh · analitik 30 hari terakhir</p>
        </div>
        <div class="flex flex-wrap items-center gap-2 text-xs font-medium">
            <span class="inline-flex items-center gap-1.5 rounded-full border border-green-200 bg-green-50 px-2.5 py-1 text-green-800"><span class="h-1.5 w-1.5 rounded-full bg-green-500"></span> Sistem aktif</span>
            <span class="rounded-full border border-gray-200 bg-white px-2.5 py-1 text-gray-600">{{ now()->translatedFormat('d M Y') }}</span>
        </div>
    </div>

    <div class="grid shrink-0 grid-cols-2 gap-2 sm:gap-3 lg:grid-cols-4 lg:gap-4">
        @php
            $stats = [
                ['label' => 'Total Reservasi', 'value' => \App\Models\Reservation::count(), 'icon' => '📋', 'accent' => 'text-green-800', 'ring' => 'ring-green-100', 'bg' => 'bg-green-50'],
                ['label' => 'Menunggu Verifikasi', 'value' => \App\Models\Reservation::where('status', 'waiting_verification')->count(), 'icon' => '⏳', 'accent' => 'text-amber-700', 'ring' => 'ring-amber-100', 'bg' => 'bg-amber-50'],
                ['label' => 'Unit Sewa', 'value' => \App\Models\Unit::count(), 'icon' => '🏕️', 'accent' => 'text-teal-700', 'ring' => 'ring-teal-100', 'bg' => 'bg-teal-50'],
                ['label' => 'Pengguna Terdaftar', 'value' => \App\Models\User::count(), 'icon' => '👥', 'accent' => 'text-indigo-700', 'ring' => 'ring-indigo-100', 'bg' => 'bg-indigo-50'],
            ];
        @endphp
        @foreach($stats as $i => $s)
            <div class="anim-fade-up rounded-xl border border-gray-200 bg-white p-3 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md sm:p-4" style="animation-delay: {{ 80 + ($i * 70) }}ms;">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="truncate text-[11px] font-medium text-gray-500 sm:text-xs">{{ $s['label'] }}</p>
                        <p class="mt-1 text-lg font-bold tabular-nums {{ $s['accent'] }} sm:mt-1.5 sm:text-2xl">{{ number_format($s['value']) }}</p>
                    </div>
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $s['bg'] }} text-base ring-1 {{ $s['ring'] }} sm:h-10 sm:w-10">{{ $s['icon'] }}</span>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid min-h-0 flex-1 grid-cols-1 gap-3 sm:gap-4 xl:grid-cols-3 xl:overflow-hidden">
        <div class="anim-fade-up flex min-h-0 flex-col rounded-xl border border-gray-200 bg-white p-3 shadow-sm transition-shadow duration-300 hover:shadow-md sm:p-4 xl:col-span-2 xl:overflow-hidden" style="animation-delay: 380ms;">
            <div class="flex shrink-0 flex-wrap items-center justify-between gap-2 border-b border-gray-100 pb-2.5 sm:pb-3">
                <div class="min-w-0">
                    <h2 class="text-sm font-bold text-gray-800 sm:text-base">Tren Analitik</h2>
                    <p class="text-[11px] text-gray-500 sm:text-xs">Pengunjung, penjualan, dan pendapatan (30 hari)</p>
                </div>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[10px] font-medium text-gray-600 sm:text-[11px]">
                    <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-sky-500"></span>Pengunjung</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Penjualan</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-amber-500"></span>Online</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-fuchsia-500"></span>Offline</span>
                </div>
            </div>

            <div class="mt-2 grid shrink-0 grid-cols-2 gap-2 text-[11px] sm:mt-3 sm:grid-cols-4 sm:gap-3 sm:text-xs">
                <div class="rounded-lg border border-gray-100 bg-gray-50/70 p-2 sm:p-2.5 min-w-0">
                    <p class="text-gray-500">Pengunjung</p>
                    <p class="mt-0.5 text-base font-bold tabular-nums text-gray-900 sm:text-lg" id="tot-visitors">0</p>
                </div>
                <div class="rounded-lg border border-gray-100 bg-gray-50/70 p-2 sm:p-2.5 min-w-0">
                    <p class="text-gray-500">Penjualan</p>
                    <p class="mt-0.5 text-base font-bold tabular-nums text-gray-900 sm:text-lg" id="tot-sales">0</p>
                </div>
                <div class="rounded-lg border border-amber-100 bg-amber-50/60 p-2 sm:p-2.5 min-w-0">
                    <p class="truncate text-amber-700">Pendapatan Online</p>
                    <p class="mt-0.5 truncate text-xs sm:text-base font-bold tabular-nums text-amber-800" id="tot-online" title="Rp 0">Rp 0</p>
                </div>
                <div class="rounded-lg border border-fuchsia-100 bg-fuchsia-50/60 p-2 sm:p-2.5 min-w-0">
                    <p class="truncate text-fuchsia-700">Pendapatan Offline</p>
                    <p class="mt-0.5 truncate text-xs sm:text-base font-bold tabular-nums text-fuchsia-800" id="tot-offline" title="Rp 0">Rp 0</p>
                </div>
            </div>

            <div class="relative mt-2 flex-1 min-h-[240px] sm:min-h-[300px] lg:min-h-[320px] xl:min-h-0 sm:mt-3">
                <canvas id="analyticsChart" class="w-full h-full block"></canvas>
            </div>
        </div>

        <div class="anim-fade-up flex min-h-0 flex-col rounded-xl border border-gray-200 bg-white p-3 shadow-sm transition-shadow duration-300 hover:shadow-md sm:p-4 xl:overflow-hidden" style="animation-delay: 460ms;">
            <div class="flex shrink-0 items-center justify-between gap-2 border-b border-gray-100 pb-2.5 sm:pb-3">
                <div class="min-w-0">
                    <h2 class="text-sm font-bold text-gray-800 sm:text-base">Rincian Pendapatan</h2>
                    <p class="text-[11px] text-gray-500 sm:text-xs">Online & offline per kasir (30 hari)</p>
                </div>
            </div>

            <div class="mt-2 shrink-0 rounded-lg border border-green-100 bg-green-50/70 p-2.5 sm:mt-3 sm:p-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-green-700">Total Pendapatan</p>
                <p id="tot-all-revenue" class="mt-0.5 text-lg font-extrabold tabular-nums text-green-900 sm:text-xl truncate" title="Rp 0">Rp 0</p>
            </div>

            <div class="mt-2 shrink-0 rounded-lg border border-amber-100 bg-amber-50/60 p-2.5 sm:mt-3 min-w-0">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-xs font-semibold text-amber-800 sm:text-sm">Online (Reservasi)</p>
                    <p id="rev-online-total" class="text-sm font-bold tabular-nums text-amber-900 sm:text-base truncate" title="Rp 0">Rp 0</p>
                </div>
                <p class="mt-0.5 text-[11px] text-amber-700/80">Akumulasi reservasi online non-batal.</p>
            </div>

            <div class="mt-2 flex min-h-0 flex-1 flex-col rounded-lg border border-gray-100 bg-gray-50/50 p-2.5 sm:mt-3">
                <div class="flex shrink-0 items-center justify-between gap-2 min-w-0">
                    <p class="text-xs font-semibold text-gray-800 sm:text-sm">Offline (POS/Kasir)</p>
                    <p id="rev-offline-total" class="text-sm font-bold tabular-nums text-fuchsia-700 sm:text-base truncate" title="Rp 0">Rp 0</p>
                </div>
                <div class="mt-2 grid shrink-0 grid-cols-3 gap-1.5 text-[10px] sm:gap-2 sm:text-[11px]">
                    <div class="rounded-md border border-green-100 bg-white p-1.5 sm:p-2 min-w-0">
                        <p class="font-medium text-green-700 truncate">Tunai</p>
                        <p id="rev-offline-cash" class="mt-0.5 text-[10px] sm:text-sm font-bold tabular-nums text-gray-900 truncate" title="Rp 0">Rp 0</p>
                        <p class="text-[10px] text-gray-400"><span id="rev-offline-cash-count">0</span> trx</p>
                    </div>
                    <div class="rounded-md border border-blue-100 bg-white p-1.5 sm:p-2 min-w-0">
                        <p class="font-medium text-blue-700 truncate">QRIS</p>
                        <p id="rev-offline-qris" class="mt-0.5 text-[10px] sm:text-sm font-bold tabular-nums text-gray-900 truncate" title="Rp 0">Rp 0</p>
                        <p class="text-[10px] text-gray-400"><span id="rev-offline-qris-count">0</span> trx</p>
                    </div>
                    <div class="rounded-md border border-indigo-100 bg-white p-1.5 sm:p-2 min-w-0">
                        <p class="font-medium text-indigo-700 truncate">Transfer</p>
                        <p id="rev-offline-transfer" class="mt-0.5 text-[10px] sm:text-sm font-bold tabular-nums text-gray-900 truncate" title="Rp 0">Rp 0</p>
                        <p class="text-[10px] text-gray-400"><span id="rev-offline-transfer-count">0</span> trx</p>
                    </div>
                </div>
                <div class="mt-2 min-h-0 flex-1 overflow-auto xl:overflow-y-auto">
                    <table class="w-full min-w-[240px] text-left text-[11px] sm:text-xs">
                        <thead class="sticky top-0 bg-gray-50 text-gray-500">
                            <tr class="border-b border-gray-200">
                                <th class="py-1.5 pr-2 font-semibold whitespace-nowrap">Kasir</th>
                                <th class="py-1.5 pr-2 text-right font-semibold whitespace-nowrap">Trx</th>
                                <th class="py-1.5 text-right font-semibold whitespace-nowrap">Pendapatan</th>
                            </tr>
                        </thead>
                        <tbody id="cashier-revenue-body" class="divide-y divide-gray-100 text-gray-700">
                            <tr><td colspan="3" class="py-2 text-gray-400">Memuat...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(async function () {
    const fmtFull = (v) => 'Rp ' + Math.round(v || 0).toLocaleString('id-ID');
    const fmtShort = (v) => {
        const n = Math.round(v || 0);
        const abs = Math.abs(n);
        if (abs >= 1000000000) return 'Rp ' + (n / 1000000000).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' M';
        if (abs >= 1000000) return 'Rp ' + (n / 1000000).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' Jt';
        if (abs >= 1000) return 'Rp ' + (n / 1000).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' rb';
        return 'Rp ' + n.toLocaleString('id-ID');
    };
    const isMobile = () => window.innerWidth < 640;

    function setMoneyText(id, value) {
        const el = document.getElementById(id);
        if (!el) return;
        const full = fmtFull(value);
        el.textContent = full;
        el.title = full;
    }

    function chartOptions() {
        const mobile = isMobile();
        return {
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
                },
            },
            scales: {
                x: {
                    ticks: { color: '#6b7280', font: { size: 10 }, maxRotation: 0, autoSkip: true, maxTicksLimit: mobile ? 5 : 10 },
                    grid: { color: 'rgba(17,24,39,0.06)' },
                },
                yCount: {
                    type: 'linear',
                    position: 'left',
                    beginAtZero: true,
                    title: { display: true, text: 'Jumlah', color: '#6b7280', font: { size: 10 } },
                    ticks: { color: '#6b7280', font: { size: 10 }, maxTicksLimit: mobile ? 5 : 8 },
                    grid: { color: 'rgba(17,24,39,0.06)' },
                },
                yMoney: {
                    type: 'linear',
                    position: 'right',
                    beginAtZero: true,
                    title: { display: true, text: 'Rupiah', color: '#6b7280', font: { size: 10 } },
                    grid: { drawOnChartArea: false },
                    ticks: {
                        color: '#6b7280',
                        font: { size: 10 },
                        maxTicksLimit: mobile ? 5 : 8,
                        callback: (v) => (isMobile() ? fmtShort(v) : fmtFull(v)),
                    },
                },
            },
        };
    }

    let chart = null;

    function updateDashboardDOM(data, initChart) {
        if (!data || !data.labels) return;

        const visitors = data.totals?.visitors ?? 0;
        const sales = data.totals?.sales ?? 0;
        const online = data.totals?.online_revenue ?? 0;
        const offline = data.totals?.offline_revenue ?? 0;
        const total = data.totals?.total_revenue ?? (online + offline);

        document.getElementById('tot-visitors').textContent = Number(visitors).toLocaleString('id-ID');
        document.getElementById('tot-sales').textContent = Number(sales).toLocaleString('id-ID');
        setMoneyText('tot-online', online);
        setMoneyText('tot-offline', offline);
        setMoneyText('tot-all-revenue', total);
        setMoneyText('rev-online-total', online);
        setMoneyText('rev-offline-total', offline);

        const methods = Array.isArray(data.offline_by_method) ? data.offline_by_method : [];
        const byMethod = Object.fromEntries(methods.map((m) => [m.method, m]));
        ['cash', 'qris', 'transfer'].forEach((key) => {
            const row = byMethod[key] || { total_amount: 0, transactions: 0 };
            setMoneyText(`rev-offline-${key}`, row.total_amount ?? 0);
            const c = document.getElementById(`rev-offline-${key}-count`);
            if (c) c.textContent = Number(row.transactions ?? 0).toLocaleString('id-ID');
        });

        const cashierBody = document.getElementById('cashier-revenue-body');
        const cashiers = Array.isArray(data.offline_by_cashier) ? data.offline_by_cashier : [];
        if (!cashiers.length) {
            cashierBody.innerHTML = '<tr><td colspan="3" class="py-2 text-gray-400">Belum ada transaksi offline pada periode ini.</td></tr>';
        } else {
            cashierBody.innerHTML = cashiers.map((c) => {
                const name = String(c.name ?? '-').replace(/</g, '&lt;');
                const role = String(c.role ?? '').replace(/</g, '&lt;');
                const label = role ? `${name} <span class="text-gray-400">(${role})</span>` : name;
                const amt = fmtFull(c.total_amount ?? 0);
                return `<tr><td class="py-1.5 pr-2 font-medium text-gray-800 break-words">${label}</td><td class="py-1.5 pr-2 text-right tabular-nums whitespace-nowrap">${Number(c.transactions ?? 0).toLocaleString('id-ID')}</td><td class="py-1.5 text-right font-semibold tabular-nums text-fuchsia-700 whitespace-nowrap" title="${amt}">${amt}</td></tr>`;
            }).join('');
        }

        const ctxEl = document.getElementById('analyticsChart');
        if (!ctxEl) return;

        if (!chart && initChart) {
            const ctx = ctxEl.getContext('2d');
            chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [
                        { label: 'Pengunjung', data: data.visitors, borderColor: '#0ea5e9', backgroundColor: 'rgba(14,165,233,0.08)', yAxisID: 'yCount', tension: 0.35, fill: true, pointRadius: 2, pointHoverRadius: 4 },
                        { label: 'Penjualan', data: data.sales, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.08)', yAxisID: 'yCount', tension: 0.35, fill: true, pointRadius: 2, pointHoverRadius: 4 },
                        { label: 'Online', data: data.online_revenue, borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,0.08)', yAxisID: 'yMoney', tension: 0.35, fill: true, pointRadius: 2, pointHoverRadius: 4 },
                        { label: 'Offline', data: data.offline_revenue, borderColor: '#d946ef', backgroundColor: 'rgba(217,70,239,0.08)', yAxisID: 'yMoney', tension: 0.35, fill: true, pointRadius: 2, pointHoverRadius: 4 },
                    ],
                },
                options: chartOptions(),
            });
        } else if (chart) {
            chart.data.labels = data.labels;
            chart.data.datasets[0].data = data.visitors;
            chart.data.datasets[1].data = data.sales;
            chart.data.datasets[2].data = data.online_revenue;
            chart.data.datasets[3].data = data.offline_revenue;
            chart.options = chartOptions();
            chart.update('none');
        }
    }

    async function fetchAnalytics() {
        const res = await fetch('/api/admin/dashboard-analytics', { headers: { 'Accept': 'application/json' } });
        if (!res.ok) throw new Error('fetch failed');
        return await res.json();
    }

    try {
        const initial = await fetchAnalytics();
        updateDashboardDOM(initial, true);

        let timer = null;
        const intervalMs = 15000;

        async function refreshAnalytics() {
            if (document.hidden) return;
            try {
                const data = await fetchAnalytics();
                updateDashboardDOM(data, false);
            } catch (e) {}
        }

        function startTimer() {
            if (timer) return;
            timer = setInterval(refreshAnalytics, intervalMs);
        }
        function stopTimer() {
            if (!timer) return;
            clearInterval(timer);
            timer = null;
        }

        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                stopTimer();
            } else {
                refreshAnalytics();
                startTimer();
            }
        });

        window.addEventListener('resize', () => {
            if (chart) {
                chart.options = chartOptions();
                chart.resize();
                chart.update('none');
            }
        });

        startTimer();
    } catch (e) {
        console.error('analytics load failed', e);
    }
})();
</script>
@endsection
