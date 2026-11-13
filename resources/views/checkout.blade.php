@extends('layouts.app')
@section('title', 'Reservasi Unit')

@section('content')
<div class="max-w-2xl mx-auto" x-data="checkoutForm()" x-init="loadUnits()">
    <h1 class="text-2xl font-bold text-green-800 mb-6">📋 Form Reservasi</h1>

    {{-- Step 1: Pilih Tanggal & Unit --}}
    <div x-show="step === 1" class="bg-white rounded-xl shadow p-6 space-y-5">
        <h2 class="font-semibold text-lg text-gray-700">Langkah 1: Pilih Tanggal & Unit</h2>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Check-in</label>
                <input type="date" x-model="form.check_in_date" :min="today()"
                       class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-400 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Check-out</label>
                <input type="date" x-model="form.check_out_date" :min="form.check_in_date || today()"
                       class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-400 outline-none">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Tipe Unit</label>
            <select x-model="form.type" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-400 outline-none">
                <option value="">-- Semua Tipe --</option>
                <option value="glamping">Glamping</option>
                <option value="cottage">Cottage</option>
                <option value="villa">Villa</option>
            </select>
        </div>

        <button @click="cekKetersediaan()"
                :disabled="!form.check_in_date || !form.check_out_date || loading"
                class="w-full bg-green-700 text-white py-2 rounded-lg font-semibold hover:bg-green-800 transition disabled:opacity-50">
            <span x-show="!loading">Cek Ketersediaan</span>
            <span x-show="loading">Memeriksa...</span>
        </button>

        <div x-show="availableUnits.length > 0" class="pt-2">
            <p class="text-sm text-gray-500 mb-3" x-text="availableUnits.length + ' unit tersedia'"></p>
            <div class="space-y-3">
                <template x-for="unit in availableUnits" :key="unit.id">
                    <div @click="pilihUnit(unit)"
                         :class="form.unit_id === unit.id ? 'border-green-500 bg-green-50' : 'border-gray-200'"
                         class="border-2 rounded-lg p-4 cursor-pointer hover:border-green-400 transition flex justify-between items-center">
                        <div>
                            <p class="font-semibold" x-text="unit.name"></p>
                            <p class="text-sm text-gray-500 capitalize" x-text="unit.type + ' &bull; Kapasitas ' + unit.capacity + ' orang'"></p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-green-700"
                               x-text="'Rp ' + parseInt(unit.price).toLocaleString('id-ID')"></p>
                            <p class="text-xs text-gray-400">per malam</p>
                        </div>
                    </div>
                </template>
            </div>
            <button @click="step = 2" :disabled="!form.unit_id"
                    class="mt-4 w-full bg-green-700 text-white py-2 rounded-lg font-semibold hover:bg-green-800 transition disabled:opacity-50">
                Lanjutkan
            </button>
        </div>

        <div x-show="searched && availableUnits.length === 0"
             class="text-center text-red-500 text-sm py-3">
            Tidak ada unit tersedia pada tanggal yang dipilih.
        </div>
    </div>

    {{-- Step 2: Data Pengunjung --}}
    <div x-show="step === 2" class="bg-white rounded-xl shadow p-6 space-y-5">
        <h2 class="font-semibold text-lg text-gray-700">Langkah 2: Data Pengunjung</h2>

        <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Nama Lengkap *</label>
            <input type="text" x-model="form.visitor_name" placeholder="Budi Santoso"
                   class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-400 outline-none">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Nomor WhatsApp *</label>
            <input type="text" x-model="form.visitor_whatsapp" placeholder="081234567890"
                   class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-400 outline-none">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Email (opsional)</label>
            <input type="email" x-model="form.visitor_email" placeholder="email@example.com"
                   class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-400 outline-none">
        </div>

        {{-- Ringkasan --}}
        <div class="bg-gray-50 rounded-lg p-4 text-sm space-y-1 border">
            <p class="font-semibold text-gray-700 mb-2">Ringkasan Pemesanan</p>
            <p>Unit: <span class="font-medium" x-text="selectedUnit?.name"></span></p>
            <p>Check-in: <span class="font-medium" x-text="form.check_in_date"></span></p>
            <p>Check-out: <span class="font-medium" x-text="form.check_out_date"></span></p>
            <p>Jumlah Malam: <span class="font-medium" x-text="jumlahMalam"></span></p>
            <p class="text-base font-bold text-green-700 pt-1">
                Total: <span x-text="'Rp ' + totalHarga.toLocaleString('id-ID')"></span>
            </p>
        </div>

        <div class="flex gap-3">
            <button @click="step = 1" class="flex-1 border border-gray-300 py-2 rounded-lg text-gray-600 hover:bg-gray-50 transition">
                Kembali
            </button>
            <button @click="submitReservasi()"
                    :disabled="!form.visitor_name || !form.visitor_whatsapp || loading"
                    class="flex-1 bg-green-700 text-white py-2 rounded-lg font-semibold hover:bg-green-800 transition disabled:opacity-50">
                <span x-show="!loading">Buat Reservasi</span>
                <span x-show="loading">Memproses...</span>
            </button>
        </div>
    </div>

    {{-- Step 3: Upload Bukti Bayar --}}
    <div x-show="step === 3" class="bg-white rounded-xl shadow p-6 space-y-5">
        <div class="text-center">
            <div class="text-5xl mb-3">✅</div>
            <h2 class="font-bold text-xl text-green-700">Reservasi Berhasil!</h2>
            <p class="text-gray-500 text-sm mt-1">
                Kode Reservasi: <span class="font-mono font-bold text-gray-800" x-text="reservationCode"></span>
            </p>
        </div>

        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-sm text-yellow-800">
            Silakan transfer pembayaran ke rekening BUMDes dan upload bukti pembayaran di bawah ini.
            <br><strong>Rekening: BRI 1234-5678-9012</strong>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Upload Bukti Transfer *</label>
            <input type="file" accept="image/*" @change="buktiFile = $event.target.files[0]"
                   class="w-full border rounded-lg px-3 py-2 file:mr-3 file:py-1 file:px-3 file:border-0 file:bg-green-100 file:text-green-700 file:rounded file:cursor-pointer">
        </div>

        <button @click="uploadBukti()" :disabled="!buktiFile || uploadLoading"
                class="w-full bg-green-700 text-white py-2 rounded-lg font-semibold hover:bg-green-800 transition disabled:opacity-50">
            <span x-show="!uploadLoading">Upload Bukti Bayar</span>
            <span x-show="uploadLoading">Mengupload...</span>
        </button>

        <div x-show="uploadDone" class="text-center text-green-600 font-semibold">
            ✅ Bukti pembayaran berhasil dikirim! Tim kami akan memverifikasi dalam 1x24 jam.
        </div>
    </div>

    {{-- Error --}}
    <div x-show="error" class="mt-4 bg-red-100 border border-red-300 text-red-700 rounded-lg px-4 py-3 text-sm"
         x-text="error"></div>
