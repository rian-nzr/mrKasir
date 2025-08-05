<?php

namespace App\Services;

use App\Models\CashierShift;
use App\Models\CashOut;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Exception;

class CashierShiftService
{
    public function openShift(
        int $userId, 
        float $openingCash, 
        ?string $location = null, 
        ?string $notes = null
    ): CashierShift {
        $maxAttempts = 3;
        $attempt = 0;
        
        while ($attempt < $maxAttempts) {
            DB::beginTransaction();
            
            try {
                // Check if user already has an open shift
                $existingShift = CashierShift::getActiveShiftForUser($userId);
                if ($existingShift) {
                    DB::rollBack();
                    throw new Exception('User already has an active shift. Please close the current shift first.');
                }

                // Get user and store
                $user = User::findOrFail($userId);
                $storeId = $user->store_id ?? session('selected_store_id');
                
                if (!$storeId) {
                    DB::rollBack();
                    throw new Exception('Store ID not found. Please select a store.');
                }

                // Check if store already has an open shift (additional validation)
                $storeOpenShift = CashierShift::where('store_id', $storeId)
                    ->where('status', CashierShift::STATUS_OPEN)
                    ->first();
                    
                if ($storeOpenShift && $storeOpenShift->user_id !== $userId) {
                    DB::rollBack();
                    throw new Exception('Store already has an open shift by another user. Only one shift per store can be open at a time.');
                }

                // Generate unique shift number
                $shiftNumber = CashierShift::generateShiftNumber($storeId);

                // Create new shift with explicit shift number
                $shift = CashierShift::create([
                    'store_id' => $storeId,
                    'user_id' => $userId,
                    'shift_number' => $shiftNumber,
                    'status' => CashierShift::STATUS_OPEN,
                    'opened_at' => now(),
                    'opening_cash' => $openingCash,
                    'location' => $location,
                    'notes' => $notes,
                ]);

                // Capture opening balance snapshot of all payment methods
                $shift->captureOpeningBalanceSnapshot();

                // Update cash payment method balance after successful shift creation
                $this->updateCashPaymentMethodBalance($storeId, $openingCash);

                DB::commit();
                
                return $shift;
                
            } catch (\Illuminate\Database\QueryException $e) {
                DB::rollBack();
                
                // If it's a duplicate entry error, retry with a new shift number
                if ($e->getCode() == 23000 && str_contains($e->getMessage(), 'Duplicate entry')) {
                    $attempt++;
                    if ($attempt < $maxAttempts) {
                        // Small delay before retry
                        usleep(rand(100000, 300000)); // 100-300ms
                        continue;
                    }
                }
                
                throw $e;
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        }
        
        throw new Exception('Failed to create shift after multiple attempts. Please try again.');
    }

    public function closeShift(
        int $shiftId, 
        float $closingCash, 
        ?string $closingNotes = null
    ): CashierShift {
        DB::beginTransaction();
        
        try {
            $shift = CashierShift::findOrFail($shiftId);
            
            if (!$shift->canBeClosed()) {
                throw new Exception('This shift cannot be closed.');
            }

            $shift->close($closingCash, $closingNotes);
            
            // Update cash payment method balance with closing cash
            $this->updateCashPaymentMethodBalance($shift->store_id, $closingCash);
            
            DB::commit();
            
            return $shift;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Close shift with enhanced balance tracking
     */
    public function closeShiftWithBalanceTracking(
        int $shiftId,
        float $closingCash,
        float $physicalCashCount,
        array $cashDenominations = [],
        ?string $closingNotes = null
    ): CashierShift {
        DB::beginTransaction();
        
        try {
            $shift = CashierShift::findOrFail($shiftId);
            
            if (!$shift->canBeClosed()) {
                throw new Exception('This shift cannot be closed.');
            }

            // 1. Capture closing balance snapshot
            $shift->captureClosingBalanceSnapshot();
            
            // 2. Calculate expected total balance
            $shift->calculateExpectedTotalBalance();
            
            // 3. Set physical cash count
            $shift->setPhysicalCashCount($physicalCashCount, $cashDenominations);
            
            // 4. Calculate all balance differences
            $balanceDifferences = $shift->calculateBalanceDifferences();
            
            // 5. Close the shift using existing method
            $shift->close($closingCash, $closingNotes);
            
            // 6. Update cash payment method balance with physical cash count
            $this->updateCashPaymentMethodBalance($shift->store_id, $physicalCashCount);
            
            DB::commit();
            
            return $shift->fresh(); // Return fresh instance with all updates
            
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function suspendShift(int $shiftId, ?string $notes = null): CashierShift
    {
        DB::beginTransaction();
        
        try {
            $shift = CashierShift::findOrFail($shiftId);
            
            if (!$shift->isOpen()) {
                throw new Exception('Only open shifts can be suspended.');
            }

            $shift->suspend($notes);
            
            DB::commit();
            
            return $shift;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function resumeShift(int $shiftId): CashierShift
    {
        DB::beginTransaction();
        
        try {
            $shift = CashierShift::findOrFail($shiftId);
            
            if ($shift->status !== CashierShift::STATUS_SUSPENDED) {
                throw new Exception('Only suspended shifts can be resumed.');
            }

            // Check if user doesn't have another open shift
            $existingShift = CashierShift::getActiveShiftForUser($shift->user_id);
            if ($existingShift && $existingShift->id !== $shift->id) {
                throw new Exception('User already has an active shift.');
            }

            $shift->update([
                'status' => CashierShift::STATUS_OPEN,
            ]);
            
            DB::commit();
            
            return $shift;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function addCashOut(
        int $shiftId,
        int $userId,
        float $amount,
        string $reason,
        ?string $notes = null,
        bool $requireApproval = true
    ): CashOut {
        DB::beginTransaction();
        
        try {
            $shift = CashierShift::findOrFail($shiftId);
            
            if (!$shift->isOpen()) {
                throw new Exception('Cash out can only be added to open shifts.');
            }

            $cashOut = CashOut::create([
                'store_id' => $shift->store_id,
                'cashier_shift_id' => $shiftId,
                'user_id' => $userId,
                'amount' => $amount,
                'reason' => $reason,
                'notes' => $notes,
                'status' => $requireApproval ? CashOut::STATUS_PENDING : CashOut::STATUS_APPROVED,
            ]);

            // If auto-approved, update cash out amount in shift immediately
            if (!$requireApproval) {
                $cashOut->update([
                    'approved_at' => now(),
                    'approved_by' => $userId,
                ]);
            }

            DB::commit();
            
            return $cashOut;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function approveCashOut(int $cashOutId, int $approvedById): CashOut
    {
        DB::beginTransaction();
        
        try {
            $cashOut = CashOut::findOrFail($cashOutId);
            
            if (!$cashOut->isPending()) {
                throw new Exception('Only pending cash outs can be approved.');
            }

            $cashOut->approve($approvedById);
            
            // Update cash payment method balance by reducing the cash out amount
            $shift = $cashOut->cashierShift;
            $this->subtractFromCashBalance($shift->store_id, (float) $cashOut->amount);
            
            DB::commit();
            
            return $cashOut;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function rejectCashOut(int $cashOutId, int $rejectedById, string $rejectionNotes): CashOut
    {
        DB::beginTransaction();
        
        try {
            $cashOut = CashOut::findOrFail($cashOutId);
            
            if (!$cashOut->isPending()) {
                throw new Exception('Only pending cash outs can be rejected.');
            }

            $cashOut->reject($rejectedById, $rejectionNotes);
            
            DB::commit();
            
            return $cashOut;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getShiftReport(int $shiftId): array
    {
        $shift = CashierShift::with([
            'user',
            'store',
            'orders.orderProducts.product',
            'orders.paymentMethod',
            'transactions',
            'cashOuts'
        ])->findOrFail($shiftId);

        // Calculate detailed sales summary
        $salesSummary = $this->calculateSalesSummary($shift);
        
        // Calculate payment method breakdown
        $paymentBreakdown = $this->calculatePaymentBreakdown($shift);
        
        // Calculate product sales
        $productSales = $this->calculateProductSales($shift);
        
        // Calculate hourly sales
        $hourlySales = $this->calculateHourlySales($shift);

        return [
            'shift' => $shift,
            'sales_summary' => $salesSummary,
            'payment_breakdown' => $paymentBreakdown,
            'product_sales' => $productSales,
            'hourly_sales' => $hourlySales,
            'cash_management' => [
                'opening_cash' => $shift->opening_cash,
                'closing_cash' => $shift->closing_cash,
                'expected_cash' => $shift->expected_cash,
                'cash_difference' => $shift->cash_difference,
                'cash_sales' => $shift->getCashSalesTotal(),
                'cash_outs' => $shift->cashOuts()->approved()->sum('amount'),
            ],
        ];
    }

    public function validateCashDifference(CashierShift $shift, float $tolerance = 50000): array
    {
        $difference = abs((float) $shift->cash_difference);
        $status = 'ok';
        $message = 'Cash balance is correct.';
        
        if ($difference > $tolerance) {
            $status = 'warning';
            $message = "Cash difference exceeds tolerance limit of Rp " . number_format($tolerance, 0, ',', '.');
        } elseif ($difference > 0) {
            $status = 'info';
            $message = "Minor cash difference of Rp " . number_format($difference, 0, ',', '.');
        }

        return [
            'status' => $status,
            'message' => $message,
            'difference' => $shift->cash_difference,
            'absolute_difference' => $difference,
            'tolerance' => $tolerance,
            'within_tolerance' => $difference <= $tolerance,
        ];
    }

    public function generateShiftPdf(int $shiftId)
    {
        $report = $this->getShiftReport($shiftId);
        
        // Add comprehensive balance tracking data
        $report['balance_tracking'] = $this->getBalanceTrackingData($report['shift']);
        
        // Add additional insights and recommendations
        $report['recommendations'] = $this->generateRecommendations($report);
        
        // Add cash flow history
        $report['cash_flows'] = $this->getCashFlowHistory($report['shift']);
        
        // Generate PDF using DomPDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.cashier-shift-report', $report)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => true,
                'defaultFont' => 'DejaVu Sans'
            ]);
        
        $filename = "shift_report_{$shiftId}_" . now()->format('Y-m-d_H-i-s') . ".pdf";
        
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ]);
    }

    private function getBalanceTrackingData(CashierShift $shift): array
    {
        $data = [
            'opening_cash' => (float)$shift->opening_cash,
            'opening_total_balance' => (float)$shift->opening_total_balance,
            
            // Add formatted versions
            'opening_balance_formatted' => 'Rp ' . number_format((float)$shift->opening_total_balance, 0, ',', '.'),
        ];

        // Opening balance breakdown
        if ($shift->opening_balance_snapshot) {
            $openingSnapshot = is_string($shift->opening_balance_snapshot) 
                ? json_decode($shift->opening_balance_snapshot, true)
                : $shift->opening_balance_snapshot;
            
            $paymentMethods = \App\Models\PaymentMethod::where('store_id', $shift->store_id)->get();
            $data['opening_balance_breakdown'] = [];
            
            foreach ($paymentMethods as $method) {
                $balance = $openingSnapshot[$method->id] ?? 0;
                // Ensure balance is numeric
                if (is_array($balance)) {
                    $balance = 0;
                }
                $balance = (float)$balance;
                
                $data['opening_balance_breakdown'][] = [
                    'name' => $method->name,
                    'type' => $method->is_cash ? 'cash' : ($method->is_ewallet ? 'ewallet' : 'card'),
                    'balance' => $balance,
                    'formatted_amount' => 'Rp ' . number_format($balance, 0, ',', '.'),
                    'icon' => $method->is_cash ? '💵' : ($method->is_ewallet ? '📱' : '💳'),
                    'color' => $method->is_cash ? '#10b981' : ($method->is_ewallet ? '#3b82f6' : '#8b5cf6'),
                    'note' => $method->is_cash ? 'Input Kasir' : 'Saldo Sistem'
                ];
            }
        }

        // Closing balance data if shift is closed
        if ($shift->status === 'closed') {
            $data['physical_cash_count'] = (float)$shift->physical_cash_count;
            $data['expected_cash_balance'] = (float)$shift->expected_cash_balance;
            $data['closing_total_balance'] = (float)$shift->closing_total_balance;
            $data['cash_difference'] = (float)$shift->cash_difference;
            $data['balance_difference'] = (float)$shift->cash_difference;

            // Add formatted closing balance data
            $data['closing_balance_formatted'] = 'Rp ' . number_format((float)$shift->closing_total_balance, 0, ',', '.');
            $data['balance_difference_formatted'] = 'Rp ' . number_format((float)$shift->cash_difference, 0, ',', '.');

            // Closing balance breakdown
            if ($shift->closing_balance_snapshot) {
                $closingSnapshot = is_string($shift->closing_balance_snapshot) 
                    ? json_decode($shift->closing_balance_snapshot, true)
                    : $shift->closing_balance_snapshot;
                
                $openingSnapshot = is_string($shift->opening_balance_snapshot) 
                    ? json_decode($shift->opening_balance_snapshot, true)
                    : $shift->opening_balance_snapshot;

                $paymentMethods = \App\Models\PaymentMethod::where('store_id', $shift->store_id)->get();
                $data['closing_balance_breakdown'] = [];
                
                foreach ($paymentMethods as $method) {
                    $openingBalance = $openingSnapshot[$method->id] ?? 0;
                    $closingBalance = $closingSnapshot[$method->id] ?? 0;
                    
                    // Ensure values are numeric
                    if (is_array($openingBalance)) $openingBalance = 0;  
                    if (is_array($closingBalance)) $closingBalance = 0;
                    
                    $openingBalance = (float)$openingBalance;
                    $closingBalance = (float)$closingBalance;
                    $difference = $closingBalance - $openingBalance;
                    
                    // Calculate transactions total for this payment method
                    $transactionsTotal = $shift->orders()
                        ->where('payment_method_id', $method->id)
                        ->sum('total_price');
                    
                    $data['closing_balance_breakdown'][] = [
                        'name' => $method->name,
                        'opening' => $openingBalance,
                        'closing' => $closingBalance,
                        'sales' => (float)$transactionsTotal,
                        'difference' => $difference,
                        'icon' => $method->is_cash ? '💵' : ($method->is_ewallet ? '📱' : '💳'),
                        
                        // Formatted values
                        'opening_formatted' => 'Rp ' . number_format($openingBalance, 0, ',', '.'),
                        'closing_formatted' => 'Rp ' . number_format($closingBalance, 0, ',', '.'),
                        'sales_formatted' => 'Rp ' . number_format($transactionsTotal, 0, ',', '.'),
                        'difference_formatted' => 'Rp ' . number_format($difference, 0, ',', '.'),
                    ];
                }
            }
        }

        return $data;
    }

    private function generateRecommendations(array $report): array
    {
        $recommendations = [];
        $shift = $report['shift'];
        $sales = $report['sales_summary'];

        // Cash difference recommendation
        if ($shift->status === 'closed' && isset($report['balance_tracking']['cash_difference'])) {
            $cashDiff = $report['balance_tracking']['cash_difference'];
            if (abs($cashDiff) > 10000) {
                $recommendations[] = [
                    'title' => '⚠️ Selisih Kas Signifikan',
                    'message' => abs($cashDiff) > 50000 
                        ? 'Selisih kas sangat besar. Periksa kembali perhitungan dan transaksi kas.' 
                        : 'Ada selisih kas yang perlu diperhatikan. Lakukan audit kas lebih teliti.',
                    'color' => '#dc2626'
                ];
            } elseif (abs($cashDiff) == 0) {
                $recommendations[] = [
                    'title' => '✅ Kas Seimbang Sempurna',
                    'message' => 'Excellent! Kas fisik dan sistem cocok 100%. Teruskan manajemen kas yang baik.',
                    'color' => '#10b981'
                ];
            }
        }

        // Sales performance recommendation
        $totalTransactions = $sales['total_transactions'] ?? 0;
        $avgTransaction = $sales['average_transaction'] ?? 0;
        
        if ($totalTransactions > 0) {
            if ($avgTransaction < 25000) {
                $recommendations[] = [
                    'title' => '📈 Tingkatkan Nilai Transaksi',
                    'message' => 'Rata-rata transaksi masih rendah. Coba tawarkan paket bundle atau upselling.',
                    'color' => '#f59e0b'
                ];
            } elseif ($avgTransaction > 100000) {
                $recommendations[] = [
                    'title' => '🎯 Performa Transaksi Excellent',
                    'message' => 'Rata-rata transaksi sangat baik! Pertahankan strategi penjualan ini.',
                    'color' => '#10b981'
                ];
            }
        }

        // Peak hours recommendation
        if (isset($report['hourly_sales']) && count($report['hourly_sales']) > 0) {
            $peakHour = collect($report['hourly_sales'])->sortByDesc('sales')->first();
            if ($peakHour && ($peakHour['sales'] ?? 0) > 0) {
                $recommendations[] = [
                    'title' => '⏰ Jam Puncak Penjualan',
                    'message' => "Jam {$peakHour['hour']}:00 adalah periode terbaik dengan penjualan Rp " . number_format($peakHour['sales'], 0, ',', '.') . ". Optimalkan staf dan stok pada jam ini.",
                    'color' => '#3b82f6'
                ];
            }
        }

        return $recommendations;
    }

    private function getCashFlowHistory(CashierShift $shift): array
    {
        $flows = [];
        
        // Opening cash flow
        $flows[] = [
            'time' => $shift->opened_at->format('H:i:s'),
            'type' => 'in',
            'description' => 'Kas Awal Shift',
            'amount' => (float)$shift->opening_cash,
            'balance_after' => (float)$shift->opening_cash
        ];

        // Transaction cash flows (simplified - could be expanded to show individual transactions)
        $cashOrders = $shift->orders()->whereHas('paymentMethod', function($q) {
            $q->where('is_cash', true);
        })->orderBy('created_at')->get();

        $runningBalance = (float)$shift->opening_cash;
        foreach ($cashOrders as $order) {
            $runningBalance += (float)$order->total_price;
            $flows[] = [
                'time' => $order->created_at->format('H:i:s'),
                'type' => 'in',
                'description' => "Penjualan #{$order->id}",
                'amount' => (float)$order->total_price,
                'balance_after' => $runningBalance
            ];
        }

        // Closing balance (if closed)
        if ($shift->status === 'closed') {
            $flows[] = [
                'time' => $shift->closed_at->format('H:i:s'),
                'type' => 'count',
                'description' => 'Penghitungan Kas Akhir',
                'amount' => (float)$shift->physical_cash_count,
                'balance_after' => (float)$shift->physical_cash_count
            ];
        }

        return $flows;
    }

    private function calculateSalesSummary(CashierShift $shift): array
    {
        $orders = $shift->orders;
        $totalDiscounts = 0; // Placeholder since discount_amount column doesn't exist
        $grossSales = $orders->sum('total_price');
        $netSales = $grossSales - $totalDiscounts;
        $totalTransactions = $orders->count();
        
        // Calculate total items sold
        $totalItems = DB::table('orders')
            ->join('order_products', 'orders.id', '=', 'order_products.order_id')
            ->where('orders.cashier_shift_id', $shift->id)
            ->sum('order_products.quantity');
        
        // Calculate profit if possible
        $profitTotal = DB::table('orders')
            ->join('order_products as op', 'orders.id', '=', 'op.order_id')
            ->join('products as p', 'op.product_id', '=', 'p.id')
            ->where('orders.cashier_shift_id', $shift->id)
            ->select(DB::raw('SUM((op.unit_price - COALESCE(p.cost_price, 0)) * op.quantity) as profit'))
            ->value('profit') ?? 0;

        return [
            'total_transactions' => $totalTransactions,
            'total_items' => $totalItems,
            'gross_sales' => $grossSales,
            'total_discounts' => $totalDiscounts,
            'total_tax' => 0, // Placeholder for tax
            'net_sales' => $netSales,
            'profit_total' => $profitTotal,
            'average_transaction' => $totalTransactions > 0 ? $orders->avg('total_price') : 0,
            'largest_transaction' => $orders->max('total_price') ?? 0,
            'smallest_transaction' => $orders->min('total_price') ?? 0,
            
            // Formatted values for template
            'total_sales_formatted' => 'Rp ' . number_format($grossSales, 0, ',', '.'),
            'total_discount_formatted' => 'Rp ' . number_format($totalDiscounts, 0, ',', '.'),
            'total_tax_formatted' => 'Rp 0',
            'net_sales_formatted' => 'Rp ' . number_format($netSales, 0, ',', '.'),
            'profit_total_formatted' => 'Rp ' . number_format($profitTotal, 0, ',', '.'),
        ];
    }

    private function calculatePaymentBreakdown(CashierShift $shift): array
    {
        $payments = DB::table('orders')
            ->join('payment_methods as pm', 'orders.payment_method_id', '=', 'pm.id')
            ->where('orders.cashier_shift_id', $shift->id)
            ->select(
                'pm.name',
                'pm.is_cash',
                'pm.is_ewallet',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(orders.total_price) as total_amount')
            )
            ->groupBy('pm.id', 'pm.name', 'pm.is_cash', 'pm.is_ewallet')
            ->get()
            ->map(function ($payment) {
                // Determine payment type based on flags
                $type = 'other';
                if ($payment->is_cash) {
                    $type = 'cash';
                } elseif ($payment->is_ewallet) {
                    $type = 'ewallet';
                }
                
                return [
                    'name' => $payment->name,
                    'type' => $type,
                    'count' => $payment->transaction_count,
                    'amount' => $payment->total_amount,
                    'formatted_amount' => 'Rp ' . number_format($payment->total_amount, 0, ',', '.'),
                ];
            })
            ->toArray();

        return $payments;
    }

    private function calculateProductSales(CashierShift $shift): array
    {
        $products = DB::table('orders')
            ->join('order_products as op', 'orders.id', '=', 'op.order_id')
            ->join('products as p', 'op.product_id', '=', 'p.id')
            ->where('orders.cashier_shift_id', $shift->id)
            ->select(
                'p.name',
                'op.unit_price',
                'p.cost_price',
                DB::raw('SUM(op.quantity) as total_quantity'),
                DB::raw('SUM(op.quantity * op.unit_price) as total_sales')
            )
            ->groupBy('p.id', 'p.name', 'op.unit_price', 'p.cost_price')
            ->orderBy('total_sales', 'desc')
            ->limit(20)
            ->get();

        $totalSales = $products->sum('total_sales');
        
        return $products->map(function ($product, $index) use ($totalSales) {
            $profit = ($product->unit_price - ($product->cost_price ?? 0)) * $product->total_quantity;
            $percentage = $totalSales > 0 ? round(($product->total_sales / $totalSales) * 100, 1) : 0;
            
            return [
                'name' => $product->name,
                'price' => $product->unit_price,
                'quantity' => $product->total_quantity,
                'total' => $product->total_sales,
                'profit' => $profit,
                'percentage' => $percentage,
                
                // Formatted values for template
                'price_formatted' => 'Rp ' . number_format($product->unit_price, 0, ',', '.'),
                'total_formatted' => 'Rp ' . number_format($product->total_sales, 0, ',', '.'),
                'profit_formatted' => 'Rp ' . number_format($profit, 0, ',', '.'),
                'formatted_sales' => 'Rp ' . number_format($product->total_sales, 0, ',', '.'), // backward compatibility
            ];
        })->toArray();
    }

    private function calculateHourlySales(CashierShift $shift): array
    {
        if (!$shift->opened_at) {
            return [];
        }

        $startHour = $shift->opened_at->format('H');
        $endHour = $shift->closed_at ? $shift->closed_at->format('H') : now()->format('H');
        
        $hourlySales = [];
        
        for ($hour = $startHour; $hour <= $endHour; $hour++) {
            $sales = $shift->orders()
                ->whereRaw('HOUR(created_at) = ?', [$hour])
                ->sum('total_price');
                
            $transactions = $shift->orders()
                ->whereRaw('HOUR(created_at) = ?', [$hour])
                ->count();

            $hourlySales[] = [
                'hour' => str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00',
                'sales' => $sales,
                'transactions' => $transactions,
                'formatted_sales' => 'Rp ' . number_format($sales, 0, ',', '.'),
            ];
        }

        return $hourlySales;
    }

    /**
     * Update cash payment method balance
     */
    private function updateCashPaymentMethodBalance(int $storeId, float $amount): void
    {
        $cashPaymentMethod = PaymentMethod::where('store_id', $storeId)
            ->where('is_cash', true)
            ->first();
            
        if ($cashPaymentMethod) {
            $cashPaymentMethod->update(['balance' => $amount]);
        }
    }

    /**
     * Add amount to cash payment method balance
     */
    public function addToCashBalance(int $storeId, float $amount): void
    {
        DB::beginTransaction();
        
        try {
            $cashPaymentMethod = PaymentMethod::where('store_id', $storeId)
                ->where('is_cash', true)
                ->first();
                
            if ($cashPaymentMethod) {
                $newBalance = $cashPaymentMethod->balance + $amount;
                $cashPaymentMethod->update(['balance' => $newBalance]);
            }
            
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Subtract amount from cash payment method balance
     */
    public function subtractFromCashBalance(int $storeId, float $amount): void
    {
        DB::beginTransaction();
        
        try {
            $cashPaymentMethod = PaymentMethod::where('store_id', $storeId)
                ->where('is_cash', true)
                ->first();
                
            if ($cashPaymentMethod) {
                $newBalance = $cashPaymentMethod->balance - $amount;
                $cashPaymentMethod->update(['balance' => $newBalance]);
            }
            
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
