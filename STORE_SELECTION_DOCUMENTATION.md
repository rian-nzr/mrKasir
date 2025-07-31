# Dokumentasi Sistem Pemilihan Toko untuk Super Admin

## Overview
Sistem telah diperbarui untuk memaksa Super Admin memilih toko terlebih dahulu sebelum dapat mengakses dashboard dan fitur-fitur lainnya. Ini memastikan data yang ditampilkan selalu sesuai dengan toko yang sedang dikelola.

## Fitur Utama

### 1. Middleware Pemaksaan Pemilihan Toko
- **File**: `app/Http/Middleware/EnsureStoreSelected.php`
- **Fungsi**: Memastikan Super Admin sudah memilih toko sebelum mengakses halaman admin
- **Logika**: 
  - Jika user bukan Super Admin → lanjut ke request
  - Jika Super Admin belum pilih toko → redirect ke halaman store selection
  - Jika sudah pilih toko → lanjut ke request

### 2. Halaman Store Selection
- **File**: `app/Filament/Pages/StoreSelection.php`
- **URL**: `/admin/store-selection`
- **Fitur**:
  - Dropdown untuk memilih toko
  - Validasi wajib pilih toko
  - UI yang user-friendly dengan informasi dan tips
  - Auto-redirect ke dashboard setelah berhasil pilih toko

### 3. Dashboard Widgets yang Store-Aware
- **TotalBalanceOverview**: Menampilkan total saldo sesuai toko yang dipilih
- **Auto-fallback**: Jika belum pilih toko, menampilkan pesan "Pilih Toko Terlebih Dahulu"

## Alur Kerja Sistem

### Super Admin Login Pertama Kali:
1. **Login** → Berhasil masuk sistem
2. **Redirect otomatis** → Ke halaman `/admin/store-selection`
3. **Pilih toko** → Dari dropdown yang tersedia
4. **Konfirmasi** → Klik tombol "Pilih Toko"
5. **Redirect** → Ke dashboard dengan data toko yang dipilih

### Super Admin yang Sudah Pernah Pilih Toko:
1. **Login** → Berhasil masuk sistem
2. **Langsung ke dashboard** → Menggunakan toko yang sebelumnya dipilih
3. **Data konsisten** → Semua widgets dan data sesuai toko yang dipilih

### Mengganti Toko (Store Switching):
1. **Klik user menu** → Di pojok kanan atas
2. **Klik "Ganti Toko"** → Menu dengan icon arrow-path
3. **Session cleared** → Data toko sebelumnya dihapus
4. **Redirect** → Ke halaman store selection untuk pilih ulang

## Komponen Teknis

### Middleware Integration:
```php
// AdminPanelProvider.php
->authMiddleware([
    Authenticate::class,
    \App\Http\Middleware\StoreMiddleware::class,
    \App\Http\Middleware\EnsureStoreSelected::class, // NEW
])
```

### Session Management:
- **Key**: `selected_store_id`
- **Storage**: Laravel Session
- **Scope**: Per user login session
- **Clearing**: Otomatis saat logout atau manual saat ganti toko

### User Menu Enhancement:
```php
'store_info' => MenuItem::make()
    ->label(function () {
        // Menampilkan info toko yang sedang aktif
        // Format: "Aktif: Nama Toko (Kode)" untuk Super Admin
        // Format: "Toko: Nama Toko" untuk user biasa
    })
    ->icon('heroicon-o-building-storefront')
    ->color('success'|'warning'|'danger') // Berdasarkan status

'change_store' => MenuItem::make()
    ->label('Ganti Toko')
    ->url(route('store.change'))
    ->visible(fn() => auth()->user()?->isSuperAdmin()) // Hanya untuk Super Admin
```

## UI/UX Improvements

### Store Selection Page:
- **Clean Design**: Centered layout dengan icon store
- **User Guidance**: Welcome message dan instruksi yang jelas
- **Informational Tips**: Penjelasan tentang sistem multi-store
- **Validation**: Real-time disable/enable tombol berdasarkan pilihan
- **Feedback**: Notifikasi sukses dengan nama toko yang dipilih

