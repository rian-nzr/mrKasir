# Implementasi Kontrol Akses POS Berdasarkan Status Shift Kasir

## Overview

Implementasi ini memastikan bahwa halaman POS (`/admin/pos`) hanya dapat diakses jika kasir sudah membuka shift terlebih dahulu.

## Komponen yang Dimodifikasi

### 1. Middleware CheckCashierShift
**File:** `app/Http/Middleware/CheckCashierShift.php`

Middleware yang memeriksa apakah user memiliki shift kasir yang aktif sebelum mengakses halaman tertentu.

**Fitur:**
- Memeriksa status shift kasir aktif (status: 'open')
- **Support untuk Super Admin**: Dapat menggunakan `session('selected_store_id')` atau mengakses jika ada shift aktif manapun
- **Support untuk User Biasa**: Menggunakan `user->store_id` yang tetap
- Redirect ke `/admin` jika shift belum dibuka
- Menampilkan notifikasi peringatan
- Support untuk request AJAX/API

**Logika Store Selection:**
- **Super Admin** (`hasRole('super_admin')`):
  - Prioritas: `session('selected_store_id')`
  - Fallback: Jika ada shift aktif manapun di sistem
- **User Biasa**: Menggunakan `user->store_id`

### 2. Registrasi Middleware
**File:** `bootstrap/app.php`

Mendaftarkan middleware dengan alias `check.cashier.shift` untuk kemudahan penggunaan.

### 3. PosPage Enhancement
**File:** `app/Filament/Pages/PosPage.php`

**Modifikasi:**
- **mount()**: Pengecekan shift aktif saat halaman dimuat dengan support multi-user
- **shouldRegisterNavigation()**: Menyembunyikan item navigasi POS jika shift belum aktif
- **getNavigationBadge()**: Menampilkan badge "AKTIF" jika shift terbuka

**Logika Store Selection:**
- **Super Admin**: Dapat mengakses shift aktif dari store manapun
- **User Biasa**: Hanya dapat mengakses shift dari store yang ditugaskan

### 4. Pos Livewire Component Enhancement
**File:** `app/Livewire/Pos.php`

**Modifikasi:**
- **checkCashierShift()**: Method private untuk validasi shift dengan support multi-user
- **getCurrentShift()**: Method public untuk mendapatkan shift aktif dengan logic berbeda untuk Super Admin dan User biasa
- **mount()**: Pengecekan awal shift
- **addToOrder()**: Validasi shift sebelum menambah produk
- **checkout()**: Validasi shift sebelum checkout
- **saveTransaction()**: Validasi shift sebelum transaksi

**Perbaikan untuk Super Admin:**
- Dapat mengakses shift aktif dari store manapun jika `session('selected_store_id')` tidak ada
- Logic fallback untuk multi-store access

## Cara Kerja

1. **Akses Navigasi**: Item "Halaman Kasir" hanya muncul di navigasi jika ada shift aktif
2. **Akses Direct URL**: Jika user mengakses `/admin/pos` langsung tanpa shift aktif, akan di-redirect ke `/admin` dengan notifikasi
3. **Operasi POS**: Semua operasi (tambah produk, checkout, transaksi) memerlukan shift aktif
4. **Real-time Check**: Pengecekan dilakukan setiap kali operasi penting dijalankan

## Status Shift Kasir

- **STATUS_OPEN**: Shift aktif, POS dapat diakses
- **STATUS_CLOSED**: Shift ditutup, POS tidak dapat diakses
- **STATUS_SUSPENDED**: Shift ditangguhkan, POS tidak dapat diakses

## Membuka Shift Kasir

1. Akses menu "Shift Kasir" di admin panel
2. Klik tombol "Buka Shift Baru" (hanya muncul jika belum ada shift aktif)
3. Isi kas awal dan informasi lainnya
4. Setelah shift dibuka, navigasi POS akan muncul dan dapat diakses

## Error Handling

- Notifikasi user-friendly untuk semua kasus error
- Redirect yang aman ke halaman admin
- Logging untuk debugging (dalam middleware)

## Security

- Semua pengecekan berbasis database real-time
- Tidak mengandalkan session atau cookie
- Validasi berlapis (navigation, page mount, component operations)

## Testing

Untuk menguji implementasi:

1. **Super Admin Tanpa Store Selection**: 
   - Navigation POS muncul jika ada shift aktif manapun di sistem
   - Dapat mengakses POS selama ada shift aktif

2. **Super Admin Dengan Store Selection**:
   - Navigation POS muncul jika store yang dipilih memiliki shift aktif
   - Akses POS terbatas pada shift dari store yang dipilih

3. **User Biasa Tanpa Shift**: 
   - Navigation POS tidak muncul
   - Akses langsung `/admin/pos` redirect ke `/admin`

4. **User Biasa Dengan Shift Aktif**:
   - Navigation POS muncul dengan badge "AKTIF"
   - Semua fitur POS berfungsi normal

5. **Shift Ditutup Saat Operasi**:
   - Operasi POS akan gagal dengan notifikasi
   - User di-redirect ke admin panel

## Troubleshooting

### Masalah: Super Admin tidak bisa akses POS meski ada shift aktif
**Solusi:** 
- Super Admin `store_id = NULL`, gunakan `session('selected_store_id')` 
- Jika tidak ada store yang dipilih, sistem akan mengecek shift aktif manapun
- Pastikan ada shift dengan status 'open' di database

### Masalah: User biasa tidak bisa akses POS
**Solusi:**
- Pastikan `user.store_id` sudah diset
- Pastikan ada shift aktif untuk store tersebut
- Cek apakah shift memiliki status 'open'

## Dependencies

- Laravel Filament (untuk UI dan notifikasi)
- Model CashierShift yang sudah ada
- CashierShiftService untuk operasi shift
