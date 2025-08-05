<?php

require_once 'vendor/autoload.php';

// Set environment
$_ENV['APP_ENV'] = 'local';

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 CHECKING ORDER_PRODUCTS TABLE STRUCTURE\n";
echo "==========================================\n\n";

try {
    $columns = DB::select("SHOW COLUMNS FROM order_products");
    
    echo "📋 ORDER_PRODUCTS COLUMNS:\n";
    foreach ($columns as $column) {
        echo "  - {$column->Field} ({$column->Type})\n";
    }
    
    echo "\n📊 SAMPLE DATA:\n";
    $sample = DB::table('order_products')
        ->join('orders', 'order_products.order_id', '=', 'orders.id')
        ->select('order_products.*', 'orders.cashier_shift_id')
        ->first();
    
    if ($sample) {
        foreach ((array)$sample as $key => $value) {
            echo "  {$key}: {$value}\n";
        }
    } else {
        echo "  No data found\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
