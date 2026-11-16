# UAT (User Acceptance Testing) — SIRESTEDUH

Dokumen **User Acceptance Testing (UAT)** untuk sistem **SIRESTEDUH** (Sistem Reservasi & Manajemen Wisata — BUMDes Wisata Teduh). Dokumen ini berisi skenario uji, pertanyaan uji, jawaban/hasil yang diharapkan, data uji, serta kolom pencatatan hasil aktual untuk setiap fitur utama.

---

## 1. Informasi Dokumen

| Item | Keterangan |
|---|---|
| Nama Sistem | SIRESTEDUH |
| Deskripsi | Aplikasi reservasi glamping/tiket, pembayaran, verifikasi & e-ticket QR, POS, check-in, admin & pelaporan |
| Versi Dokumen | 1.0 |
| Tanggal Dokumen | 2026-11-16 |
| Jenis Pengujian | User Acceptance Testing (UAT) — Black Box (berbasis fungsi & alur pengguna) |
| Metode | Pengujian manual melalui UI (browser) dan/atau API sesuai skenario; verifikasi perilaku, data, dan akses |
| Referensi | `README.md`, `docs/KAMUS_DATA.md`, `docs/DFD.md`, `docs/FLOWCHART.md`, `docs/ERD.md`, `docs/BLACKBOX_TESTING.md` |

### 1.1 Tujuan
- Memastikan seluruh fitur utama SIRESTEDUH berjalan sesuai kebutuhan bisnis dan harapan pengguna.
- Memvalidasi alur end-to-end: reservasi → pembayaran → verifikasi → e-ticket → check-in → POS/laporan.
- Memvalidasi kontrol akses (RBAC), keamanan dasar, integritas data, dan penanganan error/kasus batas.

### 1.2 Ruang Lingkup (In-Scope)

| Modul | Cakupan |
|---|---|
| Autentikasi & Akses | Login/logout, redirect per peran, proteksi halaman, throttling login, blokir non-admin saat maintenance |
| Tamu/Publik | Beranda, daftar unit, cek ketersediaan, checkout/reservasi, validasi input, pembayaran manual & gateway, status pesanan, e-ticket |
| Admin | Dashboard & analitik, notifikasi, pencarian, manajemen reservasi, verifikasi/tolak pembayaran, master data (unit/produk/paket/gambar/user), kirim ulang e-ticket, laporan, log & activity, maintenance, akses bukti privat |
| POS & Kasir Makanan | Daftar item sesuai peran, transaksi/checkout POS, validasi stok/metode bayar, riwayat & ringkasan |
| Check-in | Validasi scan QR/hash (valid, sudah dipakai, tidak valid, konflik), riwayat/statistik |
| Chatbot | Chat Teduh Bot (dan TTS jika dikonfigurasi) |
| API & Integrasi | Autentikasi token (Sanctum), endpoint publik inti, webhook Midtrans/Telegram (uji terkontrol) |
| Keamanan & Non-fungsional | RBAC, rate limiting, audit/log, redaksi data sensitif, penyimpanan bukti privat, scheduler/otomasi (observasi) |

### 1.3 Out-of-Scope (di luar UAT fungsional ini)
- Pengujian penetrasi mendalam (pentest) dan audit keamanan formal pihak ketiga.
- Uji beban/stress skala besar (kecuali verifikasi skenario concurrency dasar yang terdokumentasi).
- Ketergantungan layanan eksternal riil (Midtrans production, Fonnte, Telegram, Gemini) bila kredensial tidak disediakan — dapat diuji dengan sandbox/mock/konfigurasi uji.

### 1.4 Kriteria Masuk (Entry Criteria)
- Aplikasi ter-deploy/berjalan di lingkungan uji dengan `APP_URL` benar.
- Database telah dimigrasi (`php artisan migrate --force`) dan data awal/seed tersedia.
- Akun uji per peran telah dibuat dan kredensial dibagikan secara aman.
- Konfigurasi integrasi uji (sandbox) tersedia jika skenario integrasi diuji.
- `storage/` & `bootstrap/cache/` writable; `php artisan storage:link` sudah dijalankan (jika diperlukan).

### 1.5 Kriteria Keluar (Exit Criteria)
- Seluruh skenario **kritis (Critical/High)** berstatus **PASS**.
- Tidak ada defect kritis terbuka; defect medium/low memiliki rencana tindak lanjut yang disepakati.
- Bukti uji (screenshot/log/catatan) untuk skenario utama telah dilampirkan.
- Berita Acara UAT ditandatangani oleh perwakilan bisnis/pemilik proses dan tim pengembang.

### 1.6 Definisi Status & Severity

| Status | Arti |
|---|---|
| PASS | Hasil aktual sesuai hasil yang diharapkan |
| FAIL | Hasil aktual tidak sesuai hasil yang diharapkan |
| BLOCKED | Tidak dapat diuji karena kendala/prasyarat/defect lain |
| N/A | Tidak relevan pada lingkungan/konfigurasi uji saat ini |

| Severity | Dampak |
|---|---|
| Critical | Fitur inti tidak dapat digunakan / data salah/konsistensi rusak / pelanggaran keamanan serius |
| High | Fitur utama terganggu signifikan, tidak ada workaround memadai |
| Medium | Gangguan pada fungsi non-inti atau ada workaround |
| Low | Minor/UI/kosmetik/peningkatan |

---

## 2. Lingkungan & Data Uji

### 2.1 Lingkungan Uji

| Item | Nilai (isi saat pelaksanaan) |
|---|---|
| URL Aplikasi | `https://<domain-uji>` / `http://localhost` |
| Versi/Commit | `<isi>` |
| Browser/Perangkat | Chrome/Edge/Firefox (desktop & mobile) |
| Database | MySQL/SQLite `<isi>` |
| Tanggal Pelaksanaan | `<isi>` |
| Penguji | `<isi>` |

