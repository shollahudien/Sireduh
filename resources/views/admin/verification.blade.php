@extends('layouts.admin')
@section('page-title', 'Verifikasi Pembayaran')

@section('content')
<div x-data="verificationPage()" x-init="load(); setInterval(() => silentRefresh(), 10000)">

    {{-- Toast --}}
    <div x-show="toast.message" x-cloak x-transition
         :class="toast.isError ? 'bg-red-700' : 'bg-green-700'"
         class="fixed bottom-6 right-6 z-50 text-white text-sm font-semibold rounded-xl shadow-xl px-5 py-3.5 flex items-center gap-3">
        <span x-text="toast.isError ? '⚠️' : '✅'"></span><span x-text="toast.message"></span>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        {{-- Header --}}
        <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-gray-800">💳 Menunggu Verifikasi</h2>
                <p class="text-xs text-gray-400">Klik "Lihat Bukti" untuk cek bukti transfer, lalu verifikasi.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold bg-yellow-100 text-yellow-800 rounded-full px-3 py-1.5" x-text="reservations.length + ' pending'"></span>
                <button @click="load()" class="text-xs font-semibold text-gray-500 hover:text-green-700 border border-gray-200 hover:border-green-400 rounded-full px-3 py-1.5 transition">↻ Muat ulang</button>
            </div>
        </div>

        {{-- Loading / error / empty --}}
        <div x-show="loading" x-cloak class="py-16 flex items-center justify-center gap-3 text-gray-400">
            <svg class="animate-spin h-6 w-6 text-green-700" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            Memuat pembayaran...
        </div>
        <div x-show="error && !loading" x-cloak class="py-10 text-center text-red-600 text-sm" x-text="error"></div>
        <div x-show="!loading && !error && reservations.length === 0" x-cloak class="py-16 text-center">
            <div class="text-5xl mb-3">🎉</div>
            <p class="font-semibold text-gray-700">Tidak ada pembayaran menunggu verifikasi.</p>
            <p class="text-sm text-gray-400 mt-1">Semua bukti transfer sudah diverifikasi.</p>
        </div>

        {{-- Table --}}
        <div x-show="!loading && reservations.length > 0" x-cloak class="overflow-x-auto">
            <table class="rtable w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-400 text-xs uppercase tracking-wide border-b">
                        <th class="pb-3 pr-4 pl-6">Kode Order</th>
                        <th class="pb-3 pr-4">Tamu</th>
                        <th class="pb-3 pr-4">Unit</th>
                        <th class="pb-3 pr-4">Check-in → Out</th>
                        <th class="pb-3 pr-4 text-right">Total</th>
                        <th class="pb-3 pr-4">Bukti</th>
                        <th class="pb-3 pr-6">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-for="r in reservations" :key="r.id">
                        <tr class="hover:bg-gray-50 align-middle">
                            <td data-label="Kode Order" class="py-3 pr-4 pl-6">
                                <p class="font-mono font-bold text-xs text-gray-800" x-text="r.order_code"></p>
                                <p class="text-[11px] text-gray-400" x-text="r.created_at_human"></p>
                            </td>
                            <td data-label="Tamu" class="py-3 pr-4">
                                <p class="font-semibold text-gray-800" x-text="r.visitor_name"></p>
                                <p class="text-xs text-gray-400" x-text="formatWa(r.visitor_whatsapp)"></p>
                            </td>
                            <td data-label="Unit" class="py-3 pr-4 text-gray-600" x-text="r.unit_name ?? 'Tiket Masuk'"></td>
                            <td data-label="Check-in → Out" class="py-3 pr-4 whitespace-nowrap text-gray-600">
                                <span x-text="r.check_in_date"></span><br>
                                <span class="text-xs text-gray-400" x-text="'s/d ' + r.check_out_date"></span>
                            </td>
                            <td data-label="Total" class="py-3 pr-4 text-right font-bold text-green-800 whitespace-nowrap" x-text="formatIDR(r.total_price)"></td>
                            <td data-label="Bukti" class="py-3 pr-4">
                                <button @click="openProof(r)" :disabled="!r.proof_image"
                                        :class="r.proof_image ? 'text-blue-700 bg-blue-50 hover:bg-blue-100' : 'text-gray-300 bg-gray-50 cursor-not-allowed'"
                                        class="text-xs font-bold rounded-lg px-3 py-1.5 transition">
                                    🔍 Lihat Bukti
                                </button>
                            </td>
                            <td class="py-3 pr-6">
                                <button @click="verify(r)" :disabled="verifyingId === r.id"
                                        :class="verifyingId === r.id ? 'bg-gray-200 text-gray-400 cursor-wait' : 'bg-green-700 text-white hover:bg-green-800'"
                                        class="text-xs font-bold rounded-lg px-4 py-2 transition flex items-center gap-1.5">
                                    <span x-show="verifyingId === r.id" class="animate-spin inline-block">⏳</span>
                                    <span x-text="verifyingId === r.id ? 'Memproses...' : '✅ Verifikasi'"></span>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Proof modal --}}
    <div x-show="proofModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60" @click="proofModalOpen = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100">
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide">Bukti Transfer</p>
                    <p class="font-mono font-bold text-gray-800" x-text="proofReservation?.order_code"></p>
                </div>
                <button @click="proofModalOpen = false" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">×</button>
            </div>
            <div class="p-4 bg-gray-50 flex items-center justify-center min-h-64">
                <img x-show="proofReservation" :src="'/admin/proofs/' + proofReservation?.id" alt="Bukti transfer"
                     class="max-h-96 w-auto rounded-lg shadow border border-gray-200">
            </div>
            <div class="px-5 py-4 flex items-center justify-between gap-3 border-t border-gray-100">
                <div>
                    <p class="text-xs text-gray-400">Total pembayaran</p>
                    <p class="font-extrabold text-green-800" x-text="formatIDR(proofReservation?.total_price)"></p>
                </div>
                <button @click="verify(proofReservation)" :disabled="verifyingId === proofReservation?.id"
                        class="bg-green-700 text-white text-sm font-bold rounded-xl px-5 py-2.5 hover:bg-green-800 transition">
                    ✅ Verifikasi Sekarang
                </button>
            </div>
        </div>
    </div>

