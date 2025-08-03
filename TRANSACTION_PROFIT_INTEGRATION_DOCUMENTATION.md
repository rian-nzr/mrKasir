# DOKUMENTASI: Integrasi Biaya Admin Transaksi dengan Shift Kasir

## 🎯 **Masalah yang Diselesaikan**

Sebelumnya, **biaya admin dari pencatatan transaksi** (jasa transfer, mode pulsa, dll) **TIDAK dihitung sebagai laba** dan **TIDAK muncul di View Shift Kasir**. Ini menyebabkan:

❌ Profit dari biaya admin hilang  
❌ Laporan shift tidak akurat  
❌ Data laba tidak lengkap  

## ✅ **Solusi yang Diimplementasikan**

### **1. Penambahan Method Profit di CashierShift Model**

```php
// File: app/Models/CashierShift.php

public function getTotalTransactionProfit(): float
{
    return $this->transactions()->sum(\DB::raw('
        CASE 
            WHEN type IN ("transfer", "tarik_tunai") THEN COALESCE(admin_luar, 0) + COALESCE(admin_dalam, 0)
            WHEN type = "jasa_transfer" THEN COALESCE(admin, 0)
            WHEN type = "mode_pulsa" THEN COALESCE(harga_jual, 0) - COALESCE(modal, 0) + COALESCE(admin, 0)
            ELSE 0
        END
    '));
}

public function getTotalProfit(): float
{
    // Profit dari orders (selling price - cost price)
    $orderProfit = $this->orders()
        ->join('order_products', 'orders.id', '=', 'order_products.order_id')
        ->join('products', 'order_products.product_id', '=', 'products.id')
        ->withoutGlobalScopes()
        ->where('orders.cashier_shift_id', $this->id)
        ->sum(\DB::raw('order_products.quantity * (order_products.unit_price - COALESCE(products.cost_price, 0))'));

    // Profit dari transactions (biaya admin, dll)
    $transactionProfit = $this->getTotalTransactionProfit();

    return $orderProfit + $transactionProfit;
}
```

### **2. Penambahan Field Database untuk Profit**

```sql
-- Migration: 2025_08_03_004319_add_profit_fields_to_cashier_shifts_table.php

ALTER TABLE cashier_shifts ADD COLUMN total_profit DECIMAL(15,2) NULL;
ALTER TABLE cashier_shifts ADD COLUMN transaction_profit DECIMAL(15,2) NULL;
ALTER TABLE cashier_shifts ADD COLUMN order_profit DECIMAL(15,2) NULL;
```

### **3. TransactionObserver untuk Auto-Update**

```php
// File: app/Observers/TransactionObserver.php

class TransactionObserver
{
    public function created(Transaction $transaction): void
    {
        // Auto-assign to active shift if not already assigned
        if (!$transaction->cashier_shift_id) {
            $activeShift = CashierShift::where('store_id', $transaction->store_id)
                ->where('status', CashierShift::STATUS_OPEN)
                ->first();
                
            if ($activeShift) {
                $transaction->update(['cashier_shift_id' => $activeShift->id]);
            }
        }
        
        // Update shift summary for active shift
        $this->updateActiveShiftSummary($transaction->store_id);
    }
    
    private function updateActiveShiftSummary(int $storeId): void
    {
        $activeShift = CashierShift::where('store_id', $storeId)
            ->where('status', CashierShift::STATUS_OPEN)
            ->first();
            
        if ($activeShift) {
            $totalProfit = $activeShift->getTotalProfit();
            $transactionProfit = $activeShift->getTotalTransactionProfit();
            $orderProfit = $totalProfit - $transactionProfit;
            
            // Update summary fields including profit data
            $activeShift->update([
                'total_sales' => $activeShift->getTotalSales(),
                'total_transactions' => $activeShift->getTotalTransactions(),
                'total_discounts' => $activeShift->getTotalDiscounts(),
                'total_profit' => $totalProfit,
                'transaction_profit' => $transactionProfit,
                'order_profit' => $orderProfit,
                // ... shift_summary array
            ]);
        }
    }
}
```

### **4. ViewCashierShift dengan Ringkasan Profit**

```php
// File: app/Filament/Resources/CashierShiftResource/Pages/ViewCashierShift.php

Infolists\Components\Section::make('Ringkasan Laba & Profit')
    ->schema([
        Infolists\Components\Grid::make(3)
            ->schema([
                Infolists\Components\TextEntry::make('calculated_total_profit')
                    ->label('Total Profit')
                    ->state(fn ($record) => $record->getTotalProfit())
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state ?: 0, 0, ',', '.'))
                    ->icon('heroicon-o-trending-up')
                    ->color('success')
                    ->weight('bold'),
                Infolists\Components\TextEntry::make('calculated_transaction_profit')
                    ->label('Profit dari Biaya Admin')
                    ->state(fn ($record) => $record->getTotalTransactionProfit())
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state ?: 0, 0, ',', '.'))
                    ->icon('heroicon-o-banknotes')
                    ->color('info'),
                Infolists\Components\TextEntry::make('calculated_order_profit')
                    ->label('Profit dari Penjualan')
                    ->state(fn ($record) => $record->getTotalProfit() - $record->getTotalTransactionProfit())
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state ?: 0, 0, ',', '.'))
                    ->icon('heroicon-o-shopping-bag')
                    ->color('warning'),
            ]),
    ])
    ->collapsible(),
```