### 2.2 Akun Uji (Contoh/Dummy — ganti dengan akun uji yang valid)

> Gunakan akun khusus lingkungan uji. **Jangan gunakan kredensial produksi** dalam dokumen ini.

| Peran | Email (contoh) | Password (contoh) | Akses Utama |
|---|---|---|---|
| Admin | `admin@siresteduh.test` | `Password!123` | `/admin`, master data, verifikasi, laporan, log, maintenance |
| Kasir | `kasir@siresteduh.test` | `Password!123` | `/pos`, `/riwayat` |
| Kasir Makanan | `kasirmakanan@siresteduh.test` | `Password!123` | `/kasir-makanan`, `/riwayat-makanan` |
| Petugas Check-in | `checkin@siresteduh.test` | `Password!123` | `/checkin`, `/checkin/tiket`, `/riwayat-checkin` |

### 2.3 Data Master Uji (Contoh)

| Data | Contoh Nilai |
|---|---|
| Unit glamping | `Tenda Mawar` (type: glamping, stock: 6, price sesuai data, capacity: 2) |
| Unit aula | `Aula Teduh` (type: aula, stock/capacity sesuai data) |
| Produk addon | `Paket BBQ` (type: addon, price: 50000, stock: 20) |
| Produk POS (umum) | `Kopi` (type: pos, category: minuman, price: 10000, stock: 50) |
| Produk POS tiket masuk | `Tiket Masuk` (type: pos, category: `tiket_masuk`, price: 15000, stock: 100) |
| Produk F&B kasir makanan | `Nasi Goreng` (type: pos, category: makanan, `for_kasir_makanan`: true, stock: 30) |
| Paket glamping | Kode/durasi/harga aktif (mis. paket 3 jam / 6 jam) |
| Paket aula | Kode/durasi/harga aktif |

### 2.4 Data Transaksi Uji (Contoh)

| Data | Contoh Nilai |
|---|---|
| Tamu | Nama: `Budi UAT`, WA: `081234567890` (dinormalisasi sistem), Email: `budi.uat@example.com` |
| Tanggal check-in | `H+1` dari tanggal pelaksanaan (format `YYYY-MM-DD`) |
| Tanggal check-out | `H+2` (untuk reservasi menginap non-paket) |
| Nominal transfer manual | Sesuai `total_price` pada order (mis. `Rp 350.000`) |
| File bukti valid | `bukti-transfer.jpg` (image, < 2 MB) |
| File bukti invalid | `dokumen.pdf` atau `gambar-3mb.jpg` |

---

## 3. Matriks Pengujian UAT (Pertanyaan & Jawaban per Fitur)

> **Cara membaca:** kolom **Pertanyaan Uji** adalah hal yang diverifikasi; kolom **Jawaban/Hasil yang Diharapkan** adalah perilaku benar sistem. Isi **Hasil Aktual** dan **Status** saat pelaksanaan.

### 3.1 Autentikasi & Kontrol Akses

| No | ID | Fitur/Skenario | Severity | Prasyarat | Data Uji / Langkah | Pertanyaan Uji | Jawaban / Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|---|---|---|
| 1 | UAT-AUTH-01 | Login admin valid | Critical | Akun admin aktif | 1) Buka `/login` 2) Isi email & password admin 3) Submit | Apakah admin dapat login dan diarahkan ke area admin? | Login berhasil, sesi terbuat, redirect ke `/admin` (atau intended admin) |  |  |
| 2 | UAT-AUTH-02 | Login kasir valid | Critical | Akun kasir aktif | Login dengan akun kasir | Apakah kasir diarahkan ke modul POS? | Login berhasil, redirect ke `/pos` |  |  |
| 3 | UAT-AUTH-03 | Login kasir makanan valid | Critical | Akun kasir_makanan aktif | Login dengan akun kasir makanan | Apakah kasir makanan diarahkan ke modul F&B? | Login berhasil, redirect ke `/kasir-makanan` |  |  |
| 4 | UAT-AUTH-04 | Login petugas check-in valid | Critical | Akun checkin aktif | Login dengan akun checkin | Apakah petugas diarahkan ke modul check-in? | Login berhasil, redirect ke `/checkin` |  |  |
| 5 | UAT-AUTH-05 | Login gagal (kredensial salah) | High | - | Isi email benar, password salah | Apakah sistem menolak login dengan pesan yang tepat? | Login ditolak, tetap di halaman login, pesan error “Email atau password salah.” (tanpa membocorkan detail sensitif) |  |  |
| 6 | UAT-AUTH-06 | Validasi form login | Medium | - | Kosongkan email/password atau format email invalid | Apakah validasi input login berjalan? | Form menampilkan error validasi; tidak terjadi login |  |  |
| 7 | UAT-AUTH-07 | Proteksi halaman admin tanpa login | Critical | Belum login | Akses langsung `/admin` | Apakah pengguna anonim dicegah masuk area admin? | Dialihkan ke `/login` (atau 401 untuk API JSON) |  |  |
| 8 | UAT-AUTH-08 | RBAC — kasir akses area admin | Critical | Login sebagai kasir | Akses `/admin` atau `/admin/reports` | Apakah kasir ditolak mengakses area admin? | Akses ditolak (403/redirect dengan pesan tidak berhak), tidak menampilkan data admin |  |  |
| 9 | UAT-AUTH-09 | RBAC — checkin akses POS | High | Login sebagai checkin | Akses `/pos` | Apakah petugas check-in dicegah mengakses POS? | Akses ditolak sesuai kontrol peran |  |  |
| 10 | UAT-AUTH-10 | Logout | High | Sudah login | Klik/akses logout | Apakah sesi berakhir dan pengguna keluar? | Sesi di-invalidate & token diregenerasi, redirect ke beranda `/`, halaman terproteksi tidak bisa diakses tanpa login ulang |  |  |
| 11 | UAT-AUTH-11 | Throttling login (anti brute-force) | High | - | Lakukan percobaan login gagal berulang (≥ batas/menit, kombinasi email+IP) | Apakah sistem membatasi percobaan login berlebih? | Percobaan kelebihan batas ditolak sementara (429/pesan “Terlalu banyak percobaan login…”), percobaan valid kembali setelah jeda |  |  |
| 12 | UAT-AUTH-12 | Login non-admin saat maintenance aktif | Critical | Maintenance ON, akun kasir aktif | Login sebagai kasir saat maintenance | Apakah non-admin diblokir masuk saat maintenance? | Login ditolak dengan pesan maintenance; sesi tidak aktif |  |  |
| 13 | UAT-AUTH-13 | Login admin saat maintenance aktif | Critical | Maintenance ON, akun admin aktif | Login sebagai admin | Apakah admin tetap dapat masuk saat maintenance? | Login berhasil dan admin dapat mengakses sistem untuk perbaikan |  |  |

