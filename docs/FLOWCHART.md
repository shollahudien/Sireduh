# Flowchart — SIRESTEDUH

Kumpulan flowchart alur proses utama sistem **SIRESTEDUH**. Diagram menggunakan Mermaid (render di GitHub).

---

## 1. Flowchart Reservasi Tamu (Checkout)

```mermaid
flowchart TD
    A([Mulai]) --> B[Tamu buka halaman reservasi/checkout]
    B --> C[Muat unit, produk/addon, paket, dan ketersediaan]
    C --> D[Tamu isi data: nama, WhatsApp, tanggal/waktu, unit/paket, addon]
    D --> E{Input valid?}
    E -- Tidak --> E1[Tampilkan pesan validasi/error]
    E1 --> D
    E -- Ya --> F{Ada pesanan serupa\npending baru-baru ini?}
    F -- Ya --> F1[Tolak sementara / minta tunggu atau cek pembayaran]
    F1 --> Z1([Selesai])
    F -- Tidak --> G[Normalisasi nomor WhatsApp & hitung ulang harga dari sistem]
    G --> H{Unit/paket memerlukan\nalokasi slot?}
    H -- Ya --> I[Cek & kunci slot/unit sesuai jadwal]
    H -- Tidak --> J[Cek & kunci stok addon/produk]
    I --> K{Slot tersedia?}
    K -- Tidak --> K1[Tampilkan pesan tidak tersedia/bentrok]
    K1 --> Z1
    K -- Ya --> J
    J --> L{Stok addon mencukupi?}
    L -- Tidak --> L1[Rollback & tampilkan pesan stok kurang]
    L1 --> Z1
    L -- Ya --> M[Buat/update data visitor, buat order_code unik]
    M --> N[Simpan reservation + reservation_addons\nstatus: pending]
    N --> O[Tampilkan ringkasan & arahkan ke halaman pembayaran]
    O --> Z2([Selesai])
```

---

## 2. Flowchart Pembayaran — Transfer Manual (Upload Bukti)

```mermaid
flowchart TD
    A([Mulai]) --> B[Tamu buka halaman pembayaran dengan order_code]
    B --> C[Sistem tampilkan rincian reservasi & status pembayaran]
    C --> D{Status reservasi\nboleh upload bukti?}
    D -- Tidak --> D1[Tampilkan info: tidak dapat menerima bukti]
    D1 --> Z1([Selesai])
    D -- Ya --> E[Tamu unggah file bukti gambar]
    E --> F{Validasi file\n(format/ukuran) lolos?}
    F -- Tidak --> F1[Tampilkan error validasi]
    F1 --> E
    F -- Ya --> G[Simpan file ke storage private]
    G --> H[Update/buat payment: manual_transfer, status pending]
    H --> I[Update reservation: waiting_verification]
    I --> J[Kirim notifikasi verifikasi (sesuai konfigurasi)]
    J --> K[Tampilkan pesan menunggu verifikasi admin]
    K --> Z2([Selesai])
```

---

## 3. Flowchart Pembayaran — Midtrans (Gateway Otomatis)

```mermaid
flowchart TD
    A([Mulai]) --> B[Tamu pilih bayar otomatis/gateway]
    B --> C{Gateway aktif &\nkonfigurasi lengkap?}
    C -- Tidak --> C1[Tampilkan error/gunakan transfer manual]
    C1 --> Z1([Selesai])
    C -- Ya --> D{Reservasi valid\nuntuk dibayar?}
    D -- Tidak --> D1[Tolak: status tidak sesuai/order tidak ditemukan]
    D1 --> Z1
    D -- Ya --> E{Ada transaksi pending\nyang masih berlaku?}
    E -- Ya --> F[Gunakan snap token/redirect yang ada]
    E -- Tidak --> G[Buat transaksi Snap ke Midtrans]
    G --> H[Simpan/update payment: provider midtrans, status pending]
    F --> I[Tampilkan Snap/redirect ke pembayaran]
    H --> I
    I --> J[Midtrans kirim notifikasi status]
    J --> K{Signature valid?}
    K -- Tidak --> K1[Abaikan/tolak notifikasi]
    K1 --> Z2([Selesai])
    K -- Ya --> L{Status transaksi?}
    L -- settlement/capture accepted --> M[Set payment verified/paid\nUpdate reservation paid/siap tiket]
    M --> N[Pastikan e-ticket dibuat\nKirim e-ticket/notifikasi]
    L -- pending --> O[Set payment pending]
    L -- expire --> P[Set payment expired]
    L -- deny/cancel/failure --> Q[Set payment failed]
    N --> Z3([Selesai])
    O --> Z3
    P --> Z3
    Q --> Z3
```

