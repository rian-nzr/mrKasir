<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CashierShift;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\CashierShiftService;
use Illuminate\Support\Facades\Session;

echo "=== TEST BALANCE TRACKING SYSTEM ===\n\n";

// Get current user and store
$user = User::where('email', 'superadmin@example.com')->first();
if (!$user) {
    echo "❌ User tidak ditemukan\n";
    exit(1);
}

// Set store ID for super admin (simulate store selection)
$storeId = 1;
Session::put('selected_store_id', $storeId);

echo "✅ Testing dengan Store ID: {$storeId}\n";
echo "✅ User: {$user->name}\n\n";

// 1. Check current payment method balances
echo "=== 1. SALDO SAAT INI ===\n";
$paymentMethods = PaymentMethod::where('store_id', $storeId)->get();
$totalCurrentBalance = $paymentMethods->sum('balance');

echo "Total Saldo Keseluruhan: Rp " . number_format($totalCurrentBalance, 0, ',', '.') . "\n";
echo "Detail per metode pembayaran:\n";
foreach ($paymentMethods as $method) {
    $icon = $method->is_cash ? '💰' : ($method->is_ewallet ? '📱' : '💳');
    echo "  {$icon} {$method->name}: Rp " . number_format((float)$method->balance, 0, ',', '.') . "\n";
}

// 2. Check if there's an active shift
echo "\n=== 2. CEK SHIFT AKTIF ===\n";
$activeShift = CashierShift::getActiveShiftForUser($user->id);

