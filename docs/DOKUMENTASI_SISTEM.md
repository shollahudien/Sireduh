# Dokumentasi Sistem — SIRESTEDUH

Dokumen indeks untuk artefak analisis dan perancangan sistem **SIRESTEDUH**.

## Daftar Dokumen

| Dokumen | Deskripsi | Tautan |
|---|---|---|
| Kamus Data | Definisi tabel, kolom, tipe, relasi, dan catatan domain | [KAMUS_DATA.md](KAMUS_DATA.md) |
| DFD | **DFD lengkap**: Level 0 (Konteks), Level 1, dan Level 2 untuk proses 1.0–6.0 | [DFD.md](DFD.md) |
| Flowchart | **Flowchart sistem lengkap (end-to-end)** + alur detail reservasi, pembayaran, verifikasi, check-in, POS, maintenance, autentikasi | [FLOWCHART.md](FLOWCHART.md) |
| ERD | **ERD lengkap**: entitas, atribut kunci, relasi & kardinalitas (Mermaid) | [ERD.md](ERD.md) |
| UAT | User Acceptance Testing (skenario, pertanyaan & jawaban/hasil diharapkan per fitur) | [UAT.md](UAT.md) |

## Cakupan

Dokumentasi ini disusun berdasarkan:
- Skema database & migrasi aplikasi (`database/migrations`, `database-schema`)
- Model & relasi Eloquent (`app/Models`)
- Rute web/API dan middleware akses (`routes/web.php`, `routes/api.php`, `bootstrap/app.php`)
- Layanan/proses bisnis utama (`app/Services`, controller terkait reservasi, pembayaran, check-in, POS, admin)

> Diagram menggunakan **Mermaid** sehingga dapat langsung dirender di GitHub. Jika perlu versi gambar (PNG/SVG) untuk laporan, diagram dapat diekspor dari Mermaid Live Editor atau alat kompatibel.
