<?php

/**
 * Test Script untuk Verifikasi Perbaikan Sales Summary
 * 
 * Script ini akan:
 * 1. Mengecek shift yang aktif
 * 2. Membandingkan data dari database vs calculated methods
 * 3. Menampilkan hasil perbandingan
 */

// Bootstrap Laravel
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\CashierShift;
use App\Models\Order;

echo "=== Test Sales Summary Fix ===\n\n";

// Ambil shift yang aktif
$activeShift = CashierShift::where('status', CashierShift::STATUS_OPEN)->first();

if (!$activeShift) {
    echo "❌ Tidak ada shift aktif ditemukan\n";
    echo "Silakan buka shift terlebih dahulu untuk testing\n";
    exit;
}

echo "✅ Shift aktif ditemukan: #{$activeShift->shift_number}\n";
echo "   Store ID: {$activeShift->store_id}\n";
echo "   User: {$activeShift->user->name}\n\n";

// Ambil data dari database vs calculated
$dbTotalSales = $activeShift->total_sales ?? 0;
$calculatedTotalSales = $activeShift->getTotalSales();

$dbTotalTransactions = $activeShift->total_transactions ?? 0; 
$calculatedTotalTransactions = $activeShift->getTotalTransactions();

$dbTotalDiscounts = $activeShift->total_discounts ?? 0;
$calculatedTotalDiscounts = $activeShift->getTotalDiscounts();

echo "=== PERBANDINGAN DATA ===\n\n";

echo "💰 TOTAL PENJUALAN:\n";
echo "   Database: Rp " . number_format($dbTotalSales, 0, ',', '.') . "\n";
echo "   Calculated: Rp " . number_format($calculatedTotalSales, 0, ',', '.') . "\n";
echo "   " . ($dbTotalSales == $calculatedTotalSales ? "✅ SAMA" : "❌ BEDA") . "\n\n";

echo "🛒 TOTAL TRANSAKSI:\n";
echo "   Database: {$dbTotalTransactions} transaksi\n";
echo "   Calculated: {$calculatedTotalTransactions} transaksi\n";
echo "   " . ($dbTotalTransactions == $calculatedTotalTransactions ? "✅ SAMA" : "❌ BEDA") . "\n\n";

echo "🏷️ TOTAL DISKON:\n";
echo "   Database: Rp " . number_format($dbTotalDiscounts, 0, ',', '.') . "\n";
echo "   Calculated: Rp " . number_format($calculatedTotalDiscounts, 0, ',', '.') . "\n";
echo "   " . ($dbTotalDiscounts == $calculatedTotalDiscounts ? "✅ SAMA" : "❌ BEDA") . "\n\n";

// Tampilkan beberapa order untuk verifikasi
$orders = $activeShift->orders()->with('paymentMethod')->latest()->take(3)->get();

echo "=== SAMPLE ORDERS (3 Terakhir) ===\n";
foreach ($orders as $order) {
    echo "Order #{$order->id}: Rp " . number_format($order->total_price, 0, ',', '.') . 
         " - {$order->paymentMethod->name}" . 
         ($order->discount_amount > 0 ? " (Diskon: Rp " . number_format($order->discount_amount, 0, ',', '.') . ")" : "") . "\n";
}

echo "\n=== KESIMPULAN ===\n";
if ($dbTotalSales == $calculatedTotalSales && 
    $dbTotalTransactions == $calculatedTotalTransactions && 
    $dbTotalDiscounts == $calculatedTotalDiscounts) {
    echo "✅ Semua data sudah sinkron!\n";
} else {
    echo "❌ Ada perbedaan data - ViewCashierShift sekarang menggunakan calculated methods\n";
    echo "   yang akan menampilkan data real-time yang benar.\n";
}

echo "\n=== TESTING COMPLETED ===\n";
