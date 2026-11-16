# Kamus Data — SIRESTEDUH

Dokumen ini menjelaskan struktur data utama aplikasi **SIRESTEDUH** (Sistem Reservasi & Manajemen Wisata BUMDes Wisata Teduh) berdasarkan skema database/migrasi dan model aplikasi saat ini.

> Catatan:
> - Tipe pada kolom “Tipe” mengacu pada tipe data di skema (SQLite/MySQL-compatible) dan perilaku umum di aplikasi.
> - Kolom bertanda **PK** = Primary Key, **FK** = Foreign Key, **UQ** = Unique.
> - Tabel bawaan framework infrastruktur (`cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `sessions`, `migrations`, `password_reset_tokens`, `personal_access_tokens`) dicantumkan ringkas karena terkait autentikasi/sesi/antrian, bukan entitas bisnis inti.

---

## 1. Entitas Bisnis Inti

### 1.1 `users`
Menyimpan akun pengguna internal sistem.

| Kolom | Tipe | Ket | Keterangan |
|---|---|---|---|
| id | integer (PK) | NN | ID pengguna |
| name | varchar | NN | Nama pengguna |
| email | varchar (UQ) | NN | Email login (unik) |
| email_verified_at | datetime | NULL | Waktu verifikasi email |
| password | varchar | NN | Hash password |
| role | varchar | NN | Peran akses: `admin`, `kasir`, `kasir_makanan`, `checkin` (dan sesuai data sistem) |
| remember_token | varchar | NULL | Token remember-me |
| created_at | datetime | NULL | Waktu dibuat |
| updated_at | datetime | NULL | Waktu diubah |

**Relasi:** memiliki banyak `pos_transactions`, `activity_logs` (opsional), `personal_access_tokens`.

---

### 1.2 `visitors`
Menyimpan data tamu/pemesan.

| Kolom | Tipe | Ket | Keterangan |
|---|---|---|---|
| id | integer (PK) | NN | ID tamu |
| name | varchar | NN | Nama tamu |
| whatsapp_number | varchar | NULL | Nomor WhatsApp (dinormalisasi, mis. `628...`) |
| email | varchar | NULL | Email tamu (opsional) |
| created_at | datetime | NULL | Waktu dibuat |
| updated_at | datetime | NULL | Waktu diubah |

**Relasi:** memiliki banyak `reservations`.

---

### 1.3 `units`
Menyimpan unit/fasilitas yang dapat dipesan atau dijual (mis. glamping, aula, tiket/unit lain sesuai data).

| Kolom | Tipe | Ket | Keterangan |
|---|---|---|---|
| id | integer (PK) | NN | ID unit |
| name | varchar | NN | Nama unit |
| type | varchar | NN | Tipe unit (mis. `glamping`, `aula`, dll.) |
| price | numeric/decimal | NN | Harga dasar unit |
| capacity | integer | NN | Kapasitas |
| stock | integer | NN | Jumlah stok/slot unit |
| description | text | NULL | Deskripsi unit |
| created_at | datetime | NULL | Waktu dibuat |
| updated_at | datetime | NULL | Waktu diubah |

**Relasi:** memiliki banyak `reservations`, `pos_transaction_details`; memiliki banyak `images` (polymorphic).

---

### 1.4 `products`
Menyimpan produk (addon, produk POS, F&B, tiket masuk POS, dsb.).

| Kolom | Tipe | Ket | Keterangan |
|---|---|---|---|
| id | integer (PK) | NN | ID produk |
| name | varchar | NN | Nama produk |
| category | varchar | NN | Kategori produk (mis. `tiket_masuk`, makanan, dll.) |
| price | numeric/decimal | NN | Harga produk |
| type | varchar | NN | Jenis produk, mis. `addon` (tambahan reservasi) atau `pos` (penjualan kasir) |
| stock | integer | NN | Stok tersedia |
| for_kasir_makanan | tinyint(1) | NN | Flag izin jual di kasir makanan/F&B |
| created_at | datetime | NULL | Waktu dibuat |
| updated_at | datetime | NULL | Waktu diubah |

**Relasi:** memiliki banyak `reservation_addons`, `pos_transaction_details`; memiliki banyak `images` (polymorphic).

---

### 1.5 `reservations`
Menyimpan pesanan/reservasi tamu.

| Kolom | Tipe | Ket | Keterangan |
|---|---|---|---|
| id | integer (PK) | NN | ID reservasi |
| order_code | varchar (UQ) | NN | Kode order unik (mis. `SIRES-XXXXXXXX`) |
| visitor_id | integer (FK) | NN | Referensi ke `visitors.id` |
| unit_id | integer (FK) | NULL | Referensi ke `units.id` (bisa null untuk pesanan tertentu, mis. tiket/addon-only sesuai alur) |
| package_id | varchar | NULL | ID/kode paket (glamping/aula) jika berlaku |
| package_name | varchar | NULL | Nama paket saat dipesan |
| package_duration_hours | integer | NULL | Durasi paket (jam) |
| check_in_date | date | NN | Tanggal check-in/mulai |
| check_in_time | time | NULL | Waktu check-in/mulai |
| check_out_date | date | NN | Tanggal check-out/selesai |
| check_out_time | time | NULL | Waktu check-out/selesai |
| total_price | numeric/decimal | NN | Total harga (dihitung dari data sistem) |
| status | varchar | NN | Status reservasi (contoh: `pending`, `waiting_verification`, `paid`, `confirmed`, `cancelled`, dll. sesuai alur) |
| created_at | datetime | NULL | Waktu dibuat |
| updated_at | datetime | NULL | Waktu diubah |

**Relasi:** milik `visitor`, milik `unit` (opsional); memiliki banyak `reservation_addons`; memiliki satu `payment`; memiliki satu `ticket`.

---

### 1.6 `reservation_addons`
Menyimpan item tambahan pada reservasi.

| Kolom | Tipe | Ket | Keterangan |
|---|---|---|---|
| id | integer (PK) | NN | ID addon |
| reservation_id | integer (FK) | NN | Referensi ke `reservations.id` |
| product_id | integer (FK) | NN | Referensi ke `products.id` (produk tipe addon) |
| quantity | integer | NN | Jumlah item |
| price | numeric/decimal | NN | Harga satuan/tercatat saat pemesanan |
| created_at | datetime | NULL | Waktu dibuat |
| updated_at | datetime | NULL | Waktu diubah |

**Relasi:** milik `reservation`, milik `product`.

---

### 1.7 `payments`
Menyimpan data pembayaran reservasi (manual transfer atau gateway).

| Kolom | Tipe | Ket | Keterangan |
|---|---|---|---|
| id | integer (PK) | NN | ID pembayaran |
| reservation_id | integer (FK, UQ logis) | NN | Referensi ke `reservations.id` |
| method | varchar | NULL | Metode, mis. `manual_transfer`, `gateway` |
| provider | varchar | NULL | Penyedia gateway, mis. `midtrans` |
| provider_order_id | varchar | NULL | ID order di provider/gateway |
| provider_transaction_id | varchar | NULL | ID transaksi di provider |
| payment_type | varchar | NULL | Tipe pembayaran gateway (qris, va, e-wallet, dll.) |
| snap_token | varchar | NULL | Token Snap Midtrans |
| snap_redirect_url | text | NULL | URL redirect Snap |
| va_number | varchar | NULL | Nomor VA (jika ada) |
| qr_string | text | NULL | String/URL QR pembayaran (jika ada) |
| proof_image | varchar | NULL | Path file bukti transfer (disimpan di disk private) |
| status | varchar | NN | Status pembayaran (mis. `pending`, `verified`, `rejected`, `expired`, `failed`) |
| verified_at | datetime | NULL | Waktu verifikasi |
| paid_at | datetime | NULL | Waktu terkonfirmasi bayar |
| expires_at | datetime | NULL | Waktu kedaluwarsa pembayaran (gateway) |
| gateway_payload | text/json | NULL | Payload/respons/notifikasi gateway (tersimpan untuk jejak) |
| created_at | datetime | NULL | Waktu dibuat |
| updated_at | datetime | NULL | Waktu diubah |

**Relasi:** milik `reservation`.

---

### 1.8 `tickets`
Menyimpan e-ticket/check-in token QR untuk reservasi.

| Kolom | Tipe | Ket | Keterangan |
|---|---|---|---|
| id | integer (PK) | NN | ID tiket |
| reservation_id | integer (FK) | NN | Referensi ke `reservations.id` |
| unique_hash_sha256 | varchar (UQ) | NN | Hash unik tiket (dipakai untuk validasi/QR check-in) |
| is_used | tinyint(1) | NN | Status penggunaan tiket |
| used_at | datetime | NULL | Waktu tiket dipakai/check-in |
| created_at | datetime | NULL | Waktu dibuat |
| updated_at | datetime | NULL | Waktu diubah |

**Relasi:** milik `reservation`.

---

### 1.9 `pos_transactions`
Menyimpan transaksi penjualan kasir (POS).

| Kolom | Tipe | Ket | Keterangan |
|---|---|---|---|
| id | integer (PK) | NN | ID transaksi POS |
| user_id | integer (FK) | NN | Kasir/petugas yang melakukan transaksi (`users.id`) |
| total_amount | numeric/decimal | NN | Total nilai transaksi |
| payment_method | varchar | NN | Metode bayar (mis. `cash`, `qris`, `transfer`) |
| created_at | datetime | NULL | Waktu transaksi |
| updated_at | datetime | NULL | Waktu diubah |

**Relasi:** milik `user`; memiliki banyak `pos_transaction_details`.

---

### 1.10 `pos_transaction_details`
Menyimpan rincian item dalam transaksi POS.

| Kolom | Tipe | Ket | Keterangan |
|---|---|---|---|
| id | integer (PK) | NN | ID detail |
| pos_transaction_id | integer (FK) | NN | Referensi ke `pos_transactions.id` |
| product_id | integer (FK) | NULL | Referensi ke `products.id` (jika item produk) |
| unit_id | integer (FK) | NULL | Referensi ke `units.id` (jika item sewa/unit) |
| quantity | integer | NN | Jumlah item |
| subtotal | numeric/decimal | NN | Subtotal baris item |
| created_at | datetime | NULL | Waktu dibuat |
| updated_at | datetime | NULL | Waktu diubah |

**Relasi:** milik `pos_transaction`, milik `product` (opsional), milik `unit` (opsional).

---

## 2. Entitas Pendukung Operasional & Konten

### 2.1 `images`
Menyimpan gambar terasosiasi (polymorphic) ke entitas lain.

| Kolom | Tipe | Ket | Keterangan |
|---|---|---|---|
| id | integer (PK) | NN | ID gambar |
| imageable_type | varchar | NN | Tipe model pemilik (mis. Unit/Product) |
| imageable_id | integer | NN | ID model pemilik |
| path | varchar | NN | Path/lokasi file gambar |
| order | integer | NULL | Urutan tampil |
| alt | varchar | NULL | Teks alternatif |
| created_at | datetime | NULL | Waktu dibuat |
| updated_at | datetime | NULL | Waktu diubah |

---

### 2.2 `glamping_packages`
Paket harga/durasi glamping.

| Kolom | Tipe | Ket | Keterangan |
|---|---|---|---|
| id | integer (PK) | NN | ID paket |
| code | varchar (UQ) | NN | Kode paket |
| name | varchar | NN | Nama paket |
| duration_hours | integer | NN | Durasi (jam) |
| price | numeric/decimal | NN | Harga paket |
| is_active | tinyint(1) | NN | Status aktif |
| sort_order | integer | NN | Urutan tampil |
| created_at | datetime | NULL | Waktu dibuat |
| updated_at | datetime | NULL | Waktu diubah |

### 2.3 `aula_packages`
Paket harga/durasi aula.

| Kolom | Tipe | Ket | Keterangan |
|---|---|---|---|
| id | integer (PK) | NN | ID paket |
| code | varchar (UQ) | NN | Kode paket |
| name | varchar | NN | Nama paket |
| duration_hours | integer | NN | Durasi (jam) |
| price | numeric/decimal | NN | Harga paket |
| is_active | tinyint(1) | NN | Status aktif |
| sort_order | integer | NN | Urutan tampil |
| created_at | datetime | NULL | Waktu dibuat |
| updated_at | datetime | NULL | Waktu diubah |

---

### 2.4 `settings`
Menyimpan konfigurasi/parameter aplikasi berbasis key-value.

| Kolom | Tipe | Ket | Keterangan |
|---|---|---|---|
| id | integer (PK) | NN | ID setting |
| key | varchar (UQ) | NN | Kunci pengaturan (mis. maintenance, retensi) |
| value | text | NULL | Nilai pengaturan |
| created_at | datetime | NULL | Waktu dibuat |
| updated_at | datetime | NULL | Waktu diubah |

---

### 2.5 `activity_logs`
Menyimpan jejak aktivitas/audit (request & event penting).

| Kolom | Tipe | Ket | Keterangan |
|---|---|---|---|
| id | integer (PK) | NN | ID log |
| user_id | integer (FK) | NULL | ID pengguna (jika login) |
| user_name | varchar | NULL | Nama pengguna saat kejadian |
| user_role | varchar | NULL | Peran pengguna |
| event | varchar | NULL | Nama event/jenis aktivitas |
| description | text | NULL | Deskripsi aktivitas |
| subject_type | varchar | NULL | Tipe entitas terkait (polymorphic) |
| subject_id | integer | NULL | ID entitas terkait |
| subject_label | varchar | NULL | Label ringkas entitas |
| request_id | varchar | NULL | ID korelasi request |
| ip | varchar | NULL | Alamat IP |
| method | varchar | NULL | HTTP method |
| path | varchar | NULL | Path request |
| route | varchar | NULL | Nama route |
| status | integer | NULL | HTTP status code |
| properties | text/json | NULL | Properti tambahan (ter-redaksi untuk data sensitif) |
| file_name | varchar | NULL | Nama file terkait (jika ada) |
| file_mime | varchar | NULL | MIME file |
| file_size | integer | NULL | Ukuran file |
| file_path | varchar | NULL | Path file |
| file_hash | varchar | NULL | Hash file |
| user_agent | text | NULL | User agent |
| created_at | datetime | NULL | Waktu kejadian |

---

### 2.6 `chatbot_messages`
Menyimpan riwayat percakapan chatbot (Teduh Bot).

| Kolom | Tipe | Ket | Keterangan |
|---|---|---|---|
| id | integer (PK) | NN | ID pesan |
| session_id | varchar | NN | ID sesi percakapan |
| role | varchar | NN | Peran pesan (mis. user/assistant/system) |
| content | text | NN | Isi pesan |
| user_name | varchar | NULL | Nama pengguna/tamu (jika tersedia) |
| meta | text/json | NULL | Metadata tambahan |
| created_at | datetime | NULL | Waktu dibuat |
| updated_at | datetime | NULL | Waktu diubah |

---

## 3. Tabel Infrastruktur Framework (Ringkas)

| Tabel | Fungsi utama | Kolom kunci |
|---|---|---|
| `sessions` | Penyimpanan sesi (jika driver database) | id, user_id, payload, last_activity |
| `cache`, `cache_locks` | Cache & lock | key, value/owner, expiration |
| `jobs`, `job_batches`, `failed_jobs` | Antrian & kegagalan job | queue, payload, attempts, failed_at, dll. |
| `personal_access_tokens` | Token Sanctum API | tokenable_*, name, token, abilities, expires_at |
| `password_reset_tokens` | Reset password | email, token, created_at |
| `migrations` | Riwayat migrasi skema | migration, batch |

---

## 4. Catatan Domain Penting

- **Status reservasi & pembayaran** dikendalikan oleh alur aplikasi (contoh: `pending` → `waiting_verification` → `paid`/`confirmed`/`cancelled`, dan status gateway seperti `verified/failed/expired`) — lihat implementasi controller/service untuk transisi yang sah.
- **Ketersediaan** dihitung berbasis reservasi berstatus pemblokir dan rentang waktu (check-in/out), bukan hanya kolom stok statis.
- **E-ticket** diidentifikasi oleh `tickets.unique_hash_sha256` dan dipakai sekali (ditandai `is_used`, `used_at`) saat check-in valid.
- **Bukti pembayaran manual** disimpan di storage private dan diakses melalui endpoint admin terproteksi.
- **Audit** menyimpan metadata/jejak perubahan penting; data sensitif direduksi sebelum disimpan.
