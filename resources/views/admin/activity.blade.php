@extends('layouts.admin')
@section('page-title', 'Log Aktivitas')

@section('content')
<div x-data="activityPage()" x-init="init()" class="space-y-4 sm:space-y-5">
    <div x-show="toast.message" x-cloak x-transition
         :class="toast.isError ? 'bg-red-700' : 'bg-green-700'"
         class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 z-50 max-w-[calc(100vw-2rem)] text-white text-sm font-semibold rounded-xl shadow-xl px-5 py-3.5 flex items-center gap-3">
        <span x-text="toast.isError ? '⚠️' : '✅'"></span><span x-text="toast.message"></span>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-5 gap-3 sm:gap-4">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <p class="text-[11px] uppercase font-bold text-gray-400 tracking-wide">Aktivitas Hari Ini</p>
            <p class="text-xl sm:text-2xl font-extrabold text-green-800 mt-1" x-text="meta.today ?? 0"></p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <p class="text-[11px] uppercase font-bold text-gray-400 tracking-wide">Akun Aktif (Hari Ini)</p>
            <p class="text-xl sm:text-2xl font-extrabold text-indigo-700 mt-1" x-text="meta.accounts_today ?? 0"></p>
            <p class="text-[10px] text-gray-400 mt-0.5">user login unik</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <p class="text-[11px] uppercase font-bold text-gray-400 tracking-wide">Tamu/Guest (Hari Ini)</p>
            <p class="text-xl sm:text-2xl font-extrabold text-blue-700 mt-1" x-text="meta.guests_today ?? 0"></p>
            <p class="text-[10px] text-gray-400 mt-0.5">request tanpa akun</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <p class="text-[11px] uppercase font-bold text-gray-400 tracking-wide">IP Unik (Hari Ini)</p>
            <p class="text-xl sm:text-2xl font-extrabold text-amber-700 mt-1" x-text="meta.unique_ips_today ?? 0"></p>
            <p class="text-[10px] text-gray-400 mt-0.5">akun & pengunjung</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 col-span-2 sm:col-span-1 xl:col-span-1">
            <p class="text-[11px] uppercase font-bold text-gray-400 tracking-wide">Total Tercatat</p>
            <p class="text-xl sm:text-2xl font-extrabold text-gray-800 mt-1" x-text="meta.total ?? 0"></p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-5 space-y-4">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 items-end">
            <div class="lg:col-span-7 xl:col-span-6">
                <label class="text-[10px] uppercase font-bold text-gray-500">Cari (akun, IP, path, event, order/subjek, deskripsi)</label>
                <input type="text" x-model="search" @keyup.enter="go(1)" placeholder="SIRES-..., login, payment, IP, path..."
                       class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
            </div>
            <div class="lg:col-span-3 xl:col-span-3">
                <label class="text-[10px] uppercase font-bold text-gray-500">Tipe Pengunjung</label>
                <select x-model="role" @change="go(1)" class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
                    <option value="all">Semua (akun & guest)</option>
                    <option value="guest">Tamu/Guest</option>
                    <option value="admin">Admin</option>
                    <option value="kasir">Kasir</option>
                    <option value="kasir_makanan">Kasir Makanan (F&amp;B)</option>
                    <option value="checkin">Check-in</option>
                </select>
            </div>
            <div class="lg:col-span-2 xl:col-span-3 lg:hidden">
                <button type="button" @click="showAdvancedFilter = !showAdvancedFilter"
                        class="w-full mt-1 inline-flex items-center justify-center gap-2 border border-gray-200 bg-gray-50 hover:bg-gray-100 text-gray-700 font-semibold rounded-xl px-3 py-2.5 text-sm transition min-h-[42px]">
                    <span x-text="showAdvancedFilter ? 'Sembunyikan Filter Lanjutan' : 'Filter Lanjutan'"></span>
                    <span class="text-xs" x-text="showAdvancedFilter ? '▲' : '▼'"></span>
                </button>
            </div>
            <div class="hidden lg:flex lg:col-span-2 xl:col-span-3 items-end justify-end">
                <p class="text-[11px] text-gray-500 text-right">Gunakan filter lanjutan untuk mempersempit log.</p>
            </div>
        </div>

        <div :class="showAdvancedFilter ? 'grid' : 'hidden lg:grid'" class="grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end border-t border-gray-100 pt-3 lg:border-t-0 lg:pt-0">
            <div>
                <label class="text-[10px] uppercase font-bold text-gray-500">Event / Aksi</label>
                <input type="text" x-model="event" @keyup.enter="go(1)" placeholder="auth.login, payment..."
                       class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
            </div>
            <div>
                <label class="text-[10px] uppercase font-bold text-gray-500">Subjek / Order / ID</label>
                <input type="text" x-model="subject" @keyup.enter="go(1)" placeholder="SIRES-XXXX, nama, id..."
                       class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
            </div>
            <div>
                <label class="text-[10px] uppercase font-bold text-gray-500">IP</label>
                <input type="text" x-model="ip" @keyup.enter="go(1)" placeholder="127.0.0.1"
                       class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
            </div>
            <div class="grid grid-cols-1 min-[420px]:grid-cols-2 sm:grid-cols-2 gap-3 lg:col-span-2">
                <div class="min-w-0">
                    <label class="text-[10px] uppercase font-bold text-gray-500">Dari Tanggal</label>
                    <input type="date" x-model="from" @change="go(1)" class="mt-1 w-full min-w-0 border border-gray-200 rounded-xl px-3 py-2.5 text-sm">
                </div>
                <div class="min-w-0">
                    <label class="text-[10px] uppercase font-bold text-gray-500">Sampai Tanggal</label>
                    <input type="date" x-model="to" @change="go(1)" class="mt-1 w-full min-w-0 border border-gray-200 rounded-xl px-3 py-2.5 text-sm">
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-3 border-t border-gray-100 pt-3">
            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-3">
                <div class="flex flex-col sm:flex-row sm:flex-wrap items-stretch sm:items-end gap-3">
                    <div class="w-full sm:w-auto">
                        <label class="text-[10px] uppercase font-bold text-gray-500">Hapus Otomatis</label>
                        <select x-model.number="retention" @change="setRetention()" class="mt-1 w-full sm:w-[170px] border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
                            <option :value="0">Nonaktif</option>
                            <option :value="3">3 hari</option>
                            <option :value="7">1 minggu</option>
                            <option :value="30">1 bulan</option>
                            <option :value="365">1 tahun</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 sm:flex sm:flex-wrap gap-2">
                        <button @click="go(1)" class="inline-flex items-center justify-center text-sm font-semibold text-green-800 bg-green-50 hover:bg-green-100 border border-green-200 rounded-xl px-4 py-2.5 transition min-h-[42px]">↻ Muat</button>
                        <button @click="resetFilters()" class="inline-flex items-center justify-center text-sm font-semibold text-gray-700 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-xl px-4 py-2.5 transition min-h-[42px]">Reset Filter</button>
                        <button @click="clearAll()" class="col-span-2 sm:col-span-1 inline-flex items-center justify-center text-sm font-semibold text-red-700 bg-red-50 hover:bg-red-100 border border-red-200 rounded-xl px-4 py-2.5 transition min-h-[42px]">🗑️ Kosongkan</button>
                    </div>
                </div>
                <label class="flex items-center gap-2 text-xs text-gray-500 cursor-pointer select-none lg:pb-2.5">
                    <input type="checkbox" x-model="autoRefresh" class="accent-green-700 w-4 h-4"> Auto-refresh (10s)
                </label>
            </div>
            <p class="text-[11px] text-gray-500 leading-relaxed">Klik baris/kartu untuk melihat detail lengkap (input tersanitasi, metadata file/foto, identitas akun/IP, perubahan status). Rahasia (password/token) tidak disimpan.</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="hidden xl:block overflow-x-auto">
            <table class="w-full text-sm min-w-0">
                <thead class="bg-gray-50/70">
                    <tr class="text-left text-gray-500 text-xs uppercase tracking-wide border-b border-gray-100">
                        <th class="py-3 pr-3 pl-4 2xl:pl-6 font-bold">Waktu</th>
                        <th class="py-3 pr-3 font-bold">Akun / Tamu & IP</th>
                        <th class="py-3 pr-3 font-bold">Event & Aktivitas</th>
                        <th class="py-3 pr-3 font-bold">Request</th>
                        <th class="py-3 pr-3 text-center font-bold">Status</th>
                        <th class="py-3 pr-4 2xl:pr-6 font-bold">File / Perangkat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-if="loading">
                        <tr><td colspan="6" class="py-16 text-center text-gray-400">Memuat aktivitas...</td></tr>
                    </template>
                    <template x-if="!loading && rows.length === 0">
                        <tr><td colspan="6" class="py-16 text-center text-gray-400">Tidak ada aktivitas tercatat.</td></tr>
                    </template>
                    <template x-for="row in rows" :key="'d-'+row.id">
                        <tr class="align-top">
                            <td colspan="6" class="p-0">
                                <div class="cursor-pointer transition-colors border-l-4"
                                     :class="row._open ? 'bg-green-50/60 border-green-500' : 'hover:bg-gray-50/80 border-transparent'"
                                     @click="row._open = !row._open">
                                    <div class="grid grid-cols-[145px_190px_minmax(200px,1.2fr)_minmax(180px,1fr)_80px_180px] 2xl:grid-cols-[170px_220px_minmax(220px,1.2fr)_minmax(200px,1fr)_90px_220px] gap-3 px-4 2xl:px-6 py-3 items-start">
                                        <div class="whitespace-nowrap">
                                            <p class="text-xs font-semibold text-gray-800" x-text="formatTime(row.created_at)"></p>
                                            <p class="text-[10px] text-gray-400" x-text="row.created_at_human"></p>
                                            <p class="text-[10px] text-gray-300 mt-0.5">#<span x-text="row.id"></span></p>
                                        </div>
                                        <div class="min-w-0">
                                            <template x-if="row.user_id">
                                                <div class="space-y-1">
                                                    <span class="inline-flex text-[10px] font-extrabold rounded-full px-2 py-0.5 bg-indigo-100 text-indigo-800">AKUN LOGIN</span>
                                                    <p class="font-semibold text-gray-800 leading-snug break-words" x-text="row.user_name || ('User #'+row.user_id)"></p>
                                                    <p class="text-[11px] text-gray-500">ID: <span class="font-mono font-semibold" x-text="row.user_id"></span> · <span class="font-bold" x-text="roleLabel(row.user_role)"></span></p>
                                                </div>
                                            </template>
                                            <template x-if="!row.user_id">
                                                <div class="space-y-1">
                                                    <span class="inline-flex text-[10px] font-extrabold rounded-full px-2 py-0.5 bg-blue-100 text-blue-800">TAMU / GUEST</span>
                                                    <p class="font-semibold text-gray-600">Guest (tanpa login)</p>
                                                </div>
                                            </template>
                                            <p class="font-mono text-xs font-semibold text-gray-800 mt-1 break-all" x-text="row.ip || '-'"></p>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-1.5 mb-1">
                                                <span class="text-[10px] font-bold rounded px-2 py-0.5" :class="eventBadge(row.event)" x-text="row.event || 'http.request'"></span>
                                                <template x-if="row.subject_label">
                                                    <span class="text-[10px] font-bold rounded px-2 py-0.5 bg-amber-50 text-amber-800 border border-amber-200 break-all" x-text="row.subject_label"></span>
                                                </template>
                                            </div>
                                            <p class="text-xs text-gray-800 font-semibold leading-snug break-words" x-text="row.description || '-' "></p>
                                            <p class="text-[10px] text-gray-400 mt-1 break-words" x-text="row.subject_type ? (String(row.subject_type).split(String.fromCharCode(92)).pop() + (row.subject_id ? ' #'+row.subject_id : '')) : ''"></p>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-1.5 mb-1">
                                                <span class="text-[10px] font-bold rounded px-1.5 py-0.5" :class="methodBadge(row.method)" x-text="row.method || '-'"></span>
                                                <span class="font-mono text-[11px] text-gray-800 break-all" x-text="row.path || '-'"></span>
                                            </div>
                                            <p class="text-[11px] text-gray-500 break-all"><span class="font-semibold text-gray-400">Route:</span> <span class="font-mono" x-text="row.route || '-'"></span></p>
                                        </div>
                                        <div class="text-center">
                                            <span class="inline-flex text-[11px] font-bold rounded-full px-2.5 py-0.5" :class="statusBadge(row.status)" x-text="row.status ?? '-'"></span>
                                            <p class="text-[10px] text-gray-400 mt-1" x-text="statusText(row.status)"></p>
                                        </div>
                                        <div class="min-w-0">
                                            <template x-if="row.has_file">
                                                <span class="inline-flex text-[10px] font-extrabold rounded-full px-2 py-0.5 bg-fuchsia-100 text-fuchsia-800 mb-1">FILE/FOTO</span>
                                            </template>
                                            <p class="text-[11px] font-semibold text-gray-700 break-words" x-text="shortAgent(row.user_agent)"></p>
                                            <p class="text-[10px] text-gray-500 break-all mt-1 leading-relaxed line-clamp-2" x-text="row.user_agent || '-'"></p>
                                            <p class="text-[10px] text-green-700 font-semibold mt-1" x-text="row._open ? '▲ Tutup detail' : '▼ Lihat detail'"></p>
                                        </div>
                                    </div>
                                </div>
                                <template x-if="row._open">
                                    <div class="border-t border-green-100 bg-green-50/30 px-4 2xl:px-6 py-4">
                                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 text-xs">
                                            <div class="bg-white border border-gray-200 rounded-xl p-3">
                                                <p class="font-extrabold text-gray-700 mb-2">Identitas & Akses</p>
                                                <dl class="space-y-1.5 text-gray-700">
                                                    <div><dt class="inline font-semibold text-gray-500">Akun:</dt> <dd class="inline break-words" x-text="row.user_id ? (row.user_name+' (ID '+row.user_id+', '+roleLabel(row.user_role)+')') : 'Guest/tamu (tanpa login)'"></dd></div>
                                                    <div><dt class="inline font-semibold text-gray-500">IP:</dt> <dd class="inline font-mono break-all" x-text="row.ip || '-'"></dd></div>
                                                    <div><dt class="inline font-semibold text-gray-500">Event:</dt> <dd class="inline font-mono break-all" x-text="row.event || '-'"></dd></div>
                                                    <div><dt class="inline font-semibold text-gray-500">Request ID:</dt> <dd class="inline font-mono break-all" x-text="row.request_id || '-'"></dd></div>
                                                    <div><dt class="inline font-semibold text-gray-500">Route:</dt> <dd class="inline font-mono break-all" x-text="row.route || '-'"></dd></div>
                                                    <div><dt class="inline font-semibold text-gray-500">Path:</dt> <dd class="inline font-mono break-all" x-text="(row.method||'')+' '+(row.path||'')"></dd></div>
                                                    <div><dt class="inline font-semibold text-gray-500">Status:</dt> <dd class="inline" x-text="(row.status||'-')+' '+statusText(row.status)"></dd></div>
                                                </dl>
                                            </div>
                                            <div class="bg-white border border-gray-200 rounded-xl p-3 lg:col-span-2 min-w-0">
                                                <p class="font-extrabold text-gray-700 mb-2">Detail Input / Perubahan (tersanitasi)</p>
                                                <template x-if="row.properties && Object.keys(row.properties).length">
                                                    <pre class="text-[11px] leading-relaxed bg-gray-900 text-green-100 rounded-lg p-3 overflow-x-auto max-w-full max-h-72 whitespace-pre-wrap break-all" x-text="JSON.stringify(row.properties, null, 2)"></pre>
                                                </template>
                                                <template x-if="!row.properties || !Object.keys(row.properties).length">
                                                    <p class="text-gray-400">Tidak ada detail input tambahan.</p>
                                                </template>
                                            </div>
                                            <div class="bg-white border border-gray-200 rounded-xl p-3 lg:col-span-3 min-w-0" x-show="row.has_file || row.file_name || row.file_path">
                                                <p class="font-extrabold text-gray-700 mb-2">File / Foto Masuk (Metadata)</p>
                                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2 text-gray-700">
                                                    <div class="min-w-0"><span class="font-semibold text-gray-500">Nama:</span> <span class="break-all" x-text="row.file_name || '-'"></span></div>
                                                    <div class="min-w-0"><span class="font-semibold text-gray-500">Jenis/MIME:</span> <span class="break-all" x-text="row.file_mime || '-'"></span></div>
                                                    <div><span class="font-semibold text-gray-500">Ukuran:</span> <span x-text="row.file_size ? (row.file_size+' bytes') : '-'"></span></div>
                                                    <div class="lg:col-span-2 min-w-0"><span class="font-semibold text-gray-500">Path:</span> <span class="font-mono break-all" x-text="row.file_path || '-'"></span></div>
                                                    <div class="sm:col-span-2 lg:col-span-5 min-w-0"><span class="font-semibold text-gray-500">Hash (SHA-256):</span> <span class="font-mono break-all" x-text="row.file_hash || '-'"></span></div>
                                                </div>
                                                <template x-if="row.file_path && (row.file_path.startsWith('uploads/images/') || row.file_mime?.startsWith('image/')) && !row.file_path.startsWith('reservations/proofs/')">
                                                    <div class="mt-3">
                                                        <p class="text-[11px] text-gray-500 mb-1">Pratinjau (jika file publik tersedia):</p>
                                                        <img :src="'/storage/' + row.file_path" alt="Pratinjau file" class="max-h-40 rounded-lg border border-gray-200 bg-white" loading="lazy" onerror="this.style.display='none'">
                                                    </div>
                                                </template>
                                                <p class="text-[11px] text-gray-500 mt-2" x-show="row.file_path && row.file_path.startsWith('reservations/proofs/')">Bukti pembayaran disimpan privat; tinjau via halaman verifikasi/admin.</p>
                                            </div>
                                            <div class="bg-white border border-gray-200 rounded-xl p-3 lg:col-span-3 min-w-0">
                                                <p class="font-extrabold text-gray-700 mb-1">User Agent Lengkap</p>
                                                <p class="text-[11px] text-gray-600 break-all leading-relaxed" x-text="row.user_agent || '-'"></p>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="xl:hidden">
            <template x-if="loading">
                <div class="py-16 text-center text-gray-400 text-sm">Memuat aktivitas...</div>
            </template>
            <template x-if="!loading && rows.length === 0">
                <div class="py-16 text-center text-gray-400 text-sm">Tidak ada aktivitas tercatat.</div>
            </template>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 p-3 sm:p-4">
                <template x-for="row in rows" :key="'m-'+row.id">
                    <div class="rounded-xl border bg-white shadow-sm overflow-hidden transition-colors"
                         :class="row._open ? 'border-green-400 ring-1 ring-green-200' : 'border-gray-200'">
                        <button type="button" class="w-full text-left p-3 sm:p-4 space-y-3" @click="row._open = !row._open"
                                :class="row._open ? 'bg-green-50/40' : ''">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-gray-800" x-text="formatTime(row.created_at)"></p>
                                    <p class="text-[10px] text-gray-400" x-text="row.created_at_human"></p>
                                    <p class="text-[10px] text-gray-300 mt-0.5">#<span x-text="row.id"></span></p>
                                </div>
                                <div class="flex flex-wrap items-center justify-end gap-1.5 shrink-0 max-w-[55%]">
                                    <span class="text-[10px] font-bold rounded px-1.5 py-0.5" :class="methodBadge(row.method)" x-text="row.method || '-'"></span>
                                    <span class="text-[11px] font-bold rounded-full px-2.5 py-0.5" :class="statusBadge(row.status)" x-text="row.status ?? '-'"></span>
                                    <template x-if="row.has_file">
                                        <span class="inline-flex text-[10px] font-extrabold rounded-full px-2 py-0.5 bg-fuchsia-100 text-fuchsia-800">FILE/FOTO</span>
                                    </template>
                                </div>
                            </div>

                            <div class="space-y-2 min-w-0">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <template x-if="row.user_id">
                                        <span class="inline-flex text-[10px] font-extrabold rounded-full px-2 py-0.5 bg-indigo-100 text-indigo-800">AKUN LOGIN</span>
                                    </template>
                                    <template x-if="!row.user_id">
                                        <span class="inline-flex text-[10px] font-extrabold rounded-full px-2 py-0.5 bg-blue-100 text-blue-800">TAMU / GUEST</span>
                                    </template>
                                    <span class="text-[10px] font-bold rounded px-2 py-0.5" :class="eventBadge(row.event)" x-text="row.event || 'http.request'"></span>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-800 break-words" x-text="row.user_id ? (row.user_name || ('User #'+row.user_id)) : 'Guest (tanpa login)'"></p>
                                    <p class="text-[11px] text-gray-500 mt-0.5">
                                        <template x-if="row.user_id"><span><span class="font-semibold">ID</span> <span class="font-mono" x-text="row.user_id"></span> · <span class="font-semibold" x-text="roleLabel(row.user_role)"></span> · </span></template>
                                        <span class="font-semibold">IP</span> <span class="font-mono break-all" x-text="row.ip || '-'"></span>
                                    </p>
                                </div>
                                <div class="min-w-0">
                                    <template x-if="row.subject_label">
                                        <span class="inline-flex text-[10px] font-bold rounded px-2 py-0.5 bg-amber-50 text-amber-800 border border-amber-200 mb-1 break-all" x-text="row.subject_label"></span>
                                    </template>
                                    <p class="text-xs text-gray-800 font-semibold leading-snug break-words" x-text="row.description || '-' "></p>
                                    <p class="text-[11px] text-gray-500 mt-1 break-all"><span class="font-mono" x-text="row.path || '-'"></span></p>
                                </div>
                            </div>

                            <div class="flex items-start justify-between gap-3 pt-2 border-t border-gray-100">
                                <div class="min-w-0">
                                    <p class="text-[11px] font-semibold text-gray-700 break-words" x-text="shortAgent(row.user_agent)"></p>
                                    <p class="text-[10px] text-gray-400" x-text="statusText(row.status)"></p>
                                </div>
                                <span class="text-xs font-semibold shrink-0" :class="row._open ? 'text-green-700' : 'text-gray-500'" x-text="row._open ? '▲ Tutup detail' : '▼ Lihat detail'"></span>
                            </div>
                        </button>

                        <template x-if="row._open">
                            <div class="border-t border-green-100 bg-green-50/30 p-3 sm:p-4">
                                <div class="grid grid-cols-1 gap-3 text-xs">
                                    <div class="bg-white border border-gray-200 rounded-xl p-3">
                                        <p class="font-extrabold text-gray-700 mb-2">Identitas & Akses</p>
                                        <dl class="space-y-1.5 text-gray-700">
                                            <div><dt class="inline font-semibold text-gray-500">Akun:</dt> <dd class="inline break-words" x-text="row.user_id ? (row.user_name+' (ID '+row.user_id+', '+roleLabel(row.user_role)+')') : 'Guest/tamu (tanpa login)'"></dd></div>
                                            <div><dt class="inline font-semibold text-gray-500">IP:</dt> <dd class="inline font-mono break-all" x-text="row.ip || '-'"></dd></div>
                                            <div><dt class="inline font-semibold text-gray-500">Event:</dt> <dd class="inline font-mono break-all" x-text="row.event || '-'"></dd></div>
                                            <div><dt class="inline font-semibold text-gray-500">Request ID:</dt> <dd class="inline font-mono break-all" x-text="row.request_id || '-'"></dd></div>
                                            <div><dt class="inline font-semibold text-gray-500">Route:</dt> <dd class="inline font-mono break-all" x-text="row.route || '-'"></dd></div>
                                            <div><dt class="inline font-semibold text-gray-500">Path:</dt> <dd class="inline font-mono break-all" x-text="(row.method||'')+' '+(row.path||'')"></dd></div>
                                            <div><dt class="inline font-semibold text-gray-500">Status:</dt> <dd class="inline" x-text="(row.status||'-')+' '+statusText(row.status)"></dd></div>
                                        </dl>
                                    </div>
                                    <div class="bg-white border border-gray-200 rounded-xl p-3 min-w-0">
                                        <p class="font-extrabold text-gray-700 mb-2">Detail Input / Perubahan (tersanitasi)</p>
                                        <template x-if="row.properties && Object.keys(row.properties).length">
                                            <pre class="text-[11px] leading-relaxed bg-gray-900 text-green-100 rounded-lg p-3 overflow-x-auto max-w-full max-h-72 whitespace-pre-wrap break-all" x-text="JSON.stringify(row.properties, null, 2)"></pre>
                                        </template>
                                        <template x-if="!row.properties || !Object.keys(row.properties).length">
                                            <p class="text-gray-400">Tidak ada detail input tambahan.</p>
                                        </template>
                                    </div>
                                    <div class="bg-white border border-gray-200 rounded-xl p-3 min-w-0" x-show="row.has_file || row.file_name || row.file_path">
                                        <p class="font-extrabold text-gray-700 mb-2">File / Foto Masuk (Metadata)</p>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-gray-700">
                                            <div class="min-w-0"><span class="font-semibold text-gray-500">Nama:</span> <span class="break-all" x-text="row.file_name || '-'"></span></div>
                                            <div class="min-w-0"><span class="font-semibold text-gray-500">Jenis/MIME:</span> <span class="break-all" x-text="row.file_mime || '-'"></span></div>
                                            <div><span class="font-semibold text-gray-500">Ukuran:</span> <span x-text="row.file_size ? (row.file_size+' bytes') : '-'"></span></div>
                                            <div class="sm:col-span-2 min-w-0"><span class="font-semibold text-gray-500">Path:</span> <span class="font-mono break-all" x-text="row.file_path || '-'"></span></div>
                                            <div class="sm:col-span-2 min-w-0"><span class="font-semibold text-gray-500">Hash (SHA-256):</span> <span class="font-mono break-all" x-text="row.file_hash || '-'"></span></div>
                                        </div>
                                        <template x-if="row.file_path && (row.file_path.startsWith('uploads/images/') || row.file_mime?.startsWith('image/')) && !row.file_path.startsWith('reservations/proofs/')">
                                            <div class="mt-3">
                                                <p class="text-[11px] text-gray-500 mb-1">Pratinjau (jika file publik tersedia):</p>
                                                <img :src="'/storage/' + row.file_path" alt="Pratinjau file" class="max-h-40 rounded-lg border border-gray-200 bg-white" loading="lazy" onerror="this.style.display='none'">
                                            </div>
                                        </template>
                                        <p class="text-[11px] text-gray-500 mt-2" x-show="row.file_path && row.file_path.startsWith('reservations/proofs/')">Bukti pembayaran disimpan privat; tinjau via halaman verifikasi/admin.</p>
                                    </div>
                                    <div class="bg-white border border-gray-200 rounded-xl p-3 min-w-0">
                                        <p class="font-extrabold text-gray-700 mb-1">User Agent Lengkap</p>
                                        <p class="text-[11px] text-gray-600 break-all leading-relaxed" x-text="row.user_agent || '-'"></p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 px-4 sm:px-6 py-3 border-t border-gray-100 text-xs text-gray-500">
            <p>Halaman <span class="font-semibold" x-text="meta.current_page"></span> / <span class="font-semibold" x-text="meta.last_page"></span> · Total <span class="font-semibold" x-text="meta.total"></span> entri</p>
            <div class="flex gap-2">
                <button @click="go(meta.current_page - 1)" :disabled="meta.current_page <= 1"
                        class="px-3 py-2 sm:py-1.5 rounded-lg border border-gray-200 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed font-semibold min-h-[38px]">← Sebelumnya</button>
                <button @click="go(meta.current_page + 1)" :disabled="meta.current_page >= meta.last_page"
                        class="px-3 py-2 sm:py-1.5 rounded-lg border border-gray-200 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed font-semibold min-h-[38px]">Selanjutnya →</button>
            </div>
        </div>
    </div>
