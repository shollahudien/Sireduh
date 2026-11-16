# ERD (Entity Relationship Diagram) — SIRESTEDUH

Diagram relasi entitas berdasarkan model, relasi Eloquent, dan skema database aplikasi.

> Render otomatis di GitHub (Mermaid). Fokus pada entitas bisnis & operasional inti.

## ERD Utama

```mermaid
erDiagram
    USERS ||--o{ POS_TRANSACTIONS : "melakukan"
    USERS ||--o{ ACTIVITY_LOGS : "tercatat (opsional)"
    USERS ||--o{ PERSONAL_ACCESS_TOKENS : "memiliki"

    VISITORS ||--o{ RESERVATIONS : "membuat"

    UNITS ||--o{ RESERVATIONS : "dipesan pada"
    UNITS ||--o{ POS_TRANSACTION_DETAILS : "terjual/disewa pada"
    UNITS ||--o{ IMAGES : "memiliki (polymorphic)"

    PRODUCTS ||--o{ RESERVATION_ADDONS : "dipilih sebagai addon"
    PRODUCTS ||--o{ POS_TRANSACTION_DETAILS : "terjual pada"
    PRODUCTS ||--o{ IMAGES : "memiliki (polymorphic)"

    RESERVATIONS ||--o{ RESERVATION_ADDONS : "memiliki"
    RESERVATIONS ||--o| PAYMENTS : "memiliki"
    RESERVATIONS ||--o| TICKETS : "menghasilkan"

    POS_TRANSACTIONS ||--o{ POS_TRANSACTION_DETAILS : "memiliki"

    SETTINGS {
      bigint id PK
      string key UK
      text value
      datetime created_at
      datetime updated_at
    }

    ACTIVITY_LOGS {
      bigint id PK
      bigint user_id FK
      string event
      string subject_type
      bigint subject_id
      string request_id
      string method
      string path
      int status
      text properties
      datetime created_at
    }

    USERS {
      bigint id PK
      string name
      string email UK
      string password
      string role
      datetime created_at
      datetime updated_at
    }

    VISITORS {
      bigint id PK
      string name
      string whatsapp_number
      string email
      datetime created_at
      datetime updated_at
    }

    UNITS {
      bigint id PK
      string name
      string type
      decimal price
      int capacity
      int stock
      text description
      datetime created_at
      datetime updated_at
    }

    PRODUCTS {
      bigint id PK
      string name
      string category
      decimal price
      string type
      int stock
      boolean for_kasir_makanan
      datetime created_at
      datetime updated_at
    }

    RESERVATIONS {
      bigint id PK
      string order_code UK
      bigint visitor_id FK
      bigint unit_id FK
      string package_id
      string package_name
      int package_duration_hours
      date check_in_date
      time check_in_time
      date check_out_date
      time check_out_time
      decimal total_price
      string status
      datetime created_at
      datetime updated_at
    }

    RESERVATION_ADDONS {
      bigint id PK
      bigint reservation_id FK
      bigint product_id FK
      int quantity
      decimal price
      datetime created_at
      datetime updated_at
    }

    PAYMENTS {
      bigint id PK
      bigint reservation_id FK
      string method
      string provider
      string provider_order_id
      string provider_transaction_id
      string payment_type
      string snap_token
      string status
      string proof_image
      datetime paid_at
      datetime verified_at
      datetime expires_at
      datetime created_at
      datetime updated_at
    }

    TICKETS {
      bigint id PK
      bigint reservation_id FK
      string unique_hash_sha256 UK
      boolean is_used
      datetime used_at
      datetime created_at
      datetime updated_at
    }

    POS_TRANSACTIONS {
      bigint id PK
      bigint user_id FK
      decimal total_amount
      string payment_method
      datetime created_at
      datetime updated_at
    }

    POS_TRANSACTION_DETAILS {
      bigint id PK
      bigint pos_transaction_id FK
      bigint product_id FK
      bigint unit_id FK
      int quantity
      decimal subtotal
      datetime created_at
      datetime updated_at
    }

    IMAGES {
      bigint id PK
      string imageable_type
      bigint imageable_id
      string path
      int order
      string alt
      datetime created_at
      datetime updated_at
    }

    GLAMPING_PACKAGES {
      bigint id PK
      string code UK
      string name
      int duration_hours
      decimal price
      boolean is_active
      int sort_order
      datetime created_at
      datetime updated_at
    }

    AULA_PACKAGES {
      bigint id PK
      string code UK
      string name
      int duration_hours
      decimal price
      boolean is_active
      int sort_order
      datetime created_at
      datetime updated_at
    }

    CHATBOT_MESSAGES {
      bigint id PK
      string session_id
      string role
      text content
      string user_name
      text meta
      datetime created_at
      datetime updated_at
    }

    PERSONAL_ACCESS_TOKENS {
      bigint id PK
      string tokenable_type
      bigint tokenable_id
      string name
      string token UK
      text abilities
      datetime last_used_at
      datetime expires_at
      datetime created_at
      datetime updated_at
    }
```

## Catatan Relasi

| Relasi | Kardinalitas | Penjelasan |
|---|---|---|
| Visitor — Reservation | 1 : N | Satu tamu dapat memiliki banyak reservasi. |
| Unit — Reservation | 1 : N (opsional) | Reservasi dapat terkait ke satu unit; `unit_id` dapat null pada kasus tertentu sesuai alur. |
| Reservation — Reservation Addon | 1 : N | Satu reservasi dapat memiliki banyak item tambahan. |
| Product — Reservation Addon | 1 : N | Produk (addon) dapat dipakai pada banyak addon reservasi. |
| Reservation — Payment | 1 : 0..1 | Setiap reservasi memiliki maksimal satu data pembayaran aktif terkait. |
| Reservation — Ticket | 1 : 0..1 | E-ticket dibuat/terkait untuk reservasi yang valid/terbayar sesuai alur. |
| User — Pos Transaction | 1 : N | Kasir/petugas mencatat transaksi POS. |
| Pos Transaction — Pos Transaction Detail | 1 : N | Satu transaksi terdiri dari banyak baris item. |
| Product/Unit — Pos Transaction Detail | 1 : N (opsional) | Detail dapat merujuk ke produk atau unit. |
| Unit/Product — Image | 1 : N (polymorphic) | Gambar dapat dimiliki oleh unit atau produk. |
| User — Activity Log | 1 : N (opsional) | Aktivitas dapat terkait user login atau tamu (user null). |

## Entitas Referensi Paket

- **`glamping_packages`** dan **`aula_packages`** menyimpan master paket (kode, durasi, harga, status aktif).
- Pada `reservations`, informasi paket yang dipilih dapat disalin/disimpan (`package_id`, `package_name`, `package_duration_hours`) untuk menjaga konsistensi historis pesanan.
