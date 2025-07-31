<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Transaction;

try {
    echo "Testing transaction data for infolist:\n";
    
    // Simulate eager loading like in resource
    $transaction = Transaction::with(['user', 'store', 'sumberDana'])->first();
    
    if (!$transaction) {
        echo "No transaction found\n";
        exit;
    }
    
    echo "✅ Transaction loaded with relations\n";
    echo "ID: {$transaction->id}\n";
    echo "Type: {$transaction->type}\n";
    echo "Amount: " . (string)$transaction->amount . "\n";
    echo "User: " . ($transaction->user ? $transaction->user->name : 'No user') . "\n";
    echo "Store: " . ($transaction->store ? $transaction->store->name : 'No store') . "\n";
    echo "Sumber Dana: " . ($transaction->sumberDana ? $transaction->sumberDana->name : 'No sumber dana') . "\n";
    echo "Status: {$transaction->status}\n";
    echo "Created: {$transaction->created_at}\n";
    
    echo "\n🔍 Testing formatStateUsing functions:\n";
    
    // Test type formatting
    $typeFormatted = match($transaction->type) {
        'transfer' => 'Transfer',
        'tarik_tunai' => 'Tarik Tunai',
        'jasa_transfer' => 'Jasa Transfer',
        'mode_pulsa' => 'Mode Pulsa',
        default => ucfirst($transaction->type)
    };
    echo "Type formatted: $typeFormatted\n";
    
    // Test financial impact parsing (the tricky part)
    echo "\n💰 Testing financial impact parsing:\n";
    
    $financialImpact = $transaction->financial_impact;
    echo "Financial Impact Type: " . gettype($financialImpact) . "\n";
    
    if (is_array($financialImpact)) {
        echo "✅ Already array: " . json_encode($financialImpact) . "\n";
        
        $profit = $financialImpact['profit'] ?? 0;
        echo "Profit extracted: $profit\n";
        
        // Test detailed formatting
        $details = [];
        
        if (isset($financialImpact['admin_dalam']) && is_array($financialImpact['admin_dalam'])) {
            $amount = $financialImpact['admin_dalam']['amount'] ?? 0;
            $details[] = 'Admin Dalam: Rp ' . number_format((float)$amount, 0, ',', '.');
        }
        
        if (isset($financialImpact['admin_luar']) && is_array($financialImpact['admin_luar'])) {
            $amount = $financialImpact['admin_luar']['amount'] ?? 0;
            $details[] = 'Admin Luar: Rp ' . number_format((float)$amount, 0, ',', '.');
        }
        
        if (isset($financialImpact['margin'])) {
            $details[] = 'Margin: Rp ' . number_format((float)$financialImpact['margin'], 0, ',', '.');
        }
        
        $formattedDetails = empty($details) ? '-' : implode(', ', $details);
        echo "Financial details: $formattedDetails\n";
        
    } elseif (is_string($financialImpact)) {
        echo "String format, attempting to decode...\n";
        $decoded = json_decode($financialImpact, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "✅ Successfully decoded JSON\n";
        } else {
            echo "❌ JSON decode error: " . json_last_error_msg() . "\n";
        }
    } else {
        echo "Other format: " . var_export($financialImpact, true) . "\n";
    }
    
    echo "\n✅ All tests completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
