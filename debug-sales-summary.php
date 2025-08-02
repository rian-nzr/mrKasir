<?php

/**
 * Debug Script untuk Sales Summary Issue
 * 
 * Script ini akan melakukan deep investigation:
 * 1. Cek struktur database orders
 * 2. Cek relationship CashierShift -> Orders  
 * 3. Cek apakah orders memiliki cashier_shift_id
 * 4. Cek data orders yang ada
 */

// Bootstrap Laravel
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\CashierShift;
use App\Models\Order;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

echo "=== SALES SUMMARY DEBUG ===\n\n";

// 1. Cek struktur tabel orders
echo "1. CEK STRUKTUR TABEL ORDERS:\n";
$columns = Schema::getColumnListing('orders');
echo "   Kolom yang ada: " . implode(', ', $columns) . "\n";
echo "   cashier_shift_id exists: " . (in_array('cashier_shift_id', $columns) ? "✅ YA" : "❌ TIDAK") . "\n\n";

// 2. Cek shift aktif
$activeShift = CashierShift::where('status', CashierShift::STATUS_OPEN)->first();

if (!$activeShift) {
    echo "❌ Tidak ada shift aktif ditemukan\n";
    exit;
}

echo "2. SHIFT AKTIF:\n";
echo "   ID: {$activeShift->id}\n";
echo "   Number: {$activeShift->shift_number}\n";
echo "   Store ID: {$activeShift->store_id}\n";
echo "   User: {$activeShift->user->name}\n";
echo "   Status: {$activeShift->status}\n\n";

// 3. Cek orders terkait shift
echo "3. ORDERS TERKAIT SHIFT:\n";
$ordersViaRelation = $activeShift->orders()->get();
echo "   Via Relationship: {$ordersViaRelation->count()} orders\n";

$ordersDirectQuery = Order::where('cashier_shift_id', $activeShift->id)->get();
echo "   Via Direct Query: {$ordersDirectQuery->count()} orders\n";

// 4. Cek semua orders di store yang sama
$allOrdersInStore = Order::where('store_id', $activeShift->store_id)->get();
echo "   Total Orders di Store: {$allOrdersInStore->count()}\n";

// 5. Cek orders tanpa cashier_shift_id
if (in_array('cashier_shift_id', $columns)) {
    $ordersWithoutShift = Order::where('store_id', $activeShift->store_id)
        ->whereNull('cashier_shift_id')
        ->get();
    echo "   Orders tanpa cashier_shift_id: {$ordersWithoutShift->count()}\n\n";
} else {
    echo "   Field cashier_shift_id tidak ada!\n\n";
}

// 6. Sample orders detail
echo "4. SAMPLE ORDERS (5 Terakhir di Store):\n";
$sampleOrders = Order::where('store_id', $activeShift->store_id)
    ->with('paymentMethod')
    ->latest()
    ->take(5)
    ->get();

foreach ($sampleOrders as $order) {
    $shiftId = $order->cashier_shift_id ?? 'NULL';
    $paymentMethod = $order->paymentMethod->name ?? 'Unknown';
    echo "   Order #{$order->id}: Rp " . number_format($order->total_price, 0, ',', '.') . 
         " - {$paymentMethod} - Shift ID: {$shiftId}\n";
}

// 7. Test calculated methods
echo "\n5. TEST CALCULATED METHODS:\n";
echo "   getTotalSales(): Rp " . number_format($activeShift->getTotalSales(), 0, ',', '.') . "\n";
echo "   getTotalTransactions(): {$activeShift->getTotalTransactions()} transaksi\n";
echo "   getTotalDiscounts(): Rp " . number_format($activeShift->getTotalDiscounts(), 0, ',', '.') . "\n";

// 8. Raw SQL query test  
echo "\n6. RAW SQL TEST:\n";
$rawTotal = \DB::table('orders')
    ->where('cashier_shift_id', $activeShift->id)
    ->sum('total_price');
echo "   Raw SQL Sum: Rp " . number_format($rawTotal, 0, ',', '.') . "\n";

$rawCount = \DB::table('orders')
    ->where('cashier_shift_id', $activeShift->id)
    ->count();
echo "   Raw SQL Count: {$rawCount} transaksi\n";

echo "\n=== DEBUG COMPLETED ===\n";
