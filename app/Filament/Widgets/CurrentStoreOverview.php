<?php

namespace App\Filament\Widgets;

use App\Models\Store;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CurrentStoreOverview extends BaseWidget
{
    protected static ?int $sort = -10;
    
    protected function getStats(): array
    {
        $user = Auth::user();
        $currentStore = null;
        
        if ($user->isSuperAdmin()) {
            $selectedStoreId = Session::get('selected_store_id');
            if ($selectedStoreId) {
                $currentStore = Store::find($selectedStoreId);
            }
        } else {
            $currentStore = $user->store;
        }

        if (!$currentStore) {
            return [
                Stat::make('Status Toko', 'Tidak ada toko dipilih')
                    ->description('Silakan pilih toko untuk melanjutkan')
                    ->descriptionIcon('heroicon-o-exclamation-triangle')
                    ->color('danger'),
            ];
        }

        return [
            Stat::make('Toko Aktif', $currentStore->name)
                ->description($currentStore->code . ' • ' . $currentStore->address)
                ->descriptionIcon('heroicon-o-building-storefront')
                ->color('success'),
                
            Stat::make('Total Produk', $currentStore->products()->count())
                ->description('Produk dalam toko ini')
                ->descriptionIcon('heroicon-o-cube')
                ->color('info'),
                
            Stat::make('Order Hari Ini', $currentStore->orders()->whereDate('created_at', today())->count())
                ->description('Transaksi hari ini')
                ->descriptionIcon('heroicon-o-shopping-cart')
                ->color('warning'),
        ];
    }
}