### **5. Perbaikan Transaction Model**

```php
// File: app/Models/Transaction.php

public function getTotalProfitAttribute(): float
{
    return match($this->type) {
        'transfer', 'tarik_tunai' => (float)($this->admin_luar ?? 0) + (float)($this->admin_dalam ?? 0),
        'jasa_transfer' => (float)($this->admin ?? 0),
        'mode_pulsa' => (float)($this->harga_jual ?? 0) - (float)($this->modal ?? 0) + (float)($this->admin ?? 0),
        default => 0
    };
}
```

## 🔄 **Flow Kerja Sistem**

```
1. User membuat transaksi dengan biaya admin (POS)
    ↓
2. TransactionService::processTransaction()
    ↓
3. TransactionObserver::created() triggered
    ↓
4. Auto-assign cashier_shift_id jika belum ada
    ↓
5. updateActiveShiftSummary() menghitung ulang profit
    ↓
6. Database fields di cashier_shifts diupdate
    ↓
7. ViewCashierShift menampilkan data profit real-time
    ↓
8. ✅ Biaya admin masuk ke View Shift Kasir sebagai laba
```

## 📊 **Jenis Profit yang Dihitung**

### **Jasa Transfer**
- **Profit**: `admin` (biaya admin)
- **Contoh**: Admin Rp 2,500 → Profit Rp 2,500

### **Mode Pulsa**  
- **Profit**: `(harga_jual - modal) + admin`
- **Contoh**: Modal Rp 10,000, Jual Rp 12,000, Admin Rp 500 → Profit Rp 2,500

### **Transfer & Tarik Tunai**
- **Profit**: `admin_luar + admin_dalam` 
- **Contoh**: Admin Luar Rp 1,000, Admin Dalam Rp 500 → Profit Rp 1,500

### **Penjualan Produk**
- **Profit**: `(selling_price - cost_price) * quantity`
- **Contoh**: Jual Rp 15,000, Modal Rp 10,000 → Profit Rp 5,000

## ✅ **Hasil Testing**

```
=== VERIFIKASI PERHITUNGAN ===
📊 Expected Increase: Rp 5.000
📊 Actual Increase: Rp 5.000  
📊 Transaction Profit Increase: Rp 5.000
✅ BERHASIL! Biaya admin sudah masuk ke perhitungan profit

=== DATA DATABASE FIELDS ===
DB Total Profit: Rp 5.000
DB Transaction Profit: Rp 5.000
DB Order Profit: Rp 0

=== TRANSAKSI TERKAIT SHIFT ===
🔄 Total Transaksi di Shift: 2
📋 Detail Transaksi:
   - ID 17: jasa_transfer - Profit: Rp 2.500
   - ID 18: mode_pulsa - Profit: Rp 2.500
```

## 🎯 **Keuntungan Implementasi**

✅ **Biaya admin dari transaksi** sekarang dihitung sebagai **laba**  
✅ **View Shift Kasir** menampilkan **ringkasan profit** yang akurat  
✅ **Real-time update** profit saat ada transaksi baru  
✅ **Pemisahan profit** dari transaksi vs penjualan produk  
✅ **Data consistency** antara database dan calculated methods  
✅ **Auto-assignment** transaksi ke shift aktif  

## 📁 **File yang Dimodifikasi**

1. **`app/Models/CashierShift.php`** - Penambahan method profit
2. **`app/Models/Transaction.php`** - Perbaikan getTotalProfitAttribute  
3. **`app/Observers/TransactionObserver.php`** - Observer baru (CREATED)
4. **`app/Observers/OrderObserver.php`** - Update untuk profit calculation
5. **`app/Providers/AppServiceProvider.php`** - Register TransactionObserver
6. **`app/Filament/Resources/CashierShiftResource/Pages/ViewCashierShift.php`** - UI profit section
7. **`database/migrations/2025_08_03_004319_add_profit_fields_to_cashier_shifts_table.php`** - Migration (CREATED)

## 🚀 **Cara Testing**

```bash
cd /path/to/mrkasir
php test-transaction-profit-with-shift.php
```

Script akan:
1. ✅ Membuka shift baru jika diperlukan
2. ✅ Membuat transaksi sample dengan biaya admin  
3. ✅ Verifikasi perhitungan profit
4. ✅ Menampilkan data di View Shift Kasir

## 🎉 **Status Implementasi**

✅ **COMPLETED** - Semua fitur telah diimplementasikan dan teruji

**Biaya admin dari pencatatan transaksi sekarang berhasil dihitung sebagai laba dan muncul di View Shift Kasir** dengan breakdown yang jelas antara profit dari transaksi vs profit dari penjualan produk.