### 3.2 Tamu — Beranda, Unit & Ketersediaan

| No | ID | Fitur/Skenario | Severity | Prasyarat | Data Uji / Langkah | Pertanyaan Uji | Jawaban / Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|---|---|---|
| 14 | UAT-PUB-01 | Akses beranda publik | High | - | Buka `/` | Apakah beranda tampil dengan informasi & navigasi utama? | Halaman beranda tampil normal (hero, daftar unit/CTA), tanpa error |  |  |
| 15 | UAT-PUB-02 | Muat daftar unit/ketersediaan | High | Ada data unit aktif | Buka beranda/checkout, amati daftar unit | Apakah sistem menampilkan unit dan ketersediaan dari data aktual? | Unit tampil (nama/tipe/harga/kapasitas sesuai), data dimuat via API/halaman tanpa error |  |  |
| 16 | UAT-PUB-03 | Cek ketersediaan berdasarkan tanggal | High | Unit dengan stok & reservasi uji | Pilih tanggal check-in/out atau paket/jam | Apakah ketersediaan berubah sesuai reservasi yang memblokir? | Slot/unit tersedia/tidak tersedia sesuai perhitungan ketersediaan & jadwal |  |  |
| 17 | UAT-PUB-04 | Halaman checkout dapat diakses | High | - | Buka `/checkout` | Apakah halaman reservasi/checkout terbuka? | Halaman checkout tampil dan siap diisi |  |  |

### 3.3 Tamu — Reservasi / Checkout

| No | ID | Fitur/Skenario | Severity | Prasyarat | Data Uji / Langkah | Pertanyaan Uji | Jawaban / Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|---|---|---|
| 18 | UAT-RSV-01 | Reservasi unit glamping valid (paket) | Critical | Unit glamping & paket aktif, slot tersedia | Isi nama, WA valid, pilih unit glamping + paket, tanggal valid, submit | Apakah reservasi berhasil dibuat dengan order code? | Reservasi tersimpan (status `pending`), `order_code` (SIRES-*) terbentuk, total harga dihitung sistem, diarahkan ke pembayaran |  |  |
| 19 | UAT-RSV-02 | Reservasi unit menginap (non-paket) dengan check-out | Critical | Unit non-paket tersedia | Isi check-in, check-out, waktu sesuai; submit | Apakah sistem mewajibkan check-out dan menyimpan jadwal benar? | Reservasi berhasil; `check_out_date` wajib & tersimpan; durasi/harga sesuai aturan |  |  |
| 20 | UAT-RSV-03 | Reservasi dengan addon/produk tambahan | High | Produk addon stok cukup | Tambahkan addon + qty, submit | Apakah addon tersimpan dan stok dipotong sesuai? | Addon tercatat pada reservasi, total harga mencakup addon, stok produk addon berkurang |  |  |
| 21 | UAT-RSV-04 | Validasi WA wajib & format | High | - | Kosongkan WA / isi `123` / isi format valid `0812...` | Apakah nomor WhatsApp divalidasi dan dinormalisasi? | Input tidak valid ditolak dengan pesan; input valid diterima (dinormalisasi, mis. 62...) |  |  |
| 22 | UAT-RSV-05 | Validasi tanggal lampau | High | - | Set `check_in_date` < hari ini | Apakah sistem menolak tanggal kunjungan lampau? | Validasi gagal (422/pesan error), reservasi tidak dibuat |  |  |
| 23 | UAT-RSV-06 | Validasi check-out sebelum/sama dengan check-in | High | Reservasi unit non-paket | Set check-out <= check-in | Apakah sistem menolak rentang waktu tidak valid? | Ditolak dengan pesan waktu check-out harus setelah check-in |  |  |
| 24 | UAT-RSV-07 | Duplikasi pesanan serupa (anti-spam) | Medium | Reservasi pending baru dibuat dari WA/unit/tanggal sama | Ulangi submit pesanan identik dalam waktu singkat | Apakah sistem mencegah duplikasi pesanan pending beruntun? | Permintaan ditolak (429/pesan tunggu/cek pembayaran), tidak membuat order ganda |  |  |
| 25 | UAT-RSV-08 | Slot/jadwal tidak tersedia (bentrok) | Critical | Slot unit/paket penuh pada jadwal sama | Pesan jadwal yang sudah penuh | Apakah sistem menolak double booking? | Reservasi ditolak dengan pesan unit/paket penuh/sedang terisi |  |  |
| 26 | UAT-RSV-09 | Stok addon tidak mencukupi | High | Addon stok lebih kecil dari qty diminta | Pesan addon melebihi stok | Apakah sistem mencegah pemesanan melebihi stok? | Ditolak dengan pesan stok tidak mencukupi; tidak ada perubahan stok/reservasi parsial |  |  |
| 27 | UAT-RSV-10 | Minimal satu item (unit/addon) | Medium | - | Submit tanpa memilih unit dan tanpa addon | Apakah pesanan kosong ditolak? | Ditolak: wajib pilih minimal satu unit atau produk tambahan |  |  |
| 28 | UAT-RSV-11 | Paket glamping/aula wajib dipilih saat unit bertipe terkait | High | Unit tipe glamping/aula | Pilih unit glamping/aula tanpa memilih paket | Apakah sistem mewajibkan paket? | Ditolak dengan pesan pilih paket yang tersedia |  |  |

