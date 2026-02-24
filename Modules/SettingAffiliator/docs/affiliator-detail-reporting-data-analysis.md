# Analisis Data: Detail Reporting Affiliator

## Kebutuhan Frontend

1. **Filter** (paling atas): pilih **Semua Kode Promo** atau **satu kode promo tertentu**. Semua data di bawah mengikuti filter ini.

2. **Summary cards** (setelah filter):
   - Total Register Usage (jumlah penggunaan saat registrasi)
   - Total Pembelian Baru (total nilai + jumlah transaksi)
   - Total Perpanjangan (total nilai + jumlah transaksi)

3. **Tab Register Usage**: tabel — No, Tanggal, Kode Promo, Nama Client, Email.

4. **Tab Pembelian Baru**: tabel — No, Tanggal, Kode Promo, Invoice, Client, Total Pembelian, Diskon, Komisi Affiliator.

5. **Tab Perpanjangan**: tabel — kolom sama dengan Pembelian Baru.

---

## Tabel yang Terlibat

### 1. `promo_codes` (backoffice)

| Kolom relevan | Keterangan |
|---------------|------------|
| id | UUID, PK |
| code | Kode promo (unique) |
| name | Nama promo |
| affiliator_user_id | UUID user affiliator (nullable) — **untuk filter affiliator** |
| discount_* | Konfigurasi diskon (tidak dipakai langsung di reporting) |

**Relasi:** `affiliator_user_id` → `users.id` (user affiliator).

---

### 2. `promo_code_usages` (backoffice)

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | uuid | PK |
| promo_code_id | uuid | FK → promo_codes.id |
| customer_id | uuid nullable | Contact/customer (backoffice contacts.id) — dipakai saat checkout |
| company_id | uuid nullable | Company (client) |
| contact_id | string nullable | Contact id (saat registrasi) — dipakai untuk Register Usage |
| discount_amount | decimal | Nilai diskon |
| purchase_amount | decimal | Nilai pembelian (subtotal) |
| metadata | text/json | Bisa berisi source, invoice_id, email (registrasi), dll. |
| is_ref | boolean | true = dari registrasi (referral) |
| status | char(1) | **B** = Pembelian baru, **P** = Perpanjangan, **R** = Register (is_ref) |
| invoice_id | uuid nullable | FK → invoices.id — ada saat status B/P (dari checkout) |
| created_at | timestamp | **Tanggal** untuk kolom “Tanggal” di tabel |

**Relasi:**
- `promo_code_id` → `promo_codes.id`
- `customer_id` → `contacts.id` (backoffice)
- `invoice_id` → `invoices.id`

**Sumber data per status:**
- **R (Register):** dari `ContactApiController` — pakai `contact_id`, `customer_id` null, `metadata` bisa berisi email.
- **B / P:** dari `CheckoutApiController` (recordPromoUsageForInvoice) — pakai `invoice_id`, `customer_id` dari invoice, `contact_id` null.

---

### 3. `contacts` (backoffice)

| Kolom | Keterangan |
|-------|------------|
| id | UUID, PK |
| company_id | UUID (company di client/Retalk) |
| code | Kode contact |
| name | **Nama client** |
| phone | Telepon |
| email | **Email client** |
| is_customer | Flag customer |

Dipakai untuk:
- **Register Usage:** nama & email client — via `contact_id` (atau dari metadata jika contact_id tidak dipakai).
- **Pembelian Baru / Perpanjangan:** nama client (dan bisa email) — via `customer_id` pada usage, atau via invoice → customer.

---

### 4. `invoices` (backoffice)

| Kolom relevan | Keterangan |
|---------------|------------|
| id | UUID, PK |
| code | **Kode invoice** (untuk kolom “Invoice”) |
| customer_id | FK → contacts.id |
| customer_name | Nama pelanggan (denormalized) |
| customer_email | Email (denormalized) |
| subtotal | Bisa dipakai sebagai “Total Pembelian” |
| discount_amount | Diskon |
| total | Total akhir |

Relasi: `promo_code_usages.invoice_id` → `invoices.id`. Untuk usage B/P kita punya `invoice_id`, jadi bisa ambil code, customer_name, customer_email, subtotal, discount_amount.

---

### 5. `affiliator_configs` (backoffice)

| Kolom | Keterangan |
|-------|------------|
| user_id | FK → users.id (affiliator) |
| commission_value_registrasi | Persen komisi pembelian baru (B) |
| commission_value_perpanjangan | Persen komisi perpanjangan (P) |

Dipakai untuk menghitung **Komisi Affiliator** per baris (dan total komisi).

---

## Relasi Antar Tabel (Ringkas)

```
users (affiliator)
  └── affiliator_configs (user_id)
  └── promo_codes (affiliator_user_id)

promo_codes
  └── promo_code_usages (promo_code_id)

promo_code_usages
  ├── customer_id → contacts.id
  ├── contact_id  → contacts.id (untuk R; tipe string di DB)
  └── invoice_id  → invoices.id (untuk B/P)

invoices
  └── customer_id → contacts.id
```

---

## Mapping Kebutuhan Frontend → Sumber Data

