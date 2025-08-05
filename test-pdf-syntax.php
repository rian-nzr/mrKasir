<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: __DIR__)
    ->withRouting(
        web: __DIR__.'/routes/web.php',
        commands: __DIR__.'/routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🧪 PDF TEMPLATE SYNTAX TEST\n";
echo "===========================\n\n";

try {
    // Get a shift for testing
    $shift = App\Models\CashierShift::with(['user', 'store'])->first();
    
    if (!$shift) {
        echo "❌ No shift found for testing\n";
        exit;
    }

    echo "✅ Testing with shift: {$shift->shift_number}\n\n";

    // Create minimal test data
    $testData = [
        'shift' => $shift,
        'sales_summary' => [
            'total_transactions' => 0,
            'gross_sales' => 0,
            'total_discounts' => 0,
            'net_sales' => 0,
            'average_transaction' => 0,
            'largest_transaction' => 0,
            'smallest_transaction' => 0,
        ],
        'payment_breakdown' => [],
        'product_sales' => [],
        'hourly_sales' => [],
        'cash_management' => [
            'opening_cash' => $shift->opening_cash ?? 0,
            'closing_cash' => 0,
            'expected_cash' => 0,
            'cash_difference' => 0,
            'cash_outs' => 0
        ],
        'balance_tracking' => [
            'opening_cash' => $shift->opening_cash ?? 0,
            'opening_total_balance' => $shift->opening_total_balance ?? 0,
        ],
        'recommendations' => [],
        'cash_flows' => []
    ];

    echo "📋 Attempting to compile template...\n";
    $html = view('pdf.cashier-shift-report', $testData)->render();
    
    echo "✅ SUCCESS! Template compiled without errors\n";
    echo "📄 HTML Length: " . number_format(strlen($html)) . " characters\n";
    echo "🎨 Contains icons: " . (strpos($html, '💰') !== false ? 'YES' : 'NO') . "\n";
    echo "📊 Contains sections: " . (strpos($html, 'section-title') !== false ? 'YES' : 'NO') . "\n\n";
    
    echo "🎉 TEMPLATE SYNTAX FIXED!\n";
    echo "========================\n";
    echo "The PDF template no longer has syntax errors.\n";
    echo "You can now generate PDF reports from Filament.\n\n";
    
    echo "🚀 Next steps:\n";
    echo "1. Go to Filament Admin Panel\n";
    echo "2. Navigate to Cashier Shifts\n";
    echo "3. View any shift detail\n";
    echo "4. Click 'Generate PDF Report' button\n";
    echo "5. PDF should download successfully\n";

} catch (Exception $e) {
    echo "❌ ERROR FOUND: " . $e->getMessage() . "\n";
    echo "📍 Location: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    
    if (strpos($e->getMessage(), 'endif') !== false) {
        echo "🔧 BLADE DIRECTIVE ERROR:\n";
        echo "This indicates missing or unmatched @if/@endif pairs\n";
        echo "The template needs further syntax correction.\n";
    }
    
    if (strpos($e->getMessage(), 'unexpected') !== false) {
        echo "🔧 SYNTAX ERROR:\n";
        echo "There's still a syntax issue in the template.\n";
        echo "Check for unmatched brackets, quotes, or blade directives.\n";
    }
}
