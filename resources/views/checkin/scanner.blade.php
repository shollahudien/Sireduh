<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Scanner Check-in &mdash; SIRESTEDUH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
</head>
<body class="bg-teal-950 min-h-screen text-gray-100 antialiased">

<div x-data="scannerPage()" x-init="init()" class="min-h-screen flex flex-col">

    {{-- Top bar --}}
    <header class="bg-teal-900/60 border-b border-teal-800 px-4 sm:px-6 py-3 flex items-center justify-between sticky top-0 z-30 backdrop-blur">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-teal-500 text-teal-950 rounded-xl flex items-center justify-center text-xl">📷</div>
            <div>
                <p class="font-extrabold leading-tight">Scanner Check-in</p>
                <p class="text-[11px] text-teal-300 uppercase tracking-widest">SIRESTEDUH &middot; Petugas</p>
            </div>
        </div>
        <div class="flex items-center gap-3 text-sm">
            <span class="hidden sm:block text-teal-200">{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-red-300 hover:text-red-100 font-semibold transition">Keluar</button>
            </form>
        </div>
    </header>

    {{-- Main --}}
    <main class="flex-1 w-full max-w-5xl mx-auto px-4 py-5 sm:py-8 grid lg:grid-cols-2 gap-5 items-start">

        {{-- ===== LEFT: Camera ===== --}}
        <section class="bg-teal-900/40 border border-teal-800 rounded-3xl overflow-hidden shadow-2xl">
            <div class="px-5 pt-5 pb-4 flex items-center justify-between">
                <div>
                    <h1 class="font-bold text-lg">Scan QR Tiket</h1>
                    <p class="text-xs text-teal-300">Arahkan kamera ke QR code pada e-tiket tamu.</p>
                </div>
                <span class="flex items-center gap-1.5 text-[11px] font-bold rounded-full px-3 py-1"
                      :class="cameraState === 'running' ? 'bg-green-500/15 text-green-300' : (cameraState === 'error' ? 'bg-red-500/15 text-red-300' : 'bg-yellow-500/15 text-yellow-300')">
                    <span class="w-2 h-2 rounded-full" :class="cameraState === 'running' ? 'bg-green-400 animate-pulse' : (cameraState === 'error' ? 'bg-red-400' : 'bg-yellow-400 animate-pulse')"></span>
                    <span x-text="cameraState === 'running' ? 'KAMERA AKTIF' : (cameraState === 'error' ? 'KAMERA ERROR' : 'MENYIAPKAN...')"></span>
                </span>
            </div>

            {{-- Camera viewport --}}
            <div class="relative mx-5 mb-4 rounded-2xl overflow-hidden bg-black border border-teal-800">
                <div id="reader" class="w-full min-h-56 [&_video]:w-full [&_video]:rounded-2xl"></div>
                {{-- Scan frame corners --}}
                <div class="pointer-events-none absolute inset-6">
                    <div class="absolute top-0 left-0 w-8 h-8 border-t-4 border-l-4 border-teal-400 rounded-tl-lg"></div>
                    <div class="absolute top-0 right-0 w-8 h-8 border-t-4 border-r-4 border-teal-400 rounded-tr-lg"></div>
                    <div class="absolute bottom-0 left-0 w-8 h-8 border-b-4 border-l-4 border-teal-400 rounded-bl-lg"></div>
                    <div class="absolute bottom-0 right-0 w-8 h-8 border-b-4 border-r-4 border-teal-400 rounded-br-lg"></div>
                </div>
            </div>

            {{-- Camera controls --}}
            <div class="px-5 pb-5 flex items-center gap-2">
                <button x-show="cameraState === 'error' || cameraState === 'stopped'" @click="startCamera()"
                        class="bg-teal-500 text-teal-950 text-sm font-bold rounded-xl px-4 py-2.5 hover:bg-teal-400 transition">
                    🔄 Coba Kamera Lagi
                </button>
                <button x-show="cameras.length > 1" @click="switchCamera()"
                        class="bg-teal-800 text-teal-100 text-sm font-semibold rounded-xl px-4 py-2.5 hover:bg-teal-700 transition">
                    🔁 Ganti Kamera
                </button>
                <p class="text-[11px] text-teal-400 leading-snug">Butuh izin kamera. Jika gagal, gunakan input kode manual di bawah.</p>
            </div>

            {{-- Manual input --}}
            <div class="border-t border-teal-800 px-5 py-4">
                <label for="manual_hash" class="text-[11px] font-semibold text-teal-300 uppercase tracking-wide">Kode Tiket Manual (SHA-256)</label>
                <form @submit.prevent="validate(manualHash)" class="flex gap-2 mt-1.5">
                    <input id="manual_hash" type="text" x-model="manualHash" placeholder="e.g. 9f3a1b... (64 karakter)"
                           autocomplete="off" spellcheck="false"
                           class="flex-1 bg-teal-950/60 border border-teal-800 rounded-xl px-3 py-2.5 text-sm font-mono placeholder-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-400">
                    <button type="submit" :disabled="!manualHash.trim() || loading"
                            class="bg-teal-600 disabled:bg-teal-800 disabled:text-teal-600 text-white text-sm font-bold rounded-xl px-4 py-2.5 hover:bg-teal-500 transition">
                        Validasi
                    </button>
                </form>
            </div>
        </section>

        {{-- ===== RIGHT: Result + history ===== --}}
        <section class="space-y-5">

            {{-- Result area --}}
            <div class="bg-teal-900/40 border border-teal-800 rounded-3xl p-5 shadow-2xl min-h-64">

                {{-- Idle --}}
                <template x-if="!scanResult">
                    <div class="h-full flex flex-col items-center justify-center text-center py-8">
                        <div class="text-6xl mb-4 opacity-60">🎫</div>
                        <p class="font-bold text-lg text-teal-100">Menunggu Scan...</p>
                        <p class="text-sm text-teal-400 mt-1 max-w-xs">Arahkan QR tiket ke kamera, atau tempel kode manual.</p>
                    </div>
                </template>

                {{-- Success --}}
                <template x-if="scanResult?.success">
                    <div class="text-center py-2">
                        <div class="w-20 h-20 bg-green-500 rounded-full flex items-center justify-center text-4xl mx-auto mb-4 shadow-lg shadow-green-500/30">✅</div>
                        <h2 class="text-2xl font-extrabold text-green-300">Check-in Berhasil!</h2>
                        <p class="text-teal-100 font-semibold mt-1" x-text="scanResult.reservation?.visitor_name"></p>
                        <dl class="mt-4 grid grid-cols-2 gap-2 text-left text-sm bg-teal-950/60 border border-teal-800 rounded-2xl p-4">
                            <div>
                                <dt class="text-[10px] uppercase text-teal-400 font-semibold">Order Code</dt>
                                <dd class="font-mono font-bold" x-text="scanResult.reservation?.order_code"></dd>
                            </div>
                            <div>
                                <dt class="text-[10px] uppercase text-teal-400 font-semibold">Unit</dt>
                                <dd class="font-semibold" x-text="scanResult.reservation?.unit_name ?? 'Tiket Masuk'"></dd>
                            </div>
                            <div>
                                <dt class="text-[10px] uppercase text-teal-400 font-semibold">Check-in</dt>
                                <dd x-text="scanResult.reservation?.check_in_date"></dd>
                            </div>
                            <div>
                                <dt class="text-[10px] uppercase text-teal-400 font-semibold">Check-out</dt>
                                <dd x-text="scanResult.reservation?.check_out_date"></dd>
                            </div>
                        </dl>
                        <button @click="resetScan()" class="mt-4 bg-green-500 text-green-950 font-extrabold rounded-2xl px-6 py-3 hover:bg-green-400 transition w-full">
                            Scan Tamu Berikutnya →
                        </button>
                    </div>
                </template>

                {{-- Error --}}
                <template x-if="scanResult && !scanResult.success">
                    <div class="text-center py-2">
                        <div class="w-20 h-20 bg-red-500 rounded-full flex items-center justify-center text-4xl mx-auto mb-4 shadow-lg shadow-red-500/30">❌</div>
                        <h2 class="text-2xl font-extrabold text-red-300">Check-in Gagal</h2>
                        <p class="text-sm text-red-200/80 mt-1" x-text="scanResult.message ?? 'Tiket tidak valid.'"></p>
                        <p class="text-[11px] text-teal-500 mt-3">Tiket hanya dapat digunakan 1 kali. Tamu mungkin sudah check-in sebelumnya.</p>
                        <button @click="resetScan()" class="mt-4 bg-red-500 text-white font-extrabold rounded-2xl px-6 py-3 hover:bg-red-400 transition w-full">
                            🔄 Coba Lagi
                        </button>
                    </div>
                </template>
            </div>

            {{-- Scan history (session) --}}
            <div class="bg-teal-900/40 border border-teal-800 rounded-3xl p-5">
                <h2 class="font-bold text-sm uppercase tracking-wide text-teal-300 mb-2">Riwayat Sesi Ini</h2>
                <p x-show="history.length === 0" class="text-sm text-teal-500 py-3">Belum ada tiket dipindai.</p>
                <ul class="divide-y divide-teal-800 max-h-52 overflow-y-auto">
                    <template x-for="item in history" :key="item.id">
                        <li class="py-2.5 flex items-center gap-3">
                            <span class="text-lg" x-text="item.success ? '✅' : '❌'"></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold truncate" x-text="item.order_code ?? '—'"></p>
                                <p class="text-[11px] text-teal-400 truncate" x-text="item.label + ' · ' + item.time"></p>
                            </div>
                        </li>
                    </template>
                </ul>
            </div>
        </section>
    </main>

    {{-- Bottom navigation --}}
    @include('checkin.partials.bottomnav')
    <div class="h-16 shrink-0"></div>
</div>

<script>
function scannerPage() {
    return {
        scanResult: null,
        isError: false,
        manualHash: '',
        loading: false,
        cameraState: 'starting',
        cameraError: '',
        cameras: [],
        activeCameraId: null,
        scanner: null,
        history: [],

        init() {
            this.$nextTick(() => this.startCamera());
        },

        async startCamera() {
            this.cameraState = 'starting';
            this.cameraError = '';

            try {
                this.cameras = await Html5Qrcode.getCameras();
            } catch (e) {
                this.cameras = [];
            }

            if (!this.scanner) {
                this.scanner = new Html5Qrcode('reader', { verbose: false });
            }

            const cameraConfig = this.activeCameraId
                ? { deviceId: { exact: this.activeCameraId } }
                : { facingMode: 'environment' };

            try {
                await this.scanner.start(
                    cameraConfig,
                    { fps: 10, qrbox: { width: 240, height: 240 }, aspectRatio: 1.0 },
                    (decodedText) => this.onScan(decodedText),
                    () => {}
                );
                this.cameraState = 'running';
            } catch (e) {
                this.cameraState = 'error';
                this.cameraError = 'Tidak bisa mengakses kamera. Periksa izin browser (atau gunakan input manual).';
            }
        },

        async switchCamera() {
            if (this.cameras.length < 2) return;
            const currentIndex = this.cameras.findIndex(c => c.id === this.activeCameraId);
            const next = this.cameras[(currentIndex + 1) % this.cameras.length];
            this.activeCameraId = next.id;
            try {
                await this.scanner.stop();
            } catch (e) {}
            await this.startCamera();
        },

        onScan(decodedText) {
            if (this.scanResult) return; // sudah ada hasil, abaikan frame berikutnya
            try { this.scanner.pause(true); } catch (e) {}
            this.validate(decodedText);
        },

        async validate(hash) {
            hash = (hash || '').trim();
            if (!hash || this.loading) return;

            this.loading = true;
            try {
                const res = await fetch('/api/checkin/validate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                    },
                    body: JSON.stringify({ hash }),
                });
                const data = await res.json();
                this.scanResult = data;
                this.isError = !data.success;
                this.pushHistory(data);

                if (data.success && navigator.vibrate) navigator.vibrate([80, 50, 80]);
                if (!data.success && navigator.vibrate) navigator.vibrate([250]);
            } catch (e) {
                this.scanResult = { success: false, message: 'Koneksi gagal. Periksa jaringan petugas.' };
                this.isError = true;
            } finally {
                this.loading = false;
                this.manualHash = '';
            }
        },

        pushHistory(data) {
            this.history.unshift({
                id: Date.now(),
                success: !!data.success,
                order_code: data.reservation?.order_code ?? null,
                label: data.success ? (data.reservation?.visitor_name ?? 'Tamu') : (data.message ?? 'Gagal'),
                time: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
            });
            this.history = this.history.slice(0, 10);
        },

        resetScan() {
            this.scanResult = null;
            this.isError = false;
            try { this.scanner?.resume(); } catch (e) {}
        },
    };
}
</script>
</body>
</html>
