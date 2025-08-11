# 🧪 TEST CASES EXECUTION GUIDE
## Panduan Eksekusi Pengujian Step-by-Step

---

## 🚀 **QUICK START TESTING**

### **Phase 1: Critical Path Testing (1-2 jam)**
Pengujian jalur utama yang paling penting untuk verifikasi cepat:

#### **Test Suite A: User Access & Authentication**
```
⏱️ Estimasi: 20 menit

TC-A001: Super Admin Login & Store Selection
1. Buka browser → http://127.0.0.1:8000
2. Login: admin@example.com / password
3. ✅ Verifikasi: Dashboard admin terbuka
4. Klik dropdown store (jika ada)
5. ✅ Verifikasi: Dapat memilih store
6. Pilih "Toko A"
7. ✅ Verifikasi: Session store tersimpan

TC-A002: Cashier Login Without Shift
1. Logout super admin
2. Login: cashier@tokoa.com / password
3. ✅ Verifikasi: Login berhasil
4. Coba akses /admin/pos
5. ❌ Verifikasi: Redirect ke admin dengan peringatan
6. ✅ Verifikasi: Menu "Halaman Kasir" tidak muncul
```

#### **Test Suite B: Cashier Shift Management**
```
⏱️ Estimasi: 15 menit

TC-B001: Open Cashier Shift
1. Masih login sebagai cashier
2. Navigate: Shift Kasir
3. ✅ Verifikasi: Tombol "Buka Shift Baru" muncul
4. Klik "Buka Shift Baru"
5. Isi kas awal: 100000
6. Submit form
7. ✅ Verifikasi: Shift status "open"
8. ✅ Verifikasi: Menu "Halaman Kasir" muncul dengan badge "AKTIF"

TC-B002: POS Access After Shift
1. Klik menu "Halaman Kasir"
2. ✅ Verifikasi: POS terbuka tanpa redirect
3. ✅ Verifikasi: Interface POS lengkap
4. ✅ Verifikasi: Dapat scan/search produk
```

#### **Test Suite C: Core POS Functionality**
```
⏱️ Estimasi: 25 menit

TC-C001: Basic Transaction
1. Di halaman POS
2. Search produk: "Buku"
3. ✅ Verifikasi: Produk muncul di hasil
4. Klik produk atau tekan Enter
5. ✅ Verifikasi: Produk masuk ke cart
6. ✅ Verifikasi: Total terupdate
7. Klik "Checkout"
8. Pilih payment method: "Tunai"
9. Masukkan jumlah bayar: 20000
10. Klik "Proses Transaksi"
11. ✅ Verifikasi: Transaksi berhasil
12. ✅ Verifikasi: Receipt muncul/print

TC-C002: Multi-Product Transaction
1. Add produk 1: Buku (qty: 2)
2. Add produk 2: Pulpen (qty: 1)
3. ✅ Verifikasi: Total benar
4. Remove 1 qty Buku
5. ✅ Verifikasi: Total terupdate
6. Checkout dengan E-wallet
7. ✅ Verifikasi: Transaksi sukses
```

#### **Test Suite D: Settings Per Store**
```
⏱️ Estimasi: 15 menit

TC-D001: Store Settings
1. Login sebagai super admin
2. Pilih "Toko A"
3. Navigate: Settings
4. ✅ Verifikasi: Form menampilkan "Toko A"
5. Edit nama toko menjadi "Toko A - Updated"
6. Save
7. ✅ Verifikasi: Setting tersimpan
8. Switch ke "Toko B"
9. Navigate: Settings
10. ✅ Verifikasi: Data berbeda dari Toko A
```

#### **Test Suite E: Print System**
```
⏱️ Estimasi: 10 menit

TC-E001: Print Configuration
1. Navigate: Settings
2. Set "Print Via Mobile" = Bluetooth
3. Save
4. Navigate: Orders
5. Klik action "Cetak" pada order
6. ✅ Verifikasi: Print dialog mobile muncul
7. Set "Print Via Mobile" = Kabel
8. Save
9. Klik action "Cetak" lagi
10. ✅ Verifikasi: Direct print (atau error printer tidak ada)
```

---

## 🔍 **DETAILED TESTING PHASE (3-4 jam)**

### **Phase 2: Comprehensive Module Testing**

#### **Test Suite F: Multi-Store Data Isolation**
```
⏱️ Estimasi: 30 menit

TC-F001: Product Data Isolation
1. Login super admin
2. Select Toko A
3. Navigate: Products
4. Count products: X items
5. Add new product: "Test Product A"
6. ✅ Verifikasi: Product added
7. Switch to Toko B
8. Navigate: Products
9. ✅ Verifikasi: "Test Product A" TIDAK muncul
10. ✅ Verifikasi: Product count berbeda

TC-F002: Order Data Isolation
1. Select Toko A
2. Navigate: Orders
3. Note order count
4. Select Toko B
5. Navigate: Orders
6. ✅ Verifikasi: Order count berbeda
7. ✅ Verifikasi: No cross-store orders visible

TC-F003: User Data Isolation
1. Select Toko A
2. Navigate: Users
3. ✅ Verifikasi: Hanya user Toko A yang visible
4. Switch to Toko B
5. ✅ Verifikasi: Hanya user Toko B yang visible
```