### Filter
- **Semua:** pakai semua `promo_codes` dengan `affiliator_user_id = {affiliator_user_id}`.
- **Satu kode:** pakai `promo_code_id = {pilihan}`.

Semua query di bawah **filter by** `promo_code_id` (satu id atau list id dari promo affiliator).

---

### Summary: Total Register Usage
- **Sumber:** `promo_code_usages`
- **Kondisi:** `promo_code_id IN (...)` dan `status = 'R'` (atau `is_ref = true` jika status belum selalu di-set).
- **Nilai:** `COUNT(*)`.

---

### Summary: Total Pembelian Baru
- **Sumber:** `promo_code_usages` (status = 'B').
- **Kondisi:** `promo_code_id IN (...)` dan `status = 'B'`.
- **Total nilai:** `SUM(discount_amount)` atau `SUM(purchase_amount)` — tergantung definisi “total” (biasanya total transaksi = sum of purchase_amount atau dari invoice).
- **Jumlah transaksi:** `COUNT(*)`.

---

### Summary: Total Perpanjangan
- Sama seperti Pembelian Baru, dengan **status = 'P'**.

---

### Tab: Register Usage (tabel)
- **Sumber:** `promo_code_usages` + `promo_codes` + `contacts`.
- **Kondisi:** `status = 'R'` (dan filter promo_code_id).
- **Kolom:**
  - No: nomor urut.
  - Tanggal: `promo_code_usages.created_at`.
  - Kode Promo: `promo_codes.code` (join via promo_code_id).
  - Nama Client: dari `contacts` — join `contacts.id` = `promo_code_usages.contact_id` (cast/trim jika string). Jika contact_id kosong, bisa fallback ke `metadata->email` atau “-”.
  - Email: `contacts.email` atau dari metadata.

**Catatan:** Di registration flow, `contact_id` di usage di-set ke `$customer->id` (contact). Tipe kolom `contact_id` di DB adalah string; perlu dipastikan join ke `contacts.id` (uuid) konsisten (cast ke string atau uuid).

---

### Tab: Pembelian Baru (tabel)
- **Sumber:** `promo_code_usages` + `promo_codes` + `invoices` + (opsional) `contacts`.
- **Kondisi:** `status = 'B'` dan filter promo_code_id.
- **Kolom:**
  - No: nomor urit.
  - Tanggal: `promo_code_usages.created_at`.
  - Kode Promo: `promo_codes.code`.
  - Invoice: `invoices.code` (join usage.invoice_id → invoices.id).
  - Client: `invoices.customer_name` atau `contacts.name` (dari usage.customer_id atau invoice.customer_id).
  - Total Pembelian: `promo_code_usages.purchase_amount` atau `invoices.subtotal`.
  - Diskon: `promo_code_usages.discount_amount` atau `invoices.discount_amount`.
  - Komisi Affiliator: `discount_amount * (commission_value_registrasi / 100)` dengan persen dari `affiliator_configs` (user affiliator = pemilik promo).

---

### Tab: Perpanjangan (tabel)
- Sama seperti Pembelian Baru, dengan **status = 'P'** dan komisi pakai **commission_value_perpanjangan**.

---

## Query yang Diperlukan (Konsep)

1. **Daftar promo affiliator (untuk filter):**  
   `PromoCode::where('affiliator_user_id', $userId)->orderBy('code')->get(['id','code','name'])`.

2. **Summary (dengan filter promo):**
   - Register: `PromoCodeUsage::whereIn('promo_code_id', $promoIds)->where('status','R')->count()`.
   - Pembelian Baru: idem `status = 'B'` → count + sum(discount_amount) atau sum(purchase_amount).
   - Perpanjangan: idem `status = 'P'`.

3. **Tabel Register Usage:**  
   `PromoCodeUsage::with(['promoCode'])->whereIn('promo_code_id', $promoIds)->where('status','R')->orderBy('created_at','desc')`  
   + join/ambil contact by contact_id (dan fallback metadata untuk nama/email jika perlu).

4. **Tabel Pembelian Baru / Perpanjangan:**  
   `PromoCodeUsage::with(['promoCode','invoice'])->whereIn('promo_code_id', $promoIds)->where('status','B')` (atau 'P')  
   + config affiliator untuk hitung komisi per baris.

---

## Catatan Implementasi

- **contact_id** di `promo_code_usages` tipe string; saat join ke `contacts.id` (uuid) perlu keseragaman tipe (mis. cast ke string atau uuid).
- **Register Usage** bisa saja hanya punya `metadata->email` tanpa contact_id; tampilkan email dari metadata dan nama “-” atau dari contact bila ada.
- **Komisi per baris:** ambil sekali `AffiliatorConfig` untuk user affiliator; untuk tiap usage B pakai `commission_value_registrasi`, untuk P pakai `commission_value_perpanjangan`.
- Filter “Semua Kode Promo” = gunakan semua `promo_code_id` milik affiliator; filter “Satu kode” = satu `promo_code_id`. Semua endpoint/query menerima parameter `promo_code_id` (nullable = all) atau list promo ids.

Dokumen ini bisa dipakai sebagai acuan untuk implementasi API/datatable dan penghitungan komisi di backend (controller/service).
