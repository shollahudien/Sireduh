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

## Daftar Isi

- [Fitur Utama](#-fitur-utama)
- [Tech Stack](#-tech-stack)
- [Prasyarat](#-prasyarat)
- [Instalasi Lokal](#-instalasi--menjalankan-lokal)
- [Konfigurasi .env](#️-konfigurasi-env)
- [Testing](#-testing)
- [Panduan Deploy / Upload Hosting](#-panduan-deploy--upload-hosting-lengkap)
  - [Checklist Sebelum Deploy](#1-checklist-sebelum-deploy)
  - [Siapkan Paket Deploy](#2-siapkan-paket-deploy-disarankan)
  - [Opsi A — Shared Hosting / cPanel](#opsi-a--shared-hosting--cpanel-upload-zip)
  - [Opsi B — VPS / Cloud Server (Nginx)](#opsi-b--vps--cloud-server-ubuntudebian--nginx)
  - [Opsi C — VPS dengan Apache](#opsi-c--vps-dengan-apache)
  - [Scheduler (Cron) & Queue Worker](#3-scheduler-cron--queue-worker-wajib-untuk-fitur-tertentu)
  - [Konfigurasi Webhook & URL Publik](#4-konfigurasi-webhook--url-publik)
  - [Update / Deploy Ulang](#5-update--deploy-ulang)
  - [Troubleshooting](#6-troubleshooting-umum)
- [Struktur Proyek](#-gambaran-struktur-proyek)
- [Catatan Keamanan](#-catatan-keamanan)
- [Lisensi](#-lisensi)

---

## ✨ Fitur Utama

### 🌿 Tamu (Publik)
- **Reservasi online** unit glamping & pembelian tiket masuk.
- **Cek ketersediaan** unit/tiket secara real-time.
- **Pembayaran**: unggah bukti transfer manual atau pembayaran via **Midtrans** (QRIS/VA/e-wallet, sesuai konfigurasi).
- **E-Ticket QR** untuk check-in, plus halaman cek status pesanan.
- **Chatbot “Teduh Bot”** (AI/Gemini) dengan dukungan suara (TTS) untuk bantuan informasi.
- **PWA-ready** (manifest & service worker).

### 🛠️ Admin
- **Dashboard & analitik** operasional.
- **Manajemen reservasi & verifikasi pembayaran** (terima/tolak).
- **Master data**: unit, gambar unit, produk, paket glamping/aula, pengguna.
- **Kirim/ulang e-ticket** ke tamu.
- **Laporan** terpadu & pencarian global.
- **Maintenance mode**, **log viewer**, dan **activity feed/audit log** (dengan retensi & prune).
- **Notifikasi** (integrasi Telegram webhook/bot sesuai konfigurasi).

### 💳 Kasir & Check-in
- **POS Kasir**: transaksi produk/unit dengan perhitungan stok.
- **Kasir Makanan (F&B)**: POS khusus kategori makanan.
- **Petugas Check-in**: pemindaian/validasi e-ticket QR dan riwayat check-in.

### 🔌 Integrasi & Lainnya
- **API** publik & autentikasi token (Laravel Sanctum).
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
| Database | SQLite / MySQL / PostgreSQL |
| Queue/Cache/Session | Database driver (default, dapat disesuaikan) |
| Testing | PHPUnit 12 |

---

## 📦 Prasyarat

**Lokal / Server:**
- PHP **8.3+** dengan ekstensi: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `json`, `mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`, `zip` (dan driver DB: `pdo_mysql` / `pdo_pgsql` / `pdo_sqlite`)
- Composer 2
- Node.js & npm (untuk build aset; tidak wajib di server produksi jika upload hasil build)
- Web server: Nginx / Apache dengan `mod_rewrite`
- Database: MySQL/MariaDB atau PostgreSQL (direkomendasikan untuk produksi), SQLite untuk dev

---

## 🚀 Instalasi & Menjalankan (Lokal)

### Setup cepat

```bash
git clone https://github.com/shollahudien/Sireduh.git
cd Sireduh
composer run setup
```

`composer run setup` akan: install dependency Composer & npm, menyalin `.env`, generate app key, migrasi database, dan build aset frontend.

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
| `APP_KEY` | Kunci enkripsi (generate: `php artisan key:generate`) |
| `APP_DEBUG` | `true` (dev) / **`false`** (production) |
| `APP_URL` | URL publik, contoh `https://domainanda.com` |
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Koneksi database |
| `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION` | Default `database` (pastikan tabel migrasi tersedia) |
| `PAYMENT_BANK`, `PAYMENT_ACCOUNT_NUMBER`, `PAYMENT_ACCOUNT_NAME`, `PAYMENT_WHATSAPP` | Info transfer manual |
| `MIDTRANS_*` | Integrasi Midtrans (server/client key, mode production, metode aktif) |
| `FONNTE_API_TOKEN`, `FONNTE_API_URL` | Notifikasi WhatsApp (Fonnte) |
| `TELEGRAM_BOT_TOKEN`, `TELEGRAM_CHAT_ID`, `TELEGRAM_WEBHOOK_SECRET` | Notifikasi/bot Telegram |
| `GEMINI_API_KEY`, `GEMINI_MODEL`, `GEMINI_TTS_*` | Chatbot AI & TTS (Teduh Bot) |
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

Script ini menyiapkan paket siap upload (install dependency production, build aset, bersihkan cache, dan buat `.zip` berisi `vendor` + `public/build`):

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
1. **PHP Version**: pilih PHP **8.3+** (Menu: *Select PHP Version* / *MultiPHP Manager*).
2. **Aktifkan ekstensi**: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `mbstring`, `openssl`, `pdo_mysql` (atau driver DB lain), `tokenizer`, `xml`, `zip`.
3. **Buat Database**: *MySQL Database Wizard* → buat database, user, password, lalu beri **ALL PRIVILEGES**.
4. (Opsional) Pastikan fungsi PHP yang dibutuhkan tidak diblokir (`proc_open`, `proc_close`, dll. jika diperlukan oleh package tertentu).

#### A2. Upload & Extract
1. Buat paket deploy (`bash scripts/prepare-deploy.sh`) di komputer lokal.
2. Buka **File Manager** → masuk ke folder di luar `public_html` (disarankan), misal `/home/username/siresteduh`.
3. Upload file `.zip` hasil deploy lalu **Extract** di folder tersebut.
   - Alternatif: upload via FTP/SFTP seluruh isi project (termasuk `vendor/` dan `public/build/`).

#### A3. Atur Document Root ke folder `public` (PENTING)
Web root **harus** mengarah ke folder `public/` Laravel, bukan root project.

- **Jika domain utama**: di banyak cPanel tidak bisa ganti root `public_html` langsung. Solusi:
  1. Pindahkan/ekstrak project ke `/home/username/siresteduh`.
  2. Kosongkan/backup isi `public_html`, lalu salin isi `/home/username/siresteduh/public/*` ke `public_html/`.
  3. Edit `public_html/index.php` ubah path agar menunjuk ke project:
     ```php
     // ubah dua baris berikut menyesuaikan lokasi project:
     require __DIR__.'/../siresteduh/vendor/autoload.php';
     $app = require_once __DIR__.'/../siresteduh/bootstrap/app.php';
     ```
  4. Buat symlink `storage` dari `public_html/storage` ke `../siresteduh/storage/app/public` (lihat A6) atau sesuaikan penanganan file publik.
- **Jika addon/subdomain**: di *Domains* / *Addon Domains* / *Subdomains*, set **Document Root** ke `/home/username/siresteduh/public` (cara paling bersih & disarankan).

#### A4. Konfigurasi `.env`
1. Di folder project (`/home/username/siresteduh`), salin `.env.example` menjadi `.env`.
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
Masuk ke **Terminal** cPanel (atau SSH jika tersedia), lalu `cd` ke folder project:
```bash
cd ~/siresteduh

php artisan key:generate --force   # jika APP_KEY masih kosong
php artisan migrate --force        # buat tabel di database produksi
php artisan storage:link           # symlink public/storage -> storage/app/public
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
Jika hosting tidak menyediakan akses terminal/SSH: jalankan migrasi & setup dari lokal yang terhubung ke DB hosting (sementara whitelist IP), atau gunakan fitur *phpMyAdmin* untuk import skema (tidak disarankan) — **akses terminal sangat dianjurkan**.

#### A6. Permission Folder
Pastikan writable oleh web server:
```bash
chmod -R 775 storage bootstrap/cache
```
Di cPanel/File Manager: set permission folder `storage/` dan `bootstrap/cache/` ke `775` (rekursif).

#### A7. SSL (HTTPS)
- Aktifkan SSL via *AutoSSL / Let's Encrypt* di cPanel.
- Pastikan `APP_URL` memakai `https://`.
- Jika di belakang proxy dan terjadi mixed/redirect issue, pastikan proxy headers dipercaya (Laravel umumnya sudah menangani; cek konfigurasi hosting).

---

### Opsi B — VPS / Cloud Server (Ubuntu/Debian + Nginx)

#### B1. Install Dependensi Server
```bash
sudo apt update
sudo apt install -y nginx mysql-server unzip git curl \
  php8.3 php8.3-fpm php8.3-cli php8.3-mbstring php8.3-xml php8.3-curl \
  php8.3-zip php8.3-bcmath php8.3-mysql php8.3-sqlite3
```
> Sesuaikan versi PHP (8.3+). Install Composer: https://getcomposer.org/download/ dan Node.js (jika build di server).

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
# edit .env (APP_ENV, APP_DEBUG, APP_URL, DB_*, integrasi, dll)
nano .env

composer install --no-dev --optimize-autoloader
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link

# build aset (butuh Node.js & npm di server)
npm ci && npm run build

# permission
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# cache optimasi
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
> Alternatif: build & `composer install` di lokal/CI lalu upload hasilnya (termasuk `vendor/` & `public/build/`) ke server.

#### B4. Konfigurasi Nginx
Buat file `/etc/nginx/sites-available/siresteduh`:
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
Pastikan `APP_URL=https://domainanda.com`.

---

### Opsi C — VPS dengan Apache

1. Set **DocumentRoot** ke `/var/www/siresteduh/public` dan aktifkan `mod_rewrite`:
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

       ErrorLog ${APACHE_LOG_DIR}/siresteduh_error.log
       CustomLog ${APACHE_LOG_DIR}/siresteduh_access.log combined
   </VirtualHost>
   ```
3. Laravel sudah menyertakan `public/.htaccess`. Reload: `sudo a2ensite siresteduh && sudo systemctl reload apache2`.
4. Gunakan Certbot untuk HTTPS: `sudo certbot --apache -d domainanda.com`.

---

### 3. Scheduler (Cron) & Queue Worker (Wajib untuk Fitur Tertentu)

Aplikasi memiliki tugas terjadwal (lihat `bootstrap/app.php`), antara lain:
- `reservations:expire-pending` (setiap menit) — melepas stok reservasi pending yang kedaluwarsa.
- `activity:prune`, `logs:prune` (harian) — pembersihan log/activity.
- `sanctum:prune-expired`, `cache:prune-expired`, `cache:clear` (harian/berkala).

#### Cron Scheduler
**VPS (crontab user web, mis. `www-data` atau user deploy):**
```bash
* * * * * cd /var/www/siresteduh && php artisan schedule:run >> /dev/null 2>&1
```

**cPanel:** *Cron Jobs* → tambahkan perintah (sesuaikan path PHP & project):
```bash
* * * * * /usr/local/bin/php /home/username/siresteduh/artisan schedule:run >> /dev/null 2>&1
```

#### Queue Worker
Default `QUEUE_CONNECTION=database`. Jika ada job yang diantrikan (notifikasi, dll.), jalankan worker:

**VPS (systemd, contoh `/etc/systemd/system/siresteduh-queue.service`):**
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

**Shared hosting:** jika tidak bisa proses long-running, gunakan cron untuk memproses antrian secara berkala (kurang ideal) atau set `QUEUE_CONNECTION=sync` (job berjalan sinkron, bisa memperlambat request).

---

### 4. Konfigurasi Webhook & URL Publik

Setelah domain aktif & HTTPS terpasang, daftarkan endpoint berikut ke layanan terkait:

| Layanan | Endpoint | Catatan |
|---|---|---|
| Midtrans (notification) | `POST https://domainanda.com/api/payments/midtrans/notification` | Set di dashboard Midtrans; isi `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, `MIDTRANS_IS_PRODUCTION` |
| Telegram (webhook bot) | `POST https://domainanda.com/api/telegram/webhook` | Set `TELEGRAM_BOT_TOKEN`, `TELEGRAM_CHAT_ID`, `TELEGRAM_WEBHOOK_SECRET` |

Pastikan `APP_URL` sesuai domain produksi agar URL yang dihasilkan aplikasi benar.

---

### 5. Update / Deploy Ulang

**Shared hosting (paket zip):**
1. Build paket baru: `bash scripts/prepare-deploy.sh`
2. Aktifkan maintenance (opsional): `php artisan down --secret="token-rahasia"`
3. Backup `.env` dan folder `storage/` (terutama `storage/app/public` berisi upload).
4. Upload & extract paket baru (timpa file kode; **jangan timpa `.env` & data upload**).
5. Jalankan:
   ```bash
   php artisan migrate --force
   php artisan storage:link
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```
6. `php artisan up`

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
php artisan queue:restart  # jika memakai queue worker
```

---

### 6. Troubleshooting Umum

| Gejala | Penyebab umum | Solusi |
|---|---|---|
| 500 Internal Server Error | Permission, `.env`/`APP_KEY`, ekstensi PHP kurang, cache rusak | Cek `storage/logs/laravel.log`; pastikan `storage` & `bootstrap/cache` writable; jalankan `php artisan key:generate --force` & `php artisan optimize:clear`; pastikan ekstensi PHP aktif |
| Halaman blank / aset (CSS/JS) tidak termuat | `public/build` belum ada, `APP_URL` salah, document root bukan ke `public/` | Jalankan `npm run build`; set `APP_URL` dengan benar; arahkan web root ke `public/` |
| 404 pada semua route kecuali `/` | Rewrite tidak aktif | Nginx: pastikan `try_files ... /index.php?$query_string`; Apache: aktifkan `mod_rewrite` & `AllowOverride All` |
| Error "storage link" / gambar/upload tidak muncul | Symlink belum dibuat | Jalankan `php artisan storage:link`; pastikan path & permission benar |
| Upload bukti pembayaran gagal (413/limit) | Batas upload server kecil | Naikkan `upload_max_filesize` & `post_max_size` (php.ini) dan `client_max_body_size` (Nginx) |
| Reservasi pending tidak pernah kedaluwarsa, log menumpuk | Scheduler tidak berjalan | Pastikan cron `schedule:run` aktif tiap menit |
| Notifikasi/job tidak terkirim | Queue worker tidak jalan / koneksi queue salah | Jalankan `queue:work`, cek `QUEUE_CONNECTION`, atau gunakan `sync` bila perlu |
| Session/login tidak stabil | `APP_URL`/domain/cookie mismatch, tabel session belum ada | Samakan domain `APP_URL`, pastikan migrasi tabel `sessions` sudah jalan (`SESSION_DRIVER=database`) |

**Perintah diagnostik berguna:**
```bash
php artisan about
php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear
tail -n 200 storage/logs/laravel.log
```

---

## 📁 Gambaran Struktur Proyek

```text
app/
  Http/Controllers/        # Web & API (Admin, POS, Check-in, Public, Telegram, Midtrans)
  Models/                  # Eloquent models (Reservation, Payment, Unit, Product, dll.)
  Services/                # Business logic (Availability, Midtrans, Telegram, Chatbot, dll.)
resources/views/           # Blade views (public, admin, pos, checkin, layouts, components)
routes/
  web.php                  # Rute web
  api.php                  # Rute API & webhook
database/migrations/       # Skema database
scripts/                   # Script bantu (mis. prepare-deploy.sh)
deploy/                    # Artefak paket deploy (lokal/arsip)
```

> **Catatan:** `Dockerfile` dan `compose*.yaml` di root saat ini merupakan placeholder (bukan konfigurasi Laravel) — gunakan panduan deploy di atas atau sesuaikan sendiri untuk containerisasi Laravel.

---

## 🔒 Catatan Keamanan

- Jangan commit `.env`, kredensial API, token, cookie/session dump, atau data pribadi.
- Selalu `APP_DEBUG=false` di produksi.
- Batasi akses ke panel admin & gunakan HTTPS.
- Lindungi endpoint webhook (Midtrans/Telegram) dengan secret/token yang sesuai konfigurasi.
- Backup database & folder `storage/app/public` secara berkala.

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
