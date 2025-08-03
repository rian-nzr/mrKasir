<?php

namespace App\Observers;

use App\Models\Transaction;

class TransactionObserver
{
    /**
     * Handle the Transaction "created" event.
     */
    public function created(Transaction $transaction): void
    {
        // Auto-assign to active shift if not already assigned
        if (!$transaction->cashier_shift_id) {
            $activeShift = \App\Models\CashierShift::where('store_id', $transaction->store_id)
                ->where('status', \App\Models\CashierShift::STATUS_OPEN)
                ->first();
                
            if ($activeShift) {
                $transaction->update(['cashier_shift_id' => $activeShift->id]);
            }
        }
        
        // Update shift summary for active shift
        $this->updateActiveShiftSummary($transaction->store_id);
    }

    /**
     * Handle the Transaction "updated" event.
     */
    public function updated(Transaction $transaction): void
    {
        // Update shift summary for active shift
        $this->updateActiveShiftSummary($transaction->store_id);
    }

    /**
     * Handle the Transaction "deleted" event.
     */
    public function deleted(Transaction $transaction): void
    {
        // Update shift summary for active shift
        $this->updateActiveShiftSummary($transaction->store_id);
    }

    /**
     * Handle the Transaction "restored" event.
     */
    public function restored(Transaction $transaction): void
    {
        // Update shift summary for active shift
        $this->updateActiveShiftSummary($transaction->store_id);
    }

    /**
     * Update shift summary for active shift in the store
     */
    private function updateActiveShiftSummary(int $storeId): void
    {
        $activeShift = \App\Models\CashierShift::where('store_id', $storeId)
            ->where('status', \App\Models\CashierShift::STATUS_OPEN)
            ->first();
            
        if ($activeShift) {
            $totalProfit = $activeShift->getTotalProfit();
            $transactionProfit = $activeShift->getTotalTransactionProfit();
            $orderProfit = $totalProfit - $transactionProfit;
            
            // Update summary fields including profit data
            $activeShift->update([
                'total_sales' => $activeShift->getTotalSales(),
                'total_transactions' => $activeShift->getTotalTransactions(),
                'total_discounts' => $activeShift->getTotalDiscounts(),
                'total_profit' => $totalProfit,
                'transaction_profit' => $transactionProfit,
                'order_profit' => $orderProfit,
                'shift_summary' => [
                    'gross_sales' => $activeShift->getTotalSales(),
                    'net_sales' => $activeShift->getTotalSales() - $activeShift->getTotalDiscounts(),
                    'cash_sales' => $activeShift->getCashSalesTotal(),
                    'non_cash_sales' => $activeShift->getNonCashSalesTotal(),
                    'total_transactions' => $activeShift->getTotalTransactions(),
                    'average_transaction' => $activeShift->getTotalTransactions() > 0 
                        ? $activeShift->getTotalSales() / $activeShift->getTotalTransactions() : 0,
                    'cash_out' => $activeShift->cashOuts()->sum('amount'),
                    'total_profit' => $totalProfit,
                    'transaction_profit' => $transactionProfit,
                    'order_profit' => $orderProfit,
                ]
            ]);
        }
    }
}
