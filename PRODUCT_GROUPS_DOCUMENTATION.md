# Dokumentasi Fitur Baru - Grup Produk & Harga Beli

## 📋 Fitur yang Ditambahkan

### 1. **Grup Produk (Product Groups)**
- **Tujuan**: Mengelompokkan produk berdasarkan kategori atau jenis tertentu untuk memudah pengelolaan
- **Lokasi**: Menu "Grup Produk" di navigasi admin
- **Fitur**:
  - Membuat, mengedit, dan menghapus grup produk
  - Setiap grup memiliki nama, deskripsi, dan status aktif/non-aktif
  - Multi-store support (setiap toko memiliki grup produknya sendiri)

### 2. **Harga Beli/Modal**
- **Tujuan**: Mencatat harga pembelian produk untuk menghitung laba
- **Lokasi**: Form produk - field "Harga Beli/Modal"
- **Fitur**:
  - Input harga beli dengan format rupiah otomatis
  - Kalkulasi laba otomatis (Harga Jual - Harga Beli)
  - Kalkulasi persentase laba otomatis

### 3. **Format Rupiah Otomatis**
- **Tujuan**: Memudahkan input dan tampilan harga dalam format rupiah Indonesia
- **Fitur**:
  - Auto-format saat input harga (contoh: 50000 → 50.000)
  - Prefix "Rp" otomatis
  - Parsing otomatis saat menyimpan data

### 4. **Kolom Laba di Tabel Produk**
- **Tujuan**: Melihat profit margin langsung di tabel produk
- **Fitur**:
  - Kolom "Laba" menampilkan selisih harga jual dan beli
  - Kolom "% Laba" menampilkan persentase keuntungan
  - Color coding: hijau untuk profit positif, merah untuk negative

## 🗄️ Struktur Database

### Tabel `product_groups`
```sql
- id (Primary Key)
- name (Nama grup)
- slug (URL-friendly name)
- description (Deskripsi grup)
- store_id (Foreign key ke stores)
- is_active (Status aktif/non-aktif)
- created_at, updated_at, deleted_at
```

### Tabel `products` (Kolom Baru)
```sql
- group_id (Foreign key ke product_groups) - NULLABLE
- cost_price (Harga beli/modal) - NULLABLE
```

## 🛠️ File yang Dimodifikasi/Ditambahkan

### Model Baru
- `app/Models/ProductGroup.php` - Model untuk grup produk

### Model yang Dimodifikasi
- `app/Models/Product.php` - Menambahkan relasi ke ProductGroup dan method profit

### Resource Filament
- `app/Filament/Resources/ProductGroupResource.php` - Resource untuk mengelola grup produk
- `app/Filament/Resources/ProductResource.php` - Menambahkan field grup dan harga beli

### Migrasi Database
- `2025_07_30_170423_create_product_groups_table.php` - Tabel grup produk

### Helper Functions
- `app/Helpers/CurrencyHelper.php` - Helper untuk format rupiah

### Seeder
- `database/seeders/ProductGroupSeeder.php` - Data awal grup produk

### JavaScript
- `public/js/currency-formatter.js` - Format rupiah real-time (optional)

## 🚀 Cara Menggunakan

### 1. Mengelola Grup Produk
1. Login ke admin panel
2. Pilih menu "Grup Produk" 
3. Klik "Buat Baru" untuk menambah grup
4. Isi nama dan deskripsi grup
5. Aktifkan status grup
6. Simpan

### 2. Menambah Produk dengan Grup dan Harga Beli
1. Buka menu "Produk"
2. Klik "Buat Baru" atau edit produk existing
3. Pilih "Grup Produk" dari dropdown
4. Isi "Harga Beli/Modal" (format: 50000 akan otomatis jadi 50.000)
5. Isi "Harga Jual" 
6. Sistem akan otomatis menghitung laba
7. Simpan produk

### 3. Melihat Analisis Laba
1. Buka halaman daftar produk
2. Lihat kolom "Laba" dan "% Laba"
3. Gunakan filter "Grup Produk" untuk melihat laba per grup
4. Sort berdasarkan laba untuk analisis

## 🎯 Manfaat Bisnis

### 1. **Organisasi Produk yang Lebih Baik**
- Produk terkelompok rapi berdasarkan jenis
- Mudah mencari produk dalam grup tertentu
- Filter dan sort yang lebih efisien

### 2. **Analisis Profitabilitas**
- Melihat margin keuntungan per produk
- Identifikasi produk dengan profit tinggi/rendah
- Basis untuk strategi pricing

### 3. **Laporan yang Lebih Detail**
- Analisis laba per grup produk
- Tracking performa kategori produk
- Optimasi inventory berdasarkan profitabilitas

### 4. **User Experience yang Lebih Baik**
- Input harga dengan format rupiah yang familiar
- Interface yang clean dan intuitive
- Informasi laba yang real-time

## 🔧 Helper Functions yang Tersedia

```php
// Format angka ke rupiah
formatRupiah(50000); // Output: "Rp 50.000"
formatRupiah(50000, false); // Output: "50.000"

// Parse rupiah ke angka
parseRupiah("Rp 50.000"); // Output: 50000

// Hitung profit
calculateProfit(75000, 50000); 
// Output: [
//   'profit' => 25000,
//   'profit_percentage' => 50.0,
//   'profit_formatted' => 'Rp 25.000'
// ]
```

## 📝 Data Awal

Grup produk default yang sudah dibuat:
1. **Makanan & Minuman** - Snack, es krim, dll
2. **Elektronik** - Handphone, charger, headset, dll  
3. **Fashion & Aksesoris** - Baju, tas, jam tangan, dll
4. **Kesehatan & Kecantikan** - Obat, vitamin, kosmetik, dll
5. **ATK & Perlengkapan** - Pulpen, buku, map, dll

## 🎨 UI/UX Improvements

### Tabel Produk
- Kolom laba dengan color coding (hijau/merah)
- Format rupiah yang konsisten
- Filter berdasarkan grup produk
- Toggle visibility untuk kolom opsional

### Form Produk  
- Input harga dengan format rupiah real-time
- Dropdown grup produk dengan search
- Helper text yang informatif
- Validasi input yang user-friendly

## 🔄 Multi-Store Support

Semua fitur mendukung multi-store:
- Setiap toko memiliki grup produknya sendiri
- Produk hanya terlihat di toko yang sesuai
- Scope otomatis berdasarkan toko yang dipilih
- Data terisolasi antar toko
