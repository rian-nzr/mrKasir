<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Support\Enums\IconPosition;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Expense;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Support\Number;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;


class StatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected function getStats(): array
    {
        $startDate = ! is_null($this->filters['startDate'] ?? null) ?
        Carbon::parse($this->filters['startDate']) :
        null;

        $endDate = ! is_null($this->filters['endDate'] ?? null) ?
        Carbon::parse($this->filters['endDate'])->addDay() :
        now();

        // Get current store
        $user = Auth::user();
        $currentStoreId = null;
        
        if ($user->isSuperAdmin()) {
            $currentStoreId = Session::get('selected_store_id');
        } else {
            $currentStoreId = $user->store_id;
        }

        // Filter berdasarkan store
        $orderQuery = Order::whereBetween('created_at', [$startDate, $endDate]);
        $expenseQuery = Expense::whereBetween('created_at', [$startDate, $endDate]);
        
        if ($currentStoreId) {
            $orderQuery->where('store_id', $currentStoreId);
            $expenseQuery->where('store_id', $currentStoreId);
        }

        $dataPriceOrder = $orderQuery->get(['total_price']);
        $dataPriceExpense = $expenseQuery->get(['amount']);
        $order_count = $orderQuery->count(); 
        $omset = $orderQuery->sum('total_price');
        $expense = $expenseQuery->sum('amount');
        
        // Hitung laba berdasarkan cost_price produk (filtered by store)
        $orderProductQuery = OrderProduct::whereHas('order', function($query) use ($startDate, $endDate, $currentStoreId) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
            if ($currentStoreId) {
                $query->where('store_id', $currentStoreId);
            }
        });
        
        $orderProducts = $orderProductQuery->with('product')->get();
        
        $totalRevenue = 0;
        $totalCost = 0;
        
        foreach ($orderProducts as $orderProduct) {
            $revenue = $orderProduct->unit_price * $orderProduct->quantity;
            $cost = ($orderProduct->product->cost_price ?? 0) * $orderProduct->quantity;
            
            $totalRevenue += $revenue;
            $totalCost += $cost;
        }
        
        $grossProfit = $totalRevenue - $totalCost; // Laba kotor
        $netProfit = $grossProfit - $expense; // Laba bersih
        return [
            // Stat::make('Order', $order_count),
            Stat::make('Pemasukan', 'Rp ' . number_format($omset,0,",","."))
            ->description('omset')
            ->descriptionIcon('heroicon-m-arrow-trending-up',IconPosition::Before)
            ->chart($dataPriceOrder->pluck('total_price')->toArray())
            ->color('success'),
            Stat::make('Pengeluaran', 'Rp ' . number_format($expense,0,",","."))
            ->description('expense')
            ->descriptionIcon('heroicon-m-arrow-trending-down',IconPosition::Before)
            ->chart($dataPriceExpense->pluck('amount')->toArray())
            ->color('danger'),
            Stat::make('Laba Kotor', 'Rp ' . number_format($grossProfit,0,",","."))
            ->description('Pendapatan - Harga Pokok Penjualan')
            ->descriptionIcon('heroicon-m-currency-dollar',IconPosition::Before)
            ->color($grossProfit >= 0 ? 'success' : 'danger'),
            Stat::make('Laba Bersih', 'Rp ' . number_format($netProfit,0,",","."))
            ->description('Laba Kotor - Pengeluaran')
            ->descriptionIcon('heroicon-m-chart-bar',IconPosition::Before)
            ->color($netProfit >= 0 ? 'success' : 'danger'),
        // ...
        ];
    }
}

