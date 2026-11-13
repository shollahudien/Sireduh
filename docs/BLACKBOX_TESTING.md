# Black Box Testing Manual Scenarios — SIRESTEDUH

## Panduan Pengujian Manual (Black Box)

Setiap skenario dijalankan melalui UI (browser) tanpa melihat kode sumber.
Status: **PASS** jika sistem berperilaku sesuai "Expected Result".

| No | Skenario | Langkah | Input | Expected Result | Status |
|---|---|---|---|---|---|
| 1 | Guest booking unit glamping valid | Buka /checkout → pilih tanggal → pilih unit → isi data → submit | Tanggal valid, unit tersedia, data lengkap | Reservasi dibuat, muncul order_code, redirect ke halaman pembayaran | ☐ |
| 2 | Guest upload bukti pembayaran JPG | Halaman pembayaran → pilih file JPG → upload | File .jpg < 2MB | Upload sukses, status `waiting_verification` | ☐ |
| 3 | Guest upload bukti pembayaran PDF | Halaman pembayaran → pilih file .pdf → upload | File .pdf | Gagal, pesan "format file tidak valid" (422) | ☐ |
| 4 | Guest upload bukti > 2MB | Halaman pembayaran → pilih gambar 3MB | File 3MB | Gagal, pesan "ukuran file terlalu besar" | ☐ |
| 5 | Double booking tanggal sama | 2 browser booking unit yang sama tanggal sama | Unit sama, tanggal sama | Yang pertama sukses (201), yang kedua gagal (409 "Unit sudah penuh") | ☐ |
| 6 | Booking tanggal lampau | Guest isi check-in kemarin | Tanggal < hari ini | Gagal validasi (422) "check_in_date harus hari ini atau setelahnya" | ☐ |
| 7 | Booking tanggal check-out < check-in | Guest isi check-out sebelum check-in | check_out < check_in | Gagal validasi (422) | ☐ |
| 8 | Tiket masuk mandiri (unit_id null) | Guest pilih "Tiket Masuk" tanpa unit | unit_id = null, addon tiket masuk | Reservasi tersimpan dengan unit_id null | ☐ |
| 9 | Admin verifikasi pembayaran valid | Admin buka /admin/verification → klik Verifikasi | Reservasi waiting_verification + bukti | Status jadi `paid`, tiket QR ter-generate, notifikasi muncul | ☐ |
| 10 | Admin tolak pembayaran invalid | Admin klik "Tolak" pada bukti palsu | Reservasi waiting_verification | Status kembali `pending`, pesan penolakan | ☐ |
| 11 | Admin akses dashboard tanpa login | Buka /admin tanpa login | — | Redirect ke /login | ☐ |
| 12 | Kasir akses admin dashboard | Login kasir → buka /admin | Role kasir | Redirect ke / (403/redirect) | ☐ |
| 13 | POS checkout produk valid | Kasir tambah 2 Kopi + 1 Tenda → bayar cash | Stok cukup | Transaksi sukses (201), stok produk -2, stok unit -1, struk muncul | ☐ |
| 14 | POS checkout stok tidak cukup | Kasir pesan 100 Kopi (stok 10) | quantity > stock | Gagal (422) "Stok ... tidak mencukupi", stok tidak berubah, tidak ada transaksi | ☐ |
| 15 | POS checkout quantity negatif | Kasir input quantity -2 | quantity = -2 | Gagal validasi (422) "quantity min 1" | ☐ |
| 16 | POS checkout item kosong | Kasir submit cart kosong | items = [] | Gagal validasi (422) "items min 1" | ☐ |
| 17 | QR check-in sukses | Petugas scan QR valid (belum dipakai) | Hash valid, is_used = false | Berhasil (200), tampil data tamu, is_used = true | ☐ |
| 18 | QR check-in ulang (sudah dipakai) | Petugas scan QR yang sama lagi | Hash valid, is_used = true | Gagal (409) "Tiket sudah digunakan" | ☐ |
| 19 | Scan QR palsu | Petugas scan QR random | Hash 64 char tidak terdaftar | Gagal (404) "Tiket Tidak Valid / Tidak Ditemukan" | ☐ |
| 20 | Petugas check-in akses POS | Login checkin → buka /pos | Role checkin | Redirect ke / (akses ditolak) | ☐ |

## Edge Cases Tambahan (Negative Testing)
- Input XSS di nama visitor → harus di-escape, tidak dieksekusi.
- Nomor WA invalid (kurang digit) → ditolak validasi.
- Total price 0 / negatif → ditolak (min:0 / numeric).
- Admin hapus unit yang masih punya reservasi aktif → harus gagal (foreign key / policy).
- Concurrent POS checkout stok tepat batas → salah satu gagal karena lockForUpdate.
