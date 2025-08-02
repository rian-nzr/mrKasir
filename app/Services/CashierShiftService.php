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
        
        // Generate PDF using DomPDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.cashier-shift-report', $report)
            ->setPaper('a4', 'portrait');
        
        $filename = "shift_report_{$shiftId}_" . now()->format('Y-m-d_H-i-s') . ".pdf";
        
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }

    private function calculateSalesSummary(CashierShift $shift): array
    {
        $orders = $shift->orders;
        $totalDiscounts = 0; // Placeholder since discount_amount column doesn't exist
        $grossSales = $orders->sum('total_price');
        
        return [
            'total_transactions' => $orders->count(),
            'gross_sales' => $grossSales,
            'total_discounts' => $totalDiscounts,
            'net_sales' => $grossSales - $totalDiscounts,
            'average_transaction' => $orders->count() > 0 ? $orders->avg('total_price') : 0,
            'largest_transaction' => $orders->max('total_price') ?? 0,
            'smallest_transaction' => $orders->min('total_price') ?? 0,
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
                DB::raw('SUM(op.quantity) as total_quantity'),
                DB::raw('SUM(op.quantity * op.unit_price) as total_sales')
            )
            ->groupBy('p.id', 'p.name', 'op.unit_price')
            ->orderBy('total_sales', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($product) {
                return [
                    'name' => $product->name,
                    'price' => $product->unit_price,
                    'quantity' => $product->total_quantity,
                    'sales' => $product->total_sales,
                    'formatted_sales' => 'Rp ' . number_format($product->total_sales, 0, ',', '.'),
                ];
            })
            ->toArray();

        return $products;
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
