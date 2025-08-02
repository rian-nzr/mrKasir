<?php

/**
 * Auto Fix Existing Orders - Assign to Active Shift
 * 
 * Script ini akan langsung fix orders tanpa konfirmasi
 */

// Bootstrap Laravel
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\CashierShift;
use App\Models\Order;

echo "=== AUTO FIX EXISTING ORDERS ===\n\n";

// 1. Cari shift yang aktif
$activeShift = CashierShift::where('status', CashierShift::STATUS_OPEN)->first();

if (!$activeShift) {
    echo "❌ Tidak ada shift aktif ditemukan\n";
    exit;
}

echo "✅ Shift aktif: #{$activeShift->shift_number} (Store: {$activeShift->store_id})\n";

// 2. Cari orders tanpa cashier_shift_id
$ordersWithoutShift = Order::where('store_id', $activeShift->store_id)
    ->whereNull('cashier_shift_id')
    ->get();

echo "📋 Orders tanpa shift: {$ordersWithoutShift->count()}\n";

if ($ordersWithoutShift->count() === 0) {
    echo "✅ Semua orders sudah ter-assign\n";
    exit;
}

// 3. Auto update orders
echo "\n🔄 Auto-assigning orders...\n";
$updated = Order::where('store_id', $activeShift->store_id)
    ->whereNull('cashier_shift_id')
    ->update(['cashier_shift_id' => $activeShift->id]);

echo "✅ Updated {$updated} orders\n";

// 4. Update shift summary
echo "🔄 Updating shift summary...\n";
$activeShift->update([
    'total_sales' => $activeShift->getTotalSales(),
    'total_transactions' => $activeShift->getTotalTransactions(),
    'total_discounts' => $activeShift->getTotalDiscounts(),
]);

// 5. Hasil
echo "\n=== HASIL AKHIR ===\n";
echo "💰 Total Sales: Rp " . number_format($activeShift->getTotalSales(), 0, ',', '.') . "\n";
echo "🛒 Total Transactions: {$activeShift->getTotalTransactions()}\n";
echo "🏷️ Total Discounts: Rp " . number_format($activeShift->getTotalDiscounts(), 0, ',', '.') . "\n";

echo "\n✅ SALES SUMMARY FIXED!\n";
