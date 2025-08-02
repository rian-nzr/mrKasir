<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\CashierShiftService;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        // If the order is paid with cash, add to cash balance
        if ($order->paymentMethod && $order->paymentMethod->is_cash) {
            $service = app(CashierShiftService::class);
            $service->addToCashBalance($order->store_id, (float) $order->total_price);
        }
        
        // Update shift summary for active shift
        $this->updateActiveShiftSummary($order->store_id);
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        // Handle payment method changes
        if ($order->isDirty('payment_method_id') || $order->isDirty('total_price')) {
            $service = app(CashierShiftService::class);
            
            // If changing from cash to non-cash, subtract from cash balance
            if ($order->getOriginal('payment_method_id')) {
                $originalPaymentMethod = \App\Models\PaymentMethod::find($order->getOriginal('payment_method_id'));
                if ($originalPaymentMethod && $originalPaymentMethod->is_cash) {
                    $originalAmount = $order->getOriginal('total_price') ?? 0;
                    $service->subtractFromCashBalance($order->store_id, (float) $originalAmount);
                }
            }
            
            // If changing to cash or updating cash amount, add to cash balance
            if ($order->paymentMethod && $order->paymentMethod->is_cash) {
                $service->addToCashBalance($order->store_id, (float) $order->total_price);
            }
        }
        
        // Update shift summary for active shift
        $this->updateActiveShiftSummary($order->store_id);
    }

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        // If the deleted order was paid with cash, subtract from cash balance
        if ($order->paymentMethod && $order->paymentMethod->is_cash) {
            $service = app(CashierShiftService::class);
            $service->subtractFromCashBalance($order->store_id, (float) $order->total_price);
        }
        
        // Update shift summary for active shift
        $this->updateActiveShiftSummary($order->store_id);
    }

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void
    {
        // If the restored order was paid with cash, add back to cash balance
        if ($order->paymentMethod && $order->paymentMethod->is_cash) {
            $service = app(CashierShiftService::class);
            $service->addToCashBalance($order->store_id, (float) $order->total_price);
        }
        
        // Update shift summary for active shift
        $this->updateActiveShiftSummary($order->store_id);
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
            // Only update summary fields for performance
            $activeShift->update([
                'total_sales' => $activeShift->getTotalSales(),
                'total_transactions' => $activeShift->getTotalTransactions(),
                'total_discounts' => $activeShift->getTotalDiscounts(),
            ]);
        }
    }
}
