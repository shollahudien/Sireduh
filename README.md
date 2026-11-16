<div align="center">

# 🏕️ SIRESTEDUH
### Sistem Reservasi & Manajemen Wisata — BUMDes Wisata Teduh

Aplikasi web terintegrasi untuk reservasi glamping/tiket masuk, verifikasi pembayaran, operasional kasir (POS), check-in e-ticket QR, hingga laporan & monitoring admin.

![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-4-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white)
![Vite](https://img.shields.io/badge/Vite-8-646CFF?style=for-the-badge&logo=vite&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)

</div>

---

## ✨ Fitur Utama

### 🌿 Tamu (Publik)
- **Reservasi online** unit glamping & pembelian tiket masuk.
- **Cek ketersediaan** unit/tiket secara real-time.
- **Pembayaran**: unggah bukti transfer manual atau pembayaran via **Midtrans** (QRIS/VA/e-wallet, sesuai konfigurasi).
- **E-Ticket QR** untuk check-in, plus halaman cek status pesanan.
- **Chatbot “Teduh Bot”** (AI/Gemini) dengan dukungan suara (TTS) untuk bantuan informasi.
- **PWA-ready** (manifest & service worker) untuk pengalaman mirip aplikasi.

### 🛠️ Admin
- **Dashboard & analitik** operasional.
- **Manajemen reservasi & verifikasi pembayaran** (terima/tolak).
- **Master data**: unit, gambar unit, produk, paket glamping/aula, pengguna.
- **Kirim/ulang e-ticket** ke tamu.
- **Laporan** terpadu & pencarian global (command palette).
- **Maintenance mode**, **log viewer**, dan **activity feed/audit log** (dengan retensi & prune).
- **Notifikasi** (termasuk integrasi Telegram webhook/bot sesuai konfigurasi).

### 💳 Kasir & Check-in
- **POS Kasir**: transaksi produk/unit dengan perhitungan stok.
- **Kasir Makanan (F&B)**: POS khusus kategori makanan.
- **Petugas Check-in**: pemindaian/validasi e-ticket QR dan riwayat check-in.

### 🔌 Integrasi & Lainnya
- **API** publik & autentikasi token (Laravel Sanctum) untuk akses eksternal.
- **Pembayaran**: Midtrans + transfer manual.
- **Notifikasi**: Fonnte (WhatsApp) & Telegram (opsional).
- **AI Chatbot**: Google Gemini (chat & TTS, opsional).
- **QR Code** e-ticket (`chillerlan/php-qrcode`).

---

## 🧱 Tech Stack

| Layer | Teknologi |
|---|---|
| Backend | PHP 8.3+, Laravel 13, Laravel Sanctum |
| Frontend | Blade, Tailwind CSS 4, Vite 8, Alpine.js |
| Database | SQLite / MySQL / PostgreSQL (konfigurasi via `.env`) |
| Queue/Cache/Session | Database driver (default, dapat disesuaikan) |
| Testing | PHPUnit 12 |

---

## 🚀 Instalasi & Menjalankan (Lokal)

### Prasyarat
- PHP **8.3+** & Composer
- Node.js & npm
- Ekstensi PHP umum Laravel (mbstring, openssl, pdo, tokenizer, xml, ctype, json, bcmath, fileinfo)

### Langkah cepat

```bash
# 1. Clone repository
git clone https://github.com/shollahudien/Sireduh.git
cd Sireduh

# 2. Install dependensi & setup awal
composer run setup
```

`composer run setup` akan: install Composer & npm dependencies, menyalin `.env`, generate app key, menjalankan migrasi, dan build aset frontend.

### Atau setup manual

```bash
composer install
cp .env.example .env
php artisan key:generate

# Konfigurasi database di .env, lalu:
php artisan migrate --seed

npm install
npm run build
```

### Menjalankan aplikasi

```bash
php artisan serve
```

Untuk pengembangan frontend (hot reload):

```bash
npm run dev
```

---

## ⚙️ Konfigurasi `.env` (Ringkasan)

> Jangan pernah commit file `.env` atau kredensial/secret ke repository.

| Variabel | Keterangan |
|---|---|
| `APP_NAME`, `APP_URL` | Identitas & URL aplikasi |
| `DB_*` | Koneksi database |
| `PAYMENT_BANK`, `PAYMENT_ACCOUNT_NUMBER`, `PAYMENT_ACCOUNT_NAME`, `PAYMENT_WHATSAPP` | Info pembayaran transfer manual |
| `MIDTRANS_*` | Integrasi Midtrans (server/client key, mode production, metode aktif) |
| `FONNTE_API_TOKEN`, `FONNTE_API_URL` | Notifikasi WhatsApp via Fonnte |
| `TELEGRAM_BOT_TOKEN`, `TELEGRAM_CHAT_ID`, `TELEGRAM_WEBHOOK_SECRET` | Notifikasi/bot Telegram |
| `GEMINI_API_KEY`, `GEMINI_MODEL`, `GEMINI_TTS_*` | Chatbot AI & TTS (Teduh Bot) |

Lihat `.env.example` untuk daftar lengkap.

---

## 🧪 Testing

```bash
php artisan test
```

Dokumen pengujian tambahan tersedia di:
- [`docs/BLACKBOX_TESTING.md`](docs/BLACKBOX_TESTING.md)
- [`docs/CONCURRENCY_TESTING.md`](docs/CONCURRENCY_TESTING.md)

---

## 📁 Gambaran Struktur

```text
app/
  Http/Controllers/        # Web & API (Admin, POS, Check-in, Public, Telegram, Midtrans)
  Models/                  # Eloquent models (Reservation, Payment, Unit, Product, dll.)
  Services/                # Business logic (Availability, Midtrans, Telegram, Chatbot, dll.)
resources/views/           # Blade views (public, admin, pos, checkin, layouts, components)
routes/
  web.php                  # Rute web (publik, admin, POS, check-in)
  api.php                  # Rute API (publik, auth, admin, webhook)
database/migrations/       # Skema database
```

---

## 🤝 Kontribusi

1. Fork repository ini
2. Buat branch fitur: `git checkout -b fitur/nama-fitur`
3. Commit perubahan: `git commit -m "feat: deskripsi singkat"`
4. Push & buka Pull Request

---

## 📄 Lisensi

Proyek ini menggunakan lisensi [MIT](https://opensource.org/licenses/MIT).

<div align="center">

Dibuat untuk **BUMDes Wisata Teduh** — reservasi mudah, pengalaman teduh 🌿

</div>