#### **Test Suite G: Advanced POS Features**
```
⏱️ Estimasi: 45 menit

TC-G001: Barcode Scanner (if available)
1. Open POS
2. Click scanner icon
3. Scan/enter barcode
4. ✅ Verifikasi: Product auto-added
5. ✅ Verifikasi: Correct product details

TC-G002: Transaction Types
1. Test Transfer transaction
2. Test Tarik Tunai
3. Test Jasa Transfer
4. Test Mode Pulsa
5. ✅ Verifikasi: Each transaction type recorded correctly

TC-G003: Payment Method Balances
1. Note initial balances
2. Process cash transaction
3. ✅ Verifikasi: Cash balance increases
4. Process e-wallet transaction
5. ✅ Verifikasi: E-wallet balance increases
6. ✅ Verifikasi: Balances accurate in summary
```

#### **Test Suite H: Reporting System**
```
⏱️ Estimasi: 30 menit

TC-H001: Sales Report
1. Navigate: Reports
2. Create sales report
3. Set date range: Today
4. Generate report
5. ✅ Verifikasi: Report includes today's transactions
6. ✅ Verifikasi: Total amounts match

TC-H002: Expense Report
1. Add some expenses
2. Generate expense report
3. ✅ Verifikasi: Expenses included
4. ✅ Verifikasi: Calculations correct

TC-H003: Report Per Store
1. Super admin: Select Toko A
2. Generate report
3. Switch to Toko B
4. Generate report
5. ✅ Verifikasi: Different data per store
```

---

## ⚠️ **ERROR SCENARIOS TESTING**

### **Test Suite I: Negative Testing**
```
⏱️ Estimasi: 30 menit

TC-I001: Invalid Shift State
1. Close active shift
2. Try direct URL: /admin/pos
3. ✅ Verifikasi: Blocked with proper message

TC-I002: Invalid Payment Amount
1. Add product worth 15000
2. Try payment with 10000
3. ✅ Verifikasi: Error shown, transaction blocked

TC-I003: Empty Cart Checkout
1. Ensure cart is empty
2. Try checkout
3. ✅ Verifikasi: Proper validation message

TC-I004: Insufficient Stock (if implemented)
1. Find product with stock = 1
2. Try add quantity = 5
3. ✅ Verifikasi: Stock validation works
```

---

## 📱 **MOBILE/RESPONSIVE TESTING**

### **Test Suite J: Mobile Compatibility**
```
⏱️ Estimasi: 20 menit

TC-J001: Mobile POS
1. Open POS on mobile browser
2. ✅ Verifikasi: Layout responsive
3. ✅ Verifikasi: All buttons accessible
4. Test transaction flow
5. ✅ Verifikasi: Touch interactions work

TC-J002: Mobile Admin
1. Access admin on mobile
2. ✅ Verifikasi: Navigation works
3. ✅ Verifikasi: Forms usable
4. ✅ Verifikasi: Tables scrollable
```

---

## 🚨 **CRITICAL BUG CHECKLIST**

### **Must-Check Issues:**
- [ ] **Database Errors**: Any 500 errors or SQL exceptions
- [ ] **Authentication Bypass**: Unauthorized access to protected areas
- [ ] **Data Corruption**: Incorrect calculations or data loss
- [ ] **Payment Issues**: Wrong amounts or failed transactions
- [ ] **Store Isolation**: Cross-store data leakage
- [ ] **Permission Violations**: Users accessing forbidden functions

---

## 📊 **PERFORMANCE BENCHMARKS**

### **Response Time Targets:**
```
POS Page Load: < 3 seconds
Product Search: < 1 second
Transaction Processing: < 2 seconds
Report Generation: < 5 seconds
Settings Save: < 1 second
```

### **Test Commands:**
```bash
# Performance testing with Apache Bench
ab -n 100 -c 10 http://127.0.0.1:8000/admin/pos
ab -n 50 -c 5 http://127.0.0.1:8000/admin/orders

# Database query analysis
php artisan telescope:install  # if available
# Monitor slow queries during testing
```

---

## ✅ **DAILY SMOKE TEST (15 menit)**

Untuk pengujian harian cepat:

```
1. Login super admin ✅
2. Switch store ✅
3. Login cashier ✅
4. Open shift ✅
5. Access POS ✅
6. Process 1 transaction ✅
7. Print receipt ✅
8. View reports ✅
9. Check settings ✅
10. Close shift ✅
```

---

## 📝 **TEST EXECUTION LOG**

```
Date: _______________
Tester: _____________
Environment: ________

Phase 1 Results:
□ Suite A: ___/5 passed
□ Suite B: ___/3 passed  
□ Suite C: ___/4 passed
□ Suite D: ___/2 passed
□ Suite E: ___/2 passed

Issues Found:
1. _____________________
2. _____________________
3. _____________________

Overall Status: PASS/FAIL
Notes: ________________
```

---

*Gunakan struktur ini sebagai panduan sistematis untuk memastikan kualitas sistem POS Mr. Kasir sebelum deployment atau setelah update.*
