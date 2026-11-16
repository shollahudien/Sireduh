# DFD (Data Flow Diagram) — SIRESTEDUH

Dokumen DFD sistem **SIRESTEDUH** (konteks, level 1, dan dekomposisi level 2 untuk proses utama). Diagram menggunakan Mermaid agar mudah dirender di GitHub.

## Entitas Eksternal

| Kode | Entitas | Deskripsi |
|---|---|---|
| E1 | Tamu/Pengunjung | Pengguna publik yang melakukan reservasi, pembayaran, cek status, dan menerima e-ticket |
| E2 | Admin | Pengelola sistem: verifikasi, master data, laporan, pengaturan, monitoring |
| E3 | Kasir / Kasir Makanan | Petugas POS yang mencatat transaksi penjualan |
| E4 | Petugas Check-in | Petugas validasi/pemindaian e-ticket QR |
| E5 | Midtrans | Payment gateway (pembuatan transaksi Snap & notifikasi pembayaran) |
| E6 | Layanan Notifikasi (Telegram/Fonnte/Email*) | Kanal pengiriman notifikasi/e-ticket sesuai konfigurasi |
| E7 | Gemini AI | Layanan chatbot/TTS (opsional, jika dikonfigurasi) |

\*Email bergantung konfigurasi mail.

## Data Store (Penyimpanan Data)

| Kode | Data Store | Keterangan |
|---|---|---|
| D1 | Data Tamu & Reservasi | `visitors`, `reservations`, `reservation_addons`, `tickets` |
| D2 | Data Pembayaran | `payments`, file bukti (storage private) |
| D3 | Data Master & Ketersediaan | `units`, `products`, `images`, `glamping_packages`, `aula_packages`, `settings` |
| D4 | Data POS | `pos_transactions`, `pos_transaction_details` |
| D5 | Data Log & Audit | `activity_logs`, log aplikasi |
| D6 | Data Chatbot/Sesi | `chatbot_messages`, sesi/token terkait |

---

## DFD Level 0 — Diagram Konteks

```mermaid
flowchart LR
    E1[Tamu/Pengunjung]
    E2[Admin]
    E3[Kasir / Kasir Makanan]
    E4[Petugas Check-in]
    E5[Midtrans]
    E6[Layanan Notifikasi]
    E7[Gemini AI]

    P0((0. Sistem SIRESTEDUH))

    E1 -- Data reservasi, bukti bayar, permintaan status/chat --> P0
    P0 -- Info ketersediaan, order, status, e-ticket, respons chat --> E1

    E2 -- Login, verifikasi, kelola data/pengaturan, permintaan laporan --> P0
    P0 -- Dashboard, data reservasi/bayar, laporan, log, notifikasi --> E2

    E3 -- Login, data transaksi POS --> P0
    P0 -- Data produk/unit, ringkasan & riwayat transaksi --> E3

    E4 -- Login, hash/QR tiket --> P0
    P0 -- Hasil validasi check-in, riwayat --> E4

    P0 -- Permintaan transaksi Snap --> E5
    E5 -- Token/redirect & notifikasi status pembayaran --> P0

    P0 -- Pesan/notifikasi/e-ticket --> E6
    E6 -- Callback/webhook (mis. Telegram) --> P0

    P0 -- Prompt/permintaan jawaban/TTS --> E7
    E7 -- Respons AI/suara --> P0
```

---

## DFD Level 1 — Dekomposisi Proses

