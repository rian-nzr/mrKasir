<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

try {
    // Set up session and authentication like a real request
    $user = User::first();
    if (!$user) {
        echo "No user found\n";
        exit;
    }
    
    Auth::login($user);
    Session::put('selected_store_id', 1);
    
    $transaction = Transaction::with(['user', 'sumberDana', 'store'])->first();
    if (!$transaction) {
        echo "No transaction found\n";
        exit;
    }
    
    echo "Testing transaction data access:\n";
    echo "ID: " . $transaction->id . "\n";
    echo "Type: " . $transaction->type . "\n";
    echo "User: " . ($transaction->user->name ?? 'N/A') . "\n";
    echo "Sumber Dana: " . ($transaction->sumberDana->name ?? 'N/A') . "\n";
    echo "Status: " . $transaction->status . "\n";
    
    // Test all potential problematic fields
    echo "\nPotentially problematic fields:\n";
    echo "financial_impact type: " . gettype($transaction->financial_impact) . "\n";
    echo "tujuan: " . var_export($transaction->tujuan, true) . "\n";
    echo "jenis_transaksi: " . var_export($transaction->jenis_transaksi, true) . "\n";
    echo "sumber: " . var_export($transaction->sumber, true) . "\n";
    echo "keterangan: " . var_export($transaction->keterangan, true) . "\n";
    
    echo "\n✅ All data access working correctly!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
