<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\PaymentMethod;

class TotalBalanceOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $balanceByType = PaymentMethod::getBalanceByType();
        $totalBalance = PaymentMethod::getTotalBalance();

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
