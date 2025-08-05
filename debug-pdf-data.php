<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Models\CashierShift;
use App\Services\CashierShiftService;

// Set environment
$_ENV['APP_ENV'] = 'local';

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 DEBUGGING PDF DATA ISSUES\n";
echo "=============================\n\n";

try {
    // Find a shift with data
    $shift = CashierShift::with(['store', 'user', 'transactions', 'orders'])
        ->whereHas('orders')
        ->orderBy('created_at', 'desc')
        ->first();

    if (!$shift) {
        echo "❌ No shifts with orders found in database\n";
        exit(1);
    }

    echo "✅ Testing with shift: {$shift->shift_number}\n";
    echo "📅 Shift date: {$shift->created_at->format('d/m/Y H:i:s')}\n";
    echo "👤 Kasir: " . ($shift->user->name ?? 'N/A') . "\n";
    echo "🏪 Store: " . ($shift->store->name ?? 'N/A') . "\n";
    echo "📦 Orders count: " . $shift->orders->count() . "\n";
    echo "💰 Transactions count: " . $shift->transactions->count() . "\n\n";

    // Initialize service
    $service = new CashierShiftService();
    
    echo "📊 Getting shift report data...\n";
    
    // Get the raw data that goes to PDF
    $reportData = $service->getShiftReport($shift->id);
    
    echo "🔍 SALES SUMMARY DEBUG:\n";
    echo "=======================\n";
    foreach ($reportData['sales_summary'] as $key => $value) {
        echo "  {$key}: {$value}\n";
    }
    
    echo "\n💳 PAYMENT BREAKDOWN DEBUG:\n";
    echo "===========================\n";
    foreach ($reportData['payment_breakdown'] as $payment) {
        echo "  Payment: " . ($payment['name'] ?? 'N/A') . "\n";
        echo "    Count: " . ($payment['count'] ?? 0) . "\n"; 
        echo "    Amount: " . ($payment['amount'] ?? 0) . "\n";
        echo "    Formatted: " . ($payment['formatted_amount'] ?? 'N/A') . "\n";
        echo "  ---\n";
    }
    
    echo "\n🏆 PRODUCT SALES DEBUG:\n";
    echo "========================\n";
    echo "Product sales count: " . count($reportData['product_sales']) . "\n";
    if (count($reportData['product_sales']) > 0) {
        $firstProduct = $reportData['product_sales'][0];
        echo "First product example:\n";
        foreach ($firstProduct as $key => $value) {
            echo "  {$key}: {$value}\n";
        }
    }
    
    echo "\n💰 CASH MANAGEMENT DEBUG:\n";
    echo "==========================\n";
    foreach ($reportData['cash_management'] as $key => $value) {
        echo "  {$key}: {$value}\n";
    }
    
    echo "\n🔍 RAW SHIFT DATA:\n";
    echo "==================\n";
    echo "Opening cash: " . $shift->opening_cash . "\n";
    echo "Closing cash: " . $shift->closing_cash . "\n";
    echo "Expected cash: " . $shift->expected_cash . "\n";
    echo "Orders total: " . $shift->orders->sum('total_amount') . "\n";
    
    // Check if we have any orders with products
    echo "\n📦 ORDERS ANALYSIS:\n";
    echo "===================\n";
    foreach ($shift->orders->take(3) as $order) {
        echo "Order ID: {$order->id}\n";
        echo "  Total: {$order->total_amount}\n";
        echo "  Status: {$order->status}\n";
        echo "  Products count: " . $order->orderProducts->count() . "\n";
        if ($order->orderProducts->count() > 0) {
            echo "  First product: " . ($order->orderProducts->first()->product->name ?? 'N/A') . "\n";
        }
        echo "  ---\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
}
