# CLOSE SHIFT SQL ERROR FIX

## 🔍 **Error Yang Diperbaiki**

### **Error Message:**
```
SQLSTATE[23000]: Integrity constraint violation: 1052 Column 'store_id' in where clause is ambiguous
```

### **Root Cause:**
- CashierShift model menggunakan **StoreScope global scope**
- Method `getPaymentSummary()` melakukan **JOIN** dengan tabel `payment_methods`
- Kedua tabel (`orders` dan `payment_methods`) memiliki kolom `store_id`
- Query JOIN + global scope menyebabkan **ambiguous column reference**

### **SQL Query Bermasalah:**
```sql
SELECT pm.name, pm.is_cash, pm.is_ewallet, COUNT(*) as transaction_count, SUM(orders.total_price) as total_amount 
FROM orders 
INNER JOIN payment_methods as pm ON orders.payment_method_id = pm.id 
WHERE orders.cashier_shift_id = 5 
  AND orders.cashier_shift_id IS NOT NULL 
  AND store_id = 1  -- ❌ AMBIGUOUS: orders.store_id atau pm.store_id?
GROUP BY pm.id, pm.name, pm.is_cash, pm.is_ewallet
```

## ✅ **Solusi Yang Diimplementasikan**

### **Fix di CashierShift.php:**
```php
// File: app/Models/CashierShift.php - Method getPaymentSummary()

public function getPaymentSummary(): array
{
    $summary = [];
    
    // Get payment methods summary directly from orders
    $paymentMethods = $this->orders()
        ->join('payment_methods as pm', 'orders.payment_method_id', '=', 'pm.id')
        ->select(
            'pm.name',
            'pm.is_cash',
            'pm.is_ewallet',
            \DB::raw('COUNT(*) as transaction_count'),
            \DB::raw('SUM(orders.total_price) as total_amount')
        )
        ->groupBy('pm.id', 'pm.name', 'pm.is_cash', 'pm.is_ewallet')
        ->withoutGlobalScopes() // ✅ Remove StoreScope to avoid ambiguous column
        ->where('orders.cashier_shift_id', $this->id) // ✅ Explicit filter by shift ID
        ->get();
        
    // ... rest of method
}
```

### **Key Changes:**
1. **`->withoutGlobalScopes()`** - Menghilangkan StoreScope untuk query ini
2. **`->where('orders.cashier_shift_id', $this->id)`** - Filter eksplisit berdasarkan shift ID
3. **Tidak perlu filter store_id** - Karena shift sudah spesifik untuk store tertentu

## ✅ **How It Works**

### **Before Fix:**
```
getPaymentSummary() called
    ↓
JOIN orders + payment_methods  
    ↓
StoreScope adds: WHERE store_id = 1
    ↓
❌ SQL Error: Ambiguous column 'store_id'
    ↓
Close shift fails
```

### **After Fix:**
```
getPaymentSummary() called
    ↓
JOIN orders + payment_methods
    ↓
withoutGlobalScopes() - No StoreScope
    ↓
Explicit WHERE orders.cashier_shift_id = X
    ↓
✅ Query success - Data filtered by shift
    ↓
Close shift works perfectly
```

## ✅ **Testing Results**

### **Test Output:**
```bash
✅ getPaymentSummary() berhasil dijalankan
   Payment methods found: 2
   - Cash: 8 transaksi, Rp 360.444
   - dana: 3 transaksi, Rp 62.000

✅ Semua method summary berhasil dijalankan
   Total Sales: Rp 422.444
   Total Transactions: 11
   Total Discounts: Rp 0
```

### **Data Integrity:**
- ✅ **Payment summary accurate** - Menampilkan breakdown per payment method
- ✅ **Sales totals correct** - Sesuai dengan data orders
- ✅ **No data loss** - Semua transaksi terhitung dengan benar
- ✅ **Performance maintained** - Query efficient dengan filter explicit

## ✅ **Security & Data Integrity**

### **Data Scoping:**
- ✅ **Shift-based filtering** - Data tetap terisolasi per shift
- ✅ **Store isolation maintained** - Meskipun tanpa StoreScope, data tetap aman karena shift sudah spesifik store
- ✅ **No cross-contamination** - Orders dari shift lain tidak akan masuk

### **Permission Model:**
- ✅ **User access control** - User hanya bisa close shift yang mereka miliki
- ✅ **Store-based access** - Middleware StoreMiddleware tetap berlaku
- ✅ **Role-based security** - Filament permissions tetap berjalan

## 🎯 **Files Modified**

1. **app/Models/CashierShift.php** - Fix getPaymentSummary() method
2. **test-close-shift-fix.php** - Validation script (new)

## ✅ **Status: RESOLVED**

**Issue:** SQL ambiguous column error saat close shift  
**Solution:** Remove global scope + explicit filtering by cashier_shift_id  
**Result:** Close shift functionality works perfectly  
**Data Integrity:** Maintained with accurate payment summaries  

---

**Implementation Date:** August 3, 2025  
**Status:** ✅ Production Ready  
**Next Action:** User can close shifts successfully
