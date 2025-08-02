<?php

/**
 * Fix Existing Orders - Assign to Active Shift
 * 
 * Script ini akan:
 * 1. Cari orders yang cashier_shift_id nya NULL
 * 2. Assign ke shift yang aktif di store yang sama
 * 3. Update data summary di shift
 */

// Bootstrap Laravel
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\CashierShift;
use App\Models\Order;

echo "=== FIX EXISTING ORDERS ===\n\n";

// 1. Cari shift yang aktif
$activeShift = CashierShift::where('status', CashierShift::STATUS_OPEN)->first();

if (!$activeShift) {
    echo "❌ Tidak ada shift aktif ditemukan\n";
    echo "Silakan buka shift terlebih dahulu\n";
    exit;
}

echo "✅ Shift aktif ditemukan: #{$activeShift->shift_number}\n";
echo "   Store ID: {$activeShift->store_id}\n";
echo "   User: {$activeShift->user->name}\n\n";

// 2. Cari orders tanpa cashier_shift_id di store yang sama
$ordersWithoutShift = Order::where('store_id', $activeShift->store_id)
    ->whereNull('cashier_shift_id')
    ->get();

echo "📋 Orders tanpa shift assignment: {$ordersWithoutShift->count()}\n";

if ($ordersWithoutShift->count() === 0) {
    echo "✅ Semua orders sudah ter-assign ke shift\n";
    exit;
}

// 3. Tanya konfirmasi user
echo "\n🤔 Apakah ingin assign semua orders ini ke shift aktif?\n";
echo "   Shift: #{$activeShift->shift_number}\n";
echo "   Orders: {$ordersWithoutShift->count()} transaksi\n";
echo "   Total Value: Rp " . number_format($ordersWithoutShift->sum('total_price'), 0, ',', '.') . "\n\n";

echo "Ketik 'ya' untuk melanjutkan: ";
$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));

if (strtolower($confirmation) !== 'ya' && strtolower($confirmation) !== 'y') {
    echo "❌ Dibatalkan oleh user\n";
    exit;
}

// 4. Update orders
echo "\n🔄 Mengupdate orders...\n";

$updated = 0;
foreach ($ordersWithoutShift as $order) {
    $order->update(['cashier_shift_id' => $activeShift->id]);
    $updated++;
    
    echo "   ✅ Order #{$order->id} (Rp " . number_format($order->total_price, 0, ',', '.') . ")\n";
}

echo "\n✅ Berhasil update {$updated} orders\n";

// 5. Update shift summary
echo "\n🔄 Mengupdate shift summary...\n";
$activeShift->update([
    'total_sales' => $activeShift->getTotalSales(),
    'total_transactions' => $activeShift->getTotalTransactions(),
    'total_discounts' => $activeShift->getTotalDiscounts(),
]);

echo "✅ Shift summary berhasil diupdate\n";

// 6. Tampilkan hasil
echo "\n=== HASIL AKHIR ===\n";
echo "💰 Total Sales: Rp " . number_format($activeShift->getTotalSales(), 0, ',', '.') . "\n";
echo "🛒 Total Transactions: {$activeShift->getTotalTransactions()}\n";
echo "🏷️ Total Discounts: Rp " . number_format($activeShift->getTotalDiscounts(), 0, ',', '.') . "\n";

echo "\n✅ FIX COMPLETED - Sales summary sekarang akurat!\n";