### 3.4 Tamu — Pembayaran Transfer Manual

| No | ID | Fitur/Skenario | Severity | Prasyarat | Data Uji / Langkah | Pertanyaan Uji | Jawaban / Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|---|---|---|
| 29 | UAT-PAY-01 | Buka halaman pembayaran order valid | High | Ada reservasi pending | Akses `/pembayaran/{order_code}` | Apakah rincian tagihan & instruksi bayar tampil? | Data reservasi, total, status, info rekening/manual & opsi gateway (jika aktif) tampil benar |  |  |
| 30 | UAT-PAY-02 | Upload bukti transfer valid (image) | Critical | Reservasi `pending`, file jpg/png < 2MB | Unggah `bukti-transfer.jpg` | Apakah bukti diterima dan status berubah menunggu verifikasi? | Upload sukses (201/pesan sukses), payment `pending` (manual), reservation `waiting_verification`, file tersimpan privat, notifikasi verifikasi terpicu (jika aktif) |  |  |
| 31 | UAT-PAY-03 | Upload bukti format tidak didukung (PDF) | High | Reservasi pending | Unggah `dokumen.pdf` | Apakah sistem menolak format non-gambar? | Upload ditolak (422/validasi), pesan format tidak valid, status tidak berubah |  |  |
| 32 | UAT-PAY-04 | Upload bukti melebihi ukuran ( > 2MB ) | High | Reservasi pending | Unggah gambar 3MB | Apakah sistem menolak file terlalu besar? | Upload ditolak (422/validasi), pesan ukuran melebihi batas |  |  |
| 33 | UAT-PAY-05 | Upload untuk order tidak ditemukan | Medium | - | Kirim order_code tidak ada | Apakah sistem memberi respon tidak ditemukan? | Respon 404/pesan “Order tidak ditemukan”, tanpa perubahan data |  |  |
| 34 | UAT-PAY-06 | Upload pada status tidak diizinkan | High | Reservasi sudah `paid/cancelled` | Coba upload bukti | Apakah upload ditolak pada status akhir/tidak valid? | Ditolak (409/pesan tidak dapat menerima bukti pada status saat ini) |  |  |
| 35 | UAT-PAY-07 | Bukti pembayaran tidak dapat diakses publik | Critical | Bukti sudah diunggah | Coba akses path file bukti secara langsung (URL publik/storage) tanpa otorisasi admin | Apakah file bukti terlindungi dari akses publik? | Akses langsung ditolak/tidak tersedia; bukti hanya dapat diakses via route admin terproteksi |  |  |

### 3.5 Tamu — Pembayaran Gateway (Midtrans)

| No | ID | Fitur/Skenario | Severity | Prasyarat | Data Uji / Langkah | Pertanyaan Uji | Jawaban / Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|---|---|---|
| 36 | UAT-MDT-01 | Info gateway tersedia (jika dikonfigurasi) | Medium | `MIDTRANS_*` valid & enabled | Buka halaman pembayaran | Apakah opsi pembayaran otomatis ditampilkan saat aktif? | Info gateway (client key/snap, metode) tersedia; jika nonaktif, fallback ke manual |  |  |
| 37 | UAT-MDT-02 | Buat transaksi Snap untuk order valid | Critical | Gateway aktif, reservasi pending | Klik bayar otomatis/gateway | Apakah token/redirect Snap berhasil dibuat? | Respon sukses berisi `snap_token/redirect_url`, payment gateway `pending` tersimpan, tidak mengubah data sensitif di client |  |  |
| 38 | UAT-MDT-03 | Buat transaksi pada status tidak valid | High | Reservasi paid/cancelled | Coba create Snap | Apakah pembuatan transaksi ditolak? | Ditolak (409/pesan status tidak dapat dibayar) |  |  |
| 39 | UAT-MDT-04 | Notifikasi pembayaran valid (settlement/capture accepted) | Critical | Payment gateway pending, payload notifikasi sandbox valid & signature benar | Kirim notifikasi ke endpoint Midtrans | Apakah status menjadi lunas, tiket terbit, dan idempotent? | Payment `verified/paid`, reservation `paid`, e-ticket dipastikan & dikirim; notifikasi duplikat tidak memproses ganda (idempotent) |  |  |
| 40 | UAT-MDT-05 | Notifikasi dengan signature tidak valid | Critical | - | Kirim payload signature salah | Apakah notifikasi palsu ditolak? | Tidak diproses (diabaikan/ditolak), status pembayaran/reservasi tidak berubah |  |  |
| 41 | UAT-MDT-06 | Notifikasi status expire/failure | High | Payment gateway pending | Kirim notifikasi expire/deny/cancel/failure | Apakah status pembayaran diperbarui sesuai? | Status payment menjadi `expired`/`failed` sesuai kasus; reservasi mengikuti aturan (tidak menjadi paid) |  |  |

> **Catatan UAT-MDT:** uji aman dilakukan di **sandbox** dengan kredensial uji. Verifikasi signature & idempotensi adalah poin kritikal.

### 3.6 Tamu — Status Pesanan & E-Ticket

