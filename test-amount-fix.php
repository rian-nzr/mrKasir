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
    
    echo "🧪 Testing Jasa Transfer with amount field fix:\n";
    
    // Test jasa transfer data (without amount field)
    $testData = [
        'type' => 'jasa_transfer',
        'terima_dana' => 50000,  // This should become the amount
        'admin' => 5000,
        'keterangan' => 'Test jasa transfer fix'
    ];
    
    echo "🔍 Input data:\n";
    echo "- terima_dana: Rp " . number_format($testData['terima_dana'], 0, ',', '.') . "\n";
    echo "- admin: Rp " . number_format($testData['admin'], 0, ',', '.') . "\n";
    echo "- amount field: " . (isset($testData['amount']) ? 'SET' : 'NOT SET') . "\n\n";
    
    // Process transaction
    $transactionService = app(TransactionService::class);
    $transaction = $transactionService->processTransaction($testData);
    
    echo "✅ Transaction created successfully!\n";
    echo "📋 Transaction details:\n";
    echo "- ID: {$transaction->id}\n";
    echo "- Type: {$transaction->type}\n";
    echo "- Amount: Rp " . number_format((float)$transaction->amount, 0, ',', '.') . "\n";
    echo "- Terima Dana: Rp " . number_format((float)$transaction->terima_dana, 0, ',', '.') . "\n";
    echo "- Admin: Rp " . number_format((float)$transaction->admin, 0, ',', '.') . "\n";
    
    // Verify amount equals terima_dana
    if ((float)$transaction->amount == (float)$transaction->terima_dana) {
        echo "✅ Amount field correctly set to terima_dana value\n";
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
