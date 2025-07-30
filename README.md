## Persiapan Project Kasir

1. Local Server Laragon/Xampp 
2. Composer
3. Git
4. Node.js
5. php version >= 8.3

*Sisi eksternal*

1. Printer Thermal ukuran 58mm (Sambungkan printer ke komputer/laptop, jika belum terdaftar pada komputer/laptop maka install driver printer terlebih dahulu atau tonton video tutorial di youtube terkait masalah ini) setelah itu salin nama printer yang ada di properties printer dimenu sharing yang telah terdaftar pada komputer/laptop ke dalam menu setting printer pada web kasirnya.
    ~ printer untuk via dekstop akan berjalan hanya pada server local komputer/dalam satu jaringan yang terhubung ke printer (tanpa windows print).
    ~ printer untuk via mobile/bluetooth akan berjalan pada browser.

2. Scanner QR Code dengan Kameran maupun alat scanner (Opsional)

## Setup Project Kasir

Perhatikan untuk menjalankan atau mensetup project ini.

1. Jalankan perintah `composer install` dan `npm install` untuk menginstall depedensi yang dibutuhkan
2. Buat database terlebih dahulu
3. Konfigurasikan file .env dengan database yang telah dibuat
4. Jalankan perintah `php artisan migrate` dan `php artisan db:seed` (untuk membuat table database beserta user default)
5. Jalankan perintah `php artisan shield:generate --all` (untuk generate policy dari semua model)
6. Jalankan perintah `php artisan shield:super-admin` (untuk menambahkan/assign role super_admin ke user tertentu)
7. Jalankan perintah `php artisan storage:link`  untuk membuat symlink
8. Jalankan perintah `php artisan serve` untuk menjalankan projek
9. Buka browser dan kunjungi link http://127.0.0.1:8000
10. Login dengan email (admin@gmail.com) dan password (password)

Aplikasi siap di gunakan....