| No | ID | Fitur/Skenario | Severity | Prasyarat | Data Uji / Langkah | Pertanyaan Uji | Jawaban / Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|---|---|---|
| 42 | UAT-STS-01 | Cek status dengan order code valid | High | Reservasi ada (pending/waiting/paid) | Buka `/status`, masukkan order_code | Apakah status reservasi & pembayaran ditampilkan? | Data reservasi, visitor, unit, payment, dan info tiket (jika ada) tampil sesuai |  |  |
| 43 | UAT-STS-02 | Cek status order code tidak ditemukan | Medium | - | Masukkan kode acak | Apakah sistem memberi respon tidak ditemukan? | Respon 404/pesan “Order code tidak ditemukan” |  |  |
| 44 | UAT-STS-03 | E-ticket tersedia untuk reservasi paid | Critical | Reservasi `paid` & tiket terbit | Cek status/detail order paid | Apakah informasi e-ticket/QR tersedia? | Tiket (`unique_hash_sha256`, status used) tersedia; reservasi paid tanpa tiket dibuat/ditampilkan sesuai jaminan sistem |  |  |
| 45 | UAT-STS-04 | Reservasi belum lunas tidak memiliki tiket aktif | High | Reservasi `pending/waiting_verification` | Cek status | Apakah e-ticket tidak diterbitkan sebelum pembayaran terkonfirmasi? | Tiket belum tersedia/tidak aktif sesuai alur (kecuali ketentuan sistem yang berlaku) |  |  |

### 3.7 Admin — Dashboard, Notifikasi & Operasional Umum

| No | ID | Fitur/Skenario | Severity | Prasyarat | Data Uji / Langkah | Pertanyaan Uji | Jawaban / Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|---|---|---|
| 46 | UAT-ADM-01 | Akses dashboard admin | High | Login admin | Buka `/admin` | Apakah dashboard dan ringkasan analitik tampil? | Halaman dashboard tampil, data ringkasan/analitik termuat tanpa error |  |  |
| 47 | UAT-ADM-02 | Notifikasi/pending verifikasi tampil | Medium | Ada reservasi menunggu verifikasi | Buka dashboard/menu verifikasi | Apakah antrean yang perlu tindakan terlihat? | Jumlah/item pending/waiting tampil konsisten dengan data |  |  |
| 48 | UAT-ADM-03 | Pencarian global admin | Medium | Data reservasi/user/unit ada | Gunakan fitur pencarian (command palette/API) | Apakah pencarian mengembalikan hasil relevan? | Hasil sesuai kata kunci & entitas yang didukung |  |  |

### 3.8 Admin — Verifikasi & Pengelolaan Pembayaran/Reservasi

| No | ID | Fitur/Skenario | Severity | Prasyarat | Data Uji / Langkah | Pertanyaan Uji | Jawaban / Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|---|---|---|
| 49 | UAT-VER-01 | Lihat daftar pembayaran/reservasi menunggu verifikasi | High | Ada data `pending/waiting_verification` | Buka `/admin/payments` atau `/admin/verification` | Apakah antrean verifikasi tampil lengkap? | Daftar menampilkan order, tamu, nominal, status, dan akses bukti |  |  |
| 50 | UAT-VER-02 | Lihat bukti pembayaran (privat) | Critical | Payment memiliki `proof_image` | Klik lihat bukti | Apakah admin dapat melihat bukti dan akses tercatat? | Bukti dapat ditampilkan/diunduh oleh admin; akses tercatat dalam audit (jika dikonfigurasi/tersedia) |  |  |
| 51 | UAT-VER-03 | Verifikasi pembayaran valid | Critical | Reservasi waiting + bukti valid | Klik Verifikasi/Approve | Apakah pembayaran diterima, reservasi lunas, dan e-ticket diterbitkan? | Payment `verified` (timestamp), reservation `paid`, tiket dibuat jika belum ada, e-ticket dikirim; status keluar dari antrean pending |  |  |
| 52 | UAT-VER-04 | Tolak/batalkan pembayaran | High | Reservasi waiting/pending | Klik Tolak/Reject/Cancel | Apakah reservasi dibatalkan dan status pembayaran diperbarui? | Reservasi `cancelled` (sesuai aturan), payment ditandai `rejected` bila berlaku, stok addon dikembalikan jika relevan, antrean diperbarui |  |  |
| 53 | UAT-VER-05 | Idempotensi verifikasi ganda | Medium | Reservasi sudah paid | Coba verifikasi ulang | Apakah verifikasi ulang tidak merusak data? | Sistem menampilkan info sudah diverifikasi/tidak memproses ganda; tidak ada duplikasi tiket/transisi invalid |  |  |
| 54 | UAT-VER-06 | Kirim ulang e-ticket | High | Reservasi paid & memiliki kontak/tujuan kirim | Gunakan aksi kirim/resend e-ticket | Apakah e-ticket dapat dikirim ulang? | Aksi berhasil (respon sukses/notifikasi terkirim sesuai konfigurasi), tercatat/log sesuai implementasi |  |  |

### 3.9 Admin — Master Data

