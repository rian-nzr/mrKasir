<?php

/**
 * Test Script - Membuka Shift dan Test Profit dari Biaya Admin
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\CashierShift;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Store;
use App\Services\CashierShiftService;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Session;

echo "=== Test dengan Membuka Shift Baru ===\n\n";

// 1. Ambil store pertama
$store = Store::first();
if (!$store) {
    echo "❌ Tidak ada store ditemukan\n";
    exit;
}

echo "✅ Store untuk test: {$store->name}\n";
echo "   Store ID: {$store->id}\n";

// 2. Ambil user dan set store_id jika diperlukan  
$user = User::first();
if (!$user) {
    echo "❌ Tidak ada user ditemukan\n";
    exit;
}

// Set store untuk user jika tidak ada
if (!$user->store_id) {
    $user->update(['store_id' => $store->id]);
}

// Set session store untuk super admin
if ($user->isSuperAdmin()) {
    Session::put('selected_store_id', $store->id);
}

echo "✅ User untuk test: {$user->name}\n";
echo "   User Store ID: {$user->store_id}\n\n";

// 2. Cek shift aktif atau buat baru
$storeId = $user->store_id ?? $store->id;
$activeShift = CashierShift::where('store_id', $storeId)
    ->where('status', CashierShift::STATUS_OPEN)
    ->first();

if (!$activeShift) {
    echo "🔄 Membuka shift baru...\n";
    
    try {
        $shiftService = app(CashierShiftService::class);
        $activeShift = $shiftService->openShift(
            $user->id,
            100000, // Kas awal Rp 100,000
            'Test Counter',
            'Test shift untuk verifikasi profit biaya admin'
        );
        
        echo "✅ Shift berhasil dibuka: #{$activeShift->shift_number}\n\n";
    } catch (\Exception $e) {
        echo "❌ Error membuka shift: " . $e->getMessage() . "\n";
        exit;
    }
} else {
    echo "✅ Menggunakan shift aktif: #{$activeShift->shift_number}\n\n";
}

// 3. Login sebagai user untuk testing
auth()->login($user);

// 4. Cek data profit sebelum transaksi
echo "=== DATA PROFIT SEBELUM TRANSAKSI ===\n";
$profitBefore = $activeShift->getTotalProfit();
$transactionProfitBefore = $activeShift->getTotalTransactionProfit();
$orderProfitBefore = $profitBefore - $transactionProfitBefore;

echo "💰 Total Profit Sebelum: Rp " . number_format($profitBefore, 0, ',', '.') . "\n";
echo "🔄 Profit dari Transaksi: Rp " . number_format($transactionProfitBefore, 0, ',', '.') . "\n";
echo "🛒 Profit dari Orders: Rp " . number_format($orderProfitBefore, 0, ',', '.') . "\n\n";

// 5. Buat beberapa transaksi sample dengan biaya admin
echo "=== MEMBUAT TRANSAKSI SAMPLE ===\n";

$testTransactions = [
    [
        'type' => 'jasa_transfer',
        'terima_dana' => 500000,
        'admin' => 2500,
        'keterangan' => 'Test jasa transfer',
        'expected_profit' => 2500
    ],
    [
        'type' => 'mode_pulsa',
        'modal' => 10000,
        'harga_jual' => 12000,
        'admin' => 500,
        'jenis_transaksi' => 'Pulsa',
        'sumber' => 'Telkomsel',
        'keterangan' => 'Test mode pulsa',
        'expected_profit' => 2500 // (12000-10000) + 500
    ]
];

$totalExpectedProfit = 0;

try {
    $transactionService = app(TransactionService::class);
    
    foreach ($testTransactions as $index => $testData) {
        echo "\n🔄 Membuat transaksi " . ($index + 1) . ": {$testData['type']}\n";
        
        $transaction = $transactionService->processTransaction($testData);
        
        echo "   ✅ ID: {$transaction->id}\n";
        echo "   ✅ Profit: Rp " . number_format($transaction->total_profit, 0, ',', '.') . "\n";
        
        $totalExpectedProfit += $testData['expected_profit'];
    }
    
    echo "\n✅ Semua transaksi berhasil dibuat!\n";
    echo "🎯 Total Expected Profit: Rp " . number_format($totalExpectedProfit, 0, ',', '.') . "\n\n";
    
} catch (\Exception $e) {
    echo "❌ Error membuat transaksi: " . $e->getMessage() . "\n";
    exit;
}

// 6. Cek data profit setelah transaksi
echo "=== DATA PROFIT SETELAH TRANSAKSI ===\n";

// Refresh data shift
$activeShift->refresh();

$profitAfter = $activeShift->getTotalProfit();
$transactionProfitAfter = $activeShift->getTotalTransactionProfit();
$orderProfitAfter = $profitAfter - $transactionProfitAfter;

echo "💰 Total Profit Setelah: Rp " . number_format($profitAfter, 0, ',', '.') . "\n";
echo "🔄 Profit dari Transaksi: Rp " . number_format($transactionProfitAfter, 0, ',', '.') . "\n";
echo "🛒 Profit dari Orders: Rp " . number_format($orderProfitAfter, 0, ',', '.') . "\n\n";

// 7. Verifikasi perhitungan
$actualIncrease = $profitAfter - $profitBefore;
$transactionIncrease = $transactionProfitAfter - $transactionProfitBefore;

echo "=== VERIFIKASI PERHITUNGAN ===\n";
echo "📊 Expected Increase: Rp " . number_format($totalExpectedProfit, 0, ',', '.') . "\n";
echo "📊 Actual Increase: Rp " . number_format($actualIncrease, 0, ',', '.') . "\n";
echo "📊 Transaction Profit Increase: Rp " . number_format($transactionIncrease, 0, ',', '.') . "\n";

if (abs($actualIncrease - $totalExpectedProfit) < 0.01 && abs($transactionIncrease - $totalExpectedProfit) < 0.01) {
    echo "✅ BERHASIL! Biaya admin sudah masuk ke perhitungan profit\n";
} else {
    echo "❌ GAGAL! Ada masalah dengan perhitungan profit\n";
    echo "   Expected: {$totalExpectedProfit}, Actual: {$actualIncrease}, Transaction: {$transactionIncrease}\n";
}

// 8. Cek data di database fields
echo "\n=== DATA DATABASE FIELDS ===\n";
echo "DB Total Profit: Rp " . number_format($activeShift->total_profit ?? 0, 0, ',', '.') . "\n";
echo "DB Transaction Profit: Rp " . number_format($activeShift->transaction_profit ?? 0, 0, ',', '.') . "\n";
echo "DB Order Profit: Rp " . number_format($activeShift->order_profit ?? 0, 0, ',', '.') . "\n";

// 9. Cek transaksi terkait shift
echo "\n=== TRANSAKSI TERKAIT SHIFT ===\n";
$transactions = $activeShift->transactions()->get();
echo "🔄 Total Transaksi di Shift: {$transactions->count()}\n";

if ($transactions->count() > 0) {
    echo "📋 Detail Transaksi:\n";
    foreach ($transactions as $trans) {
        $profit = $trans->total_profit;
        echo "   - ID {$trans->id}: {$trans->type} - Profit: Rp " . number_format($profit, 0, ',', '.') . "\n";
    }
}

echo "\n✅ TEST SELESAI!\n";
echo "🎯 Sekarang cek View Shift Kasir di admin panel untuk melihat data profit\n";
echo "📊 Profit dari biaya admin transaksi seharusnya sudah muncul di 'Ringkasan Laba & Profit'\n";
echo "🔗 URL Admin: http://localhost/admin/cashier-shifts/{$activeShift->id}\n";
