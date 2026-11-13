<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>QR Check-In &mdash; SIRESTEDUH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    {{-- html5-qrcode library --}}
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
</head>
<body class="bg-gray-100 text-gray-800 antialiased">

<div class="flex min-h-screen">
    {{-- Sidebar Check-in --}}
    <aside class="w-56 bg-teal-900 text-white flex flex-col fixed inset-y-0 left-0 z-30 shadow-xl">
        <div class="px-5 py-5 border-b border-teal-700">
            <p class="text-lg font-bold">📷 CHECK-IN</p>
            <p class="text-xs text-teal-300 mt-0.5">SIRESTEDUH</p>
        </div>
        <nav class="flex-1 px-3 py-4 text-sm">
            <a href="{{ route('checkin.officer.index') }}"
               class="flex items-center gap-2 px-3 py-2 rounded-lg bg-teal-700 text-white">
                📷 Scan QR
            </a>
        </nav>
        <div class="px-4 py-4 border-t border-teal-700 text-sm">
            <p class="text-teal-300 mb-2">{{ auth()->user()->name }}</p>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-red-300 hover:text-red-100">Keluar</button>
            </form>
        </div>
    </aside>

    {{-- Main --}}
    <div class="flex-1 ml-56 p-6" x-data="qrCheckin()">
        <div class="max-w-lg mx-auto">
            <h1 class="text-2xl font-bold text-teal-800 mb-6">📷 Scan Tiket QR</h1>

            {{-- Scanner Box --}}
            <div class="bg-white rounded-2xl shadow p-5 mb-5">
                <div id="qr-reader" class="w-full rounded-xl overflow-hidden mb-3"></div>

                {{-- Manual Input --}}
                <div class="flex gap-2">
                    <input type="text" x-model="manualHash" placeholder="Atau masukkan kode tiket manual..."
                           @keyup.enter="validateHash(manualHash)"
                           class="flex-1 border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-400 outline-none">
                    <button @click="validateHash(manualHash)" :disabled="!manualHash || loading"
                            class="bg-teal-700 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-teal-800 transition disabled:opacity-40">
                        Validasi
                    </button>
                </div>
            </div>

            {{-- Hasil Scan --}}
            <div x-show="result !== null"
                 :class="result?.success ? 'bg-green-50 border-green-300' : 'bg-red-50 border-red-300'"
                 class="border-2 rounded-2xl p-6 text-center transition-all">

                {{-- Sukses --}}
                <template x-if="result?.success">
                    <div>
                        <div class="text-5xl mb-3">✅</div>
                        <h2 class="text-xl font-bold text-green-700 mb-1">Check-In Berhasil!</h2>
                        <p class="text-gray-700 font-semibold" x-text="result.visitor?.name"></p>
                        <p class="text-sm text-gray-500" x-text="result.reservation?.order_code"></p>
                        <div class="mt-4 text-sm text-gray-600 space-y-1 text-left bg-white rounded-xl p-4 border">
                            <p>Unit: <strong x-text="result.reservation?.unit_name ?? '—'"></strong></p>
                            <p>Check-in: <strong x-text="result.reservation?.check_in_date"></strong></p>
                            <p>Check-out: <strong x-text="result.reservation?.check_out_date"></strong></p>
                        </div>
                    </div>
                </template>

                {{-- Gagal --}}
                <template x-if="result !== null && !result?.success">
                    <div>
                        <div class="text-5xl mb-3">❌</div>
                        <h2 class="text-xl font-bold text-red-600 mb-2">Tiket Tidak Valid</h2>
                        <p class="text-gray-600 text-sm" x-text="result?.message ?? 'Tiket tidak ditemukan atau sudah digunakan.'"></p>
                    </div>
                </template>

                <button @click="resetScan()" class="mt-5 bg-teal-700 text-white px-6 py-2 rounded-full font-semibold hover:bg-teal-800 transition">
                    Scan Berikutnya
                </button>
            </div>

            {{-- Riwayat Check-in Hari Ini --}}
            <div class="bg-white rounded-xl shadow p-5 mt-6">
                <h3 class="font-bold text-gray-700 mb-3">📋 Check-in Hari Ini</h3>
                <div class="text-sm text-gray-500 text-center py-4" x-show="history.length === 0">
                    Belum ada check-in hari ini.
                </div>
                <div class="divide-y">
                    <template x-for="h in history" :key="h.id">
                        <div class="py-2.5 flex justify-between items-center">
                            <div>
                                <p class="font-semibold text-sm" x-text="h.visitor_name"></p>
                                <p class="text-xs text-gray-400" x-text="h.order_code + ' &bull; ' + h.unit_name"></p>
                            </div>
                            <span class="text-xs text-teal-600 font-semibold bg-teal-50 px-2 py-0.5 rounded-full"
                                  x-text="h.checked_in_at"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function qrCheckin() {
    return {
        manualHash: '', loading: false, result: null, history: [],
        scanner: null,

        init() {
            this.$nextTick(() => this.startScanner());
        },

        startScanner() {
            try {
                this.scanner = new Html5Qrcode('qr-reader');
                this.scanner.start(
                    { facingMode: 'environment' },
                    { fps: 10, qrbox: { width: 250, height: 250 } },
                    (decodedText) => {
                        this.scanner.pause();
                        this.validateHash(decodedText);
                    },
                    () => {}
                ).catch(() => {});
            } catch(e) {}
        },

        async validateHash(hash) {
            if (!hash) return;
            this.loading = true;
            try {
                const r = await fetch('/api/checkin/validate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ hash }),
                });
                const json = await r.json();
                this.result = json;
                if (json.success && json.visitor) {
                    this.history.unshift({
                        id: Date.now(),
                        visitor_name: json.visitor.name,
                        order_code: json.reservation?.order_code ?? '',
                        unit_name: json.reservation?.unit_name ?? '',
                        checked_in_at: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
                    });
                }
            } catch(e) {
                this.result = { success: false, message: 'Gagal terhubung ke server.' };
            } finally {
                this.loading = false;
                this.manualHash = '';
            }
        },

        resetScan() {
            this.result = null;
            try { this.scanner?.resume(); } catch(e) {}
        }
    }
}
</script>
</body>
</html>
