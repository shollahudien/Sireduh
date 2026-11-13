# Concurrency Testing Guide — lockForUpdate() (Double Booking)

Panduan ini membuktikan kepada penguji skripsi bahwa mekanisme `lockForUpdate()`
pada `AvailabilityService::lockUnitForStay()` benar-benar mencegah double booking
saat dua request masuk bersamaan.

## Tujuan
Mendemonstrasikan bahwa ketika dua user mencoba memesan unit yang sama pada
tanggal yang sama secara bersamaan, hanya satu yang berhasil dan yang lain
ditolak dengan pesan "Unit sudah penuh pada tanggal yang dipilih."

## Persiapan
1. Pastikan aplikasi berjalan di server lokal:
   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```
2. Pastikan database sudah di-seed (minimal 1 unit glamping dengan stock = 1):
   ```bash
   php artisan migrate:fresh --seed
   php artisan db:seed --class=TestingSeeder
   ```
3. Buka **dua browser berbeda** (Chrome & Firefox) agar session/cookie terpisah.
   (Jangan gunakan 2 tab di browser yang sama karena session cookie akan bentrok.)

## Langkah Pengujian

### Metode A — Simulasi Manual via UI (paling visual untuk penguji)
1. Di Chrome: buka http://127.0.0.1:8000/checkout
   - Pilih tanggal check-in: 2026-12-01, check-out: 2026-12-03
   - Pilih unit "Glamping Premium UAT" (stock 1)
   - Isi data tamu (nama, WA) — **JANGAN submit dulu**
2. Di Firefox: buka http://127.0.0.1:8000/checkout
   - Pilih tanggal & unit yang SAMA
   - Isi data tamu berbeda — **JANGAN submit dulu**
3. Siapkan kedua browser pada tombol "Buat Reservasi".
4. Tekan submit di Chrome, lalu **segera** (dalam < 1 detik) tekan submit di Firefox.
   - Tips: gunakan countdown "3, 2, 1, GO" dengan 2 orang operator.
5. Amati hasil:
   - Chrome → "Reservasi berhasil dibuat. Silakan lanjutkan pembayaran." (201)
   - Firefox → "Unit sudah penuh pada tanggal yang dipilih." (409)
6. Verifikasi di database:
   ```bash
   php artisan tinker
   >>> \App\Models\Reservation::where('unit_id', 1)->count(); // harus = 1
   ```

### Metode B — Simulasi via curl (lebih presisi, bukti konkret)
Buka 2 terminal, jalankan hampir bersamaan:
```bash
# Terminal 1
curl -s -X POST http://127.0.0.1:8000/api/reservations \
  -H "Content-Type: application/json" \
  -d '{"visitor_name":"User A","visitor_whatsapp":"081111111111","unit_id":1,"check_in_date":"2026-12-01","check_out_date":"2026-12-03","total_price":1000000}' &
# Terminal 2
curl -s -X POST http://127.0.0.1:8000/api/reservations \
  -H "Content-Type: application/json" \
  -d '{"visitor_name":"User B","visitor_whatsapp":"082222222222","unit_id":1,"check_in_date":"2026-12-01","check_out_date":"2026-12-03","total_price":1000000}' &
wait
```
Hasil yang diharapkan: satu response `201 success:true`, satu response `409 success:false`.

### Metode C — Automated (PHPUnit, paling kredibel untuk sidang)
```bash
php artisan test --filter=DoubleBookingTest
```
Output yang diharapkan: 3 tests passed, termasuk "first reservation succeeds
and second conflicts".

## Penjelasan Teknis untuk Penguji
- `AvailabilityService::lockUnitForStay()` memanggil `Unit::query()->whereKey($unitId)->lockForUpdate()->firstOrFail()`.
- `lockForUpdate()` mengeluarkan `SELECT ... FOR UPDATE` di InnoDB (SQLite dalam test
  menggunakan transaction-level locking).
- Setelah row unit terkunci, sistem menghitung jumlah reservasi overlapping dengan
  status blocking (`pending`, `waiting_verification`, `paid`, `confirmed`).
- Jika `stock - overlapping <= 0`, dilempar `RuntimeException` → controller
  mengembalikan 409.
- Request kedua yang masuk saat row masih terkunci akan **menunggu** (blocking)
  sampai transaksi pertama commit, lalu melihat data terbaru (overlapping count = 1)
  dan ditolak.

## Bukti yang Harus Disiapkan untuk Sidang
1. Screenshot 2 browser menampilkan hasil berbeda (sukses vs gagal).
2. Query database menunjukkan hanya 1 reservasi tersimpan.
3. Output `php artisan test --filter=DoubleBookingTest` (3 passed).
4. (Opsional) Log query `EXPLAIN` menunjukkan `FOR UPDATE` — aktifkan
   `DB::listen` di `AppServiceProvider` untuk mencetak SQL.