### Dashboard Widgets:
- **Conditional Display**: Widget menampilkan pesan jika belum pilih toko
- **Store-Specific Data**: Data yang ditampilkan sesuai toko yang dipilih
- **Visual Indicators**: Icon dan warna yang konsisten
- **Performance**: Query yang efficient dengan filtering store_id

### User Menu:
- **Store Status**: Info toko aktif dengan color coding
- **Quick Switch**: Tombol ganti toko yang mudah diakses
- **Visual Feedback**: Icon dan warna sesuai status

## Security & Validation

### Access Control:
- **Middleware Protection**: Semua route admin protected
- **Role-Based**: Fitur ganti toko hanya untuk Super Admin
- **Session Validation**: Validasi keberadaan selected_store_id

### Data Integrity:
- **Store Filtering**: Semua query payment method filtered by store_id
- **Consistent State**: Session state selalu konsisten dengan tampilan
- **Graceful Fallback**: Handling ketika toko tidak ditemukan

## Testing Scenarios

### Test Case 1: Super Admin First Login
1. Login sebagai Super Admin baru
2. Verify redirect ke `/admin/store-selection`
3. Verify form store selection muncul
4. Pilih toko dan verify redirect ke dashboard
5. Verify data dashboard sesuai toko yang dipilih

### Test Case 2: Super Admin Returning Login
1. Login sebagai Super Admin yang sudah pernah pilih toko
2. Verify langsung masuk dashboard
3. Verify data sesuai toko yang sebelumnya dipilih
4. Verify user menu menampilkan info toko aktif

### Test Case 3: Store Switching
1. Di dashboard, klik user menu
2. Klik "Ganti Toko"
3. Verify redirect ke store selection
4. Pilih toko berbeda
5. Verify data dashboard berubah sesuai toko baru

### Test Case 4: Non-Super Admin
1. Login sebagai user biasa (bukan Super Admin)
2. Verify langsung masuk dashboard
3. Verify tidak ada menu "Ganti Toko"
4. Verify data sesuai store_id user

## Error Handling

### Common Scenarios:
1. **Store tidak ditemukan**: Fallback ke store selection
2. **Session expired**: Auto-redirect ke login
3. **Invalid store_id**: Clear session dan redirect ke store selection
4. **Network error**: Graceful error message dengan retry option

### Monitoring Points:
- **Session duration**: Monitor selected_store_id persistence
- **Widget performance**: Query time untuk store-filtered data
- **User behavior**: Frequency of store switching
- **Error rates**: Failed store selection attempts

## Future Enhancements

### Planned Features:
1. **Recent Stores**: Dropdown dengan toko yang recently diakses
2. **Store Bookmarks**: Favorit toko untuk quick access
3. **Multi-Store Dashboard**: View gabungan dari multiple stores
4. **Store Permissions**: Granular permission per store untuk Super Admin
5. **Activity Log**: Log aktivitas switch store untuk audit
6. **Store Analytics**: Statistik usage per store
7. **Quick Store Info**: Tooltip dengan info detail toko
8. **Store Health Check**: Status kesehatan data per toko

## Migration Guide

### For Existing Super Admin:
1. **First Access**: Akan diminta pilih toko saat pertama login setelah update
2. **Data Consistency**: Data sebelumnya tidak terpengaruh
3. **No Data Loss**: Semua data historis tetap utuh
4. **Smooth Transition**: UI yang intuitif untuk adaptasi

### For Developers:
1. **Widget Updates**: Semua widget custom perlu update untuk handle store filtering
2. **Query Updates**: Pastikan semua query sudah include store_id filtering
3. **Session Handling**: Gunakan `Session::get('selected_store_id')` untuk mendapatkan store aktif
4. **Middleware Order**: Pastikan `EnsureStoreSelected` dipasang setelah authentication middleware
