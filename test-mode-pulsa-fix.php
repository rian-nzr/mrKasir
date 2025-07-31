<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\TransactionService;
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
        session(['selected_store_id' => 1]);
    }
    
    echo "🔐 Logged in as: {$user->name}\n\n";
    
    echo "🧪 Testing Mode Pulsa with amount field fix:\n";
    
    // Test mode pulsa data (without amount field)
    $testData = [
        'type' => 'mode_pulsa',
        'jenis_transaksi' => 'Pulsa Telkomsel',
        'sumber' => 'Server A',
        'modal' => 20000,
        'harga_jual' => 25000,  // This should become the amount
        'keterangan' => 'Test mode pulsa fix'
    ];
    
    echo "🔍 Input data:\n";
    echo "- modal: Rp " . number_format($testData['modal'], 0, ',', '.') . "\n";
    echo "- harga_jual: Rp " . number_format($testData['harga_jual'], 0, ',', '.') . "\n";
    echo "- amount field: " . (isset($testData['amount']) ? 'SET' : 'NOT SET') . "\n\n";
    
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
    
    // Verify amount equals harga_jual
    if ((float)$transaction->amount == (float)$transaction->harga_jual) {
        echo "✅ Amount field correctly set to harga_jual value\n";
    } else {
        echo "❌ Amount field mismatch!\n";
    }
    
    echo "\n💰 Financial Impact:\n";
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