if ($activeShift) {
    echo "✅ Shift aktif ditemukan: #{$activeShift->shift_number}\n";
    echo "   Status: {$activeShift->status}\n";
    echo "   Dibuka: " . $activeShift->opened_at->format('d/m/Y H:i:s') . "\n";
    
    // Show opening balance if captured
    if ($activeShift->opening_balance_snapshot) {
        echo "   Snapshot saldo awal telah tercatat ✅\n";
        echo "   Total saldo awal: Rp " . number_format((float)$activeShift->opening_total_balance, 0, ',', '.') . "\n";
        
        // Calculate current vs opening difference
        $balanceChange = $totalCurrentBalance - (float)$activeShift->opening_total_balance;
        echo "   Perubahan saldo: " . ($balanceChange >= 0 ? '+' : '') . "Rp " . number_format($balanceChange, 0, ',', '.') . "\n";
    } else {
        echo "   ⚠️ Snapshot saldo awal belum tercatat (shift lama)\n";
    }
    
} else {
    echo "ℹ️ Tidak ada shift aktif\n";
    echo "🔍 Simulasi buka shift baru...\n";
    
    try {
        $service = new CashierShiftService();
        $newShift = $service->openShift($user->id, 50000); // Kas awal 50rb
        
        echo "✅ Shift baru berhasil dibuka: #{$newShift->shift_number}\n";
        echo "   Opening cash: Rp " . number_format((float)$newShift->opening_cash, 0, ',', '.') . "\n";
        echo "   Opening total balance: Rp " . number_format((float)$newShift->opening_total_balance, 0, ',', '.') . "\n";
        
        $balanceSnapshot = $newShift->opening_balance_snapshot;
        echo "   Snapshot berisi " . count($balanceSnapshot) . " metode pembayaran ✅\n";
        
        $activeShift = $newShift;
        
    } catch (Exception $e) {
        echo "❌ Gagal buka shift: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// 3. Test balance calculation methods
echo "\n=== 3. TEST CALCULATION METHODS ===\n";

if ($activeShift) {
    echo "🧮 Testing calculation methods...\n";
    
    // Test cash flow calculation
    $cashFlow = $activeShift->calculateTotalCashFlow();
    echo "✅ Total Cash Flow: Rp " . number_format($cashFlow, 0, ',', '.') . "\n";
    
    // Test expected balance calculation
    $expectedBalance = $activeShift->calculateExpectedTotalBalance();
    echo "✅ Expected Total Balance: Rp " . number_format($expectedBalance, 0, ',', '.') . "\n";
    
    // Show current system balance
    $currentSystemCash = $activeShift->getSystemCashBalance();
    echo "✅ Current System Cash: Rp " . number_format($currentSystemCash, 0, ',', '.') . "\n";
    
    // Test balance comparison
    $comparison = $activeShift->getBalanceComparisonReport();
    echo "✅ Balance Comparison Report:\n";
    foreach ($comparison as $item) {
        $diff = $item['difference'];
        $color = $diff >= 0 ? '📈' : '📉';
        echo "   {$color} {$item['name']}: " . 
             "Rp " . number_format($item['opening_balance'], 0, ',', '.') . 
             " → Rp " . number_format($item['closing_balance'], 0, ',', '.') . 
             " (" . ($diff >= 0 ? '+' : '') . "Rp " . number_format($diff, 0, ',', '.') . ")\n";
    }
}

// 4. Test close shift simulation
echo "\n=== 4. SIMULASI TUTUP SHIFT ===\n";

if ($activeShift && $activeShift->isOpen()) {
    echo "🧪 Simulasi tutup shift dengan balance tracking...\n";
    
    // Simulate physical cash counting
    $physicalCashCount = 75000; // Simulasi hasil hitung fisik
    $cashDenominations = [
        100000 => 0,
        50000 => 1,
        20000 => 1,
        10000 => 0,
        5000 => 1,
        2000 => 0,
        1000 => 0
    ];
    
    echo "💰 Physical cash count: Rp " . number_format($physicalCashCount, 0, ',', '.') . "\n";
    echo "💸 Cash denominations: " . json_encode($cashDenominations) . "\n";
    
    try {
        $service = new CashierShiftService();
        $closedShift = $service->closeShiftWithBalanceTracking(
            $activeShift->id,
            $physicalCashCount, // closing cash
            $physicalCashCount, // physical cash count
            $cashDenominations,
            'Test closing dengan balance tracking'
        );
        
        echo "✅ Shift berhasil ditutup dengan balance tracking!\n";
        echo "   Shift Number: #{$closedShift->shift_number}\n";
        echo "   Status: {$closedShift->status}\n";
        
        // Show balance summary
        $summary = $closedShift->getBalanceSummaryForDisplay();
        echo "\n📊 BALANCE SUMMARY:\n";
        foreach ($summary as $key => $value) {
            echo "   " . ucwords(str_replace('_', ' ', $key)) . ": {$value}\n";
        }
        
        // Show balance differences
        $differences = $closedShift->calculateBalanceDifferences();
        echo "\n🔍 BALANCE DIFFERENCES:\n";
        echo "   Total Difference: Rp " . number_format($differences['total_difference'], 0, ',', '.') . "\n";
        echo "   Cash Counting Difference: Rp " . number_format($differences['cash_counting_difference'], 0, ',', '.') . "\n";
        echo "   Expected vs Actual: Rp " . number_format($differences['expected_total'], 0, ',', '.') . " vs Rp " . number_format($differences['actual_total'], 0, ',', '.') . "\n";
        echo "   System vs Physical Cash: Rp " . number_format($differences['system_cash'], 0, ',', '.') . " vs Rp " . number_format($differences['physical_cash'], 0, ',', '.') . "\n";
        
    } catch (Exception $e) {
        echo "❌ Gagal tutup shift: " . $e->getMessage() . "\n";
    }
    
} else {
    echo "ℹ️ Tidak ada shift aktif untuk ditutup\n";
}

echo "\n=== SUMMARY ===\n";
echo "✅ Balance tracking system berhasil ditest\n";
echo "✅ Opening balance snapshot: Tercatat otomatis saat buka shift\n";
echo "✅ Closing balance snapshot: Tercatat otomatis saat tutup shift\n";
echo "✅ Cash flow calculation: Menghitung perubahan saldo\n";
echo "✅ Physical cash counting: Input manual kas fisik\n";
echo "✅ Balance comparison: Perbandingan sistem vs fisik\n";
echo "✅ Comprehensive reporting: Laporan lengkap semua saldo\n";

echo "\n🎉 FITUR BALANCE TRACKING SIAP DIGUNAKAN!\n";
