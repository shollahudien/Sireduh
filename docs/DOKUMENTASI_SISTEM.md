# Dokumentasi Sistem — SIRESTEDUH

Dokumen indeks untuk artefak analisis dan perancangan sistem **SIRESTEDUH**.

## Daftar Dokumen

| Dokumen | Deskripsi | Tautan |
|---|---|---|
| Kamus Data | Definisi tabel, kolom, tipe, relasi, dan catatan domain | [KAMUS_DATA.md](KAMUS_DATA.md) |
| DFD | Diagram Konteks (Level 0), DFD Level 1, dan DFD Level 2 proses utama | [DFD.md](DFD.md) |
| Flowchart | Alur proses reservasi, pembayaran (manual/gateway), verifikasi, check-in, POS, dan maintenance | [FLOWCHART.md](FLOWCHART.md) |
| ERD | Entity Relationship Diagram (entitas, atribut kunci, dan relasi) | [ERD.md](ERD.md) |
| UAT | User Acceptance Testing (skenario, pertanyaan & jawaban/hasil diharapkan per fitur) | [UAT.md](UAT.md) |

## Cakupan

Dokumentasi ini disusun berdasarkan:
- Skema database & migrasi aplikasi (`database/migrations`, `database-schema`)
- Model & relasi Eloquent (`app/Models`)
- Rute web/API dan middleware akses (`routes/web.php`, `routes/api.php`, `bootstrap/app.php`)
- Layanan/proses bisnis utama (`app/Services`, controller terkait reservasi, pembayaran, check-in, POS, admin)

> Diagram menggunakan **Mermaid** sehingga dapat langsung dirender di GitHub. Jika perlu versi gambar (PNG/SVG) untuk laporan, diagram dapat diekspor dari Mermaid Live Editor atau alat kompatibel.
