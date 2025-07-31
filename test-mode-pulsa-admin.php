<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\TransactionService;
use App\Models\User;
use App\Models\PaymentMethod;
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
        session(['selected_store_id' => 1]);
    }
    
    echo "🔐 Logged in as: {$user->name}\n\n";
    
    // Get current cash balance
    $cash = PaymentMethod::where('is_cash', true)->first();
    $initialBalance = $cash->balance;
    
    echo "🧪 Testing Mode Pulsa with Admin Field:\n";
    echo "💰 Initial cash balance: Rp " . number_format((float)$initialBalance, 0, ',', '.') . "\n\n";
    
    // Test mode pulsa data dengan admin
    $testData = [
        'type' => 'mode_pulsa',
        'jenis_transaksi' => 'Pulsa Telkomsel 50K',
        'sumber' => 'Server Premium',
        'modal' => 48000,
        'harga_jual' => 50000,
        'admin' => 2000,  // Biaya admin tambahan
        'keterangan' => 'Test mode pulsa dengan admin'
    ];
    
    echo "🔍 Input data:\n";
    echo "- Modal: Rp " . number_format($testData['modal'], 0, ',', '.') . "\n";
    echo "- Harga Jual: Rp " . number_format($testData['harga_jual'], 0, ',', '.') . "\n";
    echo "- Biaya Admin: Rp " . number_format($testData['admin'], 0, ',', '.') . "\n";
    
    $expectedProfit = $testData['harga_jual'] - $testData['modal'] + $testData['admin'];
    echo "- Expected Total Profit: Rp " . number_format($expectedProfit, 0, ',', '.') . "\n\n";
    
    // Process transaction
    $transactionService = app(TransactionService::class);
    $transaction = $transactionService->processTransaction($testData);
    
    echo "✅ Transaction created successfully!\n";
    echo "📋 Transaction details:\n";
    echo "- ID: {$transaction->id}\n";
    echo "- Type: {$transaction->type}\n";
    echo "- Amount: Rp " . number_format((float)$transaction->amount, 0, ',', '.') . "\n";
    echo "- Modal: Rp " . number_format((float)$transaction->modal, 0, ',', '.') . "\n";
    echo "- Harga Jual: Rp " . number_format((float)$transaction->harga_jual, 0, ',', '.') . "\n";
    echo "- Admin: Rp " . number_format((float)$transaction->admin, 0, ',', '.') . "\n";
    
    // Check cash balance changes
    $cash->refresh();
    $finalBalance = $cash->balance;
    $netChange = (float)$finalBalance - (float)$initialBalance;
    
    echo "\n💰 Cash Balance Changes:\n";
    echo "- Initial: Rp " . number_format((float)$initialBalance, 0, ',', '.') . "\n";
    echo "- Final: Rp " . number_format((float)$finalBalance, 0, ',', '.') . "\n";
    echo "- Net Change: " . ($netChange >= 0 ? '+' : '') . "Rp " . number_format($netChange, 0, ',', '.') . "\n";
    
    if ($netChange == $expectedProfit) {
        echo "✅ Cash balance change matches expected profit!\n";
    } else {
        echo "❌ Cash balance mismatch - Expected: {$expectedProfit}, Got: {$netChange}\n";
    }
    
    echo "\n💰 Financial Impact Details:\n";
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
    
    echo "\n🎉 Test completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
