@extends('layouts.admin')
@section('page-title', 'Mode Maintenance')

@section('content')
<div x-data="maintenancePage()" x-init="load()" class="max-w-3xl mx-auto space-y-6">

    {{-- Toast --}}
    <div x-show="toast.message" x-cloak x-transition
         :class="toast.isError ? 'bg-red-700' : 'bg-green-700'"
         class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 z-50 max-w-[calc(100vw-2rem)] text-white text-sm font-semibold rounded-xl shadow-xl px-5 py-3.5">
        <span x-text="toast.message"></span>
    </div>

    {{-- Status banner --}}
    <div class="rounded-2xl border p-4 sm:p-6 flex flex-col sm:flex-row items-start sm:items-center gap-4 sm:gap-5"
         :class="enabled ? 'bg-yellow-50 border-yellow-300' : 'bg-green-50 border-green-200'">
        <div class="text-5xl sm:text-6xl" x-text="enabled ? '🛠️' : '✅'"></div>
        <div>
            <p class="text-xs uppercase tracking-widest font-bold text-gray-500">Status Sistem</p>
            <p class="text-2xl font-extrabold" :class="enabled ? 'text-yellow-700' : 'text-green-800'" x-text="enabled ? 'SEDANG DALAM PERBAIKAN' : 'SISTEM AKTIF NORMAL'"></p>
            <p class="text-xs text-gray-500 mt-1" x-show="enabled">Hanya admin yang dapat login & mengakses aplikasi.</p>
        </div>
    </div>

    {{-- Toggle --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-6">
        <h2 class="font-bold text-gray-800">Aktifkan / Nonaktifkan</h2>
        <p class="text-xs text-gray-400 mt-1">Saat AKTIF, publik &amp; staf non-admin akan melihat halaman "Sedang dalam Perbaikan". Admin tetap bisa login dan bekerja penuh.</p>
        <button @click="toggle()" :disabled="saving"
                :class="enabled ? 'bg-yellow-500 hover:bg-yellow-400 text-yellow-950' : 'bg-yellow-600 hover:bg-yellow-500 text-white'"
                class="mt-4 w-full sm:w-auto font-extrabold px-8 py-4 rounded-2xl transition text-base">
            <span x-show="!saving" x-text="enabled ? '🔓 Matikan Maintenance (Aktifkan Sistem)' : '🛠️ Aktifkan Maintenance'"></span>
            <span x-show="saving" x-cloak>Menyimpan...</span>
        </button>
    </div>

    {{-- Message --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-6">
        <h2 class="font-bold text-gray-800">Pesan yang Ditampilkan</h2>
        <p class="text-xs text-gray-400 mt-1">Pesan ini yang tampil di halaman "Sedang dalam Perbaikan".</p>
        <textarea x-model="message" rows="3" maxlength="500"
                  class="mt-3 w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-600"></textarea>
        <div class="flex items-center justify-between gap-3 mt-3">
            <p class="text-[11px] text-gray-400"><span x-text="message.length"></span>/500 karakter</p>
            <button @click="saveMessage()" :disabled="saving"
                    :class="saving ? 'bg-gray-300 cursor-wait' : 'bg-green-700 hover:bg-green-800'"
                    class="text-sm font-bold text-white rounded-xl px-5 py-2.5 transition">💾 Simpan Pesan</button>
        </div>
    </div>

    {{-- Help --}}
    <div class="bg-blue-50 border border-blue-200 rounded-2xl p-5 text-sm text-blue-900">
        <p class="font-bold">ℹ️ Yang terjadi saat maintenance AKTIF:</p>
        <ul class="list-disc list-inside mt-2 space-y-1 text-blue-800">
            <li>Publik (home, checkout, pembayaran) melihat halaman "Sedang dalam Perbaikan"</li>
            <li>Login: <strong>hanya role admin</strong> yang berhasil masuk; Kasir &amp; Check-in ditolak</li>
            <li>API publik mengembalikan <code>HTTP 503</code></li>
            <li>Halaman admin tetap bisa diakses admin dengan normal</li>
        </ul>
        <a href="/" target="_blank" class="inline-flex items-center gap-1.5 mt-3 text-xs font-bold text-blue-800 hover:underline">👁️ Pratinjau halaman publik (buka di tab baru) →</a>
    </div>

</div>

<script>
function maintenancePage() {
    return {
        enabled: false,
        message: '',
        saving: false,
        toast: { message: '', isError: false },

        async load() {
            try {
                const res = await fetch('/api/admin/maintenance', { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                this.enabled = !!data.enabled;
                this.message = data.message || '';
            } catch (e) {
                this.showToast('Gagal memuat status maintenance.', true);
            }
        },

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        },

        async toggle() {
            this.saving = true;
            try {
                const res = await fetch('/api/admin/maintenance', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken() },
                    body: JSON.stringify({ enabled: !this.enabled, message: this.message }),
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.enabled = data.enabled;
                    this.message = data.message;
                    this.showToast(this.enabled ? 'Maintenance AKTIF — hanya admin yang bisa akses.' : 'Maintenance dimatikan — sistem kembali normal.');
                } else {
                    this.showToast(data.message ?? 'Gagal mengubah status.', true);
                }
            } catch (e) {
                this.showToast('Koneksi gagal.', true);
            } finally {
                this.saving = false;
            }
        },

        async saveMessage() {
            this.saving = true;
            try {
                const res = await fetch('/api/admin/maintenance', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken() },
                    body: JSON.stringify({ enabled: this.enabled, message: this.message }),
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.message = data.message;
                    this.showToast('Pesan maintenance tersimpan.');
                } else {
                    this.showToast(data.message ?? 'Gagal menyimpan pesan.', true);
                }
            } catch (e) {
                this.showToast('Koneksi gagal.', true);
            } finally {
                this.saving = false;
            }
        },

        showToast(message, isError = false) {
            this.toast = { message, isError };
            setTimeout(() => { this.toast = { message: '', isError: false }; }, 4000);
        },
    };
}
</script>
@endsection
