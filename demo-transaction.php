<?php

use App\Models\User;
use App\Models\Store;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Services\TransactionService;

// Demo script untuk menguji fitur transaksi

echo "🚀 Demo Fitur Pencatatan Transaksi\n";
echo "===================================\n\n";

// Setup user dan store untuk testing
$user = User::first();
$store = Store::first();

if (!$user || !$store) {
    echo "❌ User atau Store tidak ditemukan!\n";
    echo "Pastikan ada data user dan store di database.\n";
    exit;
}

echo "👤 User: {$user->name}\n";
echo "🏪 Store: {$store->name}\n\n";

// Cek Payment Methods
echo "💳 Payment Methods Available:\n";
$paymentMethods = PaymentMethod::where('store_id', $store->id)->get();

if ($paymentMethods->isEmpty()) {
    echo "❌ Tidak ada payment methods untuk store ini!\n";
    exit;
}

foreach ($paymentMethods as $pm) {
    echo "  - {$pm->name} (Balance: Rp " . number_format((float)$pm->balance, 0, ',', '.') . ")\n";
}
echo "\n";

// Demo Transaction Service
$transactionService = new TransactionService();

// Set current user dan store untuk testing
Auth::login($user);
Session::put('selected_store_id', $store->id);

// Test 1: Transfer Transaction
echo "📤 Test 1: Transfer Transaction\n";
echo "------------------------------\n";

$transferData = [
    'type' => 'transfer',
    'sumber_dana_id' => $paymentMethods->first()->id,
    'amount' => 100000,
    'admin_luar' => 5000,
    'admin_dalam' => 2000,
    'keterangan' => 'Transfer ke rekening BCA - Demo'
];

try {
    $transaction1 = $transactionService->processTransaction($transferData);
    echo "✅ Transfer berhasil dibuat!\n";
    echo "   ID: {$transaction1->id}\n";
    echo "   Amount: Rp " . number_format((float)$transaction1->amount, 0, ',', '.') . "\n";
    echo "   Profit: Rp " . number_format((float)$transaction1->total_profit, 0, ',', '.') . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 2: Jasa Transfer Transaction
echo "💸 Test 2: Jasa Transfer Transaction\n";
echo "----------------------------------\n";

$jasaTransferData = [
    'type' => 'jasa_transfer',
    'terima_dana' => 50000,
    'admin' => 3000,
    'keterangan' => 'Jasa transfer Western Union - Demo'
];

try {
    $transaction2 = $transactionService->processTransaction($jasaTransferData);
    echo "✅ Jasa Transfer berhasil dibuat!\n";
    echo "   ID: {$transaction2->id}\n";
    echo "   Terima Dana: Rp " . number_format((float)$transaction2->terima_dana, 0, ',', '.') . "\n";
    echo "   Profit: Rp " . number_format((float)$transaction2->total_profit, 0, ',', '.') . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 3: Mode Pulsa Transaction
echo "📱 Test 3: Mode Pulsa Transaction\n";
echo "--------------------------------\n";

$modePulsaData = [
    'type' => 'mode_pulsa',
    'jenis_transaksi' => 'Pulsa Telkomsel 25K',
    'sumber' => 'Server H2H',
    'modal' => 24000,
    'harga_jual' => 26000,
    'keterangan' => 'Penjualan pulsa ke customer - Demo'
];

try {
    $transaction3 = $transactionService->processTransaction($modePulsaData);
    echo "✅ Mode Pulsa berhasil dibuat!\n";
    echo "   ID: {$transaction3->id}\n";
    echo "   Modal: Rp " . number_format((float)$transaction3->modal, 0, ',', '.') . "\n";
    echo "   Harga Jual: Rp " . number_format((float)$transaction3->harga_jual, 0, ',', '.') . "\n";
    echo "   Profit: Rp " . number_format((float)$transaction3->total_profit, 0, ',', '.') . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Summary
echo "📊 Summary\n";
echo "==========\n";
$totalTransactions = Transaction::where('store_id', $store->id)->count();
$totalProfit = Transaction::where('store_id', $store->id)->get()->sum('total_profit');

echo "Total Transaksi Hari Ini: {$totalTransactions}\n";
echo "Total Keuntungan: Rp " . number_format((float)$totalProfit, 0, ',', '.') . "\n\n";

echo "💳 Updated Payment Methods Balance:\n";
$updatedPaymentMethods = PaymentMethod::where('store_id', $store->id)->get();
foreach ($updatedPaymentMethods as $pm) {
    echo "  - {$pm->name} (Balance: Rp " . number_format((float)$pm->balance, 0, ',', '.') . ")\n";
}

echo "\n🎉 Demo selesai! Silakan cek data di admin panel.\n";
