<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Reservation;
use App\Models\Unit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Asisten virtual publik SIRESTEDUH.
 *
 * Otak utama: Google Gemini (gratis). Jika key tidak diisi, API limit,
 * atau gagal — otomatis fallback ke bot cerdas berbasis FAQ + data DB
 * sehingga pelanggan selalu mendapat jawaban.
 *
 * Untuk pertanyaan ketersediaan, tanggal dalam Bahasa Indonesia diparse
 * (mis. "20 desember", "besok", "2 malam") lalu ketersediaan dihitung
 * real-time dari data reservasi (sumber sama dengan logika booking).
 */
class ChatbotService
{
    /** Kata kunci pertanyaan ketersediaan. */
    private const AVAILABILITY_KEYWORDS = [
        'tersedia', 'ketersediaan', 'kosong', 'penuh', 'sisa', 'available',
        'habis', 'masih ada', 'sold out', 'full',
    ];

    /**
     * Kunci cache konteks harga. Dihapus otomatis oleh event model
     * Unit/Product (booted) agar harga jawaban selalu sinkron dengan DB.
     */
    public const CONTEXT_CACHE_KEY = 'chatbot_business_context';

    public function __construct(protected AvailabilityService $availability, protected ChatbotMessageService $messages) {}

    /**
     * @param  array<int, array{role: string, text: string}>  $history
     *                                                                  Riwayat percakapan widget (user/bot, maks 10).
     *                                                                  Kirim ke Gemini sebagai multi-turn agar jawaban
     *                                                                  kontekstual ("yang kedua berapa?"); fallback FAQ
     *                                                                  tetap stateless dari data DB.
     */
    public function ask(string $question, array $history = [], ?string $userName = null, ?string $sessionId = null): string
    {
        $question = trim(strip_tags($question));
        $userName = $userName !== null ? trim(strip_tags($userName)) : null;
        $sessionId = $sessionId !== null ? trim($sessionId) : null;

        if ($userName === '') {
            $userName = null;
        }
        if ($sessionId === '') {
            $sessionId = null;
        }

        if ($question === '') {
            $question = 'Halo';
        }

        try {
            if ($sessionId) {
                $this->messages->log($sessionId, 'user', $question, $userName, ['source' => 'web']);
                if (empty($history)) {
                    $history = $this->messages->recentHistory($sessionId, 12);
                }
            }
        } catch (Throwable $e) {
            Log::warning('Gagal menyimpan/log histori chat user', ['error' => $e->getMessage()]);
        }

        $stay = $this->extractStay($question);
        $availability = null;

        if ($this->isAvailabilityQuestion($question, $stay)) {
            $availability = $stay !== null
                ? $this->availabilityContext($stay['check_in'], $stay['nights'])
                : $this->availabilityOverview();
        }

        $orderCode = $this->extractOrderCode($question);
        $reservationStatus = $orderCode !== null ? $this->reservationStatusContext($orderCode) : null;

        $key = (string) config('services.gemini.key', '');
        $learning = '';
        try {
            $learning = $this->messages->relevantExamples($question, 6);
        } catch (Throwable $e) {
            $learning = '';
        }

        $reply = null;
        if ($key === '') {
            $reply = 'Maaf, asisten AI sedang tidak tersedia saat ini. Silakan coba lagi nanti atau hubungi WhatsApp pengelola. 🙏';
        } else {
            try {
                $reply = $this->askGemini(
                    $key,
                    $question,
                    $this->systemPrompt($availability, $reservationStatus, $userName, $learning),
                    $history
                );
            } catch (Throwable $e) {
                Log::warning('Chatbot Gemini gagal', ['error' => $e->getMessage()]);
                $reply = null;
            }
            if ($reply === null) {
                $reply = 'Maaf, asisten AI sedang sibuk. Silakan coba lagi sebentar ya. 🙏';
            }
        }

        try {
            if ($sessionId) {
                $this->messages->log($sessionId, 'bot', $reply, $userName, ['source' => 'web', 'question' => mb_substr($question, 0, 200)]);
            }
        } catch (Throwable $e) {
            Log::warning('Gagal menyimpan/log histori chat bot', ['error' => $e->getMessage()]);
        }

        return $reply;
    }

    /**
     * Tanyakan ke Gemini. Mengembalikan null bila respons tidak bisa dipakai.
     *
     * @param  array<int, array{role: string, text: string}>  $history
     */
    protected function askGemini(string $key, string $question, string $system, array $history = []): ?string
    {
        $baseUrl = rtrim((string) config('services.gemini.base_url'), '/');
        $model = (string) config('services.gemini.model', 'gemini-3.5-flash-lite');

        $contents = [];
        $history = array_slice($history, -6);

        foreach ($history as $turn) {
            $text = trim(strip_tags((string) ($turn['text'] ?? '')));

            if ($text === '') {
                continue;
            }

            $role = ($turn['role'] ?? 'user') === 'bot' ? 'model' : 'user';

            $contents[] = ['role' => $role, 'parts' => [['text' => mb_substr($text, 0, 350)]]];
        }

        while ($contents !== [] && $contents[0]['role'] === 'model') {
            array_shift($contents);
        }

        $contents[] = ['role' => 'user', 'parts' => [['text' => mb_substr($question, 0, 500)]]];

        $response = Http::timeout(10)
            ->connectTimeout(5)
            ->asJson()
            ->post("{$baseUrl}/models/{$model}:generateContent?key={$key}", [
                'systemInstruction' => [
                    'parts' => [['text' => $system]],
                ],
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => 0.4,
                    'maxOutputTokens' => 350,
                ],
            ]);

