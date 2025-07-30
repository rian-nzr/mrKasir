# Dokumentasi Sistem Pembayaran POS

## Overview
Sistem POS telah diperbarui dengan fitur pembayaran yang lebih komprehensif, termasuk input jumlah pembayaran, perhitungan kembalian otomatis, dan pencatatan transaksi ke saldo metode pembayaran.

## Fitur Baru

### 1. Input Jumlah Pembayaran
- Tersedia khusus untuk metode pembayaran tunai (cash)
- Validasi otomatis untuk memastikan jumlah pembayaran tidak kurang dari total belanja
- Field input dengan format mata uang Rupiah

### 2. Perhitungan Kembalian Otomatis
- Kembalian dihitung secara real-time ketika kasir memasukkan jumlah pembayaran
- Tampilan kembalian dengan format yang jelas (hijau jika positif, merah jika negatif)
- Kembalian hanya ditampilkan untuk pembayaran tunai

### 3. Integrasi dengan Saldo Metode Pembayaran
- Setiap transaksi otomatis menambahkan nominal ke saldo metode pembayaran yang dipilih
- Pencatatan transaksi lengkap di tabel `payment_method_transactions`
- Update balance di tabel `payment_methods`

### 4. Dashboard Total Saldo
- Menampilkan total saldo dari semua metode pembayaran aktif
- Breakdown saldo per jenis: Tunai, E-Wallet, Transfer
- Tersedia di halaman POS dan admin panel
- Update real-time setiap ada transaksi

## Alur Kerja Sistem

### Untuk Pembayaran Tunai (Cash):
1. Kasir memilih produk dan memasukkannya ke keranjang
2. Kasir memilih metode pembayaran "Tunai"
3. Form input "Jumlah Dibayar" muncul
4. Kasir memasukkan jumlah uang yang diberikan pelanggan
5. Sistem otomatis menghitung dan menampilkan kembalian
6. Validasi: jumlah yang dibayar harus >= total belanja
7. Setelah checkout, sistem:
   - Membuat order baru
   - Mengurangi stok produk
   - Menambahkan nominal ke saldo kas
   - Mencatat transaksi di payment_method_transactions

### Untuk Pembayaran Non-Tunai (Transfer/E-Wallet):
1. Kasir memilih produk dan memasukkannya ke keranjang
2. Kasir memilih metode pembayaran non-tunai
3. Form pembayaran tidak menampilkan input jumlah dibayar
4. Sistem otomatis set jumlah dibayar = total belanja (tidak ada kembalian)
5. Setelah checkout, sistem melakukan hal yang sama seperti pembayaran tunai

## Validasi dan Error Handling

### Validasi Input:
- Nama customer: string, maksimal 255 karakter
- Metode pembayaran: wajib dipilih
- Jumlah dibayar (khusus tunai): wajib, numerik, minimal sama dengan total belanja
- Keranjang tidak boleh kosong

### Pesan Error:
- "Jumlah yang dibayar tidak boleh kurang dari total belanja"
- "Jumlah yang dibayar wajib diisi untuk pembayaran tunai"
- "Keranjang kosong"
- "Stok barang tidak mencukupi"

## Database Schema

### Tabel payment_method_transactions:
- `from_payment_method_id`: ID metode pembayaran asal (null untuk penjualan)
- `to_payment_method_id`: ID metode pembayaran tujuan
- `type`: 'topup' untuk penjualan
- `amount`: nominal transaksi
- `balance_before`: saldo sebelum transaksi
- `balance_after`: saldo setelah transaksi
- `description`: deskripsi transaksi
- `reference_number`: nomor referensi unik
- `created_by`: ID user yang melakukan transaksi

### Update payment_methods:
- `balance`: saldo terkini setelah transaksi

## Fitur UI/UX

### Form Pembayaran:
- Grid layout 2 kolom untuk nama customer dan metode pembayaran
- Grid layout 2 kolom tambahan untuk jumlah dibayar dan kembalian (hanya muncul untuk tunai)
- Real-time calculation saat input berubah

### Dashboard Total Saldo:
- Card informasi di bagian atas halaman POS
- Breakdown per jenis: Tunai, E-Wallet, Transfer, dan Total Keseluruhan
- Color coding untuk setiap jenis pembayaran
- Format mata uang yang konsisten

### Sidebar Cart:
- Menampilkan total belanja
- Menampilkan jumlah dibayar dan kembalian (khusus tunai)
- Format mata uang yang konsisten

### Modal Konfirmasi:
- Menampilkan ringkasan pembayaran
- Detail total, dibayar, dan kembalian (khusus tunai)
- Opsi cetak struk

## Keamanan dan Performance

### Keamanan:
- Validasi server-side untuk semua input
- Proteksi terhadap input negatif
- User authentication untuk transaksi

### Performance:
- Lazy loading untuk metode pembayaran
- Efficient query untuk update balance
- Real-time calculation tanpa server round-trip

## Testing Scenarios

### Test Case 1: Pembayaran Tunai Normal
1. Tambah produk ke keranjang (total: Rp 50,000)
2. Pilih metode pembayaran "Tunai"
3. Input jumlah dibayar: Rp 100,000
4. Verify kembalian: Rp 50,000
5. Checkout dan verify saldo kas bertambah Rp 50,000

### Test Case 2: Pembayaran Tunai Pas
1. Total belanja: Rp 25,000
2. Input jumlah dibayar: Rp 25,000
3. Verify kembalian: Rp 0
4. Checkout berhasil

### Test Case 3: Pembayaran Kurang
1. Total belanja: Rp 30,000
2. Input jumlah dibayar: Rp 20,000
3. Verify error message muncul
4. Checkout tidak bisa dilakukan

### Test Case 4: Pembayaran Transfer
1. Pilih metode pembayaran "Transfer Bank"
2. Verify field jumlah dibayar tidak muncul
3. Checkout langsung tanpa input tambahan
4. Verify saldo transfer bertambah sesuai total belanja

## Maintenance

### Monitoring:
- Monitor saldo metode pembayaran secara berkala
- Cek konsistensi data antara orders dan payment_method_transactions
- Log semua transaksi untuk audit trail

### Backup:
- Backup reguler tabel payment_method_transactions
- Backup saldo payment_methods sebelum migrasi atau update besar

## Future Enhancements

### Planned Features:
1. Laporan pembayaran per metode
2. Rekonsiliasi saldo otomatis
3. Integrasi dengan sistem akuntansi
4. Multi-currency support
5. Split payment (pembayaran dengan beberapa metode)
6. Export data total saldo ke Excel/PDF
7. Notifikasi ketika saldo mencapai batas minimum
8. Grafik trend saldo per periode
