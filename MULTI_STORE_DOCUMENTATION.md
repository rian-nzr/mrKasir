# Sistem Multi-Toko - Dokumentasi

## 📋 Overview
Sistem ini memungkinkan aplikasi kasir untuk mengelola multiple toko dengan data yang terpisah dan role-based access control.

## 🏗️ Fitur yang Diimplementasi

### 1. **Database Structure**
- **Table `stores`**: Menyimpan data toko
- **Foreign Key `store_id`**: Ditambahkan ke table users, products, categories, orders
- **Global Scope**: Otomatis filter data berdasarkan toko yang dipilih

### 2. **Role-Based Access Control**
- **Super Admin**: Dapat memilih toko dan mengelola semua data
- **Kasir/Admin**: Otomatis terikat dengan satu toko tertentu

### 3. **Store Selection System**
- **Middleware**: Menangani logic pemilihan toko
- **Session Management**: Menyimpan toko yang dipilih super admin
- **Auto Assignment**: User non-super admin otomatis menggunakan toko mereka

## 👥 User Accounts (Password: `password`)

### Super Admin
- **Email**: `superadmin@example.com`
- **Akses**: Dapat memilih dan beralih antar toko
- **Fitur**: Dapat mengelola data toko, user, dan semua master data

### Kasir Toko A
- **Email**: `kasir.tokoa@example.com`
- **Akses**: Hanya data Toko A
- **Fitur**: Dapat mengelola transaksi dan melihat produk

### Kasir Toko B
- **Email**: `kasir.tokob@example.com`
- **Akses**: Hanya data Toko B
- **Fitur**: Dapat mengelola transaksi dan melihat produk

### Admin Toko A
- **Email**: `admin.tokoa@example.com`
- **Akses**: Hanya data Toko A
- **Fitur**: Dapat mengelola produk, kategori, dan transaksi

## 🏪 Data Toko

### Toko A
- **Nama**: Toko A
- **Kode**: TOKO-A
- **Alamat**: Jl. Contoh No. 1, Jakarta

### Toko B
- **Nama**: Toko B
- **Kode**: TOKO-B
- **Alamat**: Jl. Contoh No. 2, Bandung

## 🔧 Cara Kerja Sistem

### 1. **Login Process**
```
User Login → Middleware Check Role
├── Super Admin → Redirect ke Store Selection
└── Non-Super Admin → Auto assign store dari user.store_id
```

### 2. **Data Filtering**
- Semua model dengan `store_id` menggunakan `StoreScope`
- Data otomatis ter-filter berdasarkan `selected_store_id` di session
- Create/Update otomatis assign `store_id`

### 3. **Navigation**
- Super Admin: Melihat menu "Manajemen Toko"
- User Menu: Menampilkan toko aktif dan tombol "Ganti Toko" (super admin)

## 📁 File Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   └── StoreSelectionController.php
│   └── Middleware/
│       └── StoreMiddleware.php
├── Models/
│   ├── Store.php
│   ├── Scopes/
│   │   └── StoreScope.php
│   ├── User.php (updated)
│   ├── Product.php (updated)
│   ├── Category.php (updated)
│   └── Order.php (updated)
└── Filament/
    └── Resources/
        ├── StoreResource.php
        └── UserResource.php (updated)

database/
├── migrations/
│   ├── create_stores_table.php
│   ├── add_store_id_to_users_table.php
│   ├── add_store_id_to_products_table.php
│   ├── add_store_id_to_orders_table.php
│   └── add_store_id_to_categories_table.php
└── seeders/
    ├── StoreSeeder.php
    ├── RoleSeeder.php
    └── UserSeeder.php

resources/views/
├── store-selection.blade.php
└── store-not-assigned.blade.php

routes/
└── web.php (updated)
```

## 🚀 Testing Guide

### 1. **Test Super Admin Flow**
1. Login dengan `superadmin@example.com`
2. Pilih toko dari halaman selection
3. Akses menu "Manajemen Toko"
4. Coba ganti toko dari user menu

### 2. **Test Kasir/Admin Flow**
1. Login dengan `kasir.tokoa@example.com`
2. Otomatis masuk ke dashboard Toko A
3. Coba akses menu produk (hanya data Toko A)
4. Tidak ada menu "Manajemen Toko"

### 3. **Test Data Separation**
1. Login sebagai super admin, pilih Toko A
2. Buat produk baru
3. Ganti ke Toko B
4. Produk dari Toko A tidak terlihat

## ⚙️ Configuration

### Middleware Registration
```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'store' => \App\Http\Middleware\StoreMiddleware::class,
    ]);
})
```

### Routes
```php
// routes/web.php
Route::middleware(['auth'])->group(function () {
    Route::get('/store/select', [StoreSelectionController::class, 'index'])->name('store.select');
    Route::post('/store/select', [StoreSelectionController::class, 'select'])->name('store.select');
    Route::get('/store/not-assigned', [StoreSelectionController::class, 'notAssigned'])->name('store.not-assigned');
});
```

## 🔒 Security Features

1. **Role-based Access**: Hanya super admin yang bisa akses store management
2. **Data Isolation**: Global scope memastikan data ter-filter per toko
3. **Session Security**: Store selection disimpan di session yang aman
4. **Auto Assignment**: User non-super admin tidak bisa akses data toko lain

## 📝 Notes

- Semua data existing akan perlu di-assign ke toko tertentu
- Backup database sebelum migrasi di production
- Test thoroughly sebelum deploy ke production
- Consider performance impact dari global scope pada query besar
