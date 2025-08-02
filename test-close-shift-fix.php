<?php

/**
 * Test Close Shift Fix
 * 
 * Script ini akan test method yang bermasalah saat close shift
 */

// Bootstrap Laravel
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\CashierShift;

echo "=== TEST CLOSE SHIFT FIX ===\n\n";

// Cari shift aktif
$activeShift = CashierShift::where('status', CashierShift::STATUS_OPEN)->first();

if (!$activeShift) {
    echo "❌ Tidak ada shift aktif ditemukan\n";
    exit;
}

echo "✅ Shift aktif: #{$activeShift->shift_number}\n";
echo "   Store ID: {$activeShift->store_id}\n";
echo "   User: {$activeShift->user->name}\n\n";

// Test method yang bermasalah
echo "🧪 Testing getPaymentSummary() method...\n";

try {
    $paymentSummary = $activeShift->getPaymentSummary();
    echo "✅ getPaymentSummary() berhasil dijalankan\n";
    echo "   Payment methods found: " . count($paymentSummary) . "\n";
    
    foreach ($paymentSummary as $payment) {
        echo "   - {$payment['name']}: {$payment['count']} transaksi, Rp " . number_format($payment['amount'], 0, ',', '.') . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ getPaymentSummary() error:\n";
    echo "   " . $e->getMessage() . "\n";
    exit;
}

echo "\n🧪 Testing calculateShiftSummary() method...\n";

try {
    // Test calculateShiftSummary yang dipanggil saat close
    $totalSales = $activeShift->getTotalSales();
    $totalTransactions = $activeShift->getTotalTransactions();
    $totalDiscounts = $activeShift->getTotalDiscounts();
    $paymentSummary = $activeShift->getPaymentSummary();
    
    echo "✅ Semua method summary berhasil dijalankan\n";
    echo "   Total Sales: Rp " . number_format($totalSales, 0, ',', '.') . "\n";
    echo "   Total Transactions: {$totalTransactions}\n";
    echo "   Total Discounts: Rp " . number_format($totalDiscounts, 0, ',', '.') . "\n";
    
} catch (\Exception $e) {
    echo "❌ calculateShiftSummary methods error:\n";
    echo "   " . $e->getMessage() . "\n";
    exit;
}

echo "\n✅ CLOSE SHIFT FIX BERHASIL!\n";
echo "📋 Sekarang bisa menutup shift tanpa error SQL ambiguous column\n";
echo "🎯 Silakan test close shift di interface admin\n";
