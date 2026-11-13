@extends('layouts.app')
@section('title', 'Beli Tiket Masuk')

@section('content')
<div class="max-w-xl mx-auto" x-data="ticketForm()" x-init="loadUnits()">
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-green-800">🎫 Beli Tiket Masuk</h1>
        <p class="text-gray-500 mt-1">Tiket hari ini untuk Wisata Teduh — valid 1 hari</p>
    </div>

    {{-- Step 1: Pilih Tanggal & Tipe --}}
    <div x-show="step === 1" class="bg-white rounded-xl shadow p-6 space-y-5">
        <h2 class="font-semibold text-lg text-gray-700">Langkah 1: Pilih Tanggal Kunjungan</h2>

        <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Tanggal Kunjungan *</label>
            <input type="date" x-model="form.visit_date" :min="today()"
                   class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-400 outline-none">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Tipe Tiket *</label>
            <select x-model="form.unit_id" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-400 outline-none">
                <option value="">-- Pilih Tiket --</option>
                <template x-for="unit in availableUnits" :key="unit.id">
                    <option :value="unit.id" x-text="unit.name + ' — Rp ' + parseInt(unit.price).toLocaleString('id-ID') + ' / hari'"></option>
                </template>
            </select>
            <p class="text-xs text-gray-400 mt-1" x-show="selectedUnit">
                Kapasitas: <span x-text="selectedUnit.capacity + ' orang'"></span>
                &nbsp;|&nbsp; Tersisa: <span x-text="selectedUnit.stock + ' tiket'"></span>
            </p>
        </div>

        <button @click="step = 2" :disabled="!form.unit_id"
                class="w-full bg-green-700 text-white py-2 rounded-lg font-semibold hover:bg-green-800 transition disabled:opacity-50">
            Lanjutkan Isi Data
        </button>

        <div x-show="searched && availableUnits.length === 0" class="text-center text-red-500 text-sm py-3">
            Tidak ada tiket tersedia pada tanggal tersebut.
        </div>
    </div>

    {{-- Step 2: Data Pengunjung --}}
    <div x-show="step === 2" class="bg-white rounded-xl shadow p-6 space-y-5">
        <h2 class="font-semibold text-lg text-gray-700">Langkah 2: Data Pemesan</h2>

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
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-sm space-y-1">
            <p class="font-semibold text-green-800 mb-2">Ringkasan Pemesanan</p>
            <p>Tiket: <span class="font-medium" x-text="selectedUnit?.name"></span></p>
            <p>Tanggal: <span class="font-medium" x-text="form.visit_date"></span></p>
            <p class="text-base font-bold text-green-700 pt-1">
                Total: <span x-text="'Rp ' + (selectedUnit?.price ?? 0).toLocaleString('id-ID')"></span>
            </p>
        </div>

        <div class="flex gap-3">
            <button @click="step = 1" class="flex-1 border border-gray-300 py-2 rounded-lg text-gray-600 hover:bg-gray-50 transition">
                Kembali
            </button>
            <button @click="submitTicket()" :disabled="!form.visitor_name || !form.visitor_whatsapp || loading"
                    class="flex-1 bg-green-700 text-white py-2 rounded-lg font-semibold hover:bg-green-800 transition disabled:opacity-50">
                <span x-show="!loading">Beli & Bayar</span>
                <span x-show="loading">Memproses...</span>
            </button>
        </div>
    </div>

    {{-- Step 3: Upload Bukti Bayar --}}
    <div x-show="step === 3" class="bg-white rounded-xl shadow p-6 space-y-5">
        <div class="text-center">
            <div class="text-5xl mb-3">✅</div>
            <h2 class="font-bold text-xl text-green-700">Pemesanan Berhasil!</h2>
            <p class="text-gray-500 text-sm mt-1">
                Kode Order: <span class="font-mono font-bold text-gray-800" x-text="reservationCode"></span>
            </p>
        </div>

        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-sm text-yellow-800">
            Silakan transfer pembayaran ke rekening BUMDes Wisata Teduh dan upload bukti pembayaran di bawah ini.
            <br><strong>Rekening: BRI 1234-5678-9012 a.n. BUMDes Wisata Teduh</strong>
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
            <br>E-ticket dengan QR Code akan dikirim ke WhatsApp Anda.
        </div>
    </div>

    {{-- Error --}}
    <div x-show="error" class="mt-4 bg-red-100 border border-red-300 text-red-700 rounded-lg px-4 py-3 text-sm"
         x-text="error"></div>
</div>

<script>
function ticketForm() {
    return {
        step: 1, loading: false, searched: false, error: '',
        availableUnits: [], selectedUnit: null, buktiFile: null,
        uploadLoading: false, uploadDone: false, reservationCode: '',
        reservationId: null,
        form: {
            visit_date: '', unit_id: null,
            visitor_name: '', visitor_whatsapp: '', visitor_email: '',
        },
        today() {
            return new Date().toISOString().split('T')[0];
        },
        async loadUnits() {
            try {
                const r = await fetch('/api/tickets');
                const json = await r.json();
                this.availableUnits = json.data ?? [];
            } catch(e) {
                this.availableUnits = [];
            }
        },
        get selectedUnit() {
            return this.availableUnits.find(u => u.id == this.form.unit_id) ?? null;
        },
        async submitTicket() {
            this.loading = true; this.error = '';
            const unit = this.selectedUnit;
            if (!unit) { this.error = 'Pilih tiket terlebih dahulu.'; this.loading = false; return; }

            // hitung check_out = visit_date + 1 hari
            const visit = new Date(this.form.visit_date);
            const checkout = new Date(visit);
            checkout.setDate(checkout.getDate() + 1);
            const checkOutStr = checkout.toISOString().split('T')[0];

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
                        check_in_date: this.form.visit_date,
                        check_out_date: checkOutStr,
                        total_price: unit.price,
                        addons: [],
                    }),
                });
                const json = await r.json();
                if (!r.ok) {
                    this.error = json.message ?? 'Gagal membuat pemesanan.';
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
                fd.append('reservation_id', this.reservationId);
                const r = await fetch('/api/payments/upload', {
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