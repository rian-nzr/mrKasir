<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class TotalBalanceOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // Get current store
        $user = Auth::user();
        $currentStoreId = null;
        
        if ($user->isSuperAdmin()) {
            $currentStoreId = Session::get('selected_store_id');
            
            // Jika super admin tapi belum pilih store, return empty stats
            if (!$currentStoreId) {
                return [
                    Stat::make('Pilih Toko Terlebih Dahulu', 'Tidak Ada Data')
                        ->description('Silakan pilih toko untuk melihat saldo')
                        ->descriptionIcon('heroicon-m-exclamation-triangle')
                        ->color('warning'),
                ];
            }
        } else {
            $currentStoreId = $user->store_id;
        }

        // Get payment methods for current store
        $paymentMethodsQuery = PaymentMethod::where('is_active', true);
        
        if ($currentStoreId) {
            $paymentMethodsQuery->where('store_id', $currentStoreId);
        }
        
        $paymentMethods = $paymentMethodsQuery->get();
        
        // Calculate balance by type
        $balanceByType = [
            'cash' => $paymentMethods->where('is_cash', true)->sum('balance'),
            'ewallet' => $paymentMethods->where('is_ewallet', true)->sum('balance'),
            'transfer' => $paymentMethods->where('is_cash', false)->where('is_ewallet', false)->sum('balance'),
        ];
        
        $totalBalance = array_sum($balanceByType);

        return [
            Stat::make('Total Saldo Keseluruhan', 'Rp ' . number_format($totalBalance, 0, ',', '.'))
                ->description('Gabungan semua metode pembayaran')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
                
            Stat::make('Saldo Tunai', 'Rp ' . number_format($balanceByType['cash'], 0, ',', '.'))
                ->description('Pembayaran cash')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('warning'),
                
            Stat::make('Saldo E-Wallet', 'Rp ' . number_format($balanceByType['ewallet'], 0, ',', '.'))
                ->description('GoPay, OVO, DANA, dll')
                ->descriptionIcon('heroicon-m-device-phone-mobile')
                ->color('info'),
                
            Stat::make('Saldo Transfer', 'Rp ' . number_format($balanceByType['transfer'], 0, ',', '.'))
                ->description('Bank transfer')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('primary'),
        ];
    }
}
