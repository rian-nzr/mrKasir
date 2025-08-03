<?php

/**
 * Test Script untuk Verifikasi Profit dari Biaya Admin Transaksi
 * 
 * Script ini akan:
 * 1. Mengecek shift yang aktif
 * 2. Membuat transaksi sample dengan biaya admin
 * 3. Memverifikasi bahwa biaya admin masuk ke profit shift
 * 4. Menampilkan hasil di View Shift Kasir
 */

// Bootstrap Laravel
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\CashierShift;
use App\Models\Transaction;
use App\Services\TransactionService;

echo "=== Test Profit dari Biaya Admin Transaksi ===\n\n";

// 1. Ambil shift yang aktif
$activeShift = CashierShift::where('status', CashierShift::STATUS_OPEN)->first();

if (!$activeShift) {
    echo "❌ Tidak ada shift aktif ditemukan\n";
    echo "Silakan buka shift terlebih dahulu untuk testing\n";
    exit;
}

echo "✅ Shift aktif ditemukan: #{$activeShift->shift_number}\n";
echo "   Store ID: {$activeShift->store_id}\n";
echo "   User: {$activeShift->user->name}\n\n";

// 2. Cek data profit sebelum transaksi
echo "=== DATA PROFIT SEBELUM TRANSAKSI ===\n";
$profitBefore = $activeShift->getTotalProfit();
$transactionProfitBefore = $activeShift->getTotalTransactionProfit();
$orderProfitBefore = $activeShift->getTotalProfit() - $activeShift->getTotalTransactionProfit();

echo "💰 Total Profit Sebelum: Rp " . number_format($profitBefore, 0, ',', '.') . "\n";
echo "🔄 Profit dari Transaksi: Rp " . number_format($transactionProfitBefore, 0, ',', '.') . "\n";
echo "🛒 Profit dari Orders: Rp " . number_format($orderProfitBefore, 0, ',', '.') . "\n\n";

// 3. Buat transaksi sample dengan biaya admin
echo "=== MEMBUAT TRANSAKSI SAMPLE ===\n";

try {
    // Test Jasa Transfer dengan biaya admin
    $testData = [
        'type' => 'jasa_transfer',
        'terima_dana' => 500000, // Rp 500,000
        'admin' => 2500, // Rp 2,500 biaya admin
        'keterangan' => 'Test jasa transfer untuk verifikasi profit',
        'store_id' => $activeShift->store_id,
        'user_id' => $activeShift->user_id,
        'cashier_shift_id' => $activeShift->id
    ];
    
    $transactionService = app(TransactionService::class);
    $transaction = $transactionService->processTransaction($testData);
    
    echo "✅ Transaksi berhasil dibuat!\n";
    echo "   ID: {$transaction->id}\n";
    echo "   Type: {$transaction->type}\n";
    echo "   Jumlah Transfer: Rp " . number_format((float)$transaction->terima_dana, 0, ',', '.') . "\n";
    echo "   Biaya Admin: Rp " . number_format((float)$transaction->admin, 0, ',', '.') . "\n";
    echo "   Expected Profit: Rp " . number_format((float)$transaction->admin, 0, ',', '.') . "\n\n";
    
} catch (\Exception $e) {
    echo "❌ Error membuat transaksi: " . $e->getMessage() . "\n";
    exit;
}

// 4. Cek data profit setelah transaksi
echo "=== DATA PROFIT SETELAH TRANSAKSI ===\n";

// Refresh data shift
$activeShift->refresh();

$profitAfter = $activeShift->getTotalProfit();
$transactionProfitAfter = $activeShift->getTotalTransactionProfit();
$orderProfitAfter = $activeShift->getTotalProfit() - $activeShift->getTotalTransactionProfit();

echo "💰 Total Profit Setelah: Rp " . number_format($profitAfter, 0, ',', '.') . "\n";
echo "🔄 Profit dari Transaksi: Rp " . number_format($transactionProfitAfter, 0, ',', '.') . "\n";
echo "🛒 Profit dari Orders: Rp " . number_format($orderProfitAfter, 0, ',', '.') . "\n\n";

// 5. Verifikasi perhitungan
$expectedIncrease = 2500; // Biaya admin dari transaksi
$actualIncrease = $profitAfter - $profitBefore;
$transactionIncrease = $transactionProfitAfter - $transactionProfitBefore;

echo "=== VERIFIKASI PERHITUNGAN ===\n";
echo "📊 Expected Increase: Rp " . number_format($expectedIncrease, 0, ',', '.') . "\n";
echo "📊 Actual Increase: Rp " . number_format($actualIncrease, 0, ',', '.') . "\n";
echo "📊 Transaction Profit Increase: Rp " . number_format($transactionIncrease, 0, ',', '.') . "\n";

if ($actualIncrease == $expectedIncrease && $transactionIncrease == $expectedIncrease) {
    echo "✅ BERHASIL! Biaya admin sudah masuk ke perhitungan profit\n";
} else {
    echo "❌ GAGAL! Ada masalah dengan perhitungan profit\n";
}

// 6. Cek data di database field
echo "\n=== DATA DATABASE FIELDS ===\n";
echo "DB Total Profit: Rp " . number_format($activeShift->total_profit ?? 0, 0, ',', '.') . "\n";
echo "DB Transaction Profit: Rp " . number_format($activeShift->transaction_profit ?? 0, 0, ',', '.') . "\n";
echo "DB Order Profit: Rp " . number_format($activeShift->order_profit ?? 0, 0, ',', '.') . "\n";

// 7. Cek transaksi terkait shift
echo "\n=== TRANSAKSI TERKAIT SHIFT ===\n";
$transactions = $activeShift->transactions()->get();
echo "🔄 Total Transaksi: {$transactions->count()}\n";

if ($transactions->count() > 0) {
    echo "📋 Detail Transaksi:\n";
    foreach ($transactions as $trans) {
        echo "   - ID {$trans->id}: {$trans->type} - Profit: Rp " . number_format($trans->total_profit, 0, ',', '.') . "\n";
    }
}

echo "\n✅ TEST SELESAI!\n";
echo "🎯 Sekarang cek View Shift Kasir di admin panel untuk melihat data profit\n";
echo "📊 Profit dari biaya admin transaksi seharusnya sudah muncul di 'Ringkasan Laba & Profit'\n";
