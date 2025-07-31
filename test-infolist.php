<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Transaction;
use App\Filament\Resources\TransactionResource;
use Filament\Infolists\Infolist;

try {
    $transaction = Transaction::first();
    if (!$transaction) {
        echo "No transaction found\n";
        exit;
    }
    
    echo "Testing TransactionResource infolist:\n";
    echo "Transaction ID: {$transaction->id}\n";
    echo "Type: {$transaction->type}\n";
    echo "Amount: " . (string)$transaction->amount . "\n";
    echo "Financial Impact Type: " . gettype($transaction->financial_impact) . "\n";
    
    if (is_string($transaction->financial_impact)) {
        $decoded = json_decode($transaction->financial_impact, true);
        echo "Financial Impact Decoded: " . (json_last_error() === JSON_ERROR_NONE ? "✅ Valid JSON" : "❌ Invalid JSON") . "\n";
        if ($decoded) {
            echo "Profit: " . ($decoded['profit'] ?? 'Not set') . "\n";
        }
    } elseif (is_array($transaction->financial_impact)) {
        echo "Financial Impact: Array with keys: " . implode(', ', array_keys($transaction->financial_impact)) . "\n";
        echo "Profit: " . ($transaction->financial_impact['profit'] ?? 'Not set') . "\n";
    } else {
        echo "Financial Impact: " . var_export($transaction->financial_impact, true) . "\n";
    }
    
    // Test the formatStateUsing functions
    echo "\nTesting format functions:\n";
    
    // Test type formatting
    $typeFormatted = match($transaction->type) {
        'transfer' => 'Transfer',
        'tarik_tunai' => 'Tarik Tunai',
        'jasa_transfer' => 'Jasa Transfer',
        'mode_pulsa' => 'Mode Pulsa',
        default => ucfirst($transaction->type)
    };
    echo "Type formatted: $typeFormatted\n";
    
    // Test financial impact processing
    $financialImpact = null;
    if (is_string($transaction->financial_impact)) {
        $financialImpact = json_decode((string)$transaction->financial_impact, true);
    } elseif (is_array($transaction->financial_impact)) {
        $financialImpact = $transaction->financial_impact;
    }
    
    $profit = $financialImpact['profit'] ?? 0;
    echo "Profit extracted: $profit\n";
    
    echo "\n✅ All tests passed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
