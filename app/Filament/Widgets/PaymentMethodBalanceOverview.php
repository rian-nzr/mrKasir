<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Filament\Support\Enums\IconPosition;

class PaymentMethodBalanceOverview extends BaseWidget
{
    protected static ?int $sort = 3;
    
    protected function getStats(): array
    {
        // Get current store
        $user = Auth::user();
        $currentStoreId = null;
        
        if ($user->isSuperAdmin()) {
            $currentStoreId = Session::get('selected_store_id');
        } else {
            $currentStoreId = $user->store_id;
        }

        // Get payment methods for current store
        $paymentMethodsQuery = PaymentMethod::where('is_active', true);
        
        if ($currentStoreId) {
            $paymentMethodsQuery->where('store_id', $currentStoreId);
        }
        
        $paymentMethods = $paymentMethodsQuery->get();
        
        // Calculate totals
        $totalBalance = $paymentMethods->sum('balance');
        $ewalletBalance = $paymentMethods->where('is_ewallet', true)->sum('balance');
        $bankBalance = $paymentMethods->where('is_ewallet', false)->where('is_cash', false)->sum('balance');
        $ewalletCount = $paymentMethods->where('is_ewallet', true)->count();

        return [
            Stat::make('Total Saldo Digital', 'Rp ' . number_format($totalBalance, 0, ',', '.'))
                ->description('Saldo E-Wallet + Bank')
                ->descriptionIcon('heroicon-m-credit-card', IconPosition::Before)
                ->color('success'),
                
            Stat::make('Saldo E-Wallet', 'Rp ' . number_format($ewalletBalance, 0, ',', '.'))
                ->description($ewalletCount . ' E-Wallet aktif')
                ->descriptionIcon('heroicon-m-device-phone-mobile', IconPosition::Before)
                ->color('info'),
                
            Stat::make('Saldo Bank', 'Rp ' . number_format($bankBalance, 0, ',', '.'))
                ->description('Transfer Bank')
                ->descriptionIcon('heroicon-m-building-library', IconPosition::Before)
                ->color('warning'),
        ];
    }
}
