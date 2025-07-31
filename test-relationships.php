<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Transaction;

try {
    echo "Testing Transaction relationships:\n";
    
    $transaction = Transaction::with(['user', 'store', 'sumberDana'])->first();
    
    if (!$transaction) {
        echo "No transaction found\n";
        exit;
    }
    
    echo "✅ Transaction found: ID {$transaction->id}\n";
    
    // Test user relationship
    try {
        $user = $transaction->user;
        echo "✅ User relationship: " . ($user ? "User ID {$user->id}" : "No user") . "\n";
    } catch (Exception $e) {
        echo "❌ User relationship error: " . $e->getMessage() . "\n";
    }
    
    // Test store relationship
    try {
        $store = $transaction->store;
        echo "✅ Store relationship: " . ($store ? "Store ID {$store->id}" : "No store") . "\n";
    } catch (Exception $e) {
        echo "❌ Store relationship error: " . $e->getMessage() . "\n";
    }
    
    // Test sumberDana relationship
    try {
        $sumberDana = $transaction->sumberDana;
        echo "✅ SumberDana relationship: " . ($sumberDana ? "Payment Method ID {$sumberDana->id}" : "No payment method") . "\n";
    } catch (Exception $e) {
        echo "❌ SumberDana relationship error: " . $e->getMessage() . "\n";
    }
    
    echo "\n🔍 Testing array conversion scenarios:\n";
    
    // Test toArray method
    try {
        $array = $transaction->toArray();
        echo "✅ toArray() successful, keys: " . implode(', ', array_keys($array)) . "\n";
    } catch (Exception $e) {
        echo "❌ toArray() error: " . $e->getMessage() . "\n";
    }
    
    // Test JSON serialization
    try {
        $json = $transaction->toJson();
        echo "✅ JSON serialization successful\n";
    } catch (Exception $e) {
        echo "❌ JSON serialization error: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