</div>

<script>
function verificationPage() {
    return {
        reservations: [],
        loading: true,
        error: '',
        proofModalOpen: false,
        proofReservation: null,
        verifyingId: null,
        toast: { message: '', isError: false },

        formatWa(raw) {
            // Nomor tersimpan 628… → tampilkan 08… agar familiar
            const digits = String(raw ?? '').replace(/\D/g, '');
            if (digits.startsWith('62')) return '0' + digits.slice(2);
            return String(raw ?? '');
        },

        async load() {
            this.loading = true;
            this.error = '';
            try {
                const res = await fetch('/api/admin/reservations/pending', { headers: { 'Accept': 'application/json' } });
                if (!res.ok) throw new Error('Gagal memuat data (' + res.status + ')');
                const data = await res.json();
                this.reservations = data.data || [];
            } catch (e) {
                this.error = e.message || 'Gagal memuat data.';
            } finally {
                this.loading = false;
            }
        },

        async silentRefresh() {
            try {
                const res = await fetch('/api/admin/reservations/pending', { headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                this.reservations = data.data || [];
            } catch (e) {
                // silent refresh; abaikan kegagalan sementara
            }
        },

        openProof(r) {
            this.proofReservation = r;
            this.proofModalOpen = true;
        },

        formatIDR(value) {
            return 'Rp ' + Math.round(Number(value) || 0).toLocaleString('id-ID');
        },

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        },

        async verify(r) {
            if (!r || this.verifyingId) return;
            this.verifyingId = r.id;
            try {
                const res = await fetch('/api/admin/verify/' + r.id, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                });
                const data = await res.json();

                if (res.ok && data.success) {
                    this.reservations = this.reservations.filter(item => item.id !== r.id);
                    this.proofModalOpen = false;
                    this.showToast('Pembayaran ' + r.order_code + ' berhasil diverifikasi. E-ticket dikirim.');
                } else {
                    this.showToast(data.message || 'Verifikasi gagal.', true);
                }
            } catch (e) {
                this.showToast('Koneksi gagal. Coba lagi.', true);
            } finally {
                this.verifyingId = null;
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