</div>

<script>
function activityPage() {
    return {
        rows: [],
        meta: { total: 0, per_page: 50, current_page: 1, last_page: 1, today: 0, guests_today: 0, accounts_today: 0, unique_ips_today: 0, retention_days: 365 },
        loading: true,
        search: '',
        role: 'all',
        event: '',
        subject: '',
        ip: '',
        from: '',
        to: '',
        autoRefresh: false,
        retention: 365,
        toast: { message: '', isError: false },
        showAdvancedFilter: false,

        init() {
            this.load();
            setInterval(() => { if (this.autoRefresh) this.load(); }, 10000);
        },

        async load() {
            this.loading = true;
            try {
                const params = new URLSearchParams({ page: this.meta.current_page, role: this.role });
                if (this.search.trim()) params.set('search', this.search.trim());
                if (this.event.trim()) params.set('event', this.event.trim());
                if (this.subject.trim()) params.set('subject', this.subject.trim());
                if (this.ip.trim()) params.set('ip', this.ip.trim());
                if (this.from) params.set('from', this.from);
                if (this.to) params.set('to', this.to);
                const res = await fetch('/api/admin/activity?' + params.toString(), { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                this.rows = (data.data || []).map(r => ({ ...r, _open: false }));
                this.meta = data.meta || this.meta;
                this.retention = this.meta.retention_days ?? 365;
            } catch (e) {
                this.showToast('Gagal memuat aktivitas.', true);
            } finally {
                this.loading = false;
            }
        },

        resetFilters() {
            this.search = ''; this.role = 'all'; this.event = ''; this.subject = ''; this.ip = ''; this.from = ''; this.to = '';
            this.go(1);
        },

        go(page) {
            if (page < 1 || (this.meta.last_page && page > this.meta.last_page)) return;
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
                if (res.ok && data.success) { this.showToast(data.message); this.go(1); }
                else { this.showToast(data.message ?? 'Gagal menghapus.', true); }
            } catch (e) { this.showToast('Koneksi gagal.', true); }
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
                    this.meta.retention_days = this.retention;
                    this.load();
                } else { this.showToast(data.message ?? 'Gagal mengatur retensi.', true); }
            } catch (e) { this.showToast('Koneksi gagal.', true); }
        },

        formatTime(iso) {
            if (!iso) return '-';
            return new Date(iso).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'medium' });
        },
        shortAgent(ua) {
            if (!ua) return '-';
            const browser = (ua.match(/Edg\/[\d.]+/) || ua.match(/(Firefox|Chrome|Safari|Opera|OPR)\/[\d.]+/) || [ua.slice(0, 40)])[0];
            const os = (ua.match(/(Windows NT [\d.]+|Android [\d.]+|iPhone OS [\d_]+|Mac OS X [\d_]+|Linux)/) || [''])[0].replace(/_/g, '.');
            return browser + (os ? ' · ' + os : '');
        },
        statusText(status) {
            if (status >= 500) return 'Server error';
            if (status >= 400) return 'Client error';
            if (status >= 300) return 'Redirect';
            if (status >= 200) return 'OK';
            return '';
        },
        roleLabel(role) {
            return { admin: 'ADMIN', kasir: 'KASIR', kasir_makanan: 'KASIR F&B', checkin: 'CHECK-IN', guest: 'TAMU' }[role] ?? (role || '-').toUpperCase();
        },
        methodBadge(method) {
            return { GET: 'bg-green-100 text-green-800', POST: 'bg-blue-100 text-blue-800', PUT: 'bg-amber-100 text-amber-800', PATCH: 'bg-amber-100 text-amber-800', DELETE: 'bg-red-100 text-red-800' }[method] ?? 'bg-gray-100 text-gray-600';
        },
        eventBadge(event) {
            if (!event) return 'bg-gray-100 text-gray-700';
            if (event.startsWith('auth.login.success')) return 'bg-green-100 text-green-800';
            if (event.startsWith('auth.login.failed') || event.startsWith('auth.login.blocked')) return 'bg-red-100 text-red-800';
            if (event.startsWith('auth.logout')) return 'bg-slate-100 text-slate-700';
            if (event.startsWith('reservation.')) return 'bg-indigo-100 text-indigo-800';
            if (event.startsWith('payment.')) return 'bg-emerald-100 text-emerald-800';
            if (event.startsWith('image.')) return 'bg-fuchsia-100 text-fuchsia-800';
            if (event.startsWith('pos.')) return 'bg-amber-100 text-amber-800';
            if (event.startsWith('checkin.')) return 'bg-cyan-100 text-cyan-800';
            if (event.startsWith('http.error')) return 'bg-orange-100 text-orange-800';
            return 'bg-gray-100 text-gray-700';
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
