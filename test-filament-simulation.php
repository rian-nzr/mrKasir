<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Transaction;
use Filament\Infolists\Components\TextEntry;

try {
    echo "🔍 Testing Filament TextEntry format simulation:\n";
    
    $transaction = Transaction::with(['user', 'store', 'sumberDana'])->first();
    
    if (!$transaction) {
        echo "No transaction found\n";
        exit;
    }
    
    echo "✅ Transaction loaded: ID {$transaction->id}\n";
    
    // Simulate what happens in formatStateUsing for relationships
    echo "\n📋 Testing relationship access:\n";
    
    // Test user.name access
    try {
        $userName = $transaction->user ? $transaction->user->name : '-';
        echo "✅ user.name: $userName\n";
    } catch (Exception $e) {
        echo "❌ user.name error: " . $e->getMessage() . "\n";
    }
    
    // Test sumberDana.name access
    try {
        $sumberDanaName = $transaction->sumberDana ? $transaction->sumberDana->name : '-';
        echo "✅ sumberDana.name: $sumberDanaName\n";
    } catch (Exception $e) {
        echo "❌ sumberDana.name error: " . $e->getMessage() . "\n";
    }
    
    // Test the tricky financial_impact formatStateUsing
    echo "\n💰 Testing financial impact formatStateUsing:\n";
    
    try {
        // Simulate formatStateUsing function for total_profit
        $record = $transaction;
        
        if (!$record || !isset($record->financial_impact)) {
            throw new Exception("No financial_impact");
        }
        
        $financialImpact = $record->financial_impact;
        
        // Handle string JSON
        if (is_string($financialImpact)) {
            $financialImpact = json_decode($financialImpact, true);
        }
        
        // Ensure it's an array
        if (!is_array($financialImpact)) {
            throw new Exception("financial_impact is not array");
        }
        
        $profit = $financialImpact['profit'] ?? 0;
        echo "✅ Total profit calculation: $profit\n";
        
    } catch (Exception $e) {
        echo "❌ Total profit error: " . $e->getMessage() . "\n";
    }
    
    try {
        // Simulate formatStateUsing function for financial_details
        $record = $transaction;
        
        if (!$record || !isset($record->financial_impact)) {
            throw new Exception("No financial_impact");
        }
        
        $financialImpact = $record->financial_impact;
        
        // Handle string JSON
        if (is_string($financialImpact)) {
            $financialImpact = json_decode($financialImpact, true);
        }
        
        // Ensure it's an array
        if (!is_array($financialImpact)) {
            throw new Exception("financial_impact is not array");
        }
        
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
        
        $result = empty($details) ? '-' : implode(', ', $details);
        echo "✅ Financial details: $result\n";
        
    } catch (Exception $e) {
        echo "❌ Financial details error: " . $e->getMessage() . "\n";
    }
    
    // Test all other fields
    echo "\n📊 Testing other fields:\n";
    
    $fields = [
        'keterangan' => $transaction->keterangan ?: '-',
        'tujuan' => $transaction->tujuan ?: '-',
        'terima_dana' => $transaction->terima_dana ?: '-',
        'jenis_transaksi' => $transaction->jenis_transaksi ?: '-',
        'sumber' => $transaction->sumber ?: '-',
        'modal' => $transaction->modal ?: 0,
        'harga_jual' => $transaction->harga_jual ?: 0,
    ];
    
    foreach ($fields as $field => $value) {
        echo "✅ $field: " . (is_null($value) ? 'NULL' : (string)$value) . "\n";
    }
    
    echo "\n🎉 All simulation tests passed!\n";
    
} catch (Exception $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
