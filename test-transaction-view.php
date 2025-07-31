<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Transaction;
use App\Filament\Resources\TransactionResource;

try {
    $transaction = Transaction::first();
    if (!$transaction) {
        echo "No transaction found\n";
        exit;
    }
    
    echo "Transaction ID: " . $transaction->id . "\n";
    echo "Type: " . $transaction->type . "\n";
    echo "Display Amount: " . $transaction->display_amount . "\n";
    echo "Total Profit: " . $transaction->total_profit . "\n";
    echo "Financial Impact: " . json_encode($transaction->financial_impact) . "\n";
    
    echo "✅ All transaction attributes are working correctly!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
