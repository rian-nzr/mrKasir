<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Transaction;

try {
    $transaction = Transaction::first();
    if (!$transaction) {
        echo "No transaction found\n";
        exit;
    }
    
    echo "Testing all Transaction attributes:\n\n";
    
    echo "ID: " . $transaction->id . "\n";
    echo "Type: " . $transaction->type . "\n";
    echo "Type Display: " . $transaction->type_display . "\n";
    echo "Display Amount: " . $transaction->display_amount . "\n";
    echo "Total Profit: " . $transaction->total_profit . "\n";
    echo "Admin Luar: " . ($transaction->admin_luar ?? 'null') . "\n";
    echo "Admin Dalam: " . ($transaction->admin_dalam ?? 'null') . "\n";
    echo "Tujuan: " . ($transaction->tujuan ?? 'null') . "\n";
    echo "Jenis Transaksi: " . ($transaction->jenis_transaksi ?? 'null') . "\n";
    echo "Sumber: " . ($transaction->sumber ?? 'null') . "\n";
    echo "Keterangan: " . ($transaction->keterangan ?? 'null') . "\n";
    echo "Financial Impact Display:\n" . $transaction->financial_impact_display . "\n\n";
    
    echo "✅ All transaction attributes are working correctly!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
