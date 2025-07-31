<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Transaction;
use App\Filament\Resources\TransactionResource\Pages\ViewTransaction;

try {
    $transaction = Transaction::first();
    if (!$transaction) {
        echo "No transaction found\n";
        exit;
    }
    
    echo "Testing ViewTransaction mutateRecordDataUsing:\n";
    
    // Get the raw data
    $rawData = $transaction->toArray();
    echo "Raw data keys: " . implode(', ', array_keys($rawData)) . "\n";
    
    // Test the mutation method
    $viewTransaction = new ViewTransaction();
    $method = new ReflectionMethod($viewTransaction, 'mutateRecordDataUsing');
    $method->setAccessible(true);
    
    $mutatedData = $method->invoke($viewTransaction, $rawData);
    
    echo "\nMutated data:\n";
    foreach ($mutatedData as $key => $value) {
        echo "- $key: " . gettype($value) . " -> " . (is_string($value) ? substr($value, 0, 100) : var_export($value, true)) . "\n";
    }
    
    echo "\n✅ Data mutation successful!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