| No | ID | Fitur/Skenario | Severity | Prasyarat | Data Uji / Langkah | Pertanyaan Uji | Jawaban / Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|---|---|---|
| 55 | UAT-MST-01 | CRUD Unit | High | Login admin | Buat, ubah, lihat, hapus/nonaktifkan unit (uji sesuai aturan) | Apakah pengelolaan unit berfungsi dan tervalidasi? | Data tersimpan/terbarui/terhapus sesuai; validasi field berjalan; perubahan tercermin di ketersediaan |  |  |
| 56 | UAT-MST-02 | Kelola gambar unit/produk | Medium | Unit/produk ada | Unggah/hapus/urutkan gambar | Apakah manajemen gambar berfungsi? | Gambar tersimpan & tampil pada entitas terkait; penghapusan menghilangkan asosiasi/file sesuai perilaku sistem |  |  |
| 57 | UAT-MST-03 | CRUD Produk | High | Login admin | Kelola produk (addon/POS, kategori, stok, flag kasir makanan) | Apakah produk tersimpan dan memengaruhi modul terkait? | Data produk akurat; produk POS muncul di POS sesuai peran; addon muncul di reservasi |  |  |
| 58 | UAT-MST-04 | Kelola paket glamping | High | Login admin | Buat/ubah/aktifkan/nonaktifkan paket | Apakah paket tampil dan dipakai saat reservasi? | Paket aktif tersedia di checkout/POS (sesuai tipe), harga & durasi sesuai |  |  |
| 59 | UAT-MST-05 | Kelola paket aula | High | Login admin | Kelola paket aula | Apakah paket aula berfungsi? | Paket aula aktif tersedia dan perhitungan jadwal/harga sesuai |  |  |
| 60 | UAT-MST-06 | Kelola pengguna (user & role) | Critical | Login admin | Buat/ubah user, set peran | Apakah akun dan peran tersimpan dengan benar? | User dapat login sesuai peran & hak akses; role tidak dapat disalahgunakan oleh non-admin |  |  |
| 61 | UAT-MST-07 | Validasi & batasan hapus data berelasi | Medium | Data memiliki relasi (mis. unit ber-reservasi) | Coba hapus entitas yang direferensikan | Apakah integritas referensial terjaga? | Sistem mencegah penghapusan berbahaya atau menangani sesuai aturan (error terkontrol, tanpa data korup) |  |  |

### 3.10 Admin — Laporan, Log, Activity & Maintenance

| No | ID | Fitur/Skenario | Severity | Prasyarat | Data Uji / Langkah | Pertanyaan Uji | Jawaban / Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|---|---|---|
| 62 | UAT-RPT-01 | Lihat & filter laporan | High | Ada data transaksi/reservasi | Buka `/admin/reports`, gunakan filter/periode | Apakah laporan menampilkan data akurat? | Angka, daftar, dan agregasi sesuai data sumber (reservasi/pembayaran/POS) & filter |  |  |
| 63 | UAT-LOG-01 | Lihat log aplikasi | Medium | Login admin | Buka `/admin/logs`, buka isi log | Apakah log dapat ditampilkan & dibaca? | Daftar/isi log tampil (sesuai hak admin), tanpa error |  |  |
| 64 | UAT-LOG-02 | Download/retensi/clear log (sesuai fitur) | Medium | Login admin | Coba unduh/ubah retensi/bersihkan log | Apakah aksi pengelolaan log berjalan aman? | Aksi berhasil sesuai izin & implementasi; retensi tersimpan; tidak merusak operasional |  |  |
| 65 | UAT-ACT-01 | Lihat activity feed/audit | High | Ada aktivitas tercatat | Buka `/admin/activity` | Apakah aktivitas terekam dan dapat ditinjau? | Event penting tampil (login, perubahan, verifikasi, upload, check-in, dll.) dengan informasi relevan |  |  |
| 66 | UAT-ACT-02 | Redaksi data sensitif pada audit | Critical | Event yang membawa input sensitif | Picu aksi dengan field sensitif (mis. login) lalu cek activity/properti | Apakah data sensitif tidak tersimpan terbuka? | Nilai sensitif (password/token/secret/signature/cookie/session, dll.) teredaksi (`[REDACTED]`) |  |  |
| 67 | UAT-MTN-01 | Aktifkan/nonaktifkan maintenance | High | Login admin | Ubah status maintenance di `/admin/maintenance` | Apakah pengaturan maintenance tersimpan dan diterapkan? | Status & pesan tersimpan; perilaku akses berubah sesuai (lihat UAT-AUTH-12/13) |  |  |
| 68 | UAT-MTN-02 | Tampilan/respon maintenance untuk publik & API | High | Maintenance ON | Akses halaman publik & endpoint API sebagai non-admin | Apakah respons maintenance konsisten? | Web menampilkan halaman maintenance (503); API mengembalikan JSON 503 dengan pesan maintenance |  |  |

### 3.11 POS (Kasir) & Kasir Makanan

| No | ID | Fitur/Skenario | Severity | Prasyarat | Data Uji / Langkah | Pertanyaan Uji | Jawaban / Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|---|---|---|
| 69 | UAT-POS-01 | Akses POS kasir umum | High | Login kasir | Buka `/pos` | Apakah kasir dapat mengakses modul POS? | Halaman POS tampil, daftar produk/unit sesuai hak kasir termuat |  |  |
| 70 | UAT-POS-02 | Akses POS kasir makanan (F&B) | High | Login kasir_makanan | Buka `/kasir-makanan` | Apakah kasir makanan hanya melihat item yang diizinkan? | Hanya produk yang berflag/diizinkan untuk kasir makanan (`for_kasir_makanan`) yang tampil; unit/sewa tidak tersedia sesuai mode |  |  |
| 71 | UAT-POS-03 | Checkout POS valid (tunai/QRIS/transfer sesuai peran) | Critical | Item & stok cukup, metode bayar valid | Tambah item ke keranjang, pilih metode bayar, submit | Apakah transaksi tersimpan dan stok berkurang? | Transaksi sukses, total benar, detail item tersimpan, stok diperbarui, ringkasan/struk tampil |  |  |
| 72 | UAT-POS-04 | Checkout stok tidak mencukupi | Critical | Stok item < qty | Pesan melebihi stok | Apakah transaksi ditolak tanpa merusak stok? | Ditolak (error/422), tidak ada transaksi baru, stok tidak berubah |  |  |
| 73 | UAT-POS-05 | Validasi keranjang kosong & qty tidak valid | High | - | Submit tanpa item / qty 0 atau negatif | Apakah input transaksi divalidasi? | Ditolak dengan pesan validasi; transaksi tidak dibuat |  |  |
| 74 | UAT-POS-06 | Metode bayar terbatas (mis. check-in jika berlaku) | Medium | Login peran dengan batasan metode | Coba metode bayar tidak diizinkan | Apakah pembatasan metode bayar diterapkan? | Metode tidak diizinkan ditolak sesuai aturan peran |  |  |
| 75 | UAT-POS-07 | Riwayat & ringkasan transaksi | High | Ada transaksi POS oleh user | Buka `/riwayat` atau `/riwayat-makanan` | Apakah riwayat dan rekap (metode bayar, total, item terjual) tampil benar? | Data sesuai transaksi user/periode, total & agregasi akurat |  |  |
| 76 | UAT-POS-08 | Ketersediaan paket glamping/aula di POS (jika digunakan) | Medium | Paket aktif & unit tersedia | Cek ketersediaan paket via POS | Apakah informasi slot/jadwal paket akurat? | Slot tersedia/tidak tersedia dan waktu keluar sesuai durasi paket & data |  |  |