```mermaid
flowchart TB
    %% Entitas eksternal
    E1[Tamu/Pengunjung]
    E2[Admin]
    E3[Kasir / Kasir Makanan]
    E4[Petugas Check-in]
    E5[Midtrans]
    E6[Layanan Notifikasi]
    E7[Gemini AI]

    %% Proses
    P1((1.0 Kelola Reservasi\n& Ketersediaan))
    P2((2.0 Kelola Pembayaran\n& E-Ticket))
    P3((3.0 Kelola Check-in))
    P4((4.0 Kelola POS\n& Transaksi))
    P5((5.0 Kelola Admin,\nMaster Data & Laporan))
    P6((6.0 Layanan Chatbot,\nNotifikasi & Audit))

    %% Data store
    D1[(D1 Data Tamu & Reservasi)]
    D2[(D2 Data Pembayaran)]
    D3[(D3 Data Master & Ketersediaan)]
    D4[(D4 Data POS)]
    D5[(D5 Data Log & Audit)]
    D6[(D6 Data Chatbot/Sesi)]

    %% Aliran E1
    E1 -->|Permintaan unit/ketersediaan, data pemesanan| P1
    P1 -->|Info ketersediaan, order_code, rincian pesanan| E1
    E1 -->|Order code, bukti transfer, pilih gateway| P2
    P2 -->|Instruksi bayar, status bayar, e-ticket/status| E1
    E1 -->|Cek status pesanan| P1
    E1 -->|Pertanyaan/chat| P6
    P6 -->|Jawaban chatbot/TTS| E1

    %% Aliran E2
    E2 -->|Login, kelola master, verifikasi, pengaturan| P5
    P5 -->|Dashboard, laporan, data master, log| E2
    P5 <-->|Update/verifikasi status| P2
    P5 <-->|Kelola data & aturan| D3
    P5 <-->|Lihat/kelola reservasi| D1

    %% Aliran E3/E4
    E3 -->|Data transaksi POS| P4
    P4 -->|Struk/ringkasan/riwayat| E3
    E4 -->|Hash/QR tiket| P3
    P3 -->|Hasil validasi & detail check-in| E4

    %% Integrasi eksternal
    P2 <-->|Buat transaksi Snap / terima notifikasi| E5
    P2 -->|Kirim notifikasi/e-ticket| E6
    E6 -->|Webhook/callback aksi| P2
    P6 <-->|Prompt & respons AI| E7

    %% Data store akses
    P1 <-->|Simpan/baca tamu, reservasi, addon| D1
    P1 <-->|Baca unit/produk/paket/ketersediaan| D3
    P2 <-->|Baca/tulis pembayaran & status| D2
    P2 <-->|Baca/tulis reservasi & tiket| D1
    P3 <-->|Validasi & update tiket/reservasi| D1
    P4 <-->|Baca produk/unit, tulis transaksi| D4
    P4 <-->|Baca/update stok & referensi| D3
    P5 <-->|Baca agregasi & audit| D5
    P5 <-->|Baca transaksi/laporan| D4
    P5 <-->|Baca pembayaran| D2

    %% Audit & chatbot store
    P1 -->|Catat aktivitas| P6
    P2 -->|Catat aktivitas/event| P6
    P3 -->|Catat aktivitas| P6
    P4 -->|Catat aktivitas| P6
    P5 -->|Catat aktivitas| P6
    P6 <-->|Tulis/baca log audit| D5
    P6 <-->|Tulis/baca riwayat chat| D6
```

---

## DFD Level 2 — Proses 1.0 Kelola Reservasi & Ketersediaan

```mermaid
flowchart LR
    E1[Tamu]
    D1[(D1 Tamu & Reservasi)]
    D3[(D3 Master & Ketersediaan)]

    P11((1.1 Lihat Unit/Produk\n& Cek Ketersediaan))
    P12((1.2 Validasi Input\n& Hitung Harga))
    P13((1.3 Alokasi Slot\n& Simpan Reservasi))
    P14((1.4 Tampilkan Status Pesanan))

    E1 -->|Filter tanggal/tipe/paket| P11
    D3 -->|Data unit, produk, paket| P11
    D1 -->|Data reservasi terblokir| P11
    P11 -->|Daftar ketersediaan & harga| E1

    E1 -->|Data tamu, unit/paket, addon, tanggal| P12
    D3 -->|Harga & aturan paket/produk| P12
    P12 -->|Data tervalidasi & total harga| P13

    P13 <-->|Cek konflik/slot, lock & simpan| D1
    P13 <-->|Baca unit/produk, update stok addon| D3
    P13 -->|Order code & rincian reservasi| E1

    E1 -->|Order code / identitas pencarian| P14
    D1 -->|Status reservasi, tiket, ringkasan| P14
    D2[(D2 Pembayaran)] -->|Status pembayaran| P14
    P14 -->|Informasi status pesanan| E1
```

## DFD Level 2 — Proses 2.0 Kelola Pembayaran & E-Ticket

