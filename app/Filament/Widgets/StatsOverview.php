<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Support\Enums\IconPosition;
use App\Models\Product;
use App\Models\Order;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Support\Number;


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


        $dataPriceOrder = Order::all('total_price');
        $dataPriceExpense = Expense::all('amount');
        $order_count = Order::whereBetween('created_at', [$startDate ,$endDate])->count(); 
        $omset = Order::whereBetween('created_at', [$startDate ,$endDate])->sum('total_price');
        $expense = Expense::whereBetween('created_at', [$startDate, $endDate])->sum('amount');
        return [
            Stat::make('Order', $order_count),
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
            Stat::make('Laba Bersih', 'Rp ' . number_format($omset - $expense,0,",","."))
            ->description('Keuntungan bersih')
            ->chart($dataPriceOrder->pluck('total_price')->toArray())
            ->color('info'),
        // ...
        ];
    }
}