### 3.12 Petugas Check-in

| No | ID | Fitur/Skenario | Severity | Prasyarat | Data Uji / Langkah | Pertanyaan Uji | Jawaban / Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|---|---|---|
| 77 | UAT-CKI-01 | Akses modul check-in petugas | High | Login checkin | Buka `/checkin`, `/checkin/tiket`, `/riwayat-checkin` | Apakah petugas dapat mengakses halaman check-in & riwayat? | Halaman tampil sesuai hak akses; data termuat |  |  |
| 78 | UAT-CKI-02 | Scan/validasi tiket valid (belum dipakai) | Critical | Reservasi paid, tiket valid & belum digunakan | Scan/input hash QR benar | Apakah check-in berhasil dan tiket ditandai terpakai? | Respon sukses (valid), `is_used=true` & `used_at` terisi, detail tamu/unit/jadwal tampil, waktu aktual diperbarui sesuai aturan |  |  |
| 79 | UAT-CKI-03 | Scan tiket sudah dipakai | Critical | Tiket `is_used=true` | Scan hash yang sama | Apakah penggunaan ulang ditolak? | Respon 409/status `used` dengan pesan & info pemakaian; status tiket tidak berubah menjadi ganda |  |  |
| 80 | UAT-CKI-04 | Scan tiket tidak valid/palsu | Critical | - | Input hash 64-char tidak terdaftar | Apakah tiket palsu ditolak? | Respon 404/status invalid, pesan tiket tidak ditemukan/tidak valid |  |  |
| 81 | UAT-CKI-05 | Konflik jadwal/slot saat check-in | High | Kondisi slot bentrok sesuai aturan sistem | Scan tiket pada kondisi konflik | Apakah sistem mencegah check-in yang bentrok? | Respon konflik (409) dengan pesan jelas; tiket tidak ditandai terpakai secara tidak sah |  |  |
| 82 | UAT-CKI-06 | Statistik/riwayat check-in | Medium | Ada data check-in | Buka riwayat/statistik dengan filter tanggal | Apakah rekap check-in akurat? | Jumlah check-in harian/periode & daftar sesuai data `tickets.used_at` |  |  |

### 3.13 Chatbot (Teduh Bot)

| No | ID | Fitur/Skenario | Severity | Prasyarat | Data Uji / Langkah | Pertanyaan Uji | Jawaban / Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|---|---|---|
| 83 | UAT-BOT-01 | Kirim pertanyaan ke chatbot | Medium | Endpoint chat dapat diakses; Gemini terkonfigurasi (atau fallback sesuai implementasi) | Kirim pesan umum (mis. “harga glamping?”) | Apakah chatbot merespons permintaan pengguna? | Respon diterima (jawaban relevan atau pesan error terkontrol jika layanan AI tidak aktif), tanpa crash |  |  |
| 84 | UAT-BOT-02 | Pembatasan laju chat | Medium | - | Kirim permintaan beruntun melebihi batas | Apakah rate limit chat diterapkan? | Permintaan berlebih ditolak/ditunda (429) sesuai batas |  |  |
| 85 | UAT-BOT-03 | TTS/suara chatbot (opsional) | Low | Gemini TTS terkonfigurasi | Minta respons suara | Apakah fitur suara berfungsi bila aktif? | Audio/respons suara berhasil; jika nonaktif/kuota habis, error ditangani dengan pesan terkontrol |  |  |

### 3.14 API, Token & Webhook (Penerimaan Teknis)

| No | ID | Fitur/Skenario | Severity | Prasyarat | Data Uji / Langkah | Pertanyaan Uji | Jawaban / Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|---|---|---|
| 86 | UAT-API-01 | Login token (Sanctum) & akses profil | High | Akun valid, endpoint `/api/auth/login` | POST kredensial, gunakan Bearer token ke `/api/auth/me` | Apakah token diterbitkan dan mengautentikasi permintaan? | Login mengembalikan token; `/me` mengembalikan data user; token invalid ditolak |  |  |
| 87 | UAT-API-02 | Logout token | Medium | Token aktif | POST `/api/auth/logout` | Apakah token dicabut? | Logout sukses; token tidak dapat digunakan kembali |  |  |
| 88 | UAT-API-03 | Endpoint publik ketersediaan/produk/unit | High | Data master ada | GET endpoint publik (units/tickets/availability/products) | Apakah API publik mengembalikan data yang benar? | Respon 200 dengan struktur data sesuai, filter/parameter bekerja |  |  |
| 89 | UAT-API-04 | Rate limiting API publik & reservasi | High | - | Panggil endpoint berulang di atas batas | Apakah pembatasan laju API aktif? | Respon 429 setelah melewati ambang (per endpoint/limiter yang berlaku) |  |  |
| 90 | UAT-WHK-01 | Webhook Midtrans — signature & status | Critical | Endpoint notifikasi dapat diakses, payload uji | POST notifikasi (valid/invalid signature) | Apakah hanya notifikasi sah yang diproses? | Signature valid diproses sesuai status; signature invalid ditolak/diabaikan; tercatat di log |  |  |
| 91 | UAT-WHK-02 | Webhook Telegram — proteksi secret token | High | `TELEGRAM_WEBHOOK_SECRET` terkonfigurasi | POST callback dengan/tanpa header secret benar | Apakah webhook menolak request tidak sah? | Request tanpa/secret salah ditolak (403); request sah diproses sesuai aksi |  |  |