```mermaid
flowchart TB
    E1[Tamu]
    E2[Admin]
    E5[Midtrans]
    E6[Layanan Notifikasi]
    D1[(D1 Tamu & Reservasi)]
    D2[(D2 Data Pembayaran)]

    P21((2.1 Tampilkan Info Pembayaran))
    P22((2.2 Unggah Bukti Transfer Manual))
    P23((2.3 Buat & Proses Pembayaran Gateway))
    P24((2.4 Verifikasi Admin / Aksi Notifikasi))
    P25((2.5 Terbitkan & Kirim E-Ticket))

    E1 -->|Order code| P21
    D1 -->|Data reservasi| P21
    D2 -->|Status & info pembayaran| P21
    P21 -->|Rincian tagihan & metode bayar| E1

    E1 -->|Bukti transfer (gambar)| P22
    P22 -->|Simpan bukti & set waiting_verification| D2
    P22 -->|Update status reservasi| D1
    P22 -->|Notifikasi verifikasi| E6

    E1 -->|Permintaan bayar otomatis| P23
    P23 -->|Permintaan transaksi Snap| E5
    E5 -->|Token/redirect| P23
    E5 -->|Notifikasi status (signed)| P23
    P23 -->|Simpan/update status pembayaran gateway| D2
    P23 -->|Update status reservasi (jika lunas)| D1

    E2 -->|Setujui/Tolak| P24
    E6 -->|Callback aksi (Telegram, jika dipakai)| P24
    P24 -->|Update status verifikasi/tolak/batal| D2
    P24 -->|Update status reservasi| D1

    P23 -->|Trigger jika lunas| P25
    P24 -->|Trigger jika diverifikasi| P25
    P25 -->|Buat/ambil tiket (hash QR)| D1
    P25 -->|Kirim e-ticket/notifikasi| E6
```

## DFD Level 2 — Proses 3.0 Kelola Check-in

```mermaid
flowchart LR
    E4[Petugas Check-in]
    D1[(D1 Tamu & Reservasi)]
    D5[(D5 Log & Audit)]

    P31((3.1 Terima Input Hash/QR))
    P32((3.2 Validasi Tiket & Otorisasi))
    P33((3.3 Cek Konflik Jadwal/Slot))
    P34((3.4 Tandai Digunakan & Catat Hasil))

    E4 -->|Scan/masukkan hash QR| P31
    P31 -->|Hash tervalidasi format| P32
    P32 <-->|Ambil tiket & reservasi| D1
    P32 -->|Status tiket (valid/used/invalid)| P34
    P32 -->|Data reservasi & unit/jadwal| P33
    D1 -->|Reservasi aktif/slot terkait| P33
    P33 -->|Hasil cek bentrok| P34
    P34 -->|Update used_at, is_used, waktu aktual| D1
    P34 -->|Catat event check-in| D5
    P34 -->|Hasil check-in & detail tamu/unit| E4
```

## DFD Level 2 — Proses 4.0 Kelola POS & Transaksi

```mermaid
flowchart LR
    E3[Kasir / Kasir Makanan]
    D3[(D3 Master & Ketersediaan)]
    D4[(D4 Data POS)]
    D1[(D1 Tamu & Reservasi)]

    P41((4.1 Muat Produk/Unit Sesuai Peran))
    P42((4.2 Validasi Item, Stok & Harga))
    P43((4.3 Proses Checkout & Simpan Transaksi))
    P44((4.4 Tampilkan Riwayat & Ringkasan))

    E3 -->|Permintaan daftar jual| P41
    D3 -->|Produk/unit/paket| P41
    P41 -->|Daftar item yang diizinkan| E3

    E3 -->|Item, qty, metode bayar| P42
    D3 -->|Stok/harga/aturan| P42
    D1 -->|Cek slot/jadwal (jika sewa paket/unit)| P42
    P42 -->|Item valid & total| P43

    P43 -->|Simpan transaksi & detail| D4
    P43 <-->|Update stok/slot terkait| D3
    P43 -->|Bukti/ringkasan transaksi| E3

    E3 -->|Filter periode| P44
    D4 -->|Riwayat & agregasi transaksi| P44
    P44 -->|Riwayat, rekap, item terjual| E3
```