        if ($response->failed()) {
            return null;
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($text) || trim($text) === '') {
            return null;
        }

        return trim($text);
    }

    /**
     * Instruksi sistem + konteks bisnis terkini (dari database, cache 5 menit).
     * Bisa dilengkapi blok ketersediaan real-time bila pertanyaannya soal tanggal.
     */
    protected function systemPrompt(?string $availability = null, ?string $reservationStatus = null, ?string $userName = null, ?string $learningExamples = null): string
    {
        $prompt = <<<'PROMPT'
Kamu adalah "Teduh Bot", asisten virtual AI di situs SIRESTEDUH (BUMDes Wisata Teduh).

Aturan:
1. Jawab dalam Bahasa Indonesia yang ramah, jelas, dan membantu.
2. Kamu bisa menjawab PERTANYAAN APA SAJA (umum, pengetahuan, saran, teknologi, dll), bukan hanya seputar Teduh.
3. Jika pertanyaan berkaitan dengan Teduh Glamping/SIRESTEDUH (unit, harga, tiket, alat wisata, aula, UMKM, reservasi, pembayaran, e-tiket, check-in, status pesanan, ketersediaan), gunakan data dari KONTEKS BISNIS di bawah dan JANGAN mengarang harga, tanggal, kebijakan, atau ketersediaan.
4. Untuk ketersediaan: gunakan KETERSEDIAAN TERKINI bila ada dan jangan menebak. Jika ditanya ketersediaan tanpa tanggal, minta tanggal check-in yang diinginkan.
5. Untuk status pesanan: gunakan STATUS PESANAN bila ada. Jika ditanya status tanpa kode order, minta kode order (contoh: SIRES-XXXXXX) atau arahkan ke halaman status.
6. WAJIB: Jika ada CONTOH PERCAKAPAN TERDAHULU (data yang sudah dipelajari) yang relevan dengan pertanyaan, jawab selaras dan konsisten mengikuti jawaban tersebut; gunakan sebagai acuan utama untuk pertanyaan serupa. Jangan bertentangan kecuali data/konteks bisnis terkini menyatakan berbeda.
7. Arahkan ke halaman yang relevan bila sesuai: checkout (reservasi/pembelian), status (cek pesanan & QR e-tiket), pembayaran {kode-order} (unggah bukti).
8. Format jawaban rapi (baris baru/daftar dengan - bila perlu). Hindari markdown tebal (**) atau tabel.
9. Pahami pertanyaan lanjutan dari riwayat percakapan.

KONTEKS BISNIS TEDUH (data terkini, gunakan bila relevan):
PROMPT.($userName !== null ? "Nama tamu: {$userName}. Sapa bila relevan.\n\n" : "\n").$this->businessContext();

        if ($availability !== null) {
            $prompt .= "\n\nKETERSEDIAAN TERKINI (real-time):\n".$availability;
        }

        if ($reservationStatus !== null) {
            $prompt .= "\n\nSTATUS PESANAN (real-time):\n".$reservationStatus;
        }

        if ($learningExamples) {
            $prompt .= "\n\nCONTOH PERCAKAPAN TERDAHULU (data yang sudah dipelajari, wajib dijadikan acuan jawaban bila relevan dengan pertanyaan):\n".$learningExamples;
        }

        $prompt .= "\n\nINFORMASI FASILITAS & WISATA TEDUH GLAMPING BONDOWOSO (gunakan untuk menjawab pertanyaan fasilitas/pemandian/wisata):\n".$this->facilityContext();

        return $prompt;
    }

    protected function facilityContext(): string
    {
        return <<<'TEXT'
Teduh Glamping Bondowoso (lereng Gunung Raung, Sumberwringin) memiliki fasilitas pemandian berupa kolam alami (sendang) yang berasal langsung dari sumber mata air pegunungan. Selain area berendam dan bermain air, kawasan wisata ini dilengkapi berbagai fasilitas penunjang untuk pengunjung harian maupun tamu yang menginap.

Fasilitas Pemandian & Kolam Alami:
- Sendang Alami: kolam pemandian terbentuk dari aliran tiga sumber mata air asli yang dibendung menyerupai kolam renang terbuka. Air sangat jernih dan terasa dingin karena mengalir langsung dari pegunungan tanpa kaporit.
- Aliran Sungai & Air Terjun Mini: kawasan sekitar kolam terhubung dengan aliran sungai berbatu dan pancuran air menyerupai air terjun mini, cocok untuk bermain air bersama keluarga.
- Kamar Mandi & Toilet Bilas: tersedia toilet dan ruang ganti gratis di sekitar area wisata untuk pengunjung yang selesai berenang atau bermain air.

Fasilitas Akomodasi & Area Santai:
- Tenda & Kabin Glamping: unit tenda kerucut kayu serta anjungan gubuk kayu yang menghadap ke aliran sungai atau sendang. Paket menginap umumnya dilengkapi kasur/matras, bantal, selimut, colokan listrik, hingga fasilitas api unggun dan barbeque.
- Teduh Cafe & Area Kuliner: pengunjung dapat bersantai di kursi kayu tepi sungai sambil menikmati kopi Arabika dan Robusta lokal lereng Gunung Raung. Tersedia warung dan pojok UMKM desa (camilan, minuman herbal, makanan tradisional).
- Pendopo & Gazebo: area berkumpul berupa pendopo luas, aula makan, dan gazebo untuk beristirahat maupun acara komunitas/keluarga.

Fasilitas Umum & Aktivitas Tambahan:
- Fasilitas Ibadah & Parkir: tersedia musala yang memadai serta area parkir kendaraan di dalam kawasan wisata.
- Jalur Trekking & Pos Registrasi: lokasi ini berfungsi sebagai pos registrasi pendakian Gunung Raung via Sumberwringin sekaligus menyediakan jalur trekking, penyewaan sepeda, dan paket ATV keliling desa.
TEXT;
    }

    /**
     * Ringkasan data bisnis dari DB: unit, tiket, produk, pembayaran.
     * Di-cache agar tidak query setiap pesan.
     */
    protected function businessContext(): string
    {
        try {
            return Cache::remember(self::CONTEXT_CACHE_KEY, 300, function (): string {
                $lines = [];

                $unitLabels = ['glamping' => 'Glamping (menginap)', 'aula' => 'Aula (sewa)', 'alat_wisata' => 'Alat Wisata (sewa)'];

                Unit::query()->orderBy('type')->orderBy('name')->get(['name', 'type', 'price', 'capacity', 'stock'])->each(function (Unit $u) use (&$lines, $unitLabels): void {
                    $label = $unitLabels[$u->type] ?? $u->type;
                    $lines[] = sprintf('- Unit %s "%s": Rp %s/kali pakai, kapasitas %d orang, total unit %d.', $label, $u->name, number_format((float) $u->price, 0, ',', '.'), $u->capacity, $u->stock);
                });

                $productLabels = [
                    'tiket_masuk' => 'Tiket Masuk', 'makanan' => 'Makanan', 'minuman' => 'Minuman',
                    'souvenir' => 'Souvenir', 'baju_merchandise' => 'Merchandise', 'produk_umkm' => 'Produk UMKM', 'addon' => 'Add-on',
                ];

                Product::query()->orderBy('category')->orderBy('name')->get(['name', 'category', 'price', 'stock'])->each(function (Product $p) use (&$lines, $productLabels): void {
                    $label = $productLabels[$p->category] ?? $p->category;
                    $stok = $p->stock > 0 ? 'stok '.$p->stock : 'stok habis';
                    $lines[] = sprintf('- %s "%s": Rp %s (%s).', $label, $p->name, number_format((float) $p->price, 0, ',', '.'), $stok);
                });

                $lines[] = sprintf('- Pembayaran: transfer bank %s a/n %s (%s), lalu unggah bukti di halaman pembayaran.', config('services.payment.bank'), config('services.payment.account_name'), config('services.payment.account_number'));
                $lines[] = sprintf('- Konfirmasi bantuan manusia: WhatsApp pengelola %s.', config('services.payment.whatsapp'));
                $lines[] = '- Tentang: BUMDes Wisata Teduh mengelola wisata Teduh Glamping — glamping (menginap), aula, alat wisata, tiket masuk gerbang, serta gerai UMKM. Semua reservasi dilakukan online lewat SIRESTEDUH.';
                $lines[] = '- Alur reservasi: pilih unit/tiket di checkout -> isi data pemesan -> transfer & unggah bukti di pembayaran {kode} -> admin verifikasi -> e-tiket QR dikirim via WhatsApp -> scan QR saat check-in di gerbang.';
                $lines[] = '- Status pesanan bisa dicek di status dengan kode order.';

                return implode("\n", $lines);
            }).sprintf("\n- Tanggal hari ini: %s.", Carbon::now()->translatedFormat('d F Y'));
        } catch (Throwable) {
            return '- Data harga sedang tidak tersedia. Arahkan tamu ke checkout atau WhatsApp pengelola.';
        }
    }

    // =====================================================================
    // Ketersediaan per tanggal
    // =====================================================================

    /**
     * Parse tanggal & durasi menginap dari pertanyaan Bahasa Indonesia.
     *
     * Mendukung: "20 desember 2026", "20/12/2026", "2026-12-20",
     * "besok", "lusa", "hari ini", plus durasi "2 malam" / "3 hari".
     *
     * @return array{check_in: Carbon, nights: int}|null
     */
    protected function extractStay(string $question): ?array
    {
        $q = mb_strtolower($question);
        $today = Carbon::today();
        $checkIn = null;

        // yyyy-mm-dd atau yyyy/mm/dd
        if (preg_match('/\b(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})\b/u', $q, $m) === 1) {
            $checkIn = Carbon::createFromDate((int) $m[1], (int) $m[2], (int) $m[3]);
        }

        // dd/mm/yyyy, dd-mm-yyyy, dd.mm.yyyy (urutan Indonesia)
        if ($checkIn === null && preg_match('/\b(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{2,4})\b/u', $q, $m) === 1) {
            $year = (int) $m[3];

            if ($year < 100) {
                $year += 2000;
            }

            $checkIn = Carbon::createFromDate($year, (int) $m[2], (int) $m[1]);
        }

        // "20 desember 2026" / "20 des 2026" / "20 desember"
        if ($checkIn === null && preg_match('/\b(\d{1,2})\s+('.$this->monthPattern().')(?:\s+(\d{4}))?\b/u', $q, $m) === 1) {
            $year = isset($m[3]) ? (int) $m[3] : $today->year;
            $month = $this->monthNumber($m[2]);

            if ($month !== null) {
                // Tanpa tahun: pilih kemunculan berikutnya (tidak mundur ke masa lalu)
                if (! isset($m[3])) {
                    $candidate = Carbon::createFromDate($today->year, $month, (int) $m[1]);

                    if ($candidate->lt($today)) {
                        $candidate = Carbon::createFromDate($today->year + 1, $month, (int) $m[1]);
                    }

                    $checkIn = $candidate;
                } else {
                    $checkIn = Carbon::createFromDate($year, $month, (int) $m[1]);
                }
            }
        }

        // Relatif: hari ini, besok, lusa
        if ($checkIn === null) {
            if (preg_match('/\b(hari ini|besok|lusa)\b/u', $q, $m) === 1) {
                $checkIn = match ($m[1]) {
                    'besok' => $today->copy()->addDay(),
                    'lusa' => $today->copy()->addDays(2),
                    default => $today->copy(),
                };
            }
        }

        if ($checkIn === null) {
            return null;
        }

        $nights = 1;

        if (preg_match('/\b(\d{1,2})\s*(malam|hari)\b/u', $q, $m) === 1) {
            $nights = max(1, min(30, (int) $m[1]));
        }

        return ['check_in' => $checkIn->startOfDay(), 'nights' => $nights];
    }

    /**
     * Apakah pertanyaan ini menanyakan ketersediaan?
     * Kata kunci langsung (tersedia, kosong, penuh, dst) ATAU ada tanggal
     * eksplisit dipasangkan niat menginap/booking/sewa.
     */
    protected function isAvailabilityQuestion(string $question, ?array $stay = null): bool
    {
        $q = mb_strtolower($question);

        foreach (self::AVAILABILITY_KEYWORDS as $kw) {
            if (str_contains($q, $kw)) {
                return true;
            }
        }

        // "20 desember 2026 menginap 2 malam" → ketersediaan, bukan FAQ umum
        if ($stay !== null && preg_match('/\b(menginap|inap|booking|reservasi|sewa)\b/u', $q) === 1) {
            return true;
        }

        return false;
    }

    /**
     * Konteks ketersediaan untuk tanggal tertentu (sumber = logika booking resmi).
     */
    protected function availabilityContext(Carbon $checkIn, int $nights): string
    {
        try {
            $checkOut = $checkIn->copy()->addDays($nights);
            $booked = $this->availability->bookedCountPerUnit($checkIn, $checkOut);

            $lines = [sprintf('- Tanggal: check-in %s, %d malam, check-out %s.', $checkIn->translatedFormat('d M Y'), $nights, $checkOut->translatedFormat('d M Y'))];

            if ($checkIn->lt(Carbon::today())) {
                $lines[] = '- PERINGATAN: tanggal sudah lewat. Ingatkan tamu memilih tanggal mendatang.';
            }

            Unit::query()->where('stock', '>', 0)->orderBy('name')->get(['id', 'name', 'type', 'price', 'stock'])->each(function (Unit $u) use (&$lines, $booked, $nights): void {
                $remaining = $u->stock - ($booked[$u->id] ?? 0);
                $total = (float) $u->price * $nights;

                $lines[] = sprintf(
                    '- Unit "%s" (%s): %s dari %d tersedia. Harga %s/malam, total %s untuk %d malam.',
                    $u->name,
                    $u->type,
                    $remaining > 0 ? $remaining : 0,
                    $u->stock,
                    number_format((float) $u->price, 0, ',', '.'),
                    number_format($total, 0, ',', '.'),
                    $nights,
                );
            });

            return implode("\n", $lines);
        } catch (Throwable) {
            return '- Data ketersediaan sedang tidak bisa dihitung. Sarankan tamu memeriksa langsung di checkout.';
        }
    }

    /**
     * Ringkasan ketersediaan 7 hari ke depan, bila tamu belum menyebut tanggal.
     */
    protected function availabilityOverview(): string
    {
        try {
            $lines = ['- Rentang: 3 hari ke depan dari hari ini.'];

            for ($i = 0; $i < 3; $i++) {
                $day = Carbon::today()->addDays($i);
                $booked = $this->availability->bookedCountPerUnit($day, $day->copy()->addDay());
                $parts = [];

                Unit::query()->where('stock', '>', 0)->orderBy('name')->get(['id', 'name', 'stock'])->each(function (Unit $u) use (&$parts, $booked): void {
                    $remaining = $u->stock - ($booked[$u->id] ?? 0);
                    $parts[] = sprintf('%s: %s', $u->name, $remaining > 0 ? $remaining.' tersedia' : 'penuh');
                });

                $lines[] = sprintf('- %s → %s', $day->translatedFormat('d M Y'), implode(' | ', $parts));
            }

            $lines[] = '- Bila tamu belum menyebut tanggal pasti, minta tanggal check-in yang diinginkan (contoh: 20 desember 2026).';

            return implode("\n", $lines);
        } catch (Throwable) {
            return '- Data ketersediaan sedang tidak bisa dihitung. Sarankan tamu memeriksa langsung di checkout.';
        }
    }

    // =====================================================================
    // Fallback tanpa AI
    // =====================================================================
    // Status pesanan langsung dari kode order
    // =====================================================================

    /**
     * Ekstrak kode order SIRES-XXXX dari pertanyaan (case-insensitive).
     */
    protected function extractOrderCode(string $question): ?string
    {
        if (preg_match('/\b(SIRES-[A-Za-z0-9]{4,})\b/u', $question, $m) === 1) {
            return strtoupper($m[1]);
        }

        return null;
    }

    /**
     * Apakah pertanyaan menanyakan status pesanan?
     */
    protected function isStatusQuestion(string $question): bool
    {
        $q = mb_strtolower($question);

        foreach (['status', 'pesanan saya', 'order', 'kode order', 'cek pesanan', 'cek order'] as $kw) {
            if (str_contains($q, $kw)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ringkasan status pesanan dari DB untuk kode order tertentu.
     * Mengembalikan null bila kode tidak ditemukan.
     */
    protected function reservationStatusContext(string $orderCode): ?string
    {
        try {
            $reservation = Reservation::query()
                ->where('order_code', $orderCode)
                ->with(['visitor', 'unit', 'payment', 'addons.product', 'ticket'])
                ->first();

            if ($reservation === null) {
                return "Kode order {$orderCode} tidak ditemukan di sistem. Pastikan kode benar (contoh: SIRES-ABC12345) atau cek kembali di halaman status. 🔍";
            }

            $labels = [
                'pending' => 'Menunggu Pembayaran',
                'waiting_verification' => 'Menunggu Verifikasi',
                'paid' => 'Lunas',
                'confirmed' => 'Terverifikasi',
                'cancelled' => 'Dibatalkan',
            ];

            $statusLabel = $labels[$reservation->status] ?? $reservation->status;

            $lines = [];
            $lines[] = "Kode Order: {$reservation->order_code}";
            $lines[] = "Status: {$statusLabel}";
            $lines[] = 'Total: Rp '.number_format((float) $reservation->total_price, 0, ',', '.');

            if ($reservation->check_in_date !== null) {
                $lines[] = 'Check-in: '.$reservation->check_in_date->translatedFormat('d M Y');
            }

            if ($reservation->check_out_date !== null) {
                $lines[] = 'Check-out: '.$reservation->check_out_date->translatedFormat('d M Y');
            }

            if ($reservation->unit !== null) {
                $lines[] = 'Unit: '.$reservation->unit->name.' ('.$reservation->unit->type.')';
            }

            $addons = $reservation->addons;

            if ($addons->isNotEmpty()) {
                $addonLines = [];

                foreach ($addons as $a) {
                    $name = $a->product?->name ?? 'Produk';
                    $addonLines[] = $name.' x'.$a->quantity;
                }

                $lines[] = 'Tambahan: '.implode(', ', $addonLines);
            }

            $payment = $reservation->payment;

            if ($payment !== null) {
                $lines[] = 'Bukti pembayaran: '.($payment->proof_image ? 'sudah diunggah' : 'belum diunggah');
            } else {
                $lines[] = 'Bukti pembayaran: belum diunggah';
            }

            if ($reservation->ticket !== null) {
                $lines[] = 'E-tiket QR: tersedia ('.($reservation->ticket->is_used ? 'sudah dipakai' : 'belum dipakai').')';
            } elseif (in_array($reservation->status, ['paid', 'confirmed'], true)) {
                $lines[] = 'E-tiket QR: akan tersedia setelah diverifikasi admin';
            }

            $lines[] = '';
            $lines[] = 'Detail lengkap & QR e-tiket bisa dilihat di status dengan kode order ini. 🎫';

            if ($reservation->status === 'pending') {
                $lines[] = "Silakan transfer sesuai nominal lalu unggah bukti di pembayaran {$reservation->order_code}. 💳";
            } elseif ($reservation->status === 'waiting_verification') {
                $lines[] = 'Bukti sedang diverifikasi admin. E-tiket QR akan dikirim ke WhatsApp setelah terverifikasi. ⏳';
            } elseif (in_array($reservation->status, ['paid', 'confirmed'], true)) {
                $lines[] = 'Pembayaran terverifikasi. Tunjukkan QR e-tiket saat check-in di gerbang. ✅';
            } elseif ($reservation->status === 'cancelled') {
                $lines[] = 'Pesanan ini telah dibatalkan. Hubungi WhatsApp pengelola untuk info lebih lanjut. 🙏';
            }

            return implode("\n", $lines);
        } catch (Throwable) {
            return 'Maaf, data status pesanan sedang tidak bisa diambil. Silakan cek langsung di status atau hubungi WhatsApp pengelola. 🙏';
        }
    }

    // =====================================================================

    /**
     * Fallback tanpa AI: pertanyaan ketersediaan dijawab dari data real-time,
     * sisanya FAQ berbasis skor kata kunci + data DB.
     */
    protected function knowledgeBaseAnswer(string $question, ?array $stay = null, ?string $userName = null): string
    {
        $q = mb_strtolower($question);

        if ($this->isAvailabilityQuestion($q, $stay)) {
            return $this->availabilityFallbackAnswer($stay);
        }

        $scores = [];
        $faqs = $this->faqs();

        foreach ($faqs as $i => $faq) {
            $score = 0;

            foreach ($faq['keywords'] as $kw) {
                // Frasa multi-kata dicocokkan apa adanya; kata tunggal wajib berdiri sendiri
                // agar "besok" tidak dianggap "ok", "ketik" tidak dianggap "tiket", dst.
                $matched = str_contains($kw, ' ')
                    ? str_contains($q, $kw)
                    : preg_match('/\b'.preg_quote($kw, '/').'\b/u', $q) === 1;

                if ($matched) {
                    $score++;
                }
            }

            if ($score > 0) {
                $scores[$i] = $score;
            }
        }

        if ($scores === []) {
            return $this->defaultAnswer($userName);
        }

        arsort($scores);
        $best = array_key_first($scores);

        return $faqs[$best]['answer']();
    }

    /**
     * Jawaban ketersediaan untuk mode fallback (tanpa AI), dari data real-time.
     */
    protected function availabilityFallbackAnswer(?array $stay): string
    {
        if ($stay === null) {
            $overview = $this->availabilityOverview();

            return "Berikut ketersediaan 7 hari ke depan:\n\n".$overview."\n\nSilakan sebutkan tanggal check-in Anda (contoh: 20 desember 2026) agar saya bisa hitung lebih detail. 📅";
        }

        ['check_in' => $checkIn, 'nights' => $nights] = $stay;

        if ($checkIn->lt(Carbon::today())) {
            return 'Tanggal '.Carbon::today()->translatedFormat('d M Y').' sudah lewat. Silakan pilih tanggal check-in yang akan datang ya. 📅';
        }

        try {
            $checkOut = $checkIn->copy()->addDays($nights);
            $booked = $this->availability->bookedCountPerUnit($checkIn, $checkOut);
            $units = Unit::query()->where('stock', '>', 0)->orderBy('name')->get(['id', 'name', 'type', 'price', 'stock']);

            $tersedia = [];
            $penuh = [];

            $units->each(function (Unit $u) use (&$tersedia, &$penuh, $booked, $nights): void {
                $remaining = $u->stock - ($booked[$u->id] ?? 0);
                $total = (float) $u->price * $nights;
                $detail = sprintf('- %s (%s): %s/malam, total %s untuk %d malam', $u->name, $u->type, number_format((float) $u->price, 0, ',', '.'), number_format($total, 0, ',', '.'), $nights);

                if ($remaining > 0) {
                    $tersedia[] = $detail.' — '.($remaining === 1 ? 'MASIH ADA (sisa 1)' : 'tersedia '.$remaining.' unit');
                } else {
                    $penuh[] = $detail.' — PENUH';
                }
            });

            $header = sprintf("Ketersediaan untuk check-in %s, %d malam (check-out %s):\n\n", $checkIn->translatedFormat('d M Y'), $nights, $checkOut->translatedFormat('d M Y'));

            if ($tersedia !== []) {
                $body = $header."✅ Tersedia:\n".implode("\n", $tersedia);
            } else {
                $body = $header.'😔 Semua unit penuh pada tanggal ini.';
            }

            if ($penuh !== []) {
                $body .= "\n\n❌ Penuh:\n".implode("\n", $penuh);
            }

            $body .= "\n\nData dihitung real-time dari sistem reservasi. Yuk amankan slot lewat checkout! 🏕️";

            return $body;
        } catch (Throwable) {
            return 'Maaf, data ketersediaan sedang tidak bisa dihitung. Silakan cek langsung di checkout atau hubungi WhatsApp pengelola. 🙏';
        }
    }

    /**
     * Daftar FAQ fallback. Jawaban dibuat saat dipanggil agar selalu pakai data DB terkini.
     *
     * @return array<int, array{keywords: array<int, string>, answer: callable}>
     */
    protected function faqs(): array
    {
        $formatRp = fn (float $v): string => 'Rp '.number_format($v, 0, ',', '.');

        $unitList = function () use ($formatRp): string {
            $rows = Unit::query()->orderBy('type')->get(['name', 'type', 'price']);

            if ($rows->isEmpty()) {
                return 'Daftar harga sedang tidak tersedia. Silakan buka checkout untuk melihat pilihan terkini.';
            }

            $label = ['glamping' => 'Glamping', 'aula' => 'Aula', 'alat_wisata' => 'Alat Wisata'];
            $out = "Berikut daftar harga kami:\n";

            foreach ($rows as $u) {
                $out .= sprintf("- %s %s: %s\n", $label[$u->type] ?? $u->type, $u->name, $formatRp((float) $u->price));
            }

            return trim($out)."\nBuka checkout untuk mulai memesan. 😊";
        };

        $ticketPrice = function () use ($formatRp): string {
            $tickets = Product::query()->where('category', 'tiket_masuk')->get(['name', 'price']);

            if ($tickets->isEmpty()) {
                return 'Harga tiket masuk dapat dilihat langsung di checkout.';
            }

            $out = "Harga Tiket Masuk:\n";

            foreach ($tickets as $t) {
                $out .= sprintf("- %s: %s\n", $t->name, $formatRp((float) $t->price));
            }

            return trim($out)."\nTiket bisa dibeli online di checkout atau langsung di loket gerbang.";
        };

        // Produk UMKM & gerai (semua kecuali tiket) — langsung dari tabel products.
        $productList = function () use ($formatRp): string {
            $rows = Product::query()->where('category', '!=', 'tiket_masuk')
                ->orderBy('category')->orderBy('name')
                ->get(['name', 'category', 'price', 'stock', 'type']);

            if ($rows->isEmpty()) {
                return 'Daftar produk gerai sedang kosong. Silakan cek pilihan terbaru di checkout.';
            }

            $label = ['makanan' => 'Makanan', 'minuman' => 'Minuman', 'souvenir' => 'Souvenir', 'baju_merchandise' => 'Merchandise', 'produk_umkm' => 'UMKM', 'addon' => 'Add-on'];
            $out = "Produk UMKM & gerai kami:\n";

            foreach ($rows as $p) {
                $stok = $p->stock > 0 ? 'stok '.$p->stock : 'stok habis';
                $channel = $p->type === 'pos' ? 'di loket' : 'online di checkout';
                $out .= sprintf("- %s %s: %s (%s, tersedia %s)\n", $label[$p->category] ?? $p->category, $p->name, $formatRp((float) $p->price), $stok, $channel);
            }

            return trim($out)."\nUntuk pemesanan online, tambahkan saat checkout. 🛍️";
        };

        // Unit per tipe (glamping / aula / alat wisata) dari tabel units.
        $typedUnitList = function (string $type, string $title) use ($formatRp): string {
            $rows = Unit::query()->where('type', $type)->orderBy('name')->get(['name', 'price', 'capacity', 'stock']);

            if ($rows->isEmpty()) {
                return 'Belum ada unit '.$title.' yang terdaftar. Cek checkout untuk pilihan terbaru.';
            }

            $out = $title.":\n";

            foreach ($rows as $u) {
                $out .= sprintf("- %s: %s (kapasitas %d orang, stok %d)\n", $u->name, $formatRp((float) $u->price), $u->capacity, $u->stock);
            }

            return trim($out)."\nPesan via checkout — pilihannya menyesuaikan data terkini. 😊";
        };

        return [
            [
                'keywords' => ['harga', 'biaya', 'tarif', 'berapa', 'price', 'murah', 'mahal'],
                'answer' => fn (): string => "Untuk harga lengkap, begini:\n\n".$unitList()."\n\nTiket masuk:\n".$ticketPrice()."\n\n".$productList(),
            ],
            [
                'keywords' => ['tiket masuk', 'tiket', 'entrance'],
                'answer' => $ticketPrice,
            ],
            [
                'keywords' => ['reservasi', 'booking', 'pesan', 'cara pesan', 'daftar'],
                'answer' => fn (): string => "Cara reservasi sangat mudah:\n1. Buka checkout\n2. Pilih unit glamping, tiket, alat wisata\n3. Isi data pemesan (nama & nomor WhatsApp aktif)\n4. Kirim pesanan — Anda dapat kode order, contoh SIRES-XXXXXX\n5. Transfer sesuai nominal lalu unggah bukti di halaman pembayaran\n6. Setelah admin verifikasi, e-tiket QR dikirim ke WhatsApp Anda\n7. Tunjukkan QR saat check-in di gerbang 🎫",
            ],
            [
                'keywords' => ['bayar', 'pembayaran', 'transfer', 'qris', 'bank', 'rekening', 'bukti'],
                'answer' => fn (): string => 'Pembayaran dilakukan via transfer bank '.config('services.payment.bank').' a/n '.config('services.payment.account_name').' ('.config('services.payment.account_number').").\nSetelah transfer, unggah bukti di halaman pembayaran {kode-order}.\nAdmin akan memverifikasi (biasanya cepat), lalu e-tiket QR dikirim otomatis ke WhatsApp Anda. ✅",
            ],
            [
                'keywords' => ['check-in', 'checkin', 'check in', 'masuk gerbang', 'qr', 'etiket', 'e-tiket'],
                'answer' => fn (): string => "Soal check-in:\n- Setelah pembayaran terverifikasi, e-tiket QR dikirim ke WhatsApp Anda\n- QR juga bisa dilihat di status dengan memasukkan kode order\n- Saat tiba di gerbang, tunjukkan QR kepada petugas untuk discanner\n- Tiket sekali pakai dan otomatis kedaluwarsa setelah dipakai 🎟️",
            ],
            [
                'keywords' => ['status', 'pesanan saya', 'order', 'kode', 'sires'],
                'answer' => fn (): string => "Untuk cek status pesanan:\n1. Siapkan kode order Anda (contoh: SIRES-XXXXXX)\n2. Buka status\n3. Masukkan kode order\n4. Status pembayaran, bukti transfer, dan QR e-tiket tampil di sana 🔍",
            ],
            [
                'keywords' => ['glamping', 'tenda', 'menginap', 'kamar', 'penginapan', 'fasilitas'],
                'answer' => fn (): string => "Kami menyediakan unit glamping yang nyaman di alam terbuka BUMDes Wisata Teduh. 🏕️\n\n".$unitList()."\n\nDetail fasilitas tiap unit bisa ditanyakan ke WhatsApp pengelola.",
            ],
            [
                'keywords' => ['lokasi', 'alamat', 'dimana', 'di mana', 'maps', 'arah'],
                'answer' => fn (): string => 'Lokasi tepatnya di BUMDes Wisata Teduh — silakan buka Google Maps dengan kata kunci "BUMDes Wisata Teduh".\nUntuk petunjuk arah detail dari kota Anda, tanyakan ke WhatsApp pengelola di '.config('services.payment.whatsapp').'. 📍',
            ],
            [
                'keywords' => ['jam', 'buka', 'tutup', 'operasional', 'waktu'],
                'answer' => fn (): string => 'Untuk informasi jam operasional terkini, silakan hubungi WhatsApp pengelola di '.config('services.payment.whatsapp').".\nReservasi online di checkout bisa dilakukan kapan saja 24 jam. 🕐",
            ],
            [
                'keywords' => ['batal', 'refund', 'uang kembali', 'cancel', 'reschedule', 'ubah tanggal'],
                'answer' => fn (): string => 'Untuk pembatalan atau perubahan tanggal reservasi, silakan hubungi WhatsApp pengelola di '.config('services.payment.whatsapp')."\ndengan menyertakan kode order Anda (contoh: SIRES-XXXXXX). Tim kami akan membantu. 🙏",
            ],
            [
                'keywords' => ['halo', 'hai', 'hello', 'pagi', 'siang', 'malam', 'assalam', 'permisi'],
                'answer' => fn (): string => 'Halo! 👋 Selamat datang di Teduh Bot, asisten BUMDes Wisata Teduh.\nSaya bisa membantu soal: harga & unit glamping, tiket masuk, cara reservasi, pembayaran, e-tiket QR, dan status pesanan.\nSilakan tanyakan apa saja! 😊',
            ],
            [
                'keywords' => ['terima kasih', 'makasih', 'thanks', 'oke', 'ok', 'sip'],
                'answer' => fn (): string => 'Sama-sama! 😊 Jika ada pertanyaan lain tentang Teduh Glamping, saya siap membantu. Selamat berwisata! 🏕️',
            ],
            [
                'keywords' => ['info', 'informasi', 'ringkasan', 'overview', 'menu', 'layanan', 'apa saja', 'bisa apa'],
                'answer' => fn (): string => "Saya Teduh Bot, asisten BUMDes Wisata Teduh. 🏕️\n\nLayanan kami:\n- Glamping (menginap), aula (sewa acara), alat wisata (sewa)\n- Tiket masuk gerbang\n- Gerai UMKM: makanan, minuman, souvenir, merchandise\n\n".$unitList()."\n\nTiket masuk:\n".$ticketPrice()."\n\nPesan online di checkout, cek status di status dengan kode order. 💬",
            ],
            [
                'keywords' => ['kapasitas', 'berapa orang', 'muat', 'orang'],
                'answer' => fn (): string => "Kapasitas tiap unit:\n".$unitList()."\n\nBuka checkout untuk melihat detail & ketersediaan. 😊",
            ],
            [
                'keywords' => ['tentang', 'about', 'tentang teduh', 'tentang glamping', 'apa itu', 'apa itu teduh', 'kenapa', 'profil', 'deskripsi', 'pengenalan', 'kenalan'],
                'answer' => fn (): string => "Teduh Glamping adalah wisata glamping & alam yang dikelola BUMDes Wisata Teduh. 🏕️\n\nLayanan kami:\n- Menginap di unit glamping\n- Sewa aula untuk acara\n- Sewa alat wisata (pendakian)\n- Tiket masuk gerbang\n- Gerai UMKM: makanan, minuman, souvenir, merchandise\n\nSemua reservasi dilakukan online di SIRESTEDUH (checkout): pilih unit/tiket -> isi data pemesan -> bayar & unggah bukti -> e-tiket QR dikirim ke WhatsApp.\n\n".$unitList()."\nButuh penjelasan lebih lanjut? Chat WhatsApp pengelola di ".config('services.payment.whatsapp').'. 😊',
            ],
            [
                'keywords' => ['produk', 'umkm', 'makanan', 'minuman', 'souvenir', 'merchandise', 'kaos', 'cemilan', 'kopi', 'menu', 'dijual', 'jualan', 'gerai'],
                'answer' => $productList,
            ],
            [
                'keywords' => ['aula', 'gedung', 'serbaguna', 'acara', 'event', 'resepsi', 'rapat'],
                'answer' => fn (): string => $typedUnitList('aula', 'Aula (sewa untuk acara)'),
            ],
            [
                'keywords' => ['alat wisata', 'alat', 'pendakian', 'hiking', 'outbound', 'trekking', 'perkakas'],
                'answer' => fn (): string => $typedUnitList('alat_wisata', 'Alat Wisata (sewa)'),
            ],
            [
                'keywords' => ['kontak', 'hubungi', 'whatsapp', 'telepon', 'telp', 'cs', 'pengelola', 'nanya langsung', 'orang'],
                'answer' => fn (): string => 'Anda bisa menghubungi pengelola via WhatsApp di '.config('services.payment.whatsapp').".\nSaya juga siap menjawab seputar unit, harga, ketersediaan, reservasi, pembayaran, e-tiket, dan status pesanan. 💬",
            ],
        ];
    }

    protected function defaultAnswer(?string $userName = null): string
    {
        $greet = $userName !== null ? "Halo {$userName}! " : '';

        try {
            $unitCount = Unit::query()->count();
            $productCount = Product::query()->count();
            $ticketCount = Product::query()->where('category', 'tiket_masuk')->count();

            $unitSample = Unit::query()->orderBy('name')->limit(3)->pluck('name')->implode(', ');

            return $greet."Maaf, saya belum memahami pertanyaan itu. 🙏\n\n"
                ."Saya bisa membantu soal Teduh Glamping:\n"
                ."- Tentang Teduh Glamping & layanan kami\n"
                ."- Harga unit glamping, aula, alat wisata ({$unitCount} unit tersedia".($unitSample !== '' ? ', mis. '.$unitSample : '').")\n"
                ."- Tiket masuk ({$ticketCount} jenis)\n"
                ."- Produk UMKM & gerai ({$productCount} produk)\n"
                ."- Ketersediaan unit per tanggal\n"
                ."- Cara reservasi & pembayaran\n"
                ."- E-tiket QR dan check-in\n"
                ."- Cek status pesanan (kirim kode order SIRES-XXXX)\n\n"
                .'Coba tulis ulang dengan kata lain, atau hubungi WhatsApp pengelola di '.config('services.payment.whatsapp').'.';
        } catch (Throwable) {
            return "Maaf, saya belum memahami pertanyaan itu. 🙏\nSaya bisa membantu soal Teduh Glamping: harga & unit, tiket masuk, produk UMKM, ketersediaan, reservasi, pembayaran, e-tiket, dan status pesanan.\nHubungi WhatsApp pengelola di ".config('services.payment.whatsapp').'.';
        }
    }

    // =====================================================================
    // Parser tanggal Bahasa Indonesia
    // =====================================================================

    /**
     * @return array<string, int>
     */
    protected function months(): array
    {
        return [
            'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4, 'mei' => 5,
            'juni' => 6, 'juli' => 7, 'agustus' => 8, 'september' => 9,
            'oktober' => 10, 'november' => 11, 'desember' => 12,
            'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'jun' => 6, 'jul' => 7,
            'agu' => 8, 'agt' => 8, 'sep' => 9, 'okt' => 10, 'nov' => 11, 'des' => 12,
            'december' => 12, 'dec' => 12, 'august' => 8, 'aug' => 8,
        ];
    }

    protected function monthPattern(): string
    {
        // Urutkan dari yang terpanjang agar "januari" tidak tertelan "jan"
        $names = array_keys($this->months());

        usort($names, fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

        return implode('|', array_map(fn (string $n): string => preg_quote($n, '/'), $names));
    }

    protected function monthNumber(string $name): ?int
    {
        return $this->months()[$name] ?? null;
    }
}