### 3.15 Keamanan, Privasi & Operasional (Observasi)

| No | ID | Fitur/Skenario | Severity | Prasyarat | Data Uji / Langkah | Pertanyaan Uji | Jawaban / Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|---|---|---|
| 92 | UAT-SEC-01 | RBAC menyeluruh lintas modul | Critical | Akun tiap peran | Coba akses silang URL/API antar peran | Apakah setiap peran hanya mengakses sumber daya yang diizinkan? | Semua akses tidak sah ditolak (401/403/redirect) secara konsisten |  |  |
| 93 | UAT-SEC-02 | Penyimpanan & akses bukti pembayaran privat | Critical | Ada bukti upload | Uji akses sebagai non-admin & akses langsung | Apakah bukti tidak bocor ke publik? | Non-admin/tamu tidak dapat mengakses bukti privat; hanya admin via mekanisme terotorisasi |  |  |
| 94 | UAT-SEC-03 | Audit trail event kritikal | High | Lakukan verifikasi, upload, check-in, login | Cek activity/log admin | Apakah aksi penting tercatat? | Event terekam dengan identitas/waktu/subjek/status yang memadai untuk penelusuran |  |  |
| 95 | UAT-SEC-04 | Penanganan error & tidak ada kebocoran debug | High | `APP_DEBUG=false` di lingkungan uji produksi-like | Picu error terkontrol (input invalid, akses terlarang) | Apakah error aman dan informatif secukupnya? | Tidak ada stack trace/detail sensitif ke pengguna akhir; respon error sesuai (4xx/5xx terkontrol) |  |  |
| 96 | UAT-OPS-01 | Scheduler/otomasi (observasi) | Medium | Cron/scheduler aktif di lingkungan uji | Amati reservasi pending melewati batas, retensi log/activity | Apakah tugas terjadwal berjalan? | Reservasi pending kedaluwarsa diproses; pruning/cleanup berjalan sesuai jadwal/konfigurasi (dapat diverifikasi via data/log) |  |  |
| 97 | UAT-OPS-02 | Ketersediaan aset & PWA dasar | Low | Build aset tersedia | Akses via mobile/desktop, cek manifest/SW | Apakah aset termuat dan PWA terdaftar? | CSS/JS termuat normal, `manifest.json` & service worker dapat diakses (sesuai dukungan browser) |  |  |

---

## 4. Ringkasan Hasil UAT

| Kategori | Total | PASS | FAIL | BLOCKED | N/A |
|---|---:|---:|---:|---:|---:|
| Autentikasi & Akses | 13 |  |  |  |  |
| Tamu — Unit & Ketersediaan | 4 |  |  |  |  |
| Tamu — Reservasi | 11 |  |  |  |  |
| Tamu — Pembayaran Manual | 7 |  |  |  |  |
| Tamu — Midtrans | 6 |  |  |  |  |
| Tamu — Status & E-Ticket | 4 |  |  |  |  |
| Admin — Dashboard/Operasional | 3 |  |  |  |  |
| Admin — Verifikasi/Reservasi | 6 |  |  |  |  |
| Admin — Master Data | 7 |  |  |  |  |
| Admin — Laporan/Log/Activity/Maintenance | 7 |  |  |  |  |
| POS & Kasir Makanan | 8 |  |  |  |  |
| Check-in | 6 |  |  |  |  |
| Chatbot | 3 |  |  |  |  |
| API, Token & Webhook | 6 |  |  |  |  |
| Keamanan & Operasional | 6 |  |  |  |  |
| **TOTAL** | **97** |  |  |  |  |

---

## 5. Daftar Defect / Temuan

| ID Defect | ID UAT | Deskripsi | Severity | Langkah Reproduksi | Hasil Aktual | Hasil Diharapkan | Bukti | Status | PIC |
|---|---|---|---|---|---|---|---|---|---|
| DEF-001 |  |  |  |  |  |  |  | Open |  |

---

## 6. Bukti Uji (Lampiran)

Lampirkan bukti untuk skenario kritikal (minimal): login per peran, reservasi berhasil, pembayaran (manual/gateway), verifikasi & e-ticket, check-in valid/used/invalid, transaksi POS, tolak akses RBAC, dan tampilan maintenance.

| ID UAT | Jenis Bukti | Nama File/Link | Keterangan |
|---|---|---|---|
| UAT-RSV-01 | Screenshot | `uat-rsv-01-order.png` | Order code & total |
| UAT-PAY-02 | Screenshot | `uat-pay-02-waiting.png` | Status waiting_verification |
| UAT-VER-03 | Screenshot | `uat-ver-03-paid.png` | Payment verified & e-ticket |
| UAT-CKI-02 | Screenshot | `uat-cki-02-valid.png` | Check-in sukses |

---

## 7. Persetujuan (Sign-off)

| Peran | Nama | Jabatan/Unit | Tanggal | Tanda Tangan | Keputusan (Setuju/Tidak) |
|---|---|---|---|---|---|
| Pemilik Proses / Bisnis |  |  |  |  |  |
| Perwakilan Pengguna (Admin) |  |  |  |  |  |
| Perwakilan Pengguna (Kasir/Check-in) |  |  |  |  |  |
| Tim Pengembang/QA |  |  |  |  |  |

**Catatan/Kesimpulan UAT:**

- Ringkasan: `<isi>`
- Rekomendasi rilis: `<Go / Go dengan catatan / No-Go>`
- Tindak lanjut: `<isi>`
