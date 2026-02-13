# Laporan Implementasi Module Affiliator

Laporan ini merangkum langkah-langkah yang telah diambil untuk membuat layout dan routing terpisah untuk role **Affiliator** dalam aplikasi Laravel ini.

## 1. Middleware Keamanan (`CheckAffiliatorRole`)

Untuk membedakan akses antara Admin dan Affiliator, middleware baru telah dibuat.

- **File**: `app/Http/Middleware/CheckAffiliatorRole.php`
- **Fungsi**: Memeriksa apakah user yang sedang login memiliki role `affiliator`.
    - Jika **YA**: Request dilanjutkan.
    - Jika **TIDAK**: User diarahkan kembali ke dashboard utama dengan pesan error.
- **Registrasi**: Middleware ini didaftarkan di `app/Http/Kernel.php` dengan alias `'affiliator'`.

## 2. Struktur Routing

Routing untuk module Affiliator telah diamankan menggunakan middleware group.

- **File**: `Modules/Affiliator/routes/web.php`
- **Config**:
    ```php
    Route::group(['middleware' => ['auth', 'affiliator']], function () {
        Route::resource('affiliator', AffiliatorController::class)->names('affiliator');
    });
    ```
- **Base URL**: `/affiliator` (misal: `domain.com/affiliator`)

## 3. Layout Terpisah (Master Layout)

Sesuai permintaan, layout untuk Affiliator dipisahkan dari Admin agar bisa dikustomisasi secara independen tanpa mengganggu tampilan Admin.

- **Lokasi Template**: `resources/views/template/affiliator/`
    - `main.blade.php`: Layout utama yang memanggil partials lainnya.
    - `navbar.blade.php`: Header navigasi (di-copy dari admin, siap dimodifikasi).
    - `sidebar.blade.php`: Menu samping. **Sudah disederhanakan** hanya menampilkan menu "Dashboard" yang mengarah ke `/affiliator`.
    - `style.blade.php` & `script.blade.php`: Aset CSS dan JS.

- **Penggunaan**:
  View pada module Affiliator sekarang meng-extend layout ini:
    ```blade
    @extends('template.affiliator.main')
    ```

## 4. Logika Autentikasi (`AuthenticationController`)

Proses login telah diperbarui untuk menangani redirection otomatis berdasarkan role user.

- **File**: `Modules/Authentication/App/Http/Controllers/AuthenticationController.php`
- **Logika**:
  Setelah user berhasil login (email & password cocok):
    1.  Cek apakah user punya role `affiliator`.
    2.  Jika **YA** -> Redirect ke `route('affiliator.index')`.
    3.  Jika **TIDAK** -> Redirect ke `route('dashboard.index')` (Dashboard Admin biasa).

## 5. Ringkasan File yang Diubah/Dibuat

| Tipe               | File Path                                                 | Keterangan                      |
| :----------------- | :-------------------------------------------------------- | :------------------------------ |
| **Middleware**     | `app/Http/Middleware/CheckAffiliatorRole.php`             | Logic pengecekan role           |
| **Kernel**         | `app/Http/Kernel.php`                                     | Register alias middleware       |
| **Controller**     | `Modules/Authentication/.../AuthenticationController.php` | Redirect logic login            |
| **Route**          | `Modules/Affiliator/routes/web.php`                       | Route group protection          |
| **View (Main)**    | `resources/views/template/affiliator/main.blade.php`      | Master layout khusus affiliator |
| **View (Sidebar)** | `resources/views/template/affiliator/sidebar.blade.php`   | Menu sidebar khusus affiliator  |
| **Module View**    | `Modules/Affiliator/resources/views/index.blade.php`      | Halaman dashboard awal          |

## 6. Langkah Selanjutnya

Module ini sekarang sudah siap untuk dikembangkan lebih lanjut. Beberapa hal yang bisa dilakukan:

1.  **Dashboard Widget**: Mengisi halaman `index.blade.php` dengan widget statistik khusus affiliator (misal: total komisi, jumlah referral).
2.  **Menu Baru**: Menambahkan menu baru di `resources/views/template/affiliator/sidebar.blade.php` sesuai fitur yang akan dibuat.
3.  **Profile**: Menyesuaikan dropdown profile di navbar jika affiliator membutuhkan menu profile yang berbeda.

---

**Status**: âœ… Selesai & Siap Digunakan