</div>

<script>
function checkoutForm() {
    return {
        step: 1, loading: false, searched: false, error: '',
        availableUnits: [], selectedUnit: null, buktiFile: null,
        uploadLoading: false, uploadDone: false, reservationCode: '',
        reservationId: null,
        form: {
            check_in_date: '', check_out_date: '', type: '',
            unit_id: null, visitor_name: '', visitor_whatsapp: '', visitor_email: '',
        },
        get jumlahMalam() {
            if (!this.form.check_in_date || !this.form.check_out_date) return 0;
            const ms = new Date(this.form.check_out_date) - new Date(this.form.check_in_date);
            return Math.max(0, ms / 86400000);
        },
        get totalHarga() {
            return (this.selectedUnit?.price ?? 0) * this.jumlahMalam;
        },
        today() {
            return new Date().toISOString().split('T')[0];
        },
        loadUnits() {},
        pilihUnit(unit) {
            this.selectedUnit = unit;
            this.form.unit_id = unit.id;
        },
        async cekKetersediaan() {
            this.loading = true; this.error = ''; this.searched = false;
            try {
                const params = new URLSearchParams({
                    check_in: this.form.check_in_date,
                    check_out: this.form.check_out_date,
                });
                if (this.form.type) params.append('type', this.form.type);
                const r = await fetch('/api/availability?' + params);
                const json = await r.json();
                this.availableUnits = json.data ?? [];
                this.searched = true;
            } catch(e) {
                this.error = 'Gagal memeriksa ketersediaan. Coba lagi.';
            } finally {
                this.loading = false;
            }
        },
        async submitReservasi() {
            this.loading = true; this.error = '';
            try {
                const r = await fetch('/api/reservations', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        visitor_name: this.form.visitor_name,
                        visitor_whatsapp: this.form.visitor_whatsapp,
                        visitor_email: this.form.visitor_email,
                        unit_id: this.form.unit_id,
                        check_in_date: this.form.check_in_date,
                        check_out_date: this.form.check_out_date,
                        total_price: this.totalHarga,
                    }),
                });
                const json = await r.json();
                if (!r.ok) {
                    this.error = json.message ?? 'Gagal membuat reservasi.';
                    return;
                }
                this.reservationCode = json.order_code;
                this.reservationId  = json.reservation_id;
                this.step = 3;
            } catch(e) {
                this.error = 'Terjadi kesalahan jaringan. Coba lagi.';
            } finally {
                this.loading = false;
            }
        },
        async uploadBukti() {
            this.uploadLoading = true; this.error = '';
            try {
                const fd = new FormData();
                fd.append('proof_image', this.buktiFile);
                const r = await fetch('/api/reservations/' + this.reservationId + '/payment', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: fd,
                });
                const json = await r.json();
                if (!r.ok) { this.error = json.message ?? 'Gagal upload bukti.'; return; }
                this.uploadDone = true;
            } catch(e) {
                this.error = 'Gagal upload. Coba lagi.';
            } finally {
                this.uploadLoading = false;
            }
        },
    }
}
</script>
@endsection