---

## 4. Flowchart Verifikasi Pembayaran oleh Admin

```mermaid
flowchart TD
    A([Mulai]) --> B[Admin membuka daftar verifikasi/pembayaran]
    B --> C[Pilih reservasi/pembayaran yang menunggu]
    C --> D[Lihat detail & bukti pembayaran]
    D --> E{Keputusan admin}
    E -- Verifikasi/Terima --> F[Update payment: verified + timestamp]
    F --> G[Update reservation: paid/confirmed sesuai alur]
    G --> H[Pastikan ticket dibuat/tersedia]
    H --> I[Kirim e-ticket ke tamu]
    I --> J[Catat audit/log & tampilkan sukses]
    E -- Tolak/Batalkan --> K[Proses pembatalan/penolakan]
    K --> L[Update payment/reservation sesuai aturan]
    L --> M[Kembalikan stok addon bila berlaku]
    M --> N[Kirim notifikasi/status penolakan]
    N --> J
    J --> Z([Selesai])
```

---

## 5. Flowchart Check-in E-Ticket (Petugas)

```mermaid
flowchart TD
    A([Mulai]) --> B[Petugas login & buka scanner check-in]
    B --> C[Scan/input hash QR tiket]
    C --> D[Cari tiket berdasarkan unique_hash_sha256]
    D --> E{Tiket ditemukan?}
    E -- Tidak --> E1[Tampilkan: tiket tidak valid/tidak ditemukan]
    E1 --> Z1([Selesai])
    E -- Ya --> F{Tiket sudah digunakan?}
    F -- Ya --> F1[Tampilkan: tiket sudah digunakan + info pemakaian]
    F1 --> Z1
    F -- Tidak --> G[Ambil data reservasi & unit/jadwal]
    G --> H{Ada potensi konflik\nslot/jadwal aktual?}
    H -- Ya --> H1[Tolak check-in & tampilkan alasan bentrok]
    H1 --> Z1
    H -- Tidak --> I[Set is_used=true, used_at=sekarang]
    I --> J[Perbarui waktu aktual check-in/out sesuai aturan sistem]
    J --> K[Catat audit/log check-in]
    K --> L[Tampilkan sukses + detail tamu/unit/jadwal]
    L --> Z2([Selesai])
```

---

## 6. Flowchart Transaksi POS (Kasir / Kasir Makanan)

```mermaid
flowchart TD
    A([Mulai]) --> B[Kasir login sesuai peran]
    B --> C[Sistem muat item yang diizinkan\n(kasir umum / kasir makanan / check-in)]
    C --> D[Kasir pilih item, jumlah, metode bayar]
    D --> E{Item & kuantitas valid?}
    E -- Tidak --> E1[Tampilkan error validasi]
    E1 --> D
    E -- Ya --> F{Metode bayar diizinkan\nuntuk peran?}
    F -- Tidak --> F1[Tolak metode bayar]
    F1 --> D
    F -- Ya --> G[Cek stok/harga & ketersediaan slot jika sewa unit/paket]
    G --> H{Stok/slot mencukupi?}
    H -- Tidak --> H1[Rollback/tolak: stok/slot tidak cukup]
    H1 --> Z1([Selesai])
    H -- Ya --> I[Hitung total & simpan pos_transaction + details]
    I --> J[Perbarui stok/atribut terkait sesuai aturan]
    J --> K[Catat audit/log bila berlaku]
    K --> L[Tampilkan struk/ringkasan transaksi]
    L --> Z2([Selesai])
```

---

## 7. Flowchart Mode Maintenance & Akses Sistem

```mermaid
flowchart TD
    A([Request masuk ke web/API]) --> B{Maintenance aktif?}
    B -- Tidak --> C[Lanjutkan ke middleware/route tujuan]
    C --> Z1([Proses normal])
    B -- Ya --> D{User adalah admin?}
    D -- Ya --> C
    D -- Tidak --> E{Request ke halaman login?}
    E -- Ya --> C
    E -- Tidak --> F{Request API?}
    F -- Ya --> G[Respons JSON 503: maintenance]
    F -- Tidak --> H[Tampilkan halaman maintenance 503]
    G --> Z2([Ditolak sementara])
    H --> Z2
```
