@extends('layouts.admin')
@section('page-title', 'Log Aplikasi')

@section('content')
<div x-data="logPage()" x-init="init()" x-cloak>

    {{-- Toast --}}
    <div x-show="toast.message" x-cloak x-transition
         :class="toast.isError ? 'bg-red-700' : 'bg-green-700'"
         class="fixed bottom-6 right-6 z-50 text-white text-sm font-semibold rounded-xl shadow-xl px-5 py-3.5">
        <span x-text="toast.message"></span>
    </div>

    {{-- Toolbar --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-4 flex flex-wrap items-center gap-3">
        <div>
            <label class="text-[10px] uppercase font-bold text-gray-500">File Log</label>
            <select x-model="file" @change="load()" class="mt-1 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-600 max-w-56">
                <template x-for="f in files" :key="f.name">
                    <option :value="f.name" x-text="f.name + ' (' + f.size_human + ')'"></option>
                </template>
            </select>
        </div>
        <div>
            <label class="text-[10px] uppercase font-bold text-gray-500">Level</label>
            <select x-model="level" @change="load()" class="mt-1 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
                <option value="all">Semua</option>
                <option value="error">Error</option>
                <option value="warning">Warning</option>
                <option value="info">Info</option>
                <option value="debug">Debug</option>
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
        <div class="flex-1 min-w-48">
            <label class="text-[10px] uppercase font-bold text-gray-500">Cari</label>
            <input type="text" x-model="search" @keyup.enter="load()" placeholder="Ketik lalu Enter..."
                   class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
        </div>
        <div class="flex items-end gap-2">
            <button @click="load()" class="text-sm font-semibold text-green-800 bg-green-50 hover:bg-green-100 border border-green-200 rounded-xl px-4 py-2.5 transition">↻ Muat</button>
            <a :href="'/admin/logs/download?file=' + encodeURIComponent(file)" class="text-sm font-semibold text-gray-600 bg-white border border-gray-200 rounded-xl px-4 py-2.5 hover:bg-gray-50 transition">⬇️ Unduh</a>
            <button @click="clearLog()" class="text-sm font-semibold text-red-700 bg-red-50 hover:bg-red-100 border border-red-200 rounded-xl px-4 py-2.5 transition">🗑️ Kosongkan</button>
        </div>
    </div>

    {{-- Meta --}}
    <div class="flex items-center justify-between px-1 mb-2 text-xs text-gray-400">
        <p><span class="font-semibold text-gray-600" x-text="file"></span> · <span x-text="sizeHuman"></span> · <span x-text="totalLines + ' baris total'"></span> · menampilkan <span x-text="lines.length"></span> baris</p>
        <label class="flex items-center gap-2 cursor-pointer select-none">
            <input type="checkbox" x-model="autoRefresh" class="accent-green-700"> Auto-refresh (5s)
        </label>
    </div>

    {{-- Viewer --}}
    <div class="bg-gray-950 rounded-2xl border border-gray-800 shadow max-h-[70vh] overflow-auto">
        <pre class="text-[11px] leading-relaxed p-4 font-mono"><template x-for="(line, i) in lines" :key="i"><span class="block whitespace-pre-wrap break-all" :class="lineClass(line.level)" x-text="line.text"></span></template><template x-if="lines.length === 0"><span class="block py-16 text-center text-gray-500">Tidak ada baris yang cocok dengan filter.</span></template></pre>
    </div>

</div>

<script>
function logPage() {
    return {
        files: [],
        file: 'laravel.log',
        lines: [],
        totalLines: 0,
        sizeHuman: '0 B',
        level: 'all',
        search: '',
        autoRefresh: false,
        retention: 30,
        refreshTimer: null,
        toast: { message: '', isError: false },

        async init() {
            await this.loadFiles();
            this.load();
            setInterval(() => { if (this.autoRefresh) this.load(); }, 5000);
        },

        async loadFiles() {
            try {
                const res = await fetch('/api/admin/logs', { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                this.files = data.data || [];
                this.retention = data.retention_days ?? 30;
                if (this.files.length && !this.files.some(f => f.name === this.file)) {
                    this.file = this.files[0].name;
                }
            } catch (e) {}
        },

        async load() {
            try {
                const params = new URLSearchParams({ file: this.file, lines: 300, level: this.level });
                if (this.search.trim()) params.set('search', this.search.trim());
                const res = await fetch('/api/admin/logs/view?' + params.toString(), { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (data.success) {
                    this.lines = data.lines || [];
                    this.totalLines = data.total_lines || 0;
                    this.sizeHuman = data.size_human || '0 B';
                }
            } catch (e) {
                this.showToast('Gagal memuat log.', true);
            }
        },

        async clearLog() {
            if (!confirm('Kosongkan log "' + this.file + '"? Isi file akan dihapus permanen.')) return;
            try {
                const res = await fetch('/api/admin/logs/clear', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                    },
                    body: JSON.stringify({ file: this.file }),
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.showToast('Log berhasil dikosongkan.');
                    this.load();
                } else {
                    this.showToast(data.message ?? 'Gagal mengosongkan log.', true);
                }
            } catch (e) {
                this.showToast('Koneksi gagal.', true);
            }
        },

        async setRetention() {
            try {
                const res = await fetch('/api/admin/logs/retention', {
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
                    this.retention = data.retention_days;
                    this.showToast(data.message);
                    this.load();
                } else {
                    this.showToast(data.message ?? 'Gagal mengatur retensi.', true);
                }
            } catch (e) {
                this.showToast('Koneksi gagal.', true);
            }
        },

        lineClass(level) {
            return {
                error: 'text-red-400',
                warning: 'text-yellow-300',
                info: 'text-sky-300',
                debug: 'text-gray-400',
                unknown: 'text-green-300',
            }[level] ?? 'text-gray-300';
        },

        showToast(message, isError = false) {
            this.toast = { message, isError };
            setTimeout(() => { this.toast = { message: '', isError: false }; }, 4000);
        },
    };
}
</script>
@endsection
