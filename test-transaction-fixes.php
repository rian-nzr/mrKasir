<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\TransactionService;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

try {
    // Login as first user for testing
    $user = User::first();
    if (!$user) {
        echo "❌ No user found in database\n";
        exit;
    }
    Auth::login($user);
    
    // Set store if user is super admin
    if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
        session(['selected_store_id' => 1]); // Use store ID 1
    }
    
    echo "🔐 Logged in as: {$user->name}\n\n";
    echo "🧪 Testing Jasa Transfer Changes:\n";
    
    // Get cash payment method
    $cash = PaymentMethod::where('is_cash', true)->first();
    if (!$cash) {
        echo "❌ No cash payment method found\n";
        exit;
    }
    
    $initialBalance = $cash->balance;
    echo "💰 Initial cash balance: Rp " . number_format((float)$initialBalance, 0, ',', '.') . "\n";
    
    // Test jasa transfer data
    $testData = [
        'type' => 'jasa_transfer',
        'amount' => 100000,       // Add required amount field
        'terima_dana' => 100000,  // Jumlah transfer
        'admin' => 5000,          // Biaya admin
        'keterangan' => 'Test jasa transfer'
    ];
    
    $expectedReduction = $testData['terima_dana'] + $testData['admin']; // 105000
    echo "🔍 Expected cash reduction: Rp " . number_format($expectedReduction, 0, ',', '.') . "\n";
    
    // Process transaction
    $transactionService = app(TransactionService::class);
    $transaction = $transactionService->processTransaction($testData);
    
    // Check new balance
    $cash->refresh();
    $newBalance = $cash->balance;
    $actualReduction = $initialBalance - $newBalance;
    
    echo "💰 New cash balance: Rp " . number_format((float)$newBalance, 0, ',', '.') . "\n";
    echo "📊 Actual reduction: Rp " . number_format($actualReduction, 0, ',', '.') . "\n";
    
    if ($actualReduction == $expectedReduction) {
        echo "✅ Jasa Transfer: SUCCESS - Cash reduced by correct amount\n";
    } else {
        echo "❌ Jasa Transfer: FAILED - Expected {$expectedReduction}, got {$actualReduction}\n";
    }
    
    echo "\n📋 Transaction Financial Impact:\n";
    $impact = $transaction->financial_impact;
    if (is_array($impact)) {
        foreach ($impact as $key => $value) {
            if (is_array($value)) {
                echo "- $key: " . json_encode($value) . "\n";
            } else {
                echo "- $key: $value\n";
            }
        }
    }
    
    echo "\n🧪 Testing Mode Pulsa Changes:\n";
    
    // Reset balance for clean test
    $cash->update(['balance' => $initialBalance]);
    
    // Test mode pulsa with sumber_dana_id
    $testDataPulsa = [
        'type' => 'mode_pulsa',
        'amount' => 25000,        // Add required amount field (use harga_jual)
        'sumber_dana_id' => $cash->id,
        'jenis_transaksi' => 'Pulsa Telkomsel',
        'sumber' => 'Server A',
        'modal' => 20000,
        'harga_jual' => 25000,
        'keterangan' => 'Test mode pulsa'
    ];
    
    echo "🔍 Modal (should be deducted): Rp " . number_format($testDataPulsa['modal'], 0, ',', '.') . "\n";
    echo "🔍 Harga Jual (should be added): Rp " . number_format($testDataPulsa['harga_jual'], 0, ',', '.') . "\n";
    echo "🔍 Expected profit: Rp " . number_format($testDataPulsa['harga_jual'] - $testDataPulsa['modal'], 0, ',', '.') . "\n";
    
    $transactionPulsa = $transactionService->processTransaction($testDataPulsa);
    
    $cash->refresh();
    $finalBalance = $cash->balance;
    $netChange = $finalBalance - $initialBalance;
    $expectedNetChange = $testDataPulsa['harga_jual'] - $testDataPulsa['modal']; // +5000
    
    echo "💰 Final cash balance: Rp " . number_format((float)$finalBalance, 0, ',', '.') . "\n";
    echo "📊 Net change: " . ($netChange >= 0 ? '+' : '') . "Rp " . number_format($netChange, 0, ',', '.') . "\n";
    
    if ($netChange == $expectedNetChange) {
        echo "✅ Mode Pulsa: SUCCESS - Net change is correct\n";
    } else {
        echo "❌ Mode Pulsa: FAILED - Expected {$expectedNetChange}, got {$netChange}\n";
    }
    
    echo "\n📋 Mode Pulsa Financial Impact:\n";
    $impactPulsa = $transactionPulsa->financial_impact;
    if (is_array($impactPulsa)) {
        foreach ($impactPulsa as $key => $value) {
            if (is_array($value)) {
                echo "- $key: " . json_encode($value) . "\n";
            } else {
                echo "- $key: $value\n";
            }
        }
    }
    
    echo "\n🎉 All tests completed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
