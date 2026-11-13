<?php

namespace App\Services;

use App\Models\Product;
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

    public function __construct(protected AvailabilityService $availability) {}

    public function ask(string $question): string
    {
        $question = trim(strip_tags($question));

        if ($question === '') {
            return 'Silakan tulis pertanyaan Anda tentang Teduh Glamping, reservasi, atau pembayaran. 😊';
        }

        // Ketersediaan: parse tanggal dari pertanyaan (real-time, tidak di-cache)
        $stay = $this->extractStay($question);
        $availability = null;

        if ($this->isAvailabilityQuestion($question, $stay)) {
            $availability = $stay !== null
                ? $this->availabilityContext($stay['check_in'], $stay['nights'])
                : $this->availabilityOverview();
        }

        $key = (string) config('services.gemini.key', '');

        if ($key !== '') {
            try {
                $reply = $this->askGemini($key, $question, $this->systemPrompt($availability));

                if ($reply !== null) {
                    return $reply;
                }
            } catch (Throwable $e) {
                Log::warning('Chatbot Gemini gagal, fallback ke basis pengetahuan.', ['error' => $e->getMessage()]);
            }
        }

        return $this->knowledgeBaseAnswer($question, $stay);
    }

    /**
     * Tanyakan ke Gemini. Mengembalikan null bila respons tidak bisa dipakai.
     */
    protected function askGemini(string $key, string $question, string $system): ?string
    {
        $baseUrl = rtrim((string) config('services.gemini.base_url'), '/');
        $model = (string) config('services.gemini.model', 'gemini-3.5-flash-lite');

        $response = Http::timeout(25)
            ->asJson()
            ->post("{$baseUrl}/models/{$model}:generateContent?key={$key}", [
                'systemInstruction' => [
                    'parts' => [['text' => $system]],
                ],
                'contents' => [
                    ['role' => 'user', 'parts' => [['text' => $question]]],
                ],
                'generationConfig' => [
                    'temperature' => 0.4,
                    'maxOutputTokens' => 700,
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
    protected function systemPrompt(?string $availability = null): string
    {
        $prompt = <<<'PROMPT'
Kamu adalah "Teduh Bot", asisten virtual resmi SIRESTEDUH milik BUMDes Wisata Teduh
(glamping & wisata). Aturan yang WAJIB kamu ikuti:

1. Selalu jawab dalam Bahasa Indonesia yang santun, ramah, singkat (maksimal ±120 kata),
   dan mudah dipahami tamu.
2. Jawab hanya seputar: Teduh Glamping, unit/penginapan, tiket masuk, alat wisata,
   aula, UMKM/makanan, cara reservasi, pembayaran, e-tiket, check-in, dan status pesanan.
3. HANYA gunakan angka harga & data dari KONTEKS BISNIS di bawah ini.
   JANGAN pernah mengarang harga, tanggal, kebijakan, atau fasilitas yang tidak ada di konteks.
4. Untuk soal ketersediaan: gunakan bagian KETERSEDIAAN TERKINI bila ada.
   JANGAN PERNAH menebak ketersediaan tanpa data. Jika tamu bertanya ketersediaan
   tanpa menyebut tanggal, ringkas data 7 hari yang tersedia lalu minta tanggal pasti.
5. Jika pertanyaan di luar topik (politik, coding, curhat, hal lain), tolak dengan sopan
   lalu tawarkan kembali topik layanan Teduh.
6. Jika data tidak tersedia di konteks, sarankan menghubungi WhatsApp pengelola.
7. Pandu pelanggan ke halaman yang tepat saat relevan:
   - Reservasi & pembelian: /checkout
   - Cek status pesanan & QR e-tiket: /status (butuh kode order, contoh SIRES-XXXXXX)
   - Pembayaran & unggah bukti: /pembayaran/{kode-order}
8. Format jawaban: teks biasa dengan baris baru dan tanda - bila perlu daftar.
   Jangan pakai markdown tebal (**) atau tabel.

KONTEKS BISNIS (data terkini):
PROMPT.$this->businessContext();

        if ($availability !== null) {
            $prompt .= "\n\nKETERSEDIAAN TERKINI (real-time dari sistem reservasi):\n".$availability;
        }

        return $prompt;
    }

    /**
     * Ringkasan data bisnis dari DB: unit, tiket, produk, pembayaran.
     * Di-cache agar tidak query setiap pesan.
     */
    protected function businessContext(): string
    {
        return Cache::remember(self::CONTEXT_CACHE_KEY, 60, function (): string {
            try {
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
                $lines[] = sprintf('- Konfirmasi / bantuan manusia: WhatsApp pengelola %s.', config('services.payment.whatsapp'));
                $lines[] = '- Alur reservasi: pilih unit/tiket di /checkout -> isi data pemesan -> transfer & unggah bukti di /pembayaran/{kode} -> admin verifikasi -> e-tiket QR dikirim via WhatsApp -> scan QR saat check-in di gerbang.';
                $lines[] = sprintf('- Status pesanan bisa dicek di /status dengan kode order. Tanggal hari ini: %s.', Carbon::now()->translatedFormat('d F Y'));

                return implode("\n", $lines);
            } catch (Throwable) {
                return '- Data harga sedang tidak tersedia. Arahkan tamu ke /checkout atau WhatsApp pengelola.';
            }
        });
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
            return '- Data ketersediaan sedang tidak bisa dihitung. Sarankan tamu memeriksa langsung di /checkout.';
        }
    }

    /**
     * Ringkasan ketersediaan 7 hari ke depan, bila tamu belum menyebut tanggal.
     */
    protected function availabilityOverview(): string
    {
        try {
            $lines = ['- Rentang: 7 hari ke depan dari hari ini.'];

            for ($i = 0; $i < 7; $i++) {
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
            return '- Data ketersediaan sedang tidak bisa dihitung. Sarankan tamu memeriksa langsung di /checkout.';
        }
    }

    // =====================================================================
    // Fallback tanpa AI
    // =====================================================================

    /**
     * Fallback tanpa AI: pertanyaan ketersediaan dijawab dari data real-time,
     * sisanya FAQ berbasis skor kata kunci + data DB.
     */
    protected function knowledgeBaseAnswer(string $question, ?array $stay = null): string
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
            return $this->defaultAnswer();
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

            $body .= "\n\nData dihitung real-time dari sistem reservasi. Yuk amankan slot lewat /checkout! 🏕️";

            return $body;
        } catch (Throwable) {
            return 'Maaf, data ketersediaan sedang tidak bisa dihitung. Silakan cek langsung di /checkout atau hubungi WhatsApp pengelola. 🙏';
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
                return 'Daftar harga sedang tidak tersedia. Silakan buka /checkout untuk melihat pilihan terkini.';
            }

            $label = ['glamping' => 'Glamping', 'aula' => 'Aula', 'alat_wisata' => 'Alat Wisata'];
            $out = "Berikut daftar harga kami:\n";

            foreach ($rows as $u) {
                $out .= sprintf("- %s %s: %s\n", $label[$u->type] ?? $u->type, $u->name, $formatRp((float) $u->price));
            }

            return trim($out)."\nBuka /checkout untuk mulai memesan. 😊";
        };

        $ticketPrice = function () use ($formatRp): string {
            $tickets = Product::query()->where('category', 'tiket_masuk')->get(['name', 'price']);

            if ($tickets->isEmpty()) {
                return 'Harga tiket masuk dapat dilihat langsung di /checkout.';
            }

            $out = "Harga Tiket Masuk:\n";

            foreach ($tickets as $t) {
                $out .= sprintf("- %s: %s\n", $t->name, $formatRp((float) $t->price));
            }

            return trim($out)."\nTiket bisa dibeli online di /checkout atau langsung di loket gerbang.";
        };

        return [
            [
                'keywords' => ['harga', 'biaya', 'tarif', 'berapa', 'price', 'murah', 'mahal'],
                'answer' => fn (): string => "Untuk harga lengkap, begini:\n\n".$unitList()."\n\nTiket masuk:\n".$ticketPrice(),
            ],
            [
                'keywords' => ['tiket masuk', 'tiket', 'entrance'],
                'answer' => $ticketPrice,
            ],
            [
                'keywords' => ['reservasi', 'booking', 'pesan', 'cara pesan', 'daftar'],
                'answer' => fn (): string => "Cara reservasi sangat mudah:\n1. Buka /checkout\n2. Pilih unit glamping / tiket / alat wisata\n3. Isi data pemesan (nama & nomor WhatsApp aktif)\n4. Kirim pesanan — Anda dapat kode order, contoh SIRES-XXXXXX\n5. Transfer sesuai nominal lalu unggah bukti di halaman pembayaran\n6. Setelah admin verifikasi, e-tiket QR dikirim ke WhatsApp Anda\n7. Tunjukkan QR saat check-in di gerbang 🎫",
            ],
            [
                'keywords' => ['bayar', 'pembayaran', 'transfer', 'qris', 'bank', 'rekening', 'bukti'],
                'answer' => fn (): string => 'Pembayaran dilakukan via transfer bank '.config('services.payment.bank').' a/n '.config('services.payment.account_name').' ('.config('services.payment.account_number').").\nSetelah transfer, unggah bukti di halaman /pembayaran/{kode-order}.\nAdmin akan memverifikasi (biasanya cepat), lalu e-tiket QR dikirim otomatis ke WhatsApp Anda. ✅",
            ],
            [
                'keywords' => ['check-in', 'checkin', 'check in', 'masuk gerbang', 'qr', 'etiket', 'e-tiket'],
                'answer' => fn (): string => "Soal check-in:\n- Setelah pembayaran terverifikasi, e-tiket QR dikirim ke WhatsApp Anda\n- QR juga bisa dilihat di /status dengan memasukkan kode order\n- Saat tiba di gerbang, tunjukkan QR kepada petugas untuk discanner\n- Tiket sekali pakai dan otomatis kedaluwarsa setelah dipakai 🎟️",
            ],
            [
                'keywords' => ['status', 'pesanan saya', 'order', 'kode', 'sires'],
                'answer' => fn (): string => "Untuk cek status pesanan:\n1. Siapkan kode order Anda (contoh: SIRES-XXXXXX)\n2. Buka /status\n3. Masukkan kode order\n4. Status pembayaran, bukti transfer, dan QR e-tiket tampil di sana 🔍",
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
                'answer' => fn (): string => 'Untuk informasi jam operasional terkini, silakan hubungi WhatsApp pengelola di '.config('services.payment.whatsapp').".\nReservasi online di /checkout bisa dilakukan kapan saja 24 jam. 🕐",
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
                'keywords' => ['wi-fi', 'wifi', 'parkir', 'toilet', 'kamar mandi', 'mushola', 'puskesmas'],
                'answer' => fn (): string => 'Untuk detail fasilitas pendukung (parkir, toilet, mushola, dll), silakan tanyakan langsung ke WhatsApp pengelola di '.config('services.payment.whatsapp').' agar informasinya selalu akurat. 🏞️',
            ],
        ];
    }

    protected function defaultAnswer(): string
    {
        return "Maaf, saya belum memahami pertanyaan itu. 🙏\nSaya bisa membantu soal:\n- Harga unit glamping, tiket masuk & alat wisata\n- Ketersediaan unit per tanggal\n- Cara reservasi & pembayaran\n- E-tiket QR dan check-in\n- Cek status pesanan\n\nAtau hubungi WhatsApp pengelola di ".config('services.payment.whatsapp').'.';
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