## DFD Level 2 — Proses 5.0 Kelola Admin, Master Data & Laporan

```mermaid
flowchart TB
    E2[Admin]
    D1[(D1 Tamu & Reservasi)]
    D2[(D2 Data Pembayaran)]
    D3[(D3 Data Master & Ketersediaan)]
    D4[(D4 Data POS)]
    D5[(D5 Data Log & Audit)]

    P51((5.1 Autentikasi &\nKontrol Akses Admin))
    P52((5.2 Kelola Master Data\nUnit/Produk/Paket/User/Gambar))
    P53((5.3 Kelola Pengaturan,\nMaintenance & Retensi))
    P54((5.4 Monitoring, Log\n& Activity))
    P55((5.5 Laporan, Analitik\n& Pencarian))

    E2 -->|Kredensial login/permintaan akses| P51
    P51 -->|Sesi/otorisasi admin| E2

    E2 -->|Data CRUD master & upload gambar| P52
    P52 <-->|Baca/tulis unit, produk, paket, gambar, user| D3
    P52 -->|Hasil pengelolaan & validasi| E2

    E2 -->|Pengaturan maintenance/retensi/konfigurasi| P53
    P53 <-->|Baca/tulis settings| D3
    P53 -->|Status pengaturan & hasil aksi| E2

    E2 -->|Permintaan lihat log/activity/download/clear| P54
    P54 <-->|Baca/tulis activity & log| D5
    P54 -->|Tampilan log, activity, hasil aksi| E2

    E2 -->|Filter periode/kriteria laporan & pencarian| P55
    D1 -->|Data reservasi/tiket| P55
    D2 -->|Data pembayaran| P55
    D3 -->|Data master| P55
    D4 -->|Data transaksi POS| P55
    D5 -->|Data audit/agregasi pendukung| P55
    P55 -->|Dashboard, laporan, hasil pencarian| E2
```

## DFD Level 2 — Proses 6.0 Layanan Chatbot, Notifikasi & Audit

```mermaid
flowchart TB
    E1[Tamu/Pengunjung]
    E2[Admin]
    E5[Midtrans]
    E6[Layanan Notifikasi]
    E7[Gemini AI]
    D1[(D1 Tamu & Reservasi)]
    D2[(D2 Data Pembayaran)]
    D5[(D5 Data Log & Audit)]
    D6[(D6 Data Chatbot/Sesi)]

    P61((6.1 Proses Chatbot & TTS))
    P62((6.2 Kelola Notifikasi\n& Webhook))
    P63((6.3 Pencatatan Audit,\nActivity & Pruning))

    E1 -->|Pertanyaan/pesan chat, permintaan suara| P61
    P61 <-->|Baca konteks & simpan riwayat pesan| D6
    P61 <-->|Prompt & respons AI/TTS| E7
    P61 -->|Jawaban chatbot/hasil suara| E1

    D1 -->|Event reservasi/tiket| P62
    D2 -->|Event/status pembayaran| P62
    E2 -->|Aksi kirim/resend e-ticket| P62
    P62 -->|Kirim e-ticket/notifikasi| E6
    E6 -->|Webhook/callback (Telegram)| P62
    E5 -->|Notifikasi pembayaran (diteruskan dari 2.0)| P62
    P62 -->|Hasil pemrosesan webhook/aksi| E2

    E1 -->|Aktivitas pengguna publik| P63
    E2 -->|Aktivitas admin| P63
    P62 -->|Event notifikasi/webhook| P63
    P61 -->|Event chatbot| P63
    P63 <-->|Tulis/baca/prune activity & log| D5
    P63 -->|Informasi audit untuk monitoring| E2
```

## Keterangan Kelengkapan DFD

- **Level 0 (Konteks):** menggambarkan sistem SIRESTEDUH sebagai satu proses dengan seluruh entitas eksternal dan aliran data utama.
- **Level 1:** dekomposisi ke 6 proses utama (Reservasi & Ketersediaan, Pembayaran & E-Ticket, Check-in, POS, Admin/Master/Laporan, Chatbot/Notifikasi/Audit).
- **Level 2:** rincian subproses untuk seluruh proses utama (1.0–6.0) beserta interaksi ke data store dan entitas eksternal terkait.
