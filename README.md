<div align="center">

# 🏕️ SIRESTEDUH
### Sistem Reservasi & Manajemen Wisata — BUMDes Wisata Teduh

Aplikasi web terintegrasi untuk **reservasi glamping & tiket masuk**, **pembayaran (manual/Midtrans)**, **verifikasi & e-ticket QR**, **operasional kasir (POS)**, **check-in petugas**, hingga **laporan, audit, dan monitoring admin**.

![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-4-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white)
![Vite](https://img.shields.io/badge/Vite-8-646CFF?style=for-the-badge&logo=vite&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)

<sub>Repo: <a href="https://github.com/shollahudien/Sireduh">github.com/shollahudien/Sireduh</a></sub>

</div>

---

## Daftar Isi

- [Ringkasan](#-ringkasan)
- [Keunggulan Utama](#-keunggulan-utama)
- [Keamanan & Perlindungan Data (Lengkap)](#-keamanan--perlindungan-data-lengkap)
  - [Autentikasi & Kontrol Akses](#1-autentikasi--kontrol-akses-rbac)
  - [Perlindungan Endpoint & Anti-Penyalahgunaan](#2-perlindungan-endpoint--anti-penyalahgunaan-rate-limiting)
  - [Keamanan Pembayaran & Transaksi](#3-keamanan-pembayaran--transaksi)
  - [Integritas Data & Konsistensi Bisnis](#4-integritas-data--konsistensi-bisnis)
  - [Audit Trail, Logging & Retensi](#5-audit-trail-logging--retensi)
  - [Privasi File & Proteksi Data Sensitif](#6-privasi-file--proteksi-data-sensitif)
  - [Mode Maintenance & Ketersediaan Terkontrol](#7-mode-maintenance--ketersediaan-terkontrol)
  - [Validasi Input & Keamanan Aplikasi Web](#8-validasi-input--keamanan-aplikasi-web)
  - [Keamanan Webhook & Integrasi Eksternal](#9-keamanan-webhook--integrasi-eksternal)
  - [Praktik Keamanan Deployment](#10-praktik-keamanan-deployment)
- [Fitur Lengkap per Modul](#-fitur-lengkap-per-modul)
  - [Tamu / Publik](#a-tamu--publik)
  - [Admin](#b-admin)
  - [Kasir / POS & Kasir Makanan](#c-kasir--pos--kasir-makanan)
  - [Petugas Check-in](#d-petugas-check-in)
  - [API & Akses Eksternal](#e-api--akses-eksternal)
- [Integrasi & Otomasi](#-integrasi--otomasi)
- [Operasional, Keandalan & Performa](#-operasional-keandalan--performa)
- [Tech Stack](#-tech-stack)
- [Prasyarat](#-prasyarat)
- [Instalasi & Menjalankan (Lokal)](#-instalasi--menjalankan-lokal)
- [Konfigurasi .env](#️-konfigurasi-env)
- [Testing](#-testing)
- [Panduan Deploy / Upload Hosting (Lengkap)](#-panduan-deploy--upload-hosting-lengkap)
  - [Checklist Sebelum Deploy](#1-checklist-sebelum-deploy)
  - [Siapkan Paket Deploy](#2-siapkan-paket-deploy-disarankan)
  - [Opsi A — Shared Hosting / cPanel](#opsi-a--shared-hosting--cpanel-upload-zip)
  - [Opsi B — VPS / Cloud Server (Nginx)](#opsi-b--vps--cloud-server-ubuntudebian--nginx)
  - [Opsi C — VPS dengan Apache](#opsi-c--vps-dengan-apache)
  - [Scheduler (Cron) & Queue Worker](#3-scheduler-cron--queue-worker-wajib-untuk-fitur-tertentu)
  - [Konfigurasi Webhook & URL Publik](#4-konfigurasi-webhook--url-publik)
  - [Update / Deploy Ulang](#5-update--deploy-ulang)
  - [Troubleshooting](#6-troubleshooting-umum)
- [Gambaran Struktur Proyek](#-gambaran-struktur-proyek)
- [Catatan Keamanan Operasional](#-catatan-keamanan-operasional)
- [Kontribusi](#-kontribusi)
- [Lisensi](#-lisensi)

---

## 📌 Ringkasan

**SIRESTEDUH** adalah sistem manajemen wisata end-to-end untuk BUMDes Wisata Teduh yang mencakup alur:

**Pemesanan tamu → Pembayaran (transfer/manual atau gateway otomatis) → Verifikasi/admin → Penerbitan & pengiriman e-ticket QR → Check-in di lokasi → Pencatatan transaksi POS & pelaporan.**

Aplikasi dirancang agar operasional harian lebih rapi, aman, dan mudah dipantau (dengan kontrol akses berbasis peran, audit log, pembatasan akses saat gangguan/maintenance, serta automasi pembersihan data operasional).

---

## 🌟 Keunggulan Utama

| Area | Keunggulan |
|---|---|
| **End-to-end** | Satu sistem untuk reservasi, pembayaran, verifikasi, e-ticket, check-in, POS, dan laporan. |
| **Multi-peran** | Akses terpisah dan terkontrol untuk **Admin**, **Kasir**, **Kasir Makanan**, dan **Petugas Check-in**. |
| **Pembayaran fleksibel** | Mendukung **unggah bukti transfer manual** dan **pembayaran otomatis Midtrans** (Snap: QRIS/GoPay/ShopeePay/VA, sesuai konfigurasi). |
| **Keamanan berlapis** | RBAC, rate limiting, proteksi webhook (signature/secret), penyimpanan bukti privat, audit log dengan redaksi data sensitif, dan validasi input. |
| **Konsistensi transaksi** | Pemrosesan kritikal memakai transaksi DB/penguncian (lock) & penanganan idempotent pada notifikasi gateway untuk mencegah duplikasi. |
| **Operasional otomatis** | Scheduler untuk kedaluwarsa reservasi pending, pruning log/activity/token/cache, serta dukungan queue. |
| **Transparansi & audit** | Pencatatan aktivitas/riwayat (activity feed, log viewer) dengan retensi yang dapat dikelola. |
| **Kemudahan tamu** | Ketersediaan real-time, status pesanan, e-ticket QR, chatbot bantuan (AI) & PWA-ready. |
| **Siap deploy** | Mendukung shared hosting/cPanel, VPS (Nginx/Apache), skrip penyiapan paket deploy, dan panduan operasional lengkap. |

---

## 🔐 Keamanan & Perlindungan Data (Lengkap)

Bagian ini merangkum kontrol keamanan yang **diimplementasikan di aplikasi** (middleware, service, controller, konfigurasi, dan automasi). Selalu kombinasikan dengan praktik deploy yang benar (HTTPS, `APP_DEBUG=false`, server aman).

### 1. Autentikasi & Kontrol Akses (RBAC)

- **Role-Based Access Control (RBAC)** melalui middleware `role`:
  - Rute admin dilindungi `auth` + `role:admin`.
  - POS utama dilindungi `role:kasir`.
  - POS F&B dilindungi `role:kasir_makanan`.
  - Modul check-in petugas dilindungi `role:checkin`.
- **Penanganan akses terstruktur**:
  - Pengguna belum login: diarahkan ke halaman login (web) atau mendapat respons `401 Unauthenticated` (API/JSON).
  - Pengguna login tanpa hak akses: ditolak dengan `403 Forbidden` (API/JSON) atau dialihkan dengan pesan error (web).
- **Dua mekanisme autentikasi sesuai kebutuhan**:
  - **Session-based (web & API same-origin)**: cookie session Laravel digunakan untuk autentikasi permintaan dari antarmuka aplikasi.
  - **Token-based (API eksternal)**: Laravel Sanctum (`/api/auth/login`, `/api/auth/me`, `/api/auth/logout`) untuk akses API terautentikasi.

### 2. Perlindungan Endpoint & Anti-Penyalahgunaan (Rate Limiting)

Pembatasan laju diterapkan untuk mengurangi brute-force, spam, dan penyalahgunaan resource:

| Endpoint / Aksi | Batas (ringkasan) |
|---|---|
| Login API token (`POST /api/auth/login`) | `throttle:10,1` |
| Login web (rate limiter `login`) | 5 percobaan/menit per kombinasi **email + IP** |
| API publik umum (units, availability, products, chatbot, payment show/upload, dll.) | `throttle:60,1` |
| Pembuatan reservasi (`reservations-store`) | **3/menit** dan **15/jam** per IP |
| Chatbot tanya (`/api/chat`) | 15/menit |
| Chatbot suara/TTS (`/api/chat/speak`) | 10/menit |
| Webhook Telegram | 60/menit |
| Notifikasi Midtrans | 60/menit |

Respons batasan memberi kode/umpan balik yang jelas (mis. `429`) agar klien tahu harus menunggu.

### 3. Keamanan Pembayaran & Transaksi

- **Verifikasi tanda tangan notifikasi Midtrans**: notifikasi gateway divalidasi menggunakan signature (`hash('sha512', order_id + status_code + gross_amount + server_key)`) sebelum diproses — mencegah callback palsu.
- **Idempotensi & anti-duplikasi**: notifikasi pembayaran yang sama/berulang ditangani secara aman (pengecekan status & pemrosesan terkontrol) sehingga konfirmasi tidak diproses ganda.
- **Pemrosesan atomik**: pembaruan status pembayaran/reservasi kritikal dilakukan dalam **transaksi database** dengan penguncian record (`lockForUpdate`) pada alur notifikasi gateway untuk menjaga konsistensi.
- **Pemisahan metode bayar**: mendukung transfer manual (unggah bukti) dan gateway otomatis (Midtrans Snap) dengan pencatatan status pembayaran.
- **Penerbitan e-ticket terkontrol**: e-ticket dipastikan/dibuat saat pembayaran terkonfirmasi dan dapat dikirim/diulang sesuai alur verifikasi/admin.
- **Bukti bayar tidak dipublikasikan langsung**: file bukti disimpan di disk **private** (bukan akses publik langsung) dan hanya dapat diakses melalui route admin terproteksi.
- **Akses bukti ter-audit**: pembukaan/streaming bukti pembayaran oleh admin dicatat (audit log).

### 4. Integritas Data & Konsistensi Bisnis

- **Kedaluwarsa reservasi pending otomatis**: scheduler menjalankan `reservations:expire-pending` (setiap menit) agar reservasi tanpa pembayaran tidak menahan ketersediaan/stok selamanya.
- **Pembatalan aman & pengembalian stok addon**: pembatalan (manual/terkait alur tertentu) memproses perubahan status dan pengembalian stok produk tambahan secara terkontrol dalam transaksi.
- **Ketersediaan unit berbasis status aktif**: ketersediaan/slot dihitung dari reservasi berstatus pemblokir (bukan sekadar angka statis), membantu mencegah konflik pemesanan.
- **Validasi status sebelum aksi**: unggah bukti/aksi pembayaran dibatasi pada status reservasi/pembayaran yang valid (mencegah transisi tidak sah).
- **Konsistensi stok POS**: transaksi POS memvalidasi ketersediaan/stok dan menolak kuantitas tidak valid/kosong (sesuai skenario pengujian & logika aplikasi).

### 5. Audit Trail, Logging & Retensi

- **Activity logging**: middleware mencatat aktivitas/permintaan (user/tamu, method, path/route, status, metadata relevan, request id, IP, user agent) untuk jejak operasional.
- **AuditLogger terpusat** untuk event penting (mis. pembuatan transaksi gateway, unggah/lihat bukti, status gateway, dll.) dengan label subjek & properti terstruktur.
- **Redaksi data sensitif otomatis**: kunci sensitif (mis. `password`, token, secret, signature, authorization, cookie/session, dsb.) **disamarkan (`[REDACTED]`)** saat disimpan di properti audit.
- **Metadata file aman & berguna**: untuk unggah/akses file tertentu dicatat metadata (nama, mime, ukuran, hash/path sesuai konteks) tanpa mengekspos kredensial.
- **Log viewer & activity feed (admin)**: admin dapat meninjau log/aplikasi aktivitas melalui modul admin (dengan pembatasan akses admin).
- **Retensi & pembersihan terjadwal**: pruning activity/log, pembersihan token Sanctum kedaluwarsa, dan pembersihan cache terjadwal untuk menjaga kebersihan data & performa.

### 6. Privasi File & Proteksi Data Sensitif

- **Bukti pembayaran privat**: disimpan di storage private (`Storage::disk('private')`) dan diakses via controller admin (`/admin/proofs/{reservation}`) — bukan file publik.
- **`.env` & secret tidak untuk repo**: konfigurasi rahasia disimpan di environment server; `.env` diabaikan Git.
- **File sensitif lokal diabaikan**: `.gitignore` mencakup `.env`, `cookies.txt` (dump sesi lokal), dan artefak sensitif/lokal lainnya; `cookies.txt` tidak dipush.
- **Tidak mengekspos kredensial di audit**: redaksi otomatis pada audit/log properti.

### 7. Mode Maintenance & Ketersediaan Terkontrol

- **Maintenance mode berbasis pengaturan aplikasi**: saat diaktifkan, akses dibatasi untuk publik/pengguna non-admin.
- **Admin tetap dapat akses** (untuk perbaikan/operasional) dan **halaman login tetap dapat diakses** agar admin bisa masuk.
- **Respons sesuai konteks**: API mengembalikan JSON `503` (status maintenance) dengan pesan; web menampilkan halaman maintenance.
- **Diterapkan pada web & API** (middleware dijalankan setelah session dimuat) untuk perilaku konsisten.

### 8. Validasi Input & Keamanan Aplikasi Web

- **Validasi sisi server** pada input penting (mis. pembuatan reservasi, unggah bukti pembayaran: wajib file gambar, batas ukuran, order code valid; endpoint Midtrans memvalidasi order).
- **Pembatasan jenis/ukuran unggah bukti**: upload bukti pembayaran dibatasi ke **image** dengan ukuran maksimum **2 MB** (sesuai aturan controller).
- **Pemisahan akses & pengecekan kepemilikan/order**: endpoint pembayaran/reservasi publik bekerja berbasis identitas order (mis. `order_code`) dan memeriksa status yang diizinkan.
- **Proteksi CSRF & session**: aplikasi web Blade menyertakan token CSRF; API same-origin dikonfigurasi dengan middleware cookie/session yang relevan.
- **Penanganan error API**: respons JSON konsisten untuk permintaan API/JSON pada kondisi error/exception tertentu.

### 9. Keamanan Webhook & Integrasi Eksternal

- **Telegram webhook**: mendukung verifikasi **secret token header** (`X-Telegram-Bot-Api-Secret-Token`) bila dikonfigurasi; callback query diproses dengan pencocokan data & pengecekan entitas reservasi.
- **Midtrans webhook**: signature diverifikasi; payload dicatat/ditangani dengan kontrol status & logging untuk investigasi.
- **Token Sanctum**: token kedaluwarsa dapat dipangkas otomatis oleh scheduler (`sanctum:prune-expired`).

### 10. Praktik Keamanan Deployment

- Jalankan produksi dengan **`APP_ENV=production`** dan **`APP_DEBUG=false`**.
- Gunakan **HTTPS** (`APP_URL` https) dan aktifkan SSL di hosting.
- Arahkan **document root ke `public/`** (wajib untuk Laravel) agar kode sumber & `.env` tidak terekspos.
- Set permission yang benar (`storage/`, `bootstrap/cache/` writable) tanpa memberi izin berlebihan.
- Lindungi kredensial (`DB`, Midtrans, Fonnte, Telegram, Gemini) di `.env` server dan rotasi bila bocor.
- Aktifkan **cron scheduler** & (jika perlu) **queue worker** agar kontrol otomatis (expiry/prune) berjalan.

> **Catatan:** Tidak ada sistem yang 100% kebal. Keamanan efektif bergantung pada konfigurasi server/hosting, pembaruan dependensi, manajemen kredensial, dan prosedur operasional.

---

## 🧩 Fitur Lengkap per Modul

### A. Tamu / Publik
- **Beranda & eksplorasi unit**: melihat informasi dan unit tersedia.
- **Reservasi online**: pemesanan unit glamping / paket sesuai ketersediaan.
- **Pembelian/akses tiket masuk** (sesuai alur aplikasi).
- **Cek ketersediaan** unit/tiket secara real-time (endpoint availability/units).
- **Checkout & pembayaran**:
  - Unggah **bukti transfer manual**.
  - Pembayaran otomatis via **Midtrans Snap** (metode mengikuti konfigurasi, mis. QRIS, GoPay, ShopeePay, VA bank).
- **Halaman pembayaran & status pesanan** berdasarkan order.
- **E-Ticket QR** untuk check-in.
- **Pencarian/cek status pesanan tamu**.
- **Chatbot “Teduh Bot”** (AI/Gemini) untuk bantuan informasi, dengan opsi suara/TTS (jika dikonfigurasi).
- **PWA-ready**: manifest & service worker untuk pengalaman mirip aplikasi.

### B. Admin
- **Dashboard & analitik operasional**.
- **Notifikasi admin** & **pencarian global** (command palette).
- **Manajemen reservasi**: lihat, verifikasi, dan pembatalan sesuai alur.
- **Verifikasi pembayaran** (terima/tolak) dan alur penerbitan e-ticket.
- **Kirim/ulang e-ticket** ke tamu (manual resend).
- **Master data**:
  - Unit (CRUD, stok, gambar unit).
  - Produk.
  - Paket glamping & paket aula.
  - Pengguna/user.
- **Laporan** terpadu.
- **Maintenance mode** (aktif/nonaktif & pesan).
- **Log viewer** dan **activity feed/audit** (termasuk retensi & pembersihan).
- **Akses bukti pembayaran privat** khusus admin.

### C. Kasir / POS & Kasir Makanan
- **POS Kasir (`/pos`)**: transaksi penjualan (produk/unit) dengan validasi item/stok dan riwayat.
- **Kasir Makanan / F&B (`/kasir-makanan`)**: mode POS khusus penjualan kategori makanan, terpisah hak aksesnya.
- **Riwayat transaksi** untuk masing-masing peran.

### D. Petugas Check-in
- **Halaman check-in/scanner** untuk validasi e-ticket QR.
- **Tampilan tiket & riwayat check-in**.
- **Pencegahan penyalahgunaan tiket** (validasi tiket; tiket yang tidak valid/sudah dipakai ditolak sesuai logika pengujian).

### E. API & Akses Eksternal
- **Endpoint publik**: ketersediaan, unit/tiket, produk, reservasi, chatbot, info/upload pembayaran, Midtrans create/notification.
- **Autentikasi token (Sanctum)** untuk klien eksternal (`/api/auth/*`).
- **API admin** (terproteksi auth+role) untuk dashboard, notifikasi, verifikasi, laporan, master data, logs/activity, dsb.
- **Endpoint webhook**: Telegram & notifikasi Midtrans.

---

## 🔌 Integrasi & Otomasi

| Integrasi | Fungsi | Catatan |
|---|---|---|
| **Midtrans (Snap)** | Pembayaran otomatis (QRIS, e-wallet, Virtual Account sesuai konfigurasi) | Butuh `SERVER/CLIENT KEY`, `APP_URL`/webhook publik; notifikasi diverifikasi signature |
| **Fonnte (WhatsApp)** | Pengiriman notifikasi/pesan (sesuai konfigurasi & alur) | Butuh token API |
| **Telegram Bot/Webhook** | Notifikasi & aksi verifikasi/pembatalan via callback (sesuai alur) | Butuh bot token, chat id, webhook & (opsional) secret token |
| **Google Gemini** | Chatbot bantuan & TTS suara Teduh Bot | Butuh API key & model; ada rate limit khusus endpoint TTS |
| **QR Code** | E-ticket check-in | Menggunakan library QR code |

**Automasi terjadwal (scheduler):** kedaluwarsa reservasi pending, pruning activity & log, pembersihan token Sanctum expired, dan pembersihan cache.

---

## ⚙️ Operasional, Keandalan & Performa

- **Cache & optimasi produksi**: dukungan `config:cache`, `route:cache`, `view:cache`, autoloader teroptimasi.
- **Pembersihan rutin**: prune log/activity/token/cache untuk mencegah data menumpuk.
- **Scheduler & queue**: memisahkan tugas berkala dan (opsional) pekerjaan antrian.
- **Mode maintenance**: memungkinkan admin menutup akses publik sementara tanpa kehilangan kendali operasional.
- **PWA-ready**: meningkatkan pengalaman akses di perangkat mobile.
- **Dokumentasi pengujian**: tersedia skenario black-box & concurrency di folder `docs/`.

---

## 🧱 Tech Stack

| Layer | Teknologi |
|---|---|
| Backend | PHP 8.3+, Laravel 13, Laravel Sanctum |
| Frontend | Blade, Tailwind CSS 4, Vite 8, Alpine.js |
| Database | SQLite / MySQL / PostgreSQL |
| Queue/Cache/Session | Database driver (default, dapat disesuaikan) |
| QR & Integrasi | chillerlan/php-qrcode, Midtrans, Fonnte, Telegram, Gemini |
| Testing | PHPUnit 12 |

---

## 📦 Prasyarat

**Lokal / Server:**
- PHP **8.3+** dengan ekstensi: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `json`, `mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`, `zip` (+ driver DB: `pdo_mysql` / `pdo_pgsql` / `pdo_sqlite`)
- Composer 2
- Node.js & npm (untuk build aset; tidak wajib di server produksi jika upload hasil build)
- Web server Nginx/Apache (`mod_rewrite` untuk Apache)
- Database MySQL/MariaDB atau PostgreSQL (disarankan produksi), SQLite untuk dev

---

## 🚀 Instalasi & Menjalankan (Lokal)

### Setup cepat

```bash
git clone https://github.com/shollahudien/Sireduh.git
cd Sireduh
composer run setup
```

`composer run setup`: install dependency Composer & npm, salin `.env`, generate app key, migrasi database, dan build aset frontend.

### Setup manual

```bash
composer install
cp .env.example .env
php artisan key:generate

# Atur DB_* di .env, lalu:
php artisan migrate --seed

npm install
npm run build
```

### Menjalankan

```bash
php artisan serve
```

Pengembangan frontend (hot reload):
```bash
npm run dev
```

---

## ⚙️ Konfigurasi `.env`

> **Jangan pernah commit `.env` atau kredensial/secret ke repository.** Gunakan `.env.example` sebagai template.

| Variabel | Keterangan |
|---|---|
| `APP_NAME` | Nama aplikasi (contoh: `SIRESTEDUH`) |
| `APP_ENV` | `local` (dev) / `production` (hosting) |
| `APP_KEY` | Kunci enkripsi (`php artisan key:generate`) |
| `APP_DEBUG` | `true` (dev) / **`false`** (production) |
| `APP_URL` | URL publik, contoh `https://domainanda.com` |
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Koneksi database |
| `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION` | Default `database` (pastikan migrasi dijalankan) |
| `PAYMENT_BANK`, `PAYMENT_ACCOUNT_NUMBER`, `PAYMENT_ACCOUNT_NAME`, `PAYMENT_WHATSAPP` | Informasi transfer manual |
| `MIDTRANS_ENABLED`, `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, `MIDTRANS_IS_PRODUCTION`, `MIDTRANS_ENABLED_PAYMENTS` | Integrasi Midtrans |
| `FONNTE_API_TOKEN`, `FONNTE_API_URL` | Notifikasi WhatsApp (Fonnte) |
| `TELEGRAM_BOT_TOKEN`, `TELEGRAM_CHAT_ID`, `TELEGRAM_WEBHOOK_SECRET` | Notifikasi/bot Telegram & proteksi webhook |
| `GEMINI_API_KEY`, `GEMINI_MODEL`, `GEMINI_BASE_URL`, `GEMINI_TTS_*` | Chatbot AI & TTS (Teduh Bot) |
| `MAIL_*` | Konfigurasi email (jika digunakan) |

Lihat [`.env.example`](.env.example) untuk daftar lengkap.

---

## 🧪 Testing

```bash
php artisan test
```

Dokumen pengujian tambahan:
- [`docs/BLACKBOX_TESTING.md`](docs/BLACKBOX_TESTING.md)
- [`docs/CONCURRENCY_TESTING.md`](docs/CONCURRENCY_TESTING.md)

---

## 🌐 Panduan Deploy / Upload Hosting (Lengkap)

### 1. Checklist Sebelum Deploy

- [ ] Gunakan **PHP 8.3+** di server.
- [ ] Set `APP_ENV=production` dan `APP_DEBUG=false`.
- [ ] Set `APP_URL` ke domain HTTPS produksi (contoh `https://wisatateduh.id`).
- [ ] Siapkan database produksi (MySQL/MariaDB disarankan) dan kredensialnya.
- [ ] Pastikan `APP_KEY` terisi (jangan pakai key development).
- [ ] Build aset frontend (`npm run build`) sehingga folder `public/build` tersedia.
- [ ] Konfigurasikan `PAYMENT_*`, `MIDTRANS_*`, `FONNTE_*`, `TELEGRAM_*`, `GEMINI_*` sesuai kebutuhan (kosongkan jika tidak dipakai).
- [ ] **Jangan upload** `.env`, `node_modules`, `tests`, `.git`, atau file sensitif lokal.
- [ ] Siapkan cron/scheduler & queue worker jika memakai fitur terjadwal/notifikasi antrian.

### 2. Siapkan Paket Deploy (Disarankan)

Script menyiapkan paket siap upload (install dependency production, build aset, bersihkan cache, dan buat `.zip` berisi `vendor` + `public/build`):

```bash
bash scripts/prepare-deploy.sh
```

Opsi:
- `--skip-build` : lewati build frontend (jika `public/build` sudah ada).
- `--skip-composer` : lewati `composer install --no-dev`.
- `--skip-zip` : hanya siapkan file tanpa membuat arsip.

Hasil arsip: `deploy/siresteduh-deploy-YYYYMMDD-HHMMSS.zip`

> Catatan: script menjalankan `composer install --no-dev` sehingga dependency dev (PHPUnit/Pint/dll) dihapus dari `vendor/`. Untuk lanjut development lokal setelahnya jalankan `composer install`.

**Manual (tanpa script):**
```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan optimize:clear
```

---

### Opsi A — Shared Hosting / cPanel (Upload ZIP)

Cocok untuk hosting yang menyediakan PHP & MySQL (tanpa akses root penuh).

#### A1. Persiapan di cPanel
1. **PHP Version**: pilih PHP **8.3+** (*Select PHP Version* / *MultiPHP Manager*).
2. **Aktifkan ekstensi**: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `mbstring`, `openssl`, `pdo_mysql` (atau driver DB lain), `tokenizer`, `xml`, `zip`.
3. **Buat Database**: *MySQL Database Wizard* → buat database, user, password, lalu beri **ALL PRIVILEGES**.
4. (Opsional) Pastikan fungsi PHP yang dibutuhkan tidak diblokir oleh hosting.

#### A2. Upload & Extract
1. Buat paket deploy (`bash scripts/prepare-deploy.sh`) di komputer lokal.
2. Buka **File Manager** → masuk ke folder di luar `public_html` (disarankan), misal `/home/username/siresteduh`.
3. Upload file `.zip` hasil deploy lalu **Extract** di folder tersebut.
   - Alternatif: upload via FTP/SFTP seluruh isi project (termasuk `vendor/` dan `public/build/`).

#### A3. Atur Document Root ke folder `public` (PENTING)
Web root **harus** mengarah ke folder `public/` Laravel, bukan root project.

- **Jika domain utama** (root `public_html` tidak bisa diubah langsung):
  1. Ekstrak project ke `/home/username/siresteduh`.
  2. Salin isi `/home/username/siresteduh/public/*` ke `public_html/`.
  3. Edit `public_html/index.php` agar path menunjuk ke project:
     ```php
     require __DIR__.'/../siresteduh/vendor/autoload.php';
     $app = require_once __DIR__.'/../siresteduh/bootstrap/app.php';
     ```
  4. Pastikan symlink/akses `storage` publik sesuai kebutuhan (lihat A6).
- **Jika addon/subdomain** (disarankan): set **Document Root** ke `/home/username/siresteduh/public`.

#### A4. Konfigurasi `.env`
1. Salin `.env.example` menjadi `.env` di folder project.
2. Edit `.env`:
   ```env
   APP_NAME="SIRESTEDUH"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://domainanda.com

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=nama_db
   DB_USERNAME=user_db
   DB_PASSWORD=password_db

   SESSION_DRIVER=database
   CACHE_STORE=database
   QUEUE_CONNECTION=database
   ```
3. Isi konfigurasi pembayaran/integrasi (`PAYMENT_*`, `MIDTRANS_*`, dll.) sesuai kebutuhan.

#### A5. Jalankan Perintah Artisan
Melalui **Terminal/SSH** cPanel (`cd` ke folder project):
```bash
php artisan key:generate --force   # jika APP_KEY kosong
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
> Jika tidak ada akses terminal, proses migrasi/setup akan lebih sulit; akses terminal/SSH sangat dianjurkan.

#### A6. Permission Folder
```bash
chmod -R 775 storage bootstrap/cache
```
Pastikan folder `storage/` dan `bootstrap/cache/` writable oleh web server.

#### A7. SSL (HTTPS)
- Aktifkan SSL (AutoSSL/Let's Encrypt) di cPanel.
- Pastikan `APP_URL` memakai `https://`.

---

### Opsi B — VPS / Cloud Server (Ubuntu/Debian + Nginx)

#### B1. Install Dependensi Server
```bash
sudo apt update
sudo apt install -y nginx mysql-server unzip git curl \
  php8.3 php8.3-fpm php8.3-cli php8.3-mbstring php8.3-xml php8.3-curl \
  php8.3-zip php8.3-bcmath php8.3-mysql php8.3-sqlite3
```
> Sesuaikan versi PHP (8.3+). Install Composer & Node.js (jika build di server).

#### B2. Siapkan Database
```bash
sudo mysql -e "CREATE DATABASE siresteduh CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'sireduh_user'@'localhost' IDENTIFIED BY 'password_kuat';"
sudo mysql -e "GRANT ALL PRIVILEGES ON siresteduh.* TO 'sireduh_user'@'localhost'; FLUSH PRIVILEGES;"
```

#### B3. Deploy Kode Aplikasi
```bash
cd /var/www
sudo git clone https://github.com/shollahudien/Sireduh.git siresteduh
cd siresteduh

cp .env.example .env
nano .env   # set APP_ENV=production, APP_DEBUG=false, APP_URL, DB_*, integrasi

composer install --no-dev --optimize-autoloader
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link

npm ci && npm run build   # jika build di server

sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### B4. Konfigurasi Nginx
`/etc/nginx/sites-available/siresteduh`:
```nginx
server {
    listen 80;
    server_name domainanda.com www.domainanda.com;
    root /var/www/siresteduh/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    client_max_body_size 10M; # sesuaikan untuk upload bukti pembayaran
}
```
Aktifkan & reload:
```bash
sudo ln -s /etc/nginx/sites-available/siresteduh /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

#### B5. HTTPS (Let's Encrypt)
```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d domainanda.com -d www.domainanda.com
```

---

### Opsi C — VPS dengan Apache

1. Aktifkan rewrite dan arahkan **DocumentRoot** ke `/var/www/siresteduh/public`:
   ```bash
   sudo a2enmod rewrite
   ```
2. Contoh VirtualHost (`/etc/apache2/sites-available/siresteduh.conf`):
   ```apache
   <VirtualHost *:80>
       ServerName domainanda.com
       DocumentRoot /var/www/siresteduh/public

       <Directory /var/www/siresteduh/public>
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```
3. Reload: `sudo a2ensite siresteduh && sudo systemctl reload apache2`
4. HTTPS: `sudo certbot --apache -d domainanda.com`

---

### 3. Scheduler (Cron) & Queue Worker (Wajib untuk Fitur Tertentu)

Tugas terjadwal aplikasi (ringkasan): `reservations:expire-pending` (tiap menit), pruning activity/log, `sanctum:prune-expired`, pembersihan cache (harian/berkala).

#### Cron Scheduler
**VPS:**
```bash
* * * * * cd /var/www/siresteduh && php artisan schedule:run >> /dev/null 2>&1
```

**cPanel (Cron Jobs):**
```bash
* * * * * /usr/local/bin/php /home/username/siresteduh/artisan schedule:run >> /dev/null 2>&1
```

#### Queue Worker
Default `QUEUE_CONNECTION=database`. Jika memakai job antrian, jalankan worker (VPS/systemd contoh):
```ini
[Unit]
Description=SIRESTEDUH Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/siresteduh/artisan queue:work --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
```
```bash
sudo systemctl enable --now siresteduh-queue
```
> Shared hosting tanpa proses long-running: pertimbangkan `QUEUE_CONNECTION=sync` atau mekanisme cron/worker yang didukung hosting.

---

### 4. Konfigurasi Webhook & URL Publik

| Layanan | Endpoint | Catatan |
|---|---|---|
| Midtrans Notification | `POST https://domainanda.com/api/payments/midtrans/notification` | Set di dashboard Midtrans; isi `MIDTRANS_*` |
| Telegram Webhook | `POST https://domainanda.com/api/telegram/webhook` | Set `TELEGRAM_*` (termasuk secret jika dipakai) |

Pastikan `APP_URL` sesuai domain produksi.

---

### 5. Update / Deploy Ulang

**Shared hosting (zip):** build paket baru → (opsional `php artisan down`) → backup `.env` & `storage/app/public` → upload/extract (jangan timpa `.env`/data upload) → `migrate --force`, `storage:link`, cache ulang → `php artisan up`.

**VPS (git):**
```bash
cd /var/www/siresteduh
php artisan down
git pull origin master
composer install --no-dev --optimize-autoloader
npm ci && npm run build   # jika ada perubahan frontend
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache && php artisan route:cache && php artisan view:cache
sudo chown -R www-data:www-data storage bootstrap/cache
php artisan up
php artisan queue:restart  # jika memakai queue
```

---

### 6. Troubleshooting Umum

| Gejala | Penyebab umum | Solusi |
|---|---|---|
| 500 Internal Server Error | Permission, `.env`/`APP_KEY`, ekstensi PHP kurang, cache rusak | Cek `storage/logs/laravel.log`; set permission `storage` & `bootstrap/cache`; `php artisan key:generate --force` & `php artisan optimize:clear`; pastikan ekstensi aktif |
| Aset CSS/JS tidak termuat | `public/build` belum ada, `APP_URL` salah, document root keliru | `npm run build`; perbaiki `APP_URL`; arahkan web root ke `public/` |
| 404 semua route (kecuali `/`) | Rewrite tidak aktif | Nginx `try_files ... /index.php?$query_string`; Apache `mod_rewrite` & `AllowOverride All` |
| Upload/gambar tidak muncul | Symlink storage belum ada | `php artisan storage:link`; cek permission/path |
| Upload bukti gagal (413/limit) | Batas upload server kecil | Naikkan `upload_max_filesize`, `post_max_size` (PHP) dan `client_max_body_size` (Nginx) |
| Reservasi pending tidak kedaluwarsa/log menumpuk | Scheduler tidak jalan | Pastikan cron `schedule:run` aktif tiap menit |
| Job/notifikasi tertunda | Queue worker tidak aktif | Jalankan `queue:work` atau gunakan `QUEUE_CONNECTION=sync` bila sesuai |

**Diagnostik:**
```bash
php artisan about
php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear
tail -n 200 storage/logs/laravel.log
```


---

## 📚 Dokumentasi Sistem (Kamus Data, DFD, Flowchart, ERD)

Dokumentasi analisis & perancangan sistem tersedia di folder [`docs/`](docs/):

| Dokumen | Isi |
|---|---|
| [Dokumentasi Sistem (Indeks)](docs/DOKUMENTASI_SISTEM.md) | Ringkasan & tautan seluruh artefak dokumentasi |
| [Kamus Data](docs/KAMUS_DATA.md) | Definisi tabel/entitas, kolom, tipe data, relasi, dan catatan domain |
| [DFD](docs/DFD.md) | **DFD lengkap**: Level 0 (Konteks), Level 1, dan Level 2 proses 1.0–6.0 (Mermaid) |
| [Flowchart](docs/FLOWCHART.md) | **Flowchart sistem lengkap end-to-end** + alur detail (reservasi, pembayaran manual/Midtrans, verifikasi, check-in, POS, maintenance, autentikasi) |
| [ERD](docs/ERD.md) | **ERD lengkap**: entitas, atribut kunci, relasi & kardinalitas (Mermaid) |
| [UAT](docs/UAT.md) | Dokumen User Acceptance Testing (97 skenario: pertanyaan uji, jawaban/hasil diharapkan, data uji, & sign-off) |

> Diagram dibuat menggunakan **Mermaid** sehingga dapat langsung dirender di GitHub.

---

## 📁 Gambaran Struktur Proyek

```text
app/
  Http/Controllers/        # Web & API (Admin, POS, Check-in, Public, Telegram, Midtrans)
  Http/Middleware/         # Role, maintenance, activity logging
  Models/                  # Eloquent models (Reservation, Payment, Unit, Product, dll.)
  Services/                # Business logic (Availability, Midtrans, Telegram, Chatbot, Audit, dll.)
resources/views/           # Blade views (public, admin, pos, checkin, layouts, components)
routes/
  web.php                  # Rute web
  api.php                  # Rute API & webhook
database/migrations/       # Skema database
scripts/                   # Script bantu (mis. prepare-deploy.sh)
docs/                      # Dokumen pengujian
```

> **Catatan:** `Dockerfile` dan `compose*.yaml` di root saat ini merupakan placeholder (bukan konfigurasi Laravel siap pakai) — gunakan panduan deploy di atas atau sesuaikan untuk containerisasi Laravel.

---

## 🛡️ Catatan Keamanan Operasional

- Jangan commit `.env`, kredensial API, token, cookie/session dump, atau data pribadi.
- Selalu gunakan `APP_DEBUG=false` di produksi dan HTTPS.
- Batasi akses akun **admin**; gunakan kredensial kuat dan prinsip least privilege.
- Lindungi endpoint webhook (signature Midtrans, secret Telegram) dan pantau log notifikasi.
- Backup berkala database & `storage/app/public` (upload bukti/gambar).
- Perbarui dependensi & PHP secara berkala untuk patch keamanan.

---

## 🤝 Kontribusi

1. Fork repository
2. Buat branch fitur: `git checkout -b fitur/nama-fitur`
3. Commit perubahan: `git commit -m "feat: deskripsi singkat"`
4. Push & buka Pull Request

---

## 📄 Lisensi

Proyek ini menggunakan lisensi [MIT](https://opensource.org/licenses/MIT).

<div align="center">

Dibuat untuk **BUMDes Wisata Teduh** — reservasi mudah, pengalaman teduh 🌿

</div>
