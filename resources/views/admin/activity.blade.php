@extends('layouts.admin')
@section('page-title', 'Log Aktivitas')

@section('content')
<div x-data="activityPage()" x-init="load()">

    {{-- Toast --}}
    <div x-show="toast.message" x-cloak x-transition
         :class="toast.isError ? 'bg-red-700' : 'bg-green-700'"
         class="fixed bottom-6 right-6 z-50 text-white text-sm font-semibold rounded-xl shadow-xl px-5 py-3.5">
        <span x-text="toast.message"></span>
    </div>

    {{-- Stat cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-5">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <p class="text-[11px] uppercase font-bold text-gray-400 tracking-wide">Aktivitas Hari Ini</p>
            <p class="text-2xl xl:text-3xl font-extrabold text-green-800 mt-1" x-text="meta.today ?? 0"></p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <p class="text-[11px] uppercase font-bold text-gray-400 tracking-wide">Tamu Hari Ini</p>
            <p class="text-2xl xl:text-3xl font-extrabold text-blue-700 mt-1" x-text="meta.guests_today ?? 0"></p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <p class="text-[11px] uppercase font-bold text-gray-400 tracking-wide">Total Tercatat</p>
            <p class="text-2xl xl:text-3xl font-extrabold text-gray-800 mt-1" x-text="meta.total ?? 0"></p>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-4 flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-52">
            <label class="text-[10px] uppercase font-bold text-gray-500">Cari (nama / IP / path)</label>
            <input type="text" x-model="search" @keyup.enter="go(1)" placeholder="Mis: admin@simateduh.com, 127.0.0.1, /checkout"
                   class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
        </div>
        <div>
            <label class="text-[10px] uppercase font-bold text-gray-500">Role</label>
            <select x-model="role" @change="go(1)" class="mt-1 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
                <option value="all">Semua</option>
                <option value="admin">Admin</option>
                <option value="kasir">Kasir</option>
                <option value="checkin">Check-in</option>
                <option value="guest">Tamu (Guest)</option>
            </select>
        </div>
        <div>
            <label class="text-[10px] uppercase font-bold text-gray-500">Hapus Otomatis</label>
            <select x-model.number="retention" @change="setRetention()" class="mt-1 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
                <option :value="0">Nonaktif</option>
                <option :value="3">3 hari</option>
                <option :value="7">1 minggu</option>
                <option :value="30">1 bulan</option>
                <option :value="365">1 tahun</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button @click="load()" class="text-sm font-semibold text-green-800 bg-green-50 hover:bg-green-100 border border-green-200 rounded-xl px-4 py-2.5 transition">↻ Muat</button>
            <button @click="clearAll()" class="text-sm font-semibold text-red-700 bg-red-50 hover:bg-red-100 border border-red-200 rounded-xl px-4 py-2.5 transition">🗑️ Kosongkan</button>
        </div>
        <label class="flex items-center gap-2 text-xs text-gray-500 cursor-pointer select-none ml-auto">
            <input type="checkbox" x-model="autoRefresh" class="accent-green-700"> Auto-refresh (10s)
        </label>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="rtable w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-400 text-xs uppercase tracking-wide border-b">
                        <th class="pb-3 pr-4 pl-6">Waktu</th>
                        <th class="pb-3 pr-4">Pengguna</th>
                        <th class="pb-3 pr-4">IP</th>
                        <th class="pb-3 pr-4">Request</th>
                        <th class="pb-3 pr-4 text-center">Status</th>
                        <th class="pb-3 pr-6">Perangkat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-if="loading">
                        <tr><td colspan="6" class="py-16 text-center text-gray-400">Memuat aktivitas...</td></tr>
                    </template>
                    <template x-if="!loading && rows.length === 0">
                        <tr><td colspan="6" class="py-16 text-center text-gray-400">Tidak ada aktivitas tercatat.</td></tr>
                    </template>
                    <template x-for="row in rows" :key="row.id">
                        <tr class="hover:bg-gray-50">
                            <td data-label="Waktu" class="py-2.5 pr-4 pl-6 whitespace-nowrap">
                                <p class="text-xs font-semibold text-gray-700" x-text="formatTime(row.created_at)"></p>
                                <p class="text-[10px] text-gray-400" x-text="row.created_at_human"></p>
                            </td>
                            <td data-label="Pengguna" class="py-2.5 pr-4">
                                <template x-if="row.user_id">
                                    <div>
                                        <p class="font-semibold text-gray-800 leading-snug" x-text="row.user_name"></p>
                                        <span class="text-[10px] font-bold rounded-full px-2 py-0.5" :class="roleBadge(row.user_role)" x-text="(row.user_role || '').toUpperCase()"></span>
                                    </div>
                                </template>
                                <template x-if="!row.user_id">
                                    <div>
                                        <p class="font-semibold text-gray-500 leading-snug">Tamu (Guest)</p>
                                        <span class="text-[10px] font-bold rounded-full px-2 py-0.5 bg-gray-100 text-gray-500">GUEST</span>
                                    </div>
                                </template>
                            </td>
                            <td data-label="IP" class="py-2.5 pr-4 font-mono text-xs text-gray-600" x-text="row.ip"></td>
                            <td data-label="Request" class="py-2.5 pr-4">
                                <span class="text-[10px] font-bold rounded px-1.5 py-0.5 mr-1" :class="methodBadge(row.method)" x-text="row.method"></span><span class="font-mono text-xs text-gray-700 break-all" x-text="row.path"></span>
                            </td>
                            <td data-label="Status" class="py-2.5 pr-4 text-center">
                                <span class="text-[11px] font-bold rounded-full px-2.5 py-0.5" :class="statusBadge(row.status)" x-text="row.status"></span>
                            </td>
                            <td data-label="Perangkat" class="py-2.5 pr-6 text-[11px] text-gray-400 max-w-56 truncate" :title="row.user_agent" x-text="shortAgent(row.user_agent)"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="flex items-center justify-between px-6 py-3 border-t border-gray-100 text-xs text-gray-500">
            <p>Halaman <span class="font-semibold" x-text="meta.current_page"></span> / <span class="font-semibold" x-text="meta.last_page"></span> · Total <span class="font-semibold" x-text="meta.total"></span> entri</p>
            <div class="flex gap-2">
                <button @click="go(meta.current_page - 1)" :disabled="meta.current_page <= 1"
                        class="px-3 py-1.5 rounded-lg border border-gray-200 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed font-semibold">← Sebelumnya</button>
                <button @click="go(meta.current_page + 1)" :disabled="meta.current_page >= meta.last_page"
                        class="px-3 py-1.5 rounded-lg border border-gray-200 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed font-semibold">Selanjutnya →</button>
            </div>
        </div>
    </div>

</div>

<script>
function activityPage() {
    return {
        rows: [],
        meta: { total: 0, per_page: 50, current_page: 1, last_page: 1, today: 0, guests_today: 0 },
        loading: true,
        search: '',
        role: 'all',
        autoRefresh: false,
        retention: 30,
        toast: { message: '', isError: false },

        init() {
            setInterval(() => { if (this.autoRefresh) this.load(); }, 10000);
        },

        async load() {
            this.loading = true;
            try {
                const params = new URLSearchParams({ page: this.meta.current_page, role: this.role });
                if (this.search.trim()) params.set('search', this.search.trim());
                const res = await fetch('/api/admin/activity?' + params.toString(), { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                this.rows = data.data || [];
                this.meta = data.meta || this.meta;
                this.retention = this.meta.retention_days ?? 30;
            } catch (e) {
                this.showToast('Gagal memuat aktivitas.', true);
            } finally {
                this.loading = false;
            }
        },

        go(page) {
            if (page < 1 || page > this.meta.last_page) return;
            this.meta.current_page = page;
            this.load();
        },

        async clearAll() {
            if (!confirm('Hapus SELURUH riwayat aktivitas?')) return;
            try {
                const res = await fetch('/api/admin/activity/clear', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                    },
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.showToast(data.message);
                    this.go(1);
                } else {
                    this.showToast(data.message ?? 'Gagal menghapus.', true);
                }
            } catch (e) {
                this.showToast('Koneksi gagal.', true);
            }
        },

        async setRetention() {
            try {
                const res = await fetch('/api/admin/activity/retention', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                    },
                    body: JSON.stringify({ days: this.retention }),
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.showToast(data.message);
                    if (this.meta) this.meta.retention_days = this.retention;
                    this.load();
                } else {
                    this.showToast(data.message ?? 'Gagal mengatur retensi.', true);
                }
            } catch (e) {
                this.showToast('Koneksi gagal.', true);
            }
        },

        formatTime(iso) {
            if (!iso) return '-';
            return new Date(iso).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
        },

        shortAgent(ua) {
            if (!ua) return '-';
            const browser = ua.match(/(Firefox|Chrome|Edg|Safari)\/[\d.]+/);
            const os = ua.match(/(Linux|Windows|Android|iPhone|Macintosh|Android)/);
            return (browser?.[0] ?? 'Browser') + (os ? ' · ' + os[1] : '');
        },

        roleBadge(role) {
            return {
                admin: 'bg-red-100 text-red-800',
                kasir: 'bg-amber-100 text-amber-800',
                checkin: 'bg-blue-100 text-blue-800',
            }[role] ?? 'bg-gray-100 text-gray-600';
        },

        methodBadge(method) {
            return { GET: 'bg-green-100 text-green-800', POST: 'bg-blue-100 text-blue-800', PUT: 'bg-amber-100 text-amber-800', PATCH: 'bg-amber-100 text-amber-800', DELETE: 'bg-red-100 text-red-800' }[method] ?? 'bg-gray-100 text-gray-600';
        },

        statusBadge(status) {
            if (status >= 500) return 'bg-red-100 text-red-800';
            if (status >= 400) return 'bg-amber-100 text-amber-800';
            if (status >= 300) return 'bg-blue-100 text-blue-800';
            return 'bg-green-100 text-green-800';
        },

        showToast(message, isError = false) {
            this.toast = { message, isError };
            setTimeout(() => { this.toast = { message: '', isError: false }; }, 4000);
        },
    };
}
</script>
@endsection
